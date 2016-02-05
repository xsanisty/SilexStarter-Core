<?php

namespace SilexStarter\Migration;

use Exception;
use FilesystemIterator;
use RecursiveIteratorIterator;
use RecursiveDirectoryIterator;
use RecursiveCallbackFilterIterator;
use SilexStarter\Module\ModuleManager;
use Illuminate\Database\Schema\Builder as SchemaBuilder;

class Migrator
{
    protected $migrations = [];
    protected $repository;
    protected $moduleMgr;
    protected $moduleId;
    protected $config;
    protected $schemaBuilder;

    public function __construct(
        MigrationRepository $repository,
        ModuleManager $moduleMgr,
        SchemaBuilder $schemaBuilder,
        array $config
    ) {
        $this->repository   = $repository;
        $this->moduleMgr    = $moduleMgr;
        $this->moduleId     = 'main';
        $this->config       = $config;
        $this->schemaBuilder= $schemaBuilder;
    }

    /**
     * Set the migrator to work on specific module, default is main module.
     *
     * @param string    $moduleId   The module identifier
     */
    public function setModule($moduleId)
    {
        $this->moduleId = $moduleId;
    }

    /**
     * Migrate all unmigrated migration.
     */
    public function migrate()
    {
        $migrationFiles = $this->getUnmigratedFiles();
        $migrated       = $this->run($migrationFiles);

        $this->repository->addMigrations(array_keys($migrated), $this->moduleId);

        return $migrationFiles;
    }

    /**
     * Rollback all registered migration.
     */
    public function rollback()
    {
        $migrationFiles = $this->repository->getLatestMigrated();
        $migrated       = $this->run($migrationFiles);

        $this->repository->removeLatestBatch();

        return $migrationFiles;
    }

    /**
     * Get the unmigrated files.
     *
     * @return array         List of unmigrated files
     */
    public function getUnmigratedFiles()
    {
        $migrationFileInfo = $this->getMigrationFiles($this->moduleId);

        $migrationFiles = [];

        foreach ($migrationFileInfo as $key => $migrationFile) {
            $migrationFiles[] = $migrationFile->getBaseName();
        }

        $unmigratedFiles = $this->repository->filterUnmigrated($migrationFiles, $this->moduleId);

        return $unmigratedFiles;
    }

    /**
     * Resolve fully qualified class name of specific migration file on specific module
     *
     * @param  string $migrationFile The migration file
     * @param  string $module        The module identifier
     *
     * @return string                Fully qualified class name
     */
    public function resolveClass($migrationFile)
    {
        $migrationPath  = $this->getMigrationPath();
        $migrationNs    = ($this->moduleId !== 'main') ? $this->moduleMgr->getModuleNamespace($this->moduleId) . '\\Migration\\' : '';

        require $migrationPath . '/' . $migrationFile;

        $migrationPart  = explode('_', $migrationFile, 3);
        $migrationClass = end($migrationPart);
        $migrationFqcn  = $migrationNs . str_replace('.php', '', $migrationClass);

        return $migrationFqcn;
    }

    protected function run(array $migrations, $direction = 'up')
    {
        $ran    = [];
        $back   = $direction == 'up' ? 'down' : 'up';

        /**
         * rollback gracefully on error, leave no migration migrated
         */
        try {
            foreach ($migrations as $migration) {
                $migrationClass     = $this->resolveClass($migration);
                $migrationInstance  = $this->migrationFactory($migrationClass);

                $migrationInstance->$direction();
                $ran[$migration] = $migrationInstance;
            }
        } catch (Exception $e) {
            foreach ($ran as $migration) {
                $migration->$back();
            }
            throw $e;
        }

        return $ran;
    }

    /**
     * Get all migrations files.
     */
    public function getMigrationFiles()
    {
        $migrationPath = $this->getMigrationPath();

        $files  = new RecursiveCallbackFilterIterator(
            new RecursiveDirectoryIterator($migrationPath, FilesystemIterator::SKIP_DOTS),
            function ($file, $key, $iterator) {
                if ($iterator->hasChildren()) {
                    return true;
                }

                if ($file->isFile() && $file->getExtension() == 'php') {
                    return true;
                }

                return false;
            }
        );

        return new RecursiveIteratorIterator($files);
    }

    /**
     * Get the default migration path or module migration path
     *
     * @param  $module  The module identifier
     *
     * @return string   The path to migration directory
     */
    public function getMigrationPath()
    {
        if ($this->moduleId === 'main') {
            return $this->config['path'];
        } else {
            $modulePath     = $this->moduleMgr->getModulePath($this->moduleId);
            $moduleResources= $this->moduleMgr->getModule($this->moduleId)->getResources();
            $migrationPath  = $modulePath . '/' . $moduleResources->migrations;

            if (!$moduleResources->migrations) {
                throw new Exception("No migration directory specified for '{$this->moduleId}'");

            }

            return $migrationPath;
        }
    }

    /**
     * Create migration instance from the migration file
     *
     * @param  string $migrationClass    The migration class name
     *
     */
    public function migrationFactory($migrationClass)
    {
        return new $migrationClass($this->schemaBuilder);
    }

    /**
     * Get the migration repository.
     *
     */
    public function getRepository()
    {
        return $this->repository;
    }
}

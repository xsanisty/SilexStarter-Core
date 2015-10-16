<?php

namespace SilexStarter\Migration;

use SilexStarter\Storage\JsonStorage;
use Symfony\Component\Filesystem\Filesystem;

class MigrationRepository
{
    const MIGRATION_FILE = 'migrations';
    protected $storage;
    protected $migrationData;

    public function __construct(JsonStorage $storage)
    {
        $this->storage  = $storage;
        $migrations     = $this->storage->load(self::MIGRATION_FILE);

        if ($migrations) {
            $this->migrationData = $migrations;
        } else {
            $this->initialize();
        }
    }

    public function __destruct()
    {
        $this->storage->persist(self::MIGRATION_FILE, $this->migrationData);
    }

    /**
     * Initialize first json structure.
     *
     */
    public function initialize()
    {
        $this->migrationData = [
            'latest_batch'  => 0,
            'batch_module'  => [
                //batch_number => module_id
            ],
            'migrations'    => [
                'main'      => [
                    //batch_number => [
                    //   'path/to/migration.file'
                    //]
                ],
                //'module-id' => [
                //    batch_number => [
                //      'path/to/migration.file'
                //    ]
                //]
            ]
        ];
    }

    /**
     * Get the latest migration batch number.
     *
     * @return int  The latest migration batch
     */
    public function getLatestBatch()
    {
        return $this->migrationData['latest_batch'];
    }

    /**
     * Get the latest migrated module
     *
     * @return string   The module identifier
     */
    public function getLatestModule()
    {
        $batch  = $this->getLatestBatch();

        return $this->migrationData['batch_module'][$batch];
    }

    /**
     * Get the next migration batch number.
     *
     * @return int  Get the next migration batch
     */
    public function getNextBatch()
    {
        return $this->migrationData['latest_batch'] + 1;
    }

    /**
     * Remove latest migrations batch from the storage.
     */
    public function removeLatestBatch()
    {
        $batch  = $this->getLatestBatch();
        $module = $this->getLatestModule();

        unset($this->migrationData['migrations'][$module][$batch]);

        $this->migrationData['latest_batch']--;
    }

    /**
     * Get list of already migrated class.
     *
     * @param  string $module   The module identifier
     *
     * @return array            The list of migrated classes
     */
    public function getMigrated($module = 'main')
    {
        if (!isset($this->migrationData['migrations'][$module])) {
            $this->migrationData['migrations'][$module] = [];
        }

        $files = [];
        $migrations = $this->migrationData['migrations'][$module];

        foreach ($migrations as $migration) {
            $files = array_merge($files, $migration);
        }

        return $files;
    }

    /**
     * Get list of all migrated class from all available module.
     *
     * @return array    List of already migrated class
     */
    public function getAllMigrated()
    {
        $files = [];

        foreach ($this->migrationData['migrations'] as $module) {
            foreach ($module as $migrated) {
                $files = array_merge($files, $migrated);
            }
        }

        return $files;
    }

    /**
     * Get list of latest migrated files for rollback
     *
     * @param  string $module   The module identifier if any
     *
     * @return array            List of the latest migrated files
     */
    public function getLatestMigrated($module = 'main')
    {
        $latest_batch = $this->migrationData['latest_batch'];

        if ($latest_batch == 0) {
            return [];
        }

        $latest_module = $this->migrationData['batch_module'][$latest_batch];

        return $this->migrationData['migrations'][$latest_module][$latest_batch];
    }

    /**
     * Add migrated files into storage.
     *
     * @param array  $migrated      List of migrated class
     * @param string $module        Module identifier
     */
    public function addMigrations(array $migrations, $module = 'main')
    {
        $batch      = $this->getNextBatch();
        $migrations = $this->filterUnmigrated($migrations, $module);

        if ($migrations) {

            $this->migrationData['latest_batch'] = $batch;
            $this->migrationData['batch_module'][$batch] = $module;

            if (!isset($this->migrationData['migrations'][$module])) {
                $this->migrationData['migrations'][$module] = [];
            }

            $this->migrationData['migrations'][$module][$batch] = $migrations;
        }
    }

    /**
     * Check if list of migrations is already migrated.
     *
     * @param  array   $migrations List of migration class
     * @return boolean
     */
    public function isMigrated(array $migrations)
    {
        $migrated   = $this->getAllMigrated();
        $check      = array_intersect($migrations, $migrated);

        return count($migrations) == $count($check);
    }

    /**
     * Filter a list of migration classes to get the unmigrated one.
     *
     * @param  array  $migrationFiles       The list of migration files
     * @param  string $modue                The module identifier
     *
     * @return array                        The list of unmigrated files
     */
    public function filterUnmigrated(array $migrationFiles, $module = null)
    {
        $migrated   = ($module) ? $this->getMigrated($module) : $this->getAllMigrated();
        $check      = array_intersect($migrationFiles, $migrated);
        $unmigrated = array_diff($migrationFiles, $check);

        sort($unmigrated);

        return $unmigrated;
    }
}

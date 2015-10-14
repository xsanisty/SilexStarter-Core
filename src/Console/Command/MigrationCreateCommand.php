<?php

namespace SilexStarter\Console\Command;

use Exception;
use SilexStarter\Console\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class MigrationCreateCommand extends Command
{
    protected function configure()
    {
        $this->setName('migration:create')
            ->setDescription('Create migration file for app or specific module')
            ->addArgument(
                'class-name',
                InputArgument::REQUIRED,
                'The migration class name'
            )
            ->addOption(
                'module',
                'm',
                InputOption::VALUE_REQUIRED,
                'If set, the command will create migration for specific module'
            )
            ->addOption(
                'table',
                't',
                InputOption::VALUE_REQUIRED,
                'If set, the command will create migration for specific table'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $app        = $this->getSilexStarter();
        $migrator   = $app['migrator'];

        $tableName  = $input->getOption('table') ? $input->getOption('table') : 'table_name';

        $moduleMgr  = $app['module'];
        $moduleId   = $input->getOption('module');
        $module     = ($moduleId) ? $moduleMgr->getModule($moduleId) : null;
        $moduleNs   = ($module) ? "\nnamespace " . $moduleMgr->getModuleNamespace($moduleId) . "\Migration;\n" : '';

        if ($module && !$module->getResources()->migrations) {
            throw new Exception("Module '$moduleId' doesn't have migrations directory configured");
        }

        $className  = $input->getArgument('class-name');
        $timestamp  = date('Ymd_His_');
        $fileName   = "{$timestamp}{$className}.php";
        $classCode  = file_get_contents(__DIR__.'/stubs/migration.stub');
        $classCode  = str_replace(
            ['{{classNamespace}}', '{{className}}', '{{tableName}}'],
            [$moduleNs, $className, $tableName],
            $classCode
        );

        $targetDir  = ($moduleId)
                    ? $moduleMgr->getModulePath($moduleId) . '/' . $module->getResources()->migrations
                    : $migrator->getMigrationPath();

        $app['filesystem']->dumpFile($targetDir . '/' . $fileName, $classCode);

        $output->writeln('<info>Migration created at ' . $targetDir . '/' . $fileName . '</info>');
    }
}

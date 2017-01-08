<?php

namespace SilexStarter\Console\Command;

use Exception;
use SilexStarter\Console\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class MigrationCommand extends Command
{
    protected function configure()
    {
        $this->setName('migration:migrate')
            ->setDescription('Migrate the schema into database')
            ->addOption(
                'module',
                'm',
                InputOption::VALUE_REQUIRED,
                'If set, the command will migrate specific module'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $app        = $this->getSilexStarter();
        $migrator   = $app['migrator'];
        $module     = $input->getOption('module');

        if ($module) {
            $migrator->setModule($module);
        }

        try {
            $migrationFiles = $migrator->getUnmigratedFiles();
        } catch (Exception $e) {
            $output->writeln('<error>'.$e->getMessage().'</error>');
            return;
        }

        if (!$migrationFiles) {
            $output->writeln('<info>Nothing to migrate</info>');
            return;
        }

        $output->writeln('<info>Migrating '.$module.'...</info>');
        $migratedInstances  = [];

        foreach ($migrationFiles as $migration) {
            try {
                $migrationClass     = $migrator->resolveClass($migration, $module);
                $migrationInstance  = $migrator->migrationFactory($migrationClass);

                $migrationInstance->up();

                $migratedInstances[$migration] = $migrationInstance;

                $output->writeln("<comment>$migration is migrated</comment>");
            } catch (Exception $e) {
                $output->writeln('<error>Error occured while running migration with message:</error>');
                $output->writeln('<error>'.$e->getMessage().'</error>');
                $output->writeln('<info>Rolling back previously migrated files if any...</info>');

                foreach ($migratedInstances as $migrationFile => $migrationInstance) {

                    $migrationInstance->down();
                    $output->writeln('<comment>'.$migrationFile.' rolled back</comment>');
                }

                return;
            }
        }

        $migrator->getRepository()->addMigrations($migrationFiles, ($module) ? $module : 'main');
    }
}

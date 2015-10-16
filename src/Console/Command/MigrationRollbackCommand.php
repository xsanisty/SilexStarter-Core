<?php

namespace SilexStarter\Console\Command;

use SilexStarter\Console\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class MigrationRollbackCommand extends Command
{
    protected function configure()
    {
        $this->setName('migration:rollback')
            ->setDescription('Migrate the schema into database')
            ->addOption(
                'module',
                'm',
                InputOption::VALUE_REQUIRED,
                'If set, the command will rolling back specific module'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $app        = $this->getSilexStarter();
        $migrator   = $app['migrator'];
        $module     = $input->getOption('module');

        $output->writeln('<info>Rolling back migration '.$module.'...</info>');

        $migrationFiles = $migrator->getRepository()->getLatestMigrated();

        foreach ($migrationFiles as $migration) {
            try {
                $migrationClass     = $migrator->resolveClass($migration, $module);
                $migrationInstance  = $migrator->migrationFactory($migrationClass);

                $migrationInstance->down();

                $migratedInstances[$migration] = $migrationInstance;

                $output->writeln("<comment>$migration is rolled back</comment>");
            } catch (Exception $e) {
                $output->writeln('<error>Error occured while rolling back migration with message:</error>');
                $output->writeln('<error>'.$e->getMessage().'</error>');
                $output->writeln('<info>Migrating previously rolled back files if any...</info>');

                foreach ($migratedInstances as $migrationFile => $migrationInstance) {

                    $migrationInstance->up();
                    $output->writeln('<comment>'.$migrationFile.' migrated</comment>');
                }

                return;
            }
        }

        $migrator->getRepository()->removeLatestBatch();
    }
}

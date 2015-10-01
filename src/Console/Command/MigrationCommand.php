<?php

namespace SilexStarter\Console\Command;

use SilexStarter\Console\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class MigrationCommand extends Command
{
    protected function configure()
    {
        $this->setName('migrate')
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

        $migrator->migrate($module);

        $output->writeln('<info>Migrating '.$module.'...</info>');
    }
}

<?php

namespace SilexStarter\Console\Command;

use SilexStarter\Console\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class SilexStarterInitCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('migrate')
            ->setDescription('Migrate the schema into database');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $app = $this->getSilexStarter();
        $output->writeln('<info>Migrating...</info>');
    }
}

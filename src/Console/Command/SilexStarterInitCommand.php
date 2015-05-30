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
            ->setName('silexstarter:init')
            ->setDescription('Initialize the SilexStarter project');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $app = $this->getSilexStarter();
        $output->writeln('<info>Initializing SilexStarter for the first time</info>');
    }
}

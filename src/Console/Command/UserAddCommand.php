<?php

namespace SilexStarter\Console\Command;

use SilexStarter\Console\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use RecursiveIteratorIterator;
use RecursiveDirectoryIterator;
use FilesystemIterator;

class CacheClearCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('user:add')
            ->setDescription('Add new user into database');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $app = $this->getSilexStarter();
        $helper = $this->getHelper('question');
        $question = new Question('Please provide user\s email: ');

        $helper->ask($input, $output, $question);
    }
}

<?php

namespace SilexStarter\Console\Command;

use SilexStarter\Console\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use RecursiveIteratorIterator;
use RecursiveDirectoryIterator;
use FilesystemIterator;

class CacheClearCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('cache:clear')
            ->setDescription('Clear the cache directory');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $app = $this->getSilexStarter();

        foreach (['cache', 'profiler', 'view', 'console'] as $dir) {
            $cache = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($app['path.app'].'storage/'.$dir, FilesystemIterator::SKIP_DOTS),
                RecursiveIteratorIterator::CHILD_FIRST
            );

            $output->writeln('<info>Clearing '.$dir.' directory...</info>');

            try {
                $app['filesystem']->remove($cache);
            } catch (\Exception $e) {
                $output->writeln('<error>Some error occured while clearing '.$dir.' directory with message :</error>');
                $output->writeln('<error>'.$e->getMessage().'</error>');
            }
        }
    }
}

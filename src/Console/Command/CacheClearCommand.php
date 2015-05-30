<?php

namespace SilexStarter\Console\Command;

use SilexStarter\Console\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

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

        foreach (['cache', 'profiler', 'view'] as $dir) {
            $cache = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($app['path.storage'].$dir, FilesystemIterator::SKIP_DOTS)
            );

            $output->writeln('<info>Clearing cache '.$dir.'...</info>');
            $app['filesystem']->remove($cache);
        }
    }
}

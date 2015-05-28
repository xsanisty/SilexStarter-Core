<?php

namespace SilexStarter\Console;

use Symfony\Component\Console\Application as ConsoleApplication;
use SilexStarter\SilexStarter;
use RecursiveIteratorIterator;
use RecursiveDirectoryIterator;
use FilesystemIterator;
use ReflectionClass;

class Application extends ConsoleApplication
{
    protected $silexStarter;

    public function __construct(SilexStarter $silexStarter, $name = 'xpress', $version = '1.0')
    {
        parent::__construct($name, $version);
        $this->silexStarter = $silexStarter;
    }

    public function getSilexStarter()
    {
        return $this->silexStarter;
    }

    public function registerCommand(Command $command)
    {
        $this->add($command);
    }

    public function registerCommandDirectory($dir, $namespace = '')
    {
        $commands = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS)
        );

        $namespace = ($namespace) ? rtrim($namespace, '\\').'\\' : '';

        foreach ($commands as $command) {
            if ($command->getExtension() == 'php') {
                $command = str_replace([$dir, '.php', DIRECTORY_SEPARATOR], ['', '', '\\'], $command);
                $command = ltrim($command, '\\');

                $this->registerCommand(new $command);
            }
        }
    }
}

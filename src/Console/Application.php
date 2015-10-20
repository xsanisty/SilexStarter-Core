<?php

namespace SilexStarter\Console;

use Symfony\Component\Console\Application as ConsoleApplication;
use SilexStarter\SilexStarter;
use RecursiveCallbackFilterIterator;
use RecursiveIteratorIterator;
use RecursiveDirectoryIterator;
use FilesystemIterator;
use ReflectionClass;

class Application extends ConsoleApplication
{
    protected $silexStarter;

    /**
     * Init the SilexStarter console application.
     *
     * @param SilexStarter $silexStarter SilexStarter application instance
     * @param string       $name         console application name
     * @param string       $version      console application version
     */
    public function __construct(SilexStarter $silexStarter, $name = 'xpress', $version = '1.0')
    {
        parent::__construct($name, $version);
        $this->silexStarter = $silexStarter;
    }

    /**
     * Get SilexStarter application instance.
     *
     * @return SilexStarter\SilexStarter
     */
    public function getSilexStarter()
    {
        return $this->silexStarter;
    }

    /**
     * Register new command into console application.
     *
     * @param  Command $command the command instance
     */
    public function registerCommand(Command $command)
    {
        $this->add($command);
    }

    /**
     * Register multiple commands at once.
     *
     * @param  array  $command list of command class or command object
     */
    public function registerCommands(array $commands)
    {
        foreach ($commands as $command) {
            if (is_string($command)) {
                $this->registerCommand(new $command);
            } else {
                $this->registerCommand($command);
            }
        }
    }

    /**
     * Register command directory and add all available command in it.
     *
     * @param  string $dir       command directory
     * @param  string $namespace base namespace of the command directory
     */
    public function registerCommandDirectory($dir, $namespace = '')
    {
        $commands = new RecursiveCallbackFilterIterator(
            new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS),
            function ($file, $key, $iterator) {
                if ($iterator->hasChildren()) {
                    return true;
                }

                if ($file->isFile() && $file->getExtension() == 'php') {
                    return true;
                }

                return false;
            }
        );

        $commands = new RecursiveIteratorIterator($commands);

        $namespace = ($namespace) ? rtrim($namespace, '\\').'\\' : '';

        foreach ($commands as $command) {
            $command = str_replace([$dir, '.php', DIRECTORY_SEPARATOR], ['', '', '\\'], $command);
            $command = ltrim($command, '\\');
            $command = $namespace.$command;

            $this->registerCommand(new $command);
        }
    }
}

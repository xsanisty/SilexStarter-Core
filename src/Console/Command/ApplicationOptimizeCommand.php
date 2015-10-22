<?php

namespace SilexStarter\Console\Command;

use SilexStarter\Console\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use FilesystemIterator;
use RecursiveIteratorIterator;
use RecursiveDirectoryIterator;
use RecursiveCallbackFilterIterator;
use ReflectionClass;

class ApplicationOptimizeCommand extends Command
{
    protected $app;
    protected $output;

    protected function configure()
    {
        $this
            ->setName('app:optimize')
            ->setDescription('Create an optimized OptimizedModuleServiceProvider');

    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->app      = $this->getSilexStarter();
        $this->output   = $output;
        $this->moduleMgr= $this->app['module'];

        $this->moduleMgr->registerModules($this->app['config']->get('modules'));

        $this->modules  = $this->app['module']->getRegisteredModules();

        $this->createOptimizedProvider();
        $this->registerOptimizedProvider();
        $this->appendRoute();
        $this->appendMiddleware();
    }

    /**
     * Create an optimized application service provider.
     */
    protected function createOptimizedProvider()
    {
        $this->output->writeln('<info>Creating OptimizedApplicationServiceProvider...</info>');

        $classTemplate      = file_get_contents(__DIR__.'/stubs/OptimizedApplicationServiceProvider.stub');
        $controllerProvider = $this->collectController();
        $twigPath           = $this->collectTwigPath();
        $configPath         = $this->collectConfigPath();
        $commandProvider    = $this->collectCommand();

        $classTemplate = str_replace(
            ['{{provider}}', '{{twig}}', '{{config}}', '{{command}}'],
            [$controllerProvider, $twigPath, $configPath, $commandProvider],
            $classTemplate
        );
        $this->app['filesystem']->dumpFile($this->app['path.app'] . 'services/OptimizedApplicationServiceProvider.php', $classTemplate);

        $this->output->writeln('<comment>OptimizedApplicationServiceProvider created at '.$this->app['path.app'] . 'services/OptimizedApplicationServiceProvider.php</comment>');
    }

    /**
     * Search for controllers on main app and registered modules.
     *
     * @return string formatted code for controller service provider
     */
    protected function collectController()
    {
        $this->output->writeln('<info> -- Collecting available controller</info>');
        $controllerProviderCode = '';
        $controllerDirectories  = [
            'main'  => [
                'dir'   => $this->app['path.app'] . 'controllers',
                'ns'    => ''
            ]
        ];

        foreach ($this->modules as $module) {
            $resources  = $module->getResources();
            $identifier = $module->getModuleIdentifier();

            if (!$resources->controllers) {
                continue;
            }

            $controllerDir  = $this->moduleMgr->getModulePath($identifier) . '/' . $resources->controllers;
            $controllerNs   = $this->moduleMgr->getModuleNamespace($identifier) . '\\' . $resources->controllers;

            $controllerDirectories[$identifier] = ['dir' => $controllerDir, 'ns' => $controllerNs];
        }

        foreach ($controllerDirectories as $module => $controller) {
            $this->output->writeln('<comment> ---- Collecting controller from the '.$module.' module</comment>');

            $files = $this->scanDirectory($controller['dir']);

            foreach ($files as $file) {
                $controllerClass = str_replace([$controller['dir'], '.php', DIRECTORY_SEPARATOR], ['', '', '\\'], $file);
                $controllerClass = $controller['ns'] . '\\' . ltrim($controllerClass, '\\');
                $controllerClass = ltrim($controllerClass, '\\');

                $controllerProviderCode .= $this->createControllerServiceCode($controllerClass);
            }
        }

        return $controllerProviderCode;
    }

    /**
     * Create controller service provider code based on the stub.
     *
     * @param  string $controller   The fqcn controller class name
     *
     * @return string               Formatted code for registering controller as a service;
     */
    protected function createControllerServiceCode($controller)
    {
        $controllerReflection   = new ReflectionClass($controller);
        $controllerConstructor  = $controllerReflection->getConstructor();
        $controllerAction       = '';
        $providerTemplate       = file_get_contents(__DIR__.'/stubs/controllerProvider.stub');
        $dependencies           = [];


        if ($controllerConstructor) {
            $constructorParameters  = $controllerConstructor->getParameters();

            foreach ($constructorParameters as $parameterReflection) {
                $parameterClassName = $parameterReflection->getClass()->getName();

                if ($this->app->offsetExists($parameterClassName)) {
                    $dependencies[] = "\$app['$parameterClassName']";
                } elseif (class_exists($parameterClassName)) {
                    $dependencies[] = "new $parameterClassName()";
                }
            }
        }

        $indentation    = "\n                    ";
        $dependencies   = ($dependencies)
                        ? "$indentation    " .
                          implode(", $indentation    ", $dependencies) .
                          "$indentation"
                        : '';

        if ($controllerReflection->implementsInterface('\SilexStarter\Contracts\ContainerAwareInterface')) {
            $controllerAction .= "\$controller->setContainer(\$app);\n";
        }

        if ($controllerReflection->implementsInterface('\SilexStarter\Contracts\DispatcherAwareInterface')) {
            $controllerAction .= "\$controller->setDispatcher(\$app['dispatcher']);";
        }

        return str_replace(
            ['{{controller}}', '{{dependencies}}', '{{controllerAction}}'],
            [$controller, $dependencies, $controllerAction],
            $providerTemplate
        );
    }

    protected function collectTwigPath()
    {
        $this->output->writeln('<info> -- Collecting available template</info>');
        $twigPath   = '';
        $indentation= '            ';

        foreach ($this->modules as $module) {
            $resources  = $module->getResources();
            $identifier = $module->getModuleIdentifier();

            $this->output->writeln('<comment> ---- Registering template for the '.$identifier.' module</comment>');

            if (!$resources->views) {
                continue;
            }

            $templateDir  = $this->moduleMgr->getModulePath($identifier) . '/' . $resources->views;
            $publishedDir = $this->app['config']['twig.template_dir'] . '/module/' . $identifier;

            if ($this->app['filesystem']->exists($publishedDir)) {
                $twigPath .= "\$app['twig.loader.filesystem']->addPath('$publishedDir', '$identifier');\n$indentation";
            }

            $twigPath .= "\$app['twig.loader.filesystem']->addPath('$templateDir', '$identifier');\n$indentation";
        }

        return $twigPath;
    }

    protected function collectCommand()
    {
        $this->output->writeln('<info> -- Collecting available command</info>');
        $commandProviderCode = '';
        $commandDirectories  = [
            'main'  => [
                'dir'   => $this->app['path.app'] . 'commands',
                'ns'    => ''
            ]
        ];

        foreach ($this->modules as $module) {
            $resources  = $module->getResources();
            $identifier = $module->getModuleIdentifier();

            if (!$resources->commands) {
                continue;
            }

            $commandDir  = $this->moduleMgr->getModulePath($identifier) . '/' . $resources->commands;
            $commandNs   = $this->moduleMgr->getModuleNamespace($identifier) . '\\' . $resources->commands;

            $commandDirectories[$identifier] = ['dir' => $commandDir, 'ns' => $commandNs];
        }

        foreach ($commandDirectories as $module => $command) {
            $this->output->writeln('<comment> ---- Collecting command from the '.$module.' module</comment>');

            $files = $this->scanDirectory($command['dir']);

            foreach ($files as $file) {
                $commandClass = str_replace([$command['dir'], '.php', DIRECTORY_SEPARATOR], ['', '', '\\'], $file);
                $commandClass = $command['ns'] . '\\' . ltrim($commandClass, '\\');
                $commandClass = ltrim($commandClass, '\\');

                $commandProviderCode .= "\n                \$app['console']->registerCommand(new $commandClass);";
            }
        }

        return $commandProviderCode;
    }

    protected function collectConfigPath()
    {
        $this->output->writeln('<info> -- Collecting available configuration</info>');

        $configPath = '';
        $indentation= '            ';

        foreach ($this->modules as $module) {
            $resources  = $module->getResources();
            $identifier = $module->getModuleIdentifier();
            $this->output->writeln('<comment> ---- Registering configuration for the '.$identifier.' module</comment>');

            if (!$resources->config) {
                continue;
            }

            $configDir  = $this->moduleMgr->getModulePath($identifier) . '/' . $resources->config;

            $configPath .= "\$app['config']->addDirectory('$configDir', '$identifier');\n$indentation";
        }

        return $configPath;

    }

    /**
     * Add OptimizedApplicationServiceProvider into services list
     */
    protected function registerOptimizedProvider()
    {
        $this->output->writeln('<info>Registering OptimizedApplicationServiceProvider...</info>');

        $services = $this->app['config']->get('services.common');

        if (false === array_search('OptimizedApplicationServiceProvider', $services)) {
            $services[] = 'OptimizedApplicationServiceProvider';
        }

        $this->app['config']->set('services.common', $services);
        $this->app['config']->save('services');
    }

    protected function appendMiddleware()
    {

        $this->output->writeln('<info>Appending module middleware file into main middleware file...</info>');

        $middlewareFile     = $this->app['path.app'] . 'middlewares.php';
        $middlewareContent  = file_get_contents($middlewareFile);
        $middlewareContent  = preg_replace('/require_once(.*?;\n)/', '', $middlewareContent);
        $middlewareFiles    = [];

        foreach ($this->moduleMgr->getMiddlewareFiles() as $middleware) {
            $middlewareFiles[] = "require_once '$middleware';";
        }

        $middlewareContent .= implode("\n", $middlewareFiles);

        $this->app['filesystem']->dumpFile($middlewareFile, $middlewareContent . "\n");
    }

    protected function appendRoute()
    {
        $this->output->writeln('<info>Appending module route file into main route file...</info>');

        $routeFile      = $this->app['path.app'] . 'routes.php';
        $routeContent   = file_get_contents($routeFile);
        $routeContent   = preg_replace('/require_once(.*?;\n)/', '', $routeContent);
        $routeFiles     = [];

        foreach ($this->moduleMgr->getRouteFiles() as $route) {
            $routeFiles[] = "require_once '$route';";
        }

        $routeContent .= implode("\n", $routeFiles);

        $this->app['filesystem']->dumpFile($routeFile, $routeContent . "\n");
    }

    protected function scanDirectory($dir)
    {
        $fileIterator   = new RecursiveCallbackFilterIterator(
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

        return new RecursiveIteratorIterator($fileIterator);
    }
}

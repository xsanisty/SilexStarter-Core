<?php

namespace SilexStarter\Module;

use SilexStarter\SilexStarter;
use SilexStarter\Exception\ModuleRequiredException;
use SilexStarter\Exception\ModuleNotRegisteredException;
use SilexStarter\Contracts\ModuleProviderInterface;

class ModuleManager
{
    protected $app;
    protected $modules      = [];
    protected $routes       = [];
    protected $middlewares  = [];
    protected $assets       = [];
    protected $config       = [];
    protected $commands     = [];
    protected $services     = [];
    protected $views        = [];
    protected $path         = [];
    protected $namespace    = [];

    public function __construct(SilexStarter $app)
    {
        $this->app = $app;
    }

    /**
     * Check if specified module is registered.
     *
     * @param string $module The module accessor string
     *
     * @return bool
     */
    public function isRegistered($module)
    {
        return isset($this->modules[$module]);
    }

    /**
     * Get all registered modules.
     *
     * @return array
     */
    public function getRegisteredModules()
    {
        return $this->modules;
    }

    /**
     * Get module provider instance
     *
     * @param  string $moduleIdentifier
     *
     * @return ModuleProvider
     */
    public function getModule($moduleIdentifier)
    {
        if ($this->isRegistered($moduleIdentifier)) {
            return $this->modules[$moduleIdentifier];
        }

        throw new ModuleNotRegisteredException("Module \"$moduleIdentifier\" is not registered");
    }

    /**
     * Get the root path of specidied module
     * @param  string $moduleIdentifier     The module identifier
     * @return string                       The module directory path
     */
    public function getModulePath($moduleIdentifier)
    {
        if ($this->isRegistered($moduleIdentifier)) {
            return $this->path[$moduleIdentifier];
        }

        throw new ModuleNotRegisteredException("Module \"$moduleIdentifier\" is not registered");
    }

    /**
     * Get base namespace of the specified module.
     *
     * @param  string $moduleIdentifier The module identifier
     *
     * @return string                   Base namespace of the module
     */
    public function getModuleNamespace($moduleIdentifier)
    {
        if ($this->isRegistered($moduleIdentifier)) {
            return $this->namespace[$moduleIdentifier];
        }

        throw new ModuleNotRegisteredException("Module \"$moduleIdentifier\" is not registered");
    }

    /**
     * Get public asset path of specified module.
     *
     * @param  string $moduleIdentifier The module identifier
     * @return string                   Path to public asset
     */
    public function getPublicAssetPath($moduleIdentifier)
    {
        if ($this->isRegistered($moduleIdentifier)) {
            return $this->app['path.public'].'assets/'.$moduleIdentifier;
        }

        throw new ModuleNotRegisteredException("Module \"$moduleIdentifier\" is not registered");
    }

    /**
     * Get template path of specified module
     * @param  string $moduleIdentifier The module identifier
     * @return string                   Path to twig template
     */
    public function getTemplatePath($moduleIdentifier)
    {
        if ($this->isRegistered($moduleIdentifier)) {
            return $this->views[$moduleIdentifier];
        }

        throw new ModuleNotRegisteredException("Module \"$moduleIdentifier\" is not registered");
    }

    /**
     * Register multiple module provider at once.
     *
     * @param array $modules array of SilexStarter\Module\ModuleProvider
     */
    public function registerModules(array $modules)
    {
        foreach ($modules as $moduleProvider) {
            $this->register(new $moduleProvider($this->app));
        }
    }

    /**
     * Check wether required modules is registered.
     *
     * @param string $moduleIdentifier the module indentifier
     * @param array  $modules list of module identifiers
     *
     * @throws ModuleRequiredException
     */
    protected function checkRequiredModules($moduleIdentifier, array $requiredModules)
    {
        foreach ($requiredModules as $requiredModule) {
            if (!$this->isRegistered($requiredModule)) {
                throw new ModuleRequiredException($moduleIdentifier.' module require '.$requiredModule.' as its dependency');
            }
        }
    }

    /**
     * Register ModuleProvider into application.
     *
     * @param SilexStarter\Contracts\ModuleProviderInterface $module the module provider
     */
    public function register(ModuleProviderInterface $module)
    {
        $moduleIdentifier = $module->getModuleIdentifier();

        if ($this->isRegistered($moduleIdentifier)) {
            return;
        }

        $this->checkRequiredModules($moduleIdentifier, $module->getRequiredModules());

        /* Get the module path via the class reflection */
        $moduleReflection = new \ReflectionClass($module);
        $modulePath       = dirname($moduleReflection->getFileName()) . '/';
        $moduleResources  = $module->getResources();
        $moduleNamespace  = $moduleReflection->getNamespaceName();

        $this->path[$moduleIdentifier]      = $modulePath;
        $this->namespace[$moduleIdentifier] = $moduleNamespace;

        /** only register path when optimized_app is registered */
        if (isset($this->app['optimized_app'])) {
            $this->modules[$moduleIdentifier]   = $module;
            $this->assets[$moduleIdentifier]    = $modulePath.$moduleResources->assets;
            $this->config[$moduleIdentifier]    = $modulePath.$moduleResources->config;

            $module->register();

            return;
        }

        /* if middleware file exists, queue for later include */
        if ($moduleResources->middlewares) {
            $this->addMiddlewareFile($modulePath . $moduleResources->middlewares);
        }

        /* if route file exists, queue for later include */
        if ($moduleResources->routes) {
            $this->addRouteFile($modulePath . $moduleResources->routes);
        }

        /* keep assets path of the module */
        if ($moduleResources->assets) {
            $this->assets[$moduleIdentifier] = $modulePath . $moduleResources->assets;
        }

        $this->registerModuleConfig($module);
        $this->registerModuleController($module);
        $this->registerModuleCommand($module);
        $this->registerModuleServices($module);
        $this->registerModuleTemplate($module);
        $this->modules[$moduleIdentifier] = $module;

        $module->register();
    }

    /**
     * Register module controller as services
     *
     * @param  SilexStarter\Contracts\ModuleProviderInterface $module Module provider class
     */
    protected function registerModuleController(ModuleProviderInterface $module)
    {
        if ($this->app['controller_as_service'] && $module->getResources()->controllers) {
            foreach ((array) $module->getResources()->controllers as $controllerDirectory) {
                $this->app->registerControllerDirectory(
                    $this->path[$module->getModuleIdentifier()] . $controllerDirectory,
                    $this->namespace[$module->getModuleIdentifier()] . '\\' . str_replace('/', '\\', $controllerDirectory)
                );
            }
        }
    }

    /**
     * Queue module command to be registered to console application
     *
     * @param  SilexStarter\Contracts\ModuleProviderInterface $module Module provider class
     */
    protected function registerModuleCommand(ModuleProviderInterface $module)
    {
        if ($module->getResources()->commands) {
            $commandPath        = $this->path[$module->getModuleIdentifier()] . $module->getResources()->commands;
            $commandNamespace   = $this->namespace[$module->getModuleIdentifier()] . '\\' . $module->getResources()->commands;

            $this->commands[$commandNamespace] = $commandPath;
        }
    }

    /**
     * Register module template path so it can be accessed by controller
     *
     * @param  SilexStarter\Contracts\ModuleProviderInterface $module Module provider class
     */
    protected function registerModuleTemplate(ModuleProviderInterface $module)
    {
        if ($module->getResources()->views) {
            $moduleIdentifier = $module->getModuleIdentifier();

            $this->views[$moduleIdentifier] = $this->path[$module->getModuleIdentifier()] . $module->getResources()->views;

            $publishedDir   = $this->app['config']['twig.template_dir'] . '/module/' . $moduleIdentifier;
            $templateDir    = $this->app['filesystem']->exists($publishedDir)
                            ? $publishedDir
                            : $this->views[$moduleIdentifier];


            $this->app['twig.loader.filesystem']->addPath($templateDir, $moduleIdentifier);
            $this->app['twig.loader.filesystem']->addPath($this->views[$moduleIdentifier], $moduleIdentifier);
        }
    }

    /**
     * Register module services into application container
     *
     * @param  SilexStarter\Contracts\ModuleProviderInterface $module Module provider class
     */
    protected function registerModuleServices(ModuleProviderInterface $module)
    {
        if ($module->getResources()->services) {
            $servicesList = require $this->path[$module->getModuleIdentifier()] . $module->getResources()->services;
            $this->app->registerServices($servicesList);
        }
    }

    /**
     * Register module configuration to configuration container
     *
     * @param  SilexStarter\Contracts\ModuleProviderInterface $module Module provider class
     */
    protected function registerModuleConfig(ModuleProviderInterface $module)
    {
        if ($module->getResources()->config) {
            $configDir = $this->path[$module->getModuleIdentifier()] . $module->getResources()->config;

            $this->app['config']->addDirectory(
                $configDir,
                $module->getModuleIdentifier()
            );

            $this->config[$module->getModuleIdentifier()] = $configDir;
        }
    }

    /**
     * Boot up all available module.
     */
    public function boot()
    {
        foreach ($this->modules as $module) {
            $module->boot();
        }

        $commandDirectories = $this->commands;
        $app = $this->app;

        $this->app['dispatcher']->addListener(
            'console.init',
            function () use ($app, $commandDirectories) {
                foreach ($commandDirectories as $namespace => $directory) {
                    $app['console']->registerCommandDirectory($directory, $namespace);
                }
            }
        );
    }

    /**
     * Register module routes file.
     *
     * @param string $path full apath to the module route file
     */
    public function addRouteFile($path)
    {
        if (!in_array($path, $this->routes)) {
            $this->routes[] = $path;
        }
    }

    /**
     * Get all available route files.
     *
     * @return array list of route files
     */
    public function getRouteFiles()
    {
        return $this->routes;
    }

    /**
     * Register module middleware file.
     *
     * @param string $path full apath to the module middleware file
     */
    public function addMiddlewareFile($path)
    {
        if (!in_array($path, $this->middlewares)) {
            $this->middlewares[] = $path;
        }
    }

    /**
     * Get all available middleware files.
     *
     * @return array list of middleware files
     */
    public function getMiddlewareFiles()
    {
        return $this->middlewares;
    }

    /**
     * Publish module assets into public asset directory.
     *
     * @param string $module The module identifier
     */
    public function publishAsset($module)
    {
        if (!$this->modules[$module]->getResources()->assets) {
            throw new Exception("Module $module has no defined assets");
        }

        $moduleAsset = $this->assets[$module];
        $publicAsset = $this->app['path.public'].'assets/'.$module;

        $this->app['filesystem']->mirror($moduleAsset, $publicAsset);
    }

    /**
     * Publish config into application config directory .
     *
     * @param string $module The module identifier
     */
    public function publishConfig($module)
    {
        if (!$this->modules[$module]->getResources()->assets) {
            throw new Exception("Module $module has no defined config");
        }

        $moduleConfig = $this->config[$module];
        $publicConfig = $this->app['path.app'].'config/'.$module;

        $this->app['filesystem']->mirror($moduleConfig, $publicConfig);
    }

    /**
     * Publish template into application template path
     *
     * @param  string $module The module identifier
     */
    public function publishTemplate($module)
    {
        $moduleTemplate = $this->views[$module];
        $publicTemplate = $this->app['config']['twig.template_dir'] . '/module/' . $module;

        $this->app['filesystem']->mirror($moduleTemplate, $publicTemplate);
    }
}

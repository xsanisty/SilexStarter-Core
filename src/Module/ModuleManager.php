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

        throw new ModuleNotRegisteredException("Module $moduleIdentifier is not registered");
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

        throw new ModuleNotRegisteredException("Module $moduleIdentifier is not registered");
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

        throw new ModuleNotRegisteredException("Module $moduleIdentifier is not registered");
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

        throw new ModuleNotRegisteredException("Module $moduleIdentifier is not registered");
    }

    public function getTemplatePath($moduleIdentifier)
    {
        if ($this->isRegistered($moduleIdentifier)) {
            return $this->views[$moduleIdentifier];
        }

        throw new ModuleNotRegisteredException("Module $moduleIdentifier is not registered");
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
     * @param ModuleProviderInterface $module the module provider
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

        if (isset($app['optimized_app'])) {
            $this->modules[$moduleIdentifier]   = $module;
            $this->assets[$moduleIdentifier]    = $modulePath.$moduleResources->assets;
            $this->config[$moduleIdentifier]    = $modulePath.$moduleResources->config;

            $module->register();

            return;
        }

        /* if config dir exists, add namespace to the config reader */
        if ($moduleResources->config) {
            $this->app['config']->addDirectory(
                $modulePath.$moduleResources->config,
                $moduleIdentifier
            );

            $this->config[$moduleIdentifier] = $modulePath.$moduleResources->config;
        }

        /* If controller_as_service enabled, register the controllers as service */
        if ($this->app['controller_as_service'] && $moduleResources->controllers) {
            $this->app->registerControllerDirectory(
                $modulePath.$moduleResources->controllers,
                $moduleNamespace.'\\'.$moduleResources->controllers
            );
        }

        /* If command exists, register all command */
        if ($moduleResources->commands) {
            $commandPath = $modulePath.$moduleResources->commands;
            $commandNamespace = $moduleNamespace.'\\'.$moduleResources->commands;

            $this->commands[$commandNamespace] = $commandPath;
        }

        /* if route file exists, queue for later include */
        if ($moduleResources->routes) {
            $this->addRouteFile($modulePath.$moduleResources->routes);
        }

        /* if middleware file exists, queue for later include */
        if ($moduleResources->middlewares) {
            $this->addMiddlewareFile($modulePath.$moduleResources->middlewares);
        }

        /* if template file exists, register new template path under new namespace */
        if ($moduleResources->views) {
            $this->views[$moduleIdentifier] = $modulePath.$moduleResources->views;

            $publishedDir   = $this->app['config']['twig.template_dir'] . '/module/' . $moduleIdentifier;
            $templateDir    = $this->app['filesystem']->exists($publishedDir)
                            ? $publishedDir
                            : $this->views[$moduleIdentifier];


            $this->app['twig.loader.filesystem']->addPath($templateDir, $moduleIdentifier);
            $this->app['twig.loader.filesystem']->addPath($this->views[$moduleIdentifier], $moduleIdentifier);
        }

        /* keep assets path of the module */
        if ($moduleResources->assets) {
            $this->assets[$moduleIdentifier] = $modulePath.$moduleResources->assets;
        }

        $this->modules[$moduleIdentifier] = $module;
        $module->register();
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

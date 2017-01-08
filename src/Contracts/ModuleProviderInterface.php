<?php

namespace SilexStarter\Contracts;

/**
 * Module provider and module installer should be placed at the root of the module directory.
 */
interface ModuleProviderInterface
{
    /**
     * Module provider constructor.
     *
     * @param Silex\Application $app The Silex application instance
     */
    public function __construct(\Silex\Application $app);

    /**
     * Get the identifier of the module that will be installed,
     * this will be used to register template and config namespace as well
     * the name should be [a-zA-Z-_].
     *
     * @return string
     */
    public function getModuleIdentifier();

    /**
     * Get the module information.
     *
     * @return SilexStarter\Module\ModuleInfo The module information
     */
    public function getInfo();

    /**
     * Get module resources including route, config, views, etc.
     *
     * @return SilexStarter\Module\ModuleResource
     */
    public function getResources();

    /**
     * Get the required module to be present.
     *
     * @return array List of module accessor name e.g ['module1', 'module2']
     */
    public function getRequiredModules();

    /**
     * Get the required permission to access module's features
     *
     * @return array  List of [permission.name => 'Permission description']
     */
    public function getRequiredPermissions();

    /**
     * Register the module, module's service provider, or twig extension here.
     */
    public function register();

    /**
     * setup the required action here.
     */
    public function boot();

    /**
     * This method will be invoked after module installation is completed.
     * Seed database, prepare required file, and other action here.
     *
     * @return void
     */
    public function install();

    /**
     * This method will be invoked before the uninstall process is started.
     *
     * @return void
     */
    public function uninstall();
}

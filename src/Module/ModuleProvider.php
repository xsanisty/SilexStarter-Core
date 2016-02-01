<?php

namespace SilexStarter\Module;

use Silex\Application;
use SilexStarter\Module\ModuleInfo;
use SilexStarter\Module\ModuleResource;
use SilexStarter\Contracts\ModuleProviderInterface;

abstract class ModuleProvider implements ModuleProviderInterface
{
    protected $app;

    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    public function getRequiredModules()
    {
        return [];
    }

    public function getInfo()
    {
        return new ModuleInfo([]);
    }

    public function getResources()
    {
        return new ModuleResource(
            [
                'routes'        => 'Resources/routes.php',
                'views'         => 'Resources/views',
                'controllers'   => 'Controller',
                'assets'        => 'Resources/assets',
                'migrations'    => 'Resources/migrations',
                'translations'  => 'Resources/translations',
                'config'        => 'Resources/config'
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getRequiredPermissions()
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function install()
    {
    }

    /**
     * {@inheritdoc}
     */
    public function uninstall()
    {
    }

    /**
     * {@inheritdoc}
     */
    public function register()
    {
    }

    /**
     * {@inheritdoc}
     */
    public function boot()
    {
    }
}

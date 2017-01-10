<?php

namespace SilexStarter\Module;

use Silex\Application;
use SilexStarter\Module\ModuleInfo;
use SilexStarter\Module\ModuleResource;
use SilexStarter\Contracts\ModuleProviderInterface;

abstract class ModuleProvider implements ModuleProviderInterface
{
    protected $app;
    protected $info;
    protected $resources;

    public function __construct(Application $app)
    {
        $this->app  = $app;
        $this->info = new ModuleInfo([]);
        $this->resources = new ModuleResource([]);
    }

    /**
     * {@inheritdoc}
     */
    public function getRequiredModules()
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function getInfo()
    {
        return $this->info;
    }

    /**
     * {@inheritdoc}
     */
    public function getResources()
    {
        return $this->resources;
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

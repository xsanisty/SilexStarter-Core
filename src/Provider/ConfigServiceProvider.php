<?php

namespace SilexStarter\Provider;

use Silex\Application;
use Silex\ServiceProviderInterface;
use SilexStarter\SilexStarter;
use SilexStarter\Config\ConfigurationContainer;

class ConfigServiceProvider implements ServiceProviderInterface
{
    public function register(Application $app)
    {
        $app['config'] = $app->share(
            function ($app) {
                return new ConfigurationContainer($app, $app['config.path']);
            }
        );

        if ($app instanceof SilexStarter) {
            $app->bind('SilexStarter\Config\ConfigurationContainer', 'config');
        }
    }

    public function boot(Application $app)
    {
    }
}

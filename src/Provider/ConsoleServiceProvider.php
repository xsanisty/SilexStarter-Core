<?php

namespace SilexStarter\Provider;

use Silex\Application;
use Silex\ServiceProviderInterface;
use SilexStarter\Console\Application as ConsoleApplication;

class ConsoleServiceProvider implements ServiceProviderInterface
{
    public function register(Application $app)
    {
        $app['console'] = $app->share(
            function ($app) {
                return new ConsoleApplication($app, 'xpress', '1.0');
            }
        );
    }

    public function boot(Application $app)
    {
    }
}

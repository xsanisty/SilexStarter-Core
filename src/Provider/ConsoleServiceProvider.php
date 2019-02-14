<?php

namespace SilexStarter\Provider;

use Pimple\Container;
use Pimple\ServiceProviderInterface;
use SilexStarter\Console\Application as ConsoleApplication;

class ConsoleServiceProvider implements ServiceProviderInterface
{
    public function register(Container $app)
    {
        $app['console'] = $app->share(
            function ($app) {
                return new ConsoleApplication($app, $app['console_name'], $app['console_version']);
            }
        );
    }
}

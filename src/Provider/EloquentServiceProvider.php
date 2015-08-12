<?php

namespace SilexStarter\Provider;

use Silex\Application;
use Illuminate\Events\Dispatcher;
use Silex\ServiceProviderInterface;
use Illuminate\Database\Capsule\Manager as CapsuleManager;

class EloquentServiceProvider implements ServiceProviderInterface
{
    public function register(Application $app)
    {
        $app['capsule'] = $app->share(
            function ($app) {
                $capsule = new CapsuleManager();
                $eventDispatcher = new Dispatcher();

                $defaultConnection  = $app['config']['database']['default'];
                $connectionConfig   = $app['config']['database']['connections'];

                $capsule->addConnection($connectionConfig[$defaultConnection]);
                $capsule->setEventDispatcher($eventDispatcher);
                $capsule->setAsGlobal();

                return $capsule;
            }
        );

        $app['db'] = $app->share(
            function ($app) {
                return $app['capsule']->getDatabaseManager();
            }
        );

        $app->bind('Illuminate\Database\Manager', 'db');
    }

    public function boot(Application $app)
    {
        $app['capsule']->bootEloquent();
    }
}

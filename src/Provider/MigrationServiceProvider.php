<?php

namespace SilexStarter\Provider;

use Silex\Application;
use Silex\ServiceProviderInterface;
use SilexStarter\Migration\Migrator;

class MenuManagerServiceProvider implements ServiceProviderInterface
{
    public function register(Application $app)
    {
        $app['migrator'] = $app->share(
            function () {
                return new Migrator();
            }
        );

        $app->bind('SilexStarter\Migration\Migrator', 'migrator');
    }

    public function boot(Application $app)
    {
    }
}

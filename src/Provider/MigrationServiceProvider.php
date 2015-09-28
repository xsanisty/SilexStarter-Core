<?php

namespace SilexStarter\Provider;

use Silex\Application;
use Silex\ServiceProviderInterface;
use Symfony\Component\Filesystem\Filesystem;
use SilexStarter\SilexStarter;
use SilexStarter\Storage\JsonStorage;
use SilexStarter\Migration\Migrator;
use SilexStarter\Migration\MigrationRepository;

class MigrationServiceProvider implements ServiceProviderInterface
{
    public function register(Application $app)
    {
        $app['storage.json'] = $app->share(
            function (Application $app) {
                return new JsonStorage($app['filesystem'], ['path' => $app['path.app'] . 'storage/meta/']);
            }
        );

        $app['migration.repository'] = $app->share(
            function (Application $app) {
                return new MigrationRepository($app['storage.json'], $app['filesystem']);
            }
        );
        $app['migrator'] = $app->share(
            function (Application $app) {
                return new Migrator($app['migration.repository'], $app['module']);
            }
        );

        if ($app instanceof SilexStarter) {
            $app->bind('SilexStarter\Migration\Migrator', 'migrator');
        }
    }

    public function boot(Application $app)
    {
    }
}

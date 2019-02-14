<?php

namespace SilexStarter\Provider;

use Pimple\Container;
use Pimple\ServiceProviderInterface;
use Symfony\Component\Filesystem\Filesystem;
use SilexStarter\SilexStarter;
use SilexStarter\Storage\JsonStorage;
use SilexStarter\Migration\Migrator;
use SilexStarter\Migration\MigrationRepository;

class MigrationServiceProvider implements ServiceProviderInterface
{
    public function register(Container $app)
    {
        $app['storage.json'] = $app->share(
            function (Container $app) {
                return new JsonStorage($app['filesystem'], ['path' => $app['path.app'] . 'storage/meta/']);
            }
        );

        $app['migration.repository'] = $app->share(
            function (Container $app) {
                return new MigrationRepository($app['storage.json'], $app['filesystem']);
            }
        );

        $app['migrator'] = $app->share(
            function (Container $app) {
                return new Migrator(
                    $app['migration.repository'],
                    $app['module'],
                    $app['capsule']->schema(),
                    [
                        'path' => $app['path.app'].'database/migrations'
                    ]
                );
            }
        );

        if ($app instanceof SilexStarter) {
            $app->bind('SilexStarter\Migration\Migrator', 'migrator');
        }
    }
}

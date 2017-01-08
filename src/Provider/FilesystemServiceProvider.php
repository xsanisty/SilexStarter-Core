<?php

namespace SilexStarter\Provider;

use Silex\Application;
use Silex\ServiceProviderInterface;
use SilexStarter\SilexStarter;
use Symfony\Component\Filesystem\Filesystem;

class FilesystemServiceProvider implements ServiceProviderInterface
{
    public function register(Application $app)
    {
        $app['filesystem'] = $app->share(
            function () {
                return new Filesystem();
            }
        );

        if ($app instanceof SilexStarter) {
            $app->bind('Symfony\Component\Filesystem\Filesystem', 'filesystem');
        }
    }

    public function boot(Application $app)
    {
    }
}

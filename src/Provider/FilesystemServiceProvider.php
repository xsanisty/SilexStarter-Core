<?php

namespace SilexStarter\Provider;

use Silex\Application;
use Silex\ServiceProviderInterface;
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

        $app->bind('Symfony\Component\Filesystem\Filesystem', 'filesystem');
    }

    public function boot(Application $app)
    {
    }
}

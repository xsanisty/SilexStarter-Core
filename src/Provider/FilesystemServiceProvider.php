<?php

namespace SilexStarter\Provider;

use Pimple\Container;
use Pimple\ServiceProviderInterface;
use SilexStarter\SilexStarter;
use Symfony\Component\Filesystem\Filesystem;

class FilesystemServiceProvider implements ServiceProviderInterface
{
    public function register(Container $app)
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
}

<?php

namespace SilexStarter\Provider;

use Pimple\Container;
use Pimple\ServiceProviderInterface;
use SilexStarter\SilexStarter;
use SilexStarter\Asset\AssetManager;

class AssetManagerServiceProvider implements ServiceProviderInterface
{
    public function register(Container $app)
    {
        $app['asset_manager'] = $app->share(
            function (Container $app) {
                return new AssetManager(
                    $app['request_stack'],
                    'assets'
                );
            }
        );

        if ($app instanceof SilexStarter) {
            $app->bind('SilexStarter\Asset\AssetManager', 'asset_manager');
        }
    }
}

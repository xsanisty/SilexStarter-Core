<?php

namespace SilexStarter\Provider;

use Pimple\Container;
use Pimple\ServiceProviderInterface;
use SilexStarter\SilexStarter;
use SilexStarter\Module\ModuleManager;

class ModuleServiceProvider implements ServiceProviderInterface
{
    public function register(Container $app)
    {
        $app['module'] = $app->share(
            function (Container $app) {
                return new ModuleManager($app);
            }
        );

        if ($app instanceof SilexStarter) {
            $app->bind('SilexStarter\Module\ModuleManager', 'module');
        }
    }
}

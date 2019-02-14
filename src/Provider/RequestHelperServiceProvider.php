<?php

namespace SilexStarter\Provider;

use Pimple\Container;
use Pimple\ServiceProviderInterface;
use SilexStarter\SilexStarter;
use SilexStarter\Request\RequestHelper;

class RequestHelperServiceProvider implements ServiceProviderInterface
{
    public function register(Container $app)
    {
        $app['request_helper'] = $app->share(
            function ($app) {
                return new RequestHelper($app['request_stack']);
            }
        );

        if ($app instanceof SilexStarter) {
            $app->bind('SilexStarter\Request\RequestHelper', 'request_helper');
        }
    }
}

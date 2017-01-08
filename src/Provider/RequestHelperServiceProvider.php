<?php

namespace SilexStarter\Provider;

use Silex\Application;
use Silex\ServiceProviderInterface;
use SilexStarter\SilexStarter;
use SilexStarter\Request\RequestHelper;

class RequestHelperServiceProvider implements ServiceProviderInterface
{
    public function register(Application $app)
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

    public function boot(Application $app)
    {
    }
}

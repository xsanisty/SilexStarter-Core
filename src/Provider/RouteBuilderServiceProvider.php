<?php

namespace SilexStarter\Provider;

use Silex\Application;
use Silex\ServiceProviderInterface;
use Illuminate\Support\Str;
use SilexStarter\Router\RouteBuilder;
use SilexStarter\Router\RoutePermissionChecker;

class RouteBuilderServiceProvider implements ServiceProviderInterface
{
    public function register(Application $app)
    {
        $app['route_builder'] = $app->share(
            function (Application $app) {
                return new RouteBuilder($app, new Str());
            }
        );

        $app['route_permission_checker'] = $app->share(
            function (Application $app) {
                return new RoutePermissionChecker(
                    $app['response_builder'],
                    $app['url_generator'],
                    $app['sentry']->getUser()
                );
            }
        );
    }

    public function boot(Application $app)
    {
    }
}

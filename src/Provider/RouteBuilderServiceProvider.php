<?php

namespace SilexStarter\Provider;

use Pimple\Container;
use Pimple\ServiceProviderInterface;
use Illuminate\Support\Str;
use SilexStarter\Router\RouteBuilder;
use SilexStarter\Router\RoutePermissionChecker;

class RouteBuilderServiceProvider implements ServiceProviderInterface
{
    public function register(Container $app)
    {
        $app['route_builder'] = $app->share(
            function (Container $app) {
                return new RouteBuilder($app, new Str());
            }
        );

        $app['route_permission_checker'] = $app->share(
            function (Container $app) {
                return new RoutePermissionChecker(
                    $app['response_builder'],
                    $app['url_generator'],
                    $app['sentry']->getUser()
                );
            }
        );
    }
}

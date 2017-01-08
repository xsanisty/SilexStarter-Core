<?php

namespace SilexStarter\Provider;

use Silex\Application;
use Silex\ServiceProviderInterface;
use SilexStarter\SilexStarter;
use SilexStarter\Response\ResponseBuilder;

class ResponseBuilderServiceProvider implements ServiceProviderInterface
{
    public function register(Application $app)
    {
        $app['response_builder'] = $app->share(
            function ($app) {
                return new ResponseBuilder($app['twig']);
            }
        );

        if ($app instanceof SilexStarter) {
            $app->bind('SilexStarter\Response\ResponseBuilder', 'response_builder');
        }
    }

    public function boot(Application $app)
    {
    }
}

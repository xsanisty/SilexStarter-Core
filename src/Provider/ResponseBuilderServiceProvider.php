<?php

namespace SilexStarter\Provider;

use Pimple\Container;
use Pimple\ServiceProviderInterface;
use SilexStarter\SilexStarter;
use SilexStarter\Response\ResponseBuilder;

class ResponseBuilderServiceProvider implements ServiceProviderInterface
{
    public function register(Container $app)
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
}

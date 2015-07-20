<?php

namespace SilexStarter\StaticProxy;

use XStatic\StaticProxy;
use Symfony\Component\Routing\Exception\RouteNotFoundException;

class Url extends StaticProxy
{
    public static function getInstanceIdentifier()
    {
        return 'url_generator';
    }

    public static function to($route, array $parameter = [])
    {
        try {
            return static::$container->get('url_generator')->generate($route, $parameter);
        } catch (RouteNotFoundException $e) {
            return static::path($route);
        }
    }

    public static function path($path = '/')
    {
        $request = static::$container->get('request_stack')->getCurrentRequest();

        if ($request) {
            return $request->getScheme().'://'.$request->getHost().$request->getBasePath().'/'.ltrim($path, '/');
        } else {
            //$_SERVER['HTTP_HOST'] for now return false
            return false;
        }
    }
}

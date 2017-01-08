<?php

namespace SilexStarter\StaticProxy;

use XStatic\StaticProxy;
use Symfony\Component\Routing\Exception\RouteNotFoundException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class Url extends StaticProxy
{
    public static function getInstanceIdentifier()
    {
        return 'url_generator';
    }

    /**
     * Generate url for a route with name, fallback to path if route with specified name isn't available
     *
     * @param  string $route     The route name
     * @param  array  $parameter The route parameters
     * @param  mixed  $type      Generated url type, absolute or relative [ABSOLUTE_URL, ABSOLUTE_PATH]
     *
     * @return string            The generated url
     */
    public static function to($route, array $parameter = [], $type = UrlGeneratorInterface::ABSOLUTE_URL)
    {
        try {
            return static::$container->get('url_generator')->generate($route, $parameter, $type);
        } catch (RouteNotFoundException $e) {
            return static::path($route);
        }
    }

    /**
     * Generate url for specific path, useful for getting asset url, etc.
     *
     * @param  string $path     The url path
     *
     * @return string           The generated url
     */
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

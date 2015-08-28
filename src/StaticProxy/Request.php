<?php

namespace SilexStarter\StaticProxy;

use XStatic\StaticProxy;

class Request extends StaticProxy
{
    public static function getInstanceIdentifier()
    {
        return 'request';
    }

    protected static function getRequest()
    {
        return static::$container->get('request_stack')->getCurrentRequest();
    }

    public static function ajax()
    {
        return static::getRequest()->isXmlHttpRequest();
    }

    public static function get($key = null)
    {
        return ($key)
                ? static::getRequest()->request->get($key)
                : static::getRequest()->request->all();
    }

    public static function query($key = null)
    {
        return ($key)
                ? static::getRequest()->query->get($key)
                : static::getRequest()->query->all();
    }

    public static function cookie($key = null)
    {
        return ($key)
                ? static::getRequest()->cookies->get($key)
                : static::getRequest()->cookies->all();
    }

    public static function file($key = null)
    {
        return ($key)
                ? static::getRequest()->files->get($key)
                : static::getRequest()->files->all();
    }

    public static function server($key = null)
    {
        return ($key)
                ? static::getRequest()->server->get($key)
                : static::getRequest()->server->all();
    }

    public static function header($key = null)
    {
        return ($key)
                ? static::getRequest()->headers->get($key)
                : static::getRequest()->headers->all();
    }
}

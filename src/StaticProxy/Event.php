<?php

namespace SilexStarter\StaticProxy;

use XStatic\StaticProxy;

class Event extends StaticProxy
{
    public static function getInstanceIdentifier()
    {
        return 'dispatcher';
    }

    public static function listen($event, callable $listener, $priority = 0)
    {
        return static::$container->get('dispatcher')->addListener($event, $listener, $priority);
    }

    public static function fire($event)
    {
        return static::$container->get('dispatcher')->dispatch($event);
    }
}

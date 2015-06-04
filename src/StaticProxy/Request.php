<?php

namespace SilexStarter\StaticProxy;

use XStatic\StaticProxy;

class Request extends StaticProxy
{
    public static function getInstanceIdentifier()
    {
        return 'request';
    }

    public static function ajax()
    {
        return static::$container->get('request')-> isXmlHttpRequest();
    }
}

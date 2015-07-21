<?php

namespace SilexStarter\StaticProxy;

use XStatic\StaticProxy;

class Dispatcher extends StaticProxy
{
    public static function getInstanceIdentifier()
    {
        return 'dispatcher';
    }
}

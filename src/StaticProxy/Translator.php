<?php

namespace SilexStarter\StaticProxy;

use XStatic\StaticProxy;

class Translator extends StaticProxy
{
    public static function getInstanceIdentifier()
    {
        return 'translator';
    }
}

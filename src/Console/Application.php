<?php

namespace SilexStarter\Console;

use Symfony\Component\Console\Application as ConsoleApplication;
use SilexStarter\SilexStarter;

class Application extends ConsoleApplication
{
    protected $silexStarter;

    public function __construct(SilexStarter $silexStarter, $name = 'xpress', $version = '1.0')
    {
        parent::__construct($name, $version);
        $this->silexStarter = $silexStarter;
    }

    public function getSilexStarter()
    {
        return $this->silexStarter;
    }
}

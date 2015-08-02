<?php

namespace SilexStarter\Controller;

use SilexStarter\Contracts\ContainerAwareInterface;
use Pimple\Pimple;

class ContainerAwareController implements ContainerAwareInterface
{
    protected $container;

    public function setContainer(Pimple $container)
    {
        $this->container = $container;
    }

    public function getContainer()
    {
        return $this->container;
    }
}

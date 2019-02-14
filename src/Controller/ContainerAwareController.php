<?php

namespace SilexStarter\Controller;

use SilexStarter\Contracts\ContainerAwareInterface;
use Pimple\Container;

abstract class ContainerAwareController implements ContainerAwareInterface
{
    protected $container;

    public function setContainer(Container $container)
    {
        $this->container = $container;
    }

    public function getContainer()
    {
        return $this->container;
    }
}

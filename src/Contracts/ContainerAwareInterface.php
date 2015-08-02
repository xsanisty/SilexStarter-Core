<?php

namespace SilexStarter\Contracts;

use Pimple\Pimple;

interface ContainerAwareInterface
{
    public function setContainer(Pimple $container);
    public function getContainer();
}

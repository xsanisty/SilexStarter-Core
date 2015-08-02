<?php

namespace SilexStarter\Contracts;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;

interface DispatcherAwareInterface
{
    public function setDispatcher(EventDispatcherInterface $dispatcher);
    public function getDispatcher();
}
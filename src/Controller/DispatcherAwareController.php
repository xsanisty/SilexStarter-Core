<?php

namespace SilexStarter\Controller;

use SilexStarter\Contracts\DispatcherAwareInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class DispatcherAwareController implements DispatcherAwareInterface
{
    protected $dispatcher;

    public function setDispatcher(EventDispatcherInterface $dispatcher)
    {
        $this->dispatcher = $dispatcher;
    }

    public function getDispatcher()
    {
        return $this->dispatcher;
    }
}

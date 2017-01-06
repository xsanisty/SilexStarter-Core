<?php

namespace SilexStarter\TwigExtension;

use Twig_Extension;
use Twig_SimpleFunction;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class TwigEventExtension extends Twig_Extension
{
    protected $dispatcher;

    public function __construct(EventDispatcherInterface $dispatcher)
    {
        $this->dispatcher = $dispatcher;
    }

    public function getName()
    {
        return 'silex-starter-event-ext';
    }

    public function getFunctions()
    {
        return [
            new Twig_SimpleFunction('dispatch', [$this, 'triggerEvent']),
            new Twig_SimpleFunction('trigger_event', [$this, 'triggerEvent']),
        ];
    }

    public function triggerEvent($event)
    {
        $this->dispatcher->dispatch($event);

        return;
    }
}

<?php

namespace SilexStarter\TwigExtension;

use Symfony\Component\HttpFoundation\RequestStack;
use SilexStarter\Menu\MenuManager;
use Twig_Extension;
use Twig_SimpleFunction;

class TwigCookieExtension extends Twig_Extension
{
    protected $stack;

    public function __construct(RequestStack $stack)
    {
        $this->stack = $stack;
    }

    public function getName()
    {
        return 'silex-starter-cookie-ext';
    }

    public function getFunctions()
    {
        return [
            new Twig_SimpleFunction('cookie', [$this, 'getCookie']),
            new Twig_SimpleFunction('cookies', [$this, 'getCookie']),
        ];
    }

    public function getCookie($cookie, $default = null)
    {
        return $this->stack->getCurrentRequest()->cookies->get($cookie, $default);
    }
}

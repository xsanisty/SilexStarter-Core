<?php

namespace SilexStarter\Request;

use Symfony\Component\HttpFoundation\RequestStack;

class RequestHelper
{
    protected $stack;

    public function __construct(RequestStack $stack)
    {
        $this->stack = $stack;
    }

    protected function getRequest()
    {
        return $this->stack->getCurrentRequest();
    }

    public function ajax()
    {
        return $this->getRequest()->isXmlHttpRequest();
    }

    public function get($key = null)
    {
        return ($key)
                ? $this->getRequest()->request->get($key)
                : $this->getRequest()->request->all();
    }

    public function query($key = null)
    {
        return ($key)
                ? $this->getRequest()->query->get($key)
                : $this->getRequest()->query->all();
    }

    public function cookie($key = null)
    {
        return ($key)
                ? $this->getRequest()->cookies->get($key)
                : $this->getRequest()->cookies->all();
    }

    public function file($key = null)
    {
        return ($key)
                ? $this->getRequest()->files->get($key)
                : $this->getRequest()->files->all();
    }

    public function server($key = null)
    {
        return ($key)
                ? $this->getRequest()->server->get($key)
                : $this->getRequest()->server->all();
    }

    public function header($key = null)
    {
        return ($key)
                ? $this->getRequest()->headers->get($key)
                : $this->getRequest()->headers->all();
    }
}

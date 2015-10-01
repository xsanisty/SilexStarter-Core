<?php

namespace SilexStarter\Request;

use RuntimeException;
use Symfony\Component\HttpFoundation\RequestStack;

class RequestHelper
{
    protected $stack;

    public function __construct(RequestStack $stack)
    {
        $this->stack = $stack;
    }

    /**
     * Get the current request context from the context stack.
     *
     * @return Request
     */
    protected function getRequest()
    {
        $request = $this->stack->getCurrentRequest();

        if ($request) {
            return $request;
        };

        throw new RuntimeException("Error Processing Request");
    }

    /**
     * Check if request was sent via ajax.
     *
     * @return boolean
     */
    public function ajax()
    {
        return $this->getRequest()->isXmlHttpRequest();
    }

    /**
     * Get request data.
     *
     * @param  string|null $key     The data key
     *
     * @return string               The value of request data
     */
    public function get($key = null)
    {
        return ($key)
                ? $this->getRequest()->request->get($key)
                : $this->getRequest()->request->all();
    }

    /**
     * Get all request data except specified key
     *
     * @param  string|array $key    The excluded data.
     *
     * @return string               The value of request data
     */
    public function except($key)
    {
        $request = $this->getRequest()->request->all();

        if (is_array($key)) {
            $request = array_diff_key($request, array_flip($key));
        } else {
            unset($request[$key]);
        }

        return $request;
    }

    public function only($key)
    {
        if (is_array($key)) {
            $request = [];

            foreach ($key as $k) {
                $request[$k] = $this->get($k);
            }

            return $request;

        } else {
            return $this->get($key);
        }
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

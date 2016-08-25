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
     * @param  mixed       $default The default value if key doesn't exists
     *
     * @return string               The value of request data
     */
    public function get($key = null, $default = null)
    {
        return ($key)
                ? $this->getRequest()->request->get($key, $default)
                : $this->getRequest()->request->all();
    }

    /**
     * Get all request data.
     *
     * @return array
     */
    public function all()
    {
        return $this->getRequest()->request->all();
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

    /**
     * Get only specified keys from the request.
     *
     * @param  mixed $key       The data key
     * @param  mixed $default   Default value or array of default value
     *
     * @return array            Array of the requested data
     */
    public function only($key, $default = null)
    {
        if (is_array($key)) {
            $request = [];

            foreach ($key as $index => $k) {
                $defaultVal  = is_array($default) && isset($default[$index]) ? $default[$index] : $default;
                $request[$k] = $this->get($k, $defaultVal);
            }

            return $request;

        } else {
            return $this->get($key, $default);
        }
    }

    public function query($key = null, $default = null)
    {
        return ($key)
                ? $this->getRequest()->query->get($key, $default)
                : $this->getRequest()->query->all();
    }

    public function cookie($key = null, $default = null)
    {
        return ($key)
                ? $this->getRequest()->cookies->get($key, $default)
                : $this->getRequest()->cookies->all();
    }

    public function file($key = null, $default = null)
    {
        return ($key)
                ? $this->getRequest()->files->get($key, $default)
                : $this->getRequest()->files->all();
    }

    public function server($key = null, $default = null)
    {
        return ($key)
                ? $this->getRequest()->server->get($key, $default)
                : $this->getRequest()->server->all();
    }

    public function header($key = null, $default = null)
    {
        return ($key)
                ? $this->getRequest()->headers->get($key, $default)
                : $this->getRequest()->headers->all();
    }

    public function content()
    {
        return $this->getRequest()->getContent();
    }
}

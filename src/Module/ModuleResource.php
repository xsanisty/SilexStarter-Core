<?php

namespace SilexStarter\Module;

class ModuleResource
{
    protected $resources;
    protected static $resourceFields = [
        /** Routes configuration file */
        'routes',

        /** Middleware configuration file */
        'middlewares',

        /** Base controllers directory */
        'controllers',

        /** Base commands directory */
        'commands',

        /** Base views directory */
        'views',

        /** Array of registered services */
        'services',

        /** Base config directory */
        'config',

        /** Base public assets directory */
        'assets',

        /** Base migrations directory */
        'migrations',

        /** Intaller class used to install module */
        'installer',

        /** Base translations directory */
        'translations'
    ];

    public function __construct(array $resources)
    {
        foreach (static::$resourceFields as $field) {
            $this->resources[$field] = isset($resources[$field]) ? $resources[$field] : null;
        }
    }

    /**
     * Resource getter, so it possible to access $object->resources.
     *
     * @param string $resource The resource name
     *
     * @return mixed
     */
    public function __get($resource)
    {
        if (in_array($resource, static::$resourceFields)) {
            return $this->resources[$resource];
        }

        return;
    }

    /**
     * Resource setter, so it possible to assign value to resources using $object->resources = value.
     *
     * @param string $resource the resource name
     * @param mixeed $value    the resource value
     */
    public function __set($resource, $value)
    {
        if (in_array($resource, static::$resourceFields)) {
            $this->resources[$resource] = $value;
        }
    }

    /**
     * Check if resources field exists
     * @param  string  $name
     * @return boolean
     */
    public function __isset($name)
    {
        return array_key_exists($name, $this->resources);
    }
}

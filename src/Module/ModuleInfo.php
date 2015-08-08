<?php

namespace SilexStarter\Module;

class ModuleInfo
{
    protected $info;
    protected $infoFields = [
        'author_name',
        'author_email',
        'repository',
        'website',
        'name',
        'description',
        'version',
    ];

    public function __construct(array $info)
    {
        foreach ($this->infoFields as $field) {
            $this->info[$field] = isset($info[$field]) ? $info[$field] : null;
        }
    }

    /**
     * info getter, so it possible to access $object->info.
     *
     * @param string $info
     *
     * @return mixed
     */
    public function __get($info)
    {
        if (in_array($info, $this->infoFields)) {
            return $this->info[$info];
        }

        return;
    }

    /**
     * info setter, so it possible to assign value to info using $object->info = value.
     *
     * @param string $info
     * @param mixeed $value
     */
    public function __set($info, $value)
    {
        if (in_array($info, $this->infoFields)) {
            $this->info[$info] = $value;
        }
    }

    /**
     * Check if info field exists
     * @param  string  $name
     * @return boolean
     */
    public function __isset($name)
    {
        return array_key_exists($name, $this->info);
    }
}

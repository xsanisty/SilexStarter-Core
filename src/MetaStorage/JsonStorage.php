<?php

namespace SilexStarter\MetaStorage;

use LogicException;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;

class JsonStorage
{
    protected $config;
    protected $file;

    protected $loaded;

    public function __construct(Filesystem $file, $config)
    {
        $this->file     = $file;
        $this->config   = $config;
        $this->loaded   = [];

        if (!isset($config['path'])) {
            throw new LogicException("JsonStorage need default path to be configured");
        }
    }

    /**
     * [load description]
     * @param  [type] $file [description]
     * @return [type]       [description]
     */
    public function load($file)
    {
        if (isset($this->loaded[$file])) {
            return $this->loaded[$file];
        }

        $filePath = $this->config['path'] . $file . '.json';

        if ($this->file->exists($filePath)) {
            $this->file->touch($filePath);
        }

        $json = file_get_contents($filePath);

        $this->loaded[$file] = json_decode($json, true);

        return $this->loaded[$file];
    }

    /**
     * Flush content into json file
     * @param  string $file the file name
     */
    public function flush($file = null)
    {
        if (!$file) {
            foreach ($this->loaded as $file => $content) {
                $jsonString = json_encode($content);
                $jsonFile   = $this->config['path'] . $file . '.json';

                $this->file->dumpFile($jsonFile, $jsonString);
            }
        } else {
            $jsonString = json_encode($this->loaded[$file]);
            $jsonFile   = $this->config['path'] . $file . '.json';

            $this->file->dumpFile($jsonFile, $jsonString);
        }
    }
}

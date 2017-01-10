<?php

namespace SilexStarter\Storage;

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

    public function create($file, $content)
    {
        $handler = fopen($file, 'w');
        fwrite($handler, json_encode($content, JSON_PRETTY_PRINT));

        fclose($handler);
    }

    /**
     * Load info from specified file.
     *
     * @param  string $file     The storage file name.
     * @param  bool   $force    Force reload of the json content.
     *
     * @return mixed            The array translated from json content.
     */
    public function load($file, $force = false)
    {
        if (isset($this->loaded[$file]) && !$force) {
            return $this->loaded[$file];
        }

        $filePath = $this->config['path'] . $file . '.json';

        if (!$this->file->exists($filePath)) {
            $this->create($filePath, null);
        }

        $json = file_get_contents($filePath);

        $this->loaded[$file] = json_decode($json, true);

        return $this->loaded[$file];
    }

    /**
     * Persist content into json file.
     *
     * @param  string $file     The file name
     */
    public function persist($file, $content = null)
    {
        if (!$file) {
            foreach ($this->loaded as $file => $content) {
                $jsonString = json_encode($content, JSON_PRETTY_PRINT);
                $jsonFile   = $this->config['path'] . $file . '.json';

                $this->file->dumpFile($jsonFile, $jsonString);
            }
        } else {
            $jsonString = json_encode($content, JSON_PRETTY_PRINT);
            $jsonFile   = $this->config['path'] . $file . '.json';

            $this->file->dumpFile($jsonFile, $jsonString);
        }
    }
}

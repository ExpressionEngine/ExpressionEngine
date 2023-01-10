<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Service\Config;

/**
 * Config Directory
 */
class Directory
{
    /**
     * @var Directory path
     */
    protected $dirname;

    /**
     * @var Cache of file objects in this directory
     */
    protected $cache = array();

    /**
     * Create a new Config\Directory object
     *
     * @param string $dirname Path to the directory, can be relative
     */
    public function __construct($dirname)
    {
        $this->dirname = realpath($dirname);
    }

    /**
     * Get a config item from this directory
     *
     * @param String $item Config item name
     * @param Mixed  $default The value to return if $path can not be found
     * @param Mixed $default Default value to use if item doesn't exist
     */
    public function get($item, $default = null)
    {
        list($file, $item) = $this->getFileFor($item);

        return $file->get($item, $default);
    }

    /**
     * Check if this directory contains a given config file
     *
     * @return bool TRUE if it has the file, FALSE if not
     */
    public function hasFile($filename)
    {
        return file_exists($this->getPath($filename));
    }

    /**
     * Returns a Config\File class representing the config file
     *
     * @param  string $filename name of the file
     * @return File             Config\File object
     * @throws Exception If no config file is found
     */
    public function getFile($filename = 'config')
    {
        if (array_key_exists($filename, $this->cache)) {
            return $this->cache[$filename];
        }

        if (! $this->hasFile($filename)) {
            throw new \Exception('No config file was found.');
        }

        $obj = new File($this->getPath($filename));

        return $this->cache[$filename] = $obj;
    }

    /**
     * Turn a filename into a full path
     *
     * @param String $filename The config file name without an extension
     * @return String The full path to the config file
     */
    protected function getPath($filename)
    {
        return realpath($this->dirname . '/' . $filename . '.php');
    }

    /**
     * Given an item, figure out what file it resides in
     *
     * A config item with a period, such as: `lang.primary` can reside
     * in one of two places:
     *
     * In the main config file as `$config['lang']['primary']`
     * or
     * In a `lang` config file as `$config['primary']`
     *
     * The nice thing about this split is that it means that items can be
     * moved to a separate config file when an array gets too unwieldy,
     * without requiring large refactorings in the calling code.
     *
     * @param String $item The config item to fetch
     * @return String $filename A config file name (no path or ".php")
     */
    protected function getFileFor($item)
    {
        $default = array($this->getFile(), $item);

        if (strpos($item, '.') === false || $this->getFile()->has($item)) {
            return $default;
        }

        list($filename, $item) = explode('.', $item, 2);

        if ($this->hasFile($filename)) {
            $file = $this->getFile($filename);

            if ($file->has($item)) {
                return array($file, $item);
            }
        }

        return $default;
    }
}

// EOF

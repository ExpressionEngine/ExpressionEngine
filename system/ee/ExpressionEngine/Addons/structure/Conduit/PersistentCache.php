<?php

namespace ExpressionEngine\Structure\Conduit;

use ExpressionEngine\Structure\Conduit\StaticCache;

// Based on CI DB caching function
class PersistentCache
{
    private static $cache_path = PATH_CACHE;
    private static $module_cache_path = PATH_CACHE . 'structure/';

    /**
     * Retrieve the data stored in the cache.
     * @param  string|array  $key  Either a string or array of items
     * @return array|object        Unserialized data from cache
     */
    public static function get($key)
    {
        // Check to see if we have this stored in our StaticCache first.
        $static_cache_data = StaticCache::get($key, true);

        if (!empty($static_cache_data)) {
            return $static_cache_data;
        }

        if (! self::checkPath()) {
            return false;
        }

        ee()->load->helper('file');

        $key = self::createKey($key);

        if (false === ($cache_data = read_file(self::$module_cache_path . $key))) {
            ee()->load->library('logger');
            ee()->logger->developer('Structure: Write Cache enabled but could not find cache for: ' . self::$module_cache_path . $key);

            return false;
        }

        // Store the cache data in the StaticCache so we don't have
        // to read it off the disk again during this execution loop.
        StaticCache::set($key, $cache_data, true);

        $cache_data = self::serveCacheData($cache_data);

        return $cache_data;
    }

    /**
    * Set the data to be stored in the cache.
    * @param  string|array  $key  Either a string or array of items
    * @return boolean             Indicates whether the data was able to be saved
    */
    public static function set($key, $value)
    {
        if (!self::checkPath()) {
            return false;
        }

        ee()->load->helper('file');

        $key = self::createKey($key);
        $cache_data = self::prepCacheData($value);

        if (write_file(self::$module_cache_path . $key, $cache_data) === false) {
            ee()->load->library('logger');
            ee()->logger->developer('Structure: Write Cache enabled but could not write cache file: ' . self::$module_cache_path . $key);

            return false;
        }

        // Try to change the folder permissions to EE's default write mode.
        @chmod(self::$module_cache_path . $key, FILE_WRITE_MODE);

        // Store the cache data in the StaticCache so we don't have
        // to read it off the disk again during this execution loop.
        // We use $value here because we don't need it in file format.
        StaticCache::set($key, $value, true);

        return true;
    }

    /**
     * Delete the cached file for a specific key for this module.
     * @return bool  Indicates if the key was successfully deleted.
     */
    public static function delete($key)
    {
        if (!self::checkPath()) {
            return false;
        }

        $key = self::createKey($key);

        if (is_file(self::$module_cache_path . $key)) {
            @unlink(self::$module_cache_path . $key);
        }

        // Remove any StaticCache we have for this key as well.
        StaticCache::delete($key, true);

        return true;
    }

    /**
     * Find out if a specific key exists in the file cache.
     * @param  string|array  $key            Either a string or an array if items
     * @param  boolean       $key_processed  Whether the key has already be SHA1'd
     * @return boolean                       Whether the key exists in the cache.
     */
    public static function has($key, $key_processed = false)
    {
        $key = self::createKey($key);

        return is_file(self::$module_cache_path . $key);
    }

    /**
     * Delete all the cached files that this module may have created.
     * @return bool  Indicates if the cache path existed and we attempted to clear it.
     */
    public static function clear()
    {
        if (!self::checkPath()) {
            return false;
        }

        foreach (glob(self::$module_cache_path . '*') as $key) {
            if (is_file($key)) {
                @unlink($key);
            }

            // Remove any StaticCache we have for this key as well.
            // We don't want to clear the StaticCache globally as
            // we may only be looking to clear the persistent cache
            // and leave the other things in the StaticCache alone.
            StaticCache::delete(pathinfo($key, PATHINFO_FILENAME), true);
        }

        return true;
    }

    /************************************************************
    PRIVATE FUNCTIONS
    ************************************************************/

    /**
     * Create a SHA1 key from either a string or an array of pieces to
     * ensure that the key is unique.
     * @param  string|array  $key  Either a string or an array of items
     * @return string              SHA1 string
     */
    private static function createKey($key)
    {
        if (!is_array($key)) {
            $key = array($key);
        }

        return sha1(implode(':', array_filter($key)));
    }

    /**
     * Prep the data to be stored in the cache. Making this it's own
     * method so we can add to it if needed.
     * @param  array|object  $value Data to store in cache
     * @return string        Serialized string of values
     */
    private static function prepCacheData($value)
    {
        return serialize($value);
    }

    /**
     * Reverse the prep we did for the data stored in the cache.
     * @param  array|object  $value Data to store in cache
     * @return string        Serialized string of values
     */
    private static function serveCacheData($value)
    {
        return unserialize($value);
    }

    /**
     * Make sure the EE global cache folder and our module-specific folders
     * exist and are writable.
     * @return bool  Whether the folders exist and are writable
     */
    private static function checkPath()
    {
        // Add a trailing slash to the path if needed
        self::$cache_path = preg_replace("/(.+?)\/*$/", "\\1/", self::$cache_path);

        // If the generic cache folder doesn't exist, try to create it.
        if (! @is_dir(self::$cache_path)) {
            if (! @mkdir(self::$cache_path, DIR_WRITE_MODE)) {
                ee()->load->library('logger');
                ee()->logger->developer('Structure: Write Cache enabled but could not create cache folder: ' . self::$cache_path);

                return false;
            }

            // Try to change the folder permissions to EE's default write mode.
            @chmod(self::$cache_path, DIR_WRITE_MODE);
        }

        // Make sure the generic cache folder is writable.
        if (!is_writable(self::$cache_path)) {
            ee()->load->library('logger');
            ee()->logger->developer('Structure: Write Cache enabled but cache path not writable: ' . self::$cache_path);

            return false;
        }

        // If the module-specific cache folder doesn't exist, try to create it.
        if (!@is_dir(self::$module_cache_path)) {
            if (!@mkdir(self::$module_cache_path, DIR_WRITE_MODE)) {
                ee()->load->library('logger');
                ee()->logger->developer('Structure: Write Cache enabled but could not create module cache folder: ' . self::$module_cache_path);

                return false;
            }

            // Try to change the folder permissions to EE's default write mode.
            @chmod(self::$module_cache_path, DIR_WRITE_MODE);
        }

        // Make sure the module-specific cache folder is writable.
        if (!is_writable(self::$module_cache_path)) {
            // If the path is wrong we'll turn off caching
            ee()->load->library('logger');
            ee()->logger->developer('Structure: Write Cache enabled but module cache path not writable: ' . self::$module_cache_path);

            return false;
        }

        return true;
    }
}

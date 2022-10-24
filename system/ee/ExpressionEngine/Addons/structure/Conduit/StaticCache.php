<?php

namespace ExpressionEngine\Structure\Conduit;

class StaticCache
{
    public static $cache_array = array();

    /**
     * Retrieve the data stored in the cache.
     * @param  string|array  $key  Either a string or array of items
     * @return array|object        Unserialized data from cache
     */
    public static function get($key, $key_processed = false)
    {
        $key = self::createKey($key, $key_processed);

        return empty(self::$cache_array[$key]) ? false : self::$cache_array[$key];
    }

    /**
     * DEPRECATED - Alias of `put`.
     * @param  string|array  $key            Either a string or array of items
     * @param  array|object  $value          The array or object to store in the cache.
     * @param  boolean       $key_processed  If true, bypasses `create_key`. Used when key has already be SHA1'd.
     * @return boolean                       Indicates whether the data was able to be saved
     */
    public static function set($key, $value, $key_processed = false)
    {
        self::put($key, $value, $key_processed);
    }

    /**
     * Set the data to be stored in the cache.
     * @param  string|array  $key            Either a string or array of items
     * @param  array|object  $value          The array or object to store in the cache.
     * @param  boolean       $key_processed  If true, bypasses `create_key`. Used when key has already be SHA1'd.
     * @return boolean                       Indicates whether the data was able to be saved
     */
    public static function put($key, $value, $key_processed = false)
    {
        self::$cache_array[self::createKey($key, $key_processed)] = $value;
    }

    /**
     * Delete a specific key from the static cache.
     * @param  string|array  $key            Either a string or an array if items
     * @param  boolean       $key_processed  If true, bypasses `create_key`. Used when key has already be SHA1'd.
     * @return boolean                       Whether the function succeeded.
     */
    public static function delete($key, $key_processed = false)
    {
        unset(self::$cache_array[self::createKey($key, $key_processed)]);

        return isset(self::$cache_array[self::createKey($key, $key_processed)]);
    }

    /**
     * Find out if a specific key exists in the static cache.
     * @param  string|array  $key            Either a string or an array if items
     * @param  boolean       $key_processed  If true, bypasses `create_key`. Used when key has already be SHA1'd.
     * @return boolean                       Whether the key exists in the cache.
     */
    public static function has($key, $key_processed = false)
    {
        return isset(self::$cache_array[self::createKey($key, $key_processed)]);
    }

    /**
     * Return all of the cached items
     * @return array  An array of all the cached items
     */
    public static function all()
    {
        return self::$cache_array;
    }

    /**
     * Clear the entire cache.
     * @return boolean  True, always True.
     */
    public static function clear()
    {
        self::$cache_array = array();

        return true;
    }

    /**
     * Create a SHA1 key from either a string or an array of pieces to
     * ensure that the key is unique.
     * @param  string|array  $key            Either a string or an array of items
     * @param  boolean       $key_processed  If true, bypasses `create_key`. Used when key has already be SHA1'd.
     * @return string                        SHA1 string
     */
    private static function createKey($key, $key_processed = false)
    {
        if ($key_processed !== false) {
            return $key;
        }

        if (!is_array($key)) {
            $key = array($key);
        }

        return sha1(implode(':', array_filter($key)));
    }
}

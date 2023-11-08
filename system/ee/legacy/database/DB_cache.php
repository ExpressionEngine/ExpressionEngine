<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

/**
 * Database Cache Class
 */
class CI_DB_Cache
{
    // Namespace cache items will be stored in
    private $_cache_namespace = 'db_cache';

    /**
     * Retrieve a cached query
     *
     * @param	string	$sql	SQL as key name to retrieve
     * @return	mixed	Object from cache
     */
    public function read($sql)
    {
        return ee()->cache->get('/' . $this->_cache_namespace . '/' . $this->_prefixed_key($sql));
    }

    /**
     * Write a query to a cache file
     *
     * @param	string	$sql	SQL to use as a key name
     * @param	string	$object	Object to store in cache
     * @return	bool	Success or failure
     */
    public function write($sql, $object)
    {
        return ee()->cache->save(
            '/' . $this->_cache_namespace . '/' . $this->_prefixed_key($sql),
            $object,
            0 // TTL
        );
    }

    /**
     * Delete all existing cache files
     *
     * @return	bool	Success or failure
     */
    public function delete_all()
    {
        return ee()->cache->clear_namespace($this->_cache_namespace);
    }

    /**
     * Takes a cache key and gets it ready for storage or retrieval, which
     * includes prefixing the key name with the two URI segments of the
     * request and MD5ing the key name to shorten it since it's likely a
     * long SQL query
     *
     * @param	string	$key	Cache key name
     * @return	string	Key prefixed with segments
     */
    private function _prefixed_key($key)
    {
        $segment_one = (ee()->uri->segment(1) == false)
            ? 'default' : ee()->uri->segment(1);

        $segment_two = (ee()->uri->segment(2) == false)
            ? 'index' : ee()->uri->segment(2);

        return $segment_one . '+' . $segment_two . '+' . md5($key);
    }
}

// EOF

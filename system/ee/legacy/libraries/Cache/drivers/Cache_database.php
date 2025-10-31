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
 * Database Caching
 */
class EE_Cache_database extends CI_Driver
{
    /**
     * Cache table name
     *
     * @var string
     */
    protected $_cache_table = 'cache';

    /**
     * Initialize database-based cache
     *
     * @return	void
     */
    public function __construct()
    {
        ee()->load->library('localize');
        ee()->load->dbforge();
    }

    /**
     * Look for a value in the cache. If it exists, return the data
     * if not, return FALSE
     *
     * @param	string	$key 	Key name
     * @param	const	$scope	Cache::LOCAL_SCOPE or Cache::GLOBAL_SCOPE
     *		 for local or global scoping of the cache item
     * @return	mixed	Value matching $key or FALSE on failure
     */
    public function get($key, $scope = Cache::LOCAL_SCOPE)
    {
        $key = $this->_namespaced_key($key, $scope);

        ee()->db->select('data, ttl, created_at');
        ee()->db->from($this->_cache_table);
        ee()->db->where('cache_key', $key);
        $query = ee()->db->get();

        if ($query->num_rows() == 0) {
            return false;
        }

        $row = $query->row();
        $data = unserialize($row->data);

        // Check if cache has expired
        if ($row->ttl > 0 && ee()->localize->now > $row->created_at + $row->ttl) {
            $this->delete($key, $scope);
            return false;
        }

        return $data;
    }

    /**
     * Save value to cache
     *
     * @param	string	$key		Key name
     * @param	mixed	$data		Data to store
     * @param	int		$ttl = 60	Cache TTL (in seconds)
     * @param	const	$scope		Cache::LOCAL_SCOPE or Cache::GLOBAL_SCOPE
     *		 for local or global scoping of the cache item
     * @return	bool	TRUE on success, FALSE on failure
     */
    public function save($key, $data, $ttl = 60, $scope = Cache::LOCAL_SCOPE)
    {
        $key = $this->_namespaced_key($key, $scope);
        $serialized_data = serialize($data);
        $created_at = ee()->localize->now;

        // Check if key already exists
        ee()->db->select('cache_key');
        ee()->db->from($this->_cache_table);
        ee()->db->where('cache_key', $key);
        $query = ee()->db->get();

        if ($query->num_rows() > 0) {
            // Update existing record
            ee()->db->where('cache_key', $key);
            return ee()->db->update($this->_cache_table, array(
                'data' => $serialized_data,
                'ttl' => $ttl,
                'created_at' => $created_at
            ));
        } else {
            // Insert new record
            return ee()->db->insert($this->_cache_table, array(
                'cache_key' => $key,
                'data' => $serialized_data,
                'ttl' => $ttl,
                'created_at' => $created_at
            ));
        }
    }

    /**
     * Delete from cache
     *
     * To clear a particular namespace, pass in the namespace with a trailing
     * slash like so:
     *
     * ee()->cache->delete('/namespace_name/');
     *
     * @param	string	$key	Key name
     * @param	const	$scope	Cache::LOCAL_SCOPE or Cache::GLOBAL_SCOPE
     *		 for local or global scoping of the cache item
     * @return	bool	TRUE on success, FALSE on failure
     */
    public function delete($key, $scope = Cache::LOCAL_SCOPE)
    {
        // If we are deleting contents of a namespace
        if (strrpos($key, Cache::NAMESPACE_SEPARATOR, strlen($key) - 1) !== false) {
            $namespace = $this->_namespaced_key($key, $scope);
            ee()->db->like('cache_key', $namespace, 'right');
            return ee()->db->delete($this->_cache_table);
        }

        // Delete specific key
        $key = $this->_namespaced_key($key, $scope);
        ee()->db->where('cache_key', $key);
        return ee()->db->delete($this->_cache_table);
    }

    /**
     * Clean the cache
     *
     * @param	const	$scope	Cache::LOCAL_SCOPE or Cache::GLOBAL_SCOPE
     *		 for local or global scoping of the cache item
     * @return	bool	TRUE on success, FALSE on failure
     */
    public function clean($scope = Cache::LOCAL_SCOPE)
    {
        $namespace = $this->_namespaced_key('', $scope);

        if ($scope == Cache::LOCAL_SCOPE) {
            // For local scope, delete all keys that start with the site prefix
            ee()->db->like('cache_key', $namespace, 'right');
        } else {
            // For global scope, delete all keys that start with the global prefix
            ee()->db->like('cache_key', $namespace, 'right');
        }

        return ee()->db->delete($this->_cache_table);
    }

    /**
     * Cache Info
     *
     * @return	mixed	array containing cache info on success OR FALSE on failure
     */
    public function cache_info()
    {
        ee()->db->select('COUNT(*) as total_items, SUM(LENGTH(data)) as total_size');
        ee()->db->from($this->_cache_table);
        $query = ee()->db->get();

        if ($query->num_rows() > 0) {
            $row = $query->row();
            return array(
                'total_items' => $row->total_items,
                'total_size' => $row->total_size
            );
        }

        return false;
    }

    /**
     * Get Cache Metadata
     *
     * @param	string	$key	Key to get cache metadata on
     * @param	const	$scope	Cache::LOCAL_SCOPE or Cache::GLOBAL_SCOPE
     *		 for local or global scoping of the cache item
     * @return	mixed	cache item metadata
     */
    public function get_metadata($key, $scope = Cache::LOCAL_SCOPE)
    {
        $key = $this->_namespaced_key($key, $scope);

        ee()->db->select('data, ttl, created_at');
        ee()->db->from($this->_cache_table);
        ee()->db->where('cache_key', $key);
        $query = ee()->db->get();

        if ($query->num_rows() == 0) {
            return false;
        }

        $row = $query->row();
        $data = unserialize($row->data);

        return array(
            'expire' => $row->created_at + $row->ttl,
            'mtime' => $row->created_at,
            'data' => $data
        );
    }

    /**
     * Is this caching driver supported on the system?
     * Checks if cache table exists and creates it if not.
     *
     * @return	bool	TRUE if supported, FALSE otherwise
     */
    public function is_supported()
    {
        // Check if database connection is available
        if (!isset(ee()->db) || !is_object(ee()->db)) {
            return false;
        }

        // Check if cache table exists
        if (!$this->_table_exists()) {
            // Try to create the cache table
            if (!$this->_create_cache_table()) {
                return false;
            }
        }

        return true;
    }

    /**
     * Check if the cache table exists
     *
     * @return	bool	TRUE if table exists, FALSE otherwise
     */
    protected function _table_exists()
    {
        $tables = ee()->db->list_tables();
        return in_array(ee()->db->dbprefix . $this->_cache_table, $tables);
    }

    /**
     * Create the cache table
     *
     * @return	bool	TRUE on success, FALSE on failure
     */
    protected function _create_cache_table()
    {
        $fields = array(
            'cache_key' => array(
                'type' => 'varchar',
                'constraint' => '255',
                'null' => false
            ),
            'data' => array(
                'type' => 'longtext',
                'null' => false
            ),
            'ttl' => array(
                'type' => 'int',
                'constraint' => '11',
                'unsigned' => true,
                'default' => 0
            ),
            'created_at' => array(
                'type' => 'int',
                'constraint' => '11',
                'unsigned' => true,
                'null' => false
            )
        );

        ee()->dbforge->add_field($fields);
        ee()->dbforge->add_key('cache_key', true);
        ee()->dbforge->add_key('created_at');

        // Create the table
        if (ee()->dbforge->create_table($this->_cache_table, true)) {
            return true;
        }

        return false;
    }

    /**
     * If a namespace was specified, prefixes the key with it
     *
     * For the database driver, namespaces will be prefixed to the key
     *
     * @param	string	$key	Key name
     * @param	const	$scope	Cache::LOCAL_SCOPE or Cache::GLOBAL_SCOPE
     *		 for local or global scoping of the cache item
     * @return	string	Key prefixed with namespace
     */
    protected function _namespaced_key($key, $scope = Cache::LOCAL_SCOPE)
    {
        // Make sure the key doesn't begin or end with a namespace separator
        $key = trim($key, Cache::NAMESPACE_SEPARATOR);

        // Replace namespace separators with underscores for database storage
        $key = str_replace(Cache::NAMESPACE_SEPARATOR, '_', $key);

        // For locally-cached items, separate by site name
        if ($scope == Cache::LOCAL_SCOPE) {
            $site_prefix = (!empty(ee()->config->item('site_short_name')) ? ee()->config->item('site_short_name') . '_' : '');
            $key = $site_prefix . $key;
        } else {
            // For globally-cached items, use a hash of the installation
            $global_prefix = md5('__GLOBAL__' . APPPATH) . '_';
            $key = $global_prefix . $key;
        }

        return $key;
    }
}

// EOF

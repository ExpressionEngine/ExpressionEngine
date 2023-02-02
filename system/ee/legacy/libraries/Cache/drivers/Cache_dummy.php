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
 * Dummy Caching
 */
class EE_Cache_dummy extends CI_Driver
{
    /**
     * Get
     *
     * Since this is the dummy class, it's always going to return FALSE.
     *
     * @param	string
     * @return	bool	FALSE
     */
    public function get($id)
    {
        return false;
    }

    /**
     * Cache Save
     *
     * @param	string	Unique Key
     * @param	mixed	Data to store
     * @param	int	Length of time (in seconds) to cache the data
     * @return	bool	TRUE, Simulating success
     */
    public function save($id, $data, $ttl = 60)
    {
        return true;
    }

    /**
     * Delete from Cache
     *
     * @param	mixed	unique identifier of the item in the cache
     * @return	bool	TRUE, simulating success
     */
    public function delete($id)
    {
        return true;
    }

    /**
     * Clean the cache
     *
     * @return	bool	TRUE, simulating success
     */
    public function clean()
    {
        return true;
    }

    /**
     * Cache Info
     *
     * @return	bool	FALSE
     */
    public function cache_info()
    {
        return false;
    }

    /**
     * Get Cache Metadata
     *
     * @param	mixed	key to get cache metadata on
     * @return	bool	FALSE
     */
    public function get_metadata($id)
    {
        return false;
    }

    /**
     * Is this caching driver supported on the system?
     * Of course this one is.
     *
     * @return	bool	TRUE
     */
    public function is_supported()
    {
        return true;
    }
}

// EOF

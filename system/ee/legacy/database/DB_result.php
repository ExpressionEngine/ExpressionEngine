<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2021, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

/**
 * Database Result Class
 *
 * This is the platform-independent result class.
 * This class will not be called directly. Rather, the adapter
 * class for the specific database will extend and instantiate it.
 */
class CI_DB_result
{
    public $result_array = array();
    public $result_object = array();
    public $current_row = 0;
    public $num_rows = 0;
    public $row_data = null;

    protected $pdo_statement;

    public function __construct($statement = null)
    {
        $this->pdo_statement = $statement;
    }

    /**
     * Query result.  Acts as a wrapper function for the following functions.
     *
     * @access	public
     * @param	string	can be "object" or "array"
     * @return	mixed	either a result object or array
     */
    public function result($type = 'object')
    {
        return ($type == 'object') ? $this->result_object() : $this->result_array();
    }

    /**
     * Query result.  "object" version.
     *
     * @access	public
     * @return	object
     */
    public function result_object()
    {
        if (count($this->result_object) > 0) {
            return $this->result_object;
        }

        // In the event that query caching is on the pdo_statement variable
        // will return NULL since there isn't a valid SQL resource so
        // we'll simply return an empty array.
        if ($this->pdo_statement === false or $this->num_rows() == 0) {
            return array();
        }

        //$this->_data_seek(0);
        while ($row = $this->_fetch_object()) {
            $this->result_object[] = $row;
        }

        return $this->result_object;
    }

    /**
     * Query result.  "array" version.
     *
     * @access	public
     * @return	array
     */
    public function result_array()
    {
        $result = $this->result_object();

        foreach ($result as &$row) {
            $row = (array) $row;
        }

        return $result;
    }

    /**
     * Query result.  Acts as a wrapper function for the following functions.
     *
     * @access	public
     * @param	string
     * @param	string	can be "object" or "array"
     * @return	mixed	either a result object or array
     */
    public function row($n = 0, $type = 'object')
    {
        if (! is_numeric($n)) {
            // We cache the row data for subsequent uses
            if (! is_array($this->row_data)) {
                $this->row_data = $this->row_array(0);
            }

            // array_key_exists() instead of isset() to allow for MySQL NULL values
            if (array_key_exists($n, $this->row_data)) {
                return $this->row_data[$n];
            }
            // reset the $n variable if the result was not achieved
            $n = 0;
        }

        return ($type == 'object') ? $this->row_object($n) : $this->row_array($n);
    }

    /**
     * Assigns an item into a particular column slot
     *
     * @access	public
     * @return	object
     */
    public function set_row($key, $value = null)
    {
        // We cache the row data for subsequent uses
        if (! is_array($this->row_data)) {
            $this->row_data = $this->row_array(0);
        }

        if (is_array($key)) {
            foreach ($key as $k => $v) {
                $this->row_data[$k] = $v;
            }

            return;
        }

        if ($key != '' and ! is_null($value)) {
            $this->row_data[$key] = $value;
        }
    }

    /**
     * Returns a single result row - object version
     *
     * @access	public
     * @return	object
     */
    public function row_object($n = 0)
    {
        $result = $this->result_object();

        if (count($result) == 0) {
            return $result;
        }

        if ($n != $this->current_row and isset($result[$n])) {
            $this->current_row = $n;
        }

        return $result[$this->current_row];
    }

    /**
     * Returns a single result row - array version
     *
     * @access	public
     * @return	array
     */
    public function row_array($n = 0)
    {
        return (array) $this->row_object($n);
    }

    /**
     * Returns the "first" row
     *
     * @access	public
     * @return	object
     */
    public function first_row($type = 'object')
    {
        $result = $this->result($type);

        if (count($result) == 0) {
            return $result;
        }

        return $result[0];
    }

    /**
     * Returns the "last" row
     *
     * @access	public
     * @return	object
     */
    public function last_row($type = 'object')
    {
        $result = $this->result($type);

        if (count($result) == 0) {
            return $result;
        }

        return $result[count($result) - 1];
    }

    /**
     * Returns the "next" row
     *
     * @access	public
     * @return	object
     */
    public function next_row($type = 'object')
    {
        $result = $this->result($type);

        if (count($result) == 0) {
            return $result;
        }

        if (isset($result[$this->current_row + 1])) {
            ++$this->current_row;
        }

        return $result[$this->current_row];
    }

    /**
     * Returns the "previous" row
     *
     * @access	public
     * @return	object
     */
    public function previous_row($type = 'object')
    {
        $result = $this->result($type);

        if (count($result) == 0) {
            return $result;
        }

        if (isset($result[$this->current_row - 1])) {
            --$this->current_row;
        }

        return $result[$this->current_row];
    }

    /**
     * The following functions are normally overloaded by the identically named
     * methods in the platform-specific driver -- except when query caching
     * is used.  When caching is enabled we do not load the other driver.
     * These functions are primarily here to prevent undefined function errors
     * when a cached result object is in use.  They are not otherwise fully
     * operational due to the unavailability of the database resource IDs with
     * cached results.
     */
    public function num_rows()
    {
        return $this->num_rows;
    }
    public function num_fields()
    {
        return 0;
    }
    public function list_fields()
    {
        return array();
    }
    public function field_data()
    {
        return array();
    }
    public function free_result()
    {
        return true;
    }
    public function _data_seek()
    {
        return true;
    }
    public function _fetch_assoc()
    {
        return array();
    }
    public function _fetch_object()
    {
        return array();
    }
}
// END DB_result class

// EOF

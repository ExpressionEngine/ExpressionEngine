<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

if (! defined('BASEPATH')) {
    exit('No direct script access allowed');
}

// Load CI model if it doesn't exist
if (! class_exists('CI_model')) {
    load_class('Model', 'core');
}

/**
 * Pro Search Model class
 */
abstract class Pro_search_model extends CI_Model
{
    // --------------------------------------------------------------------
    // PROPERTIES
    // --------------------------------------------------------------------

    /**
     * Name of table
     *
     * @access      private
     * @var         string
     */
    private $_table;

    /**
     * Name of primary key
     *
     * @access      private
     * @var         string
     */
    private $_pk;

    /**
     * Other attributes of the table
     *
     * @access      private
     * @var         array
     */
    private $_attributes = array();

    /**
     * Site id shortcut
     *
     * @access      protected
     * @var         int
     */
    protected $site_id;

    // --------------------------------------------------------------------
    // METHODS
    // --------------------------------------------------------------------

    /**
     * PHP5 Constructor
     *
     * @return     void
     */
    public function __construct()
    {
        // Call parent constructor
        parent::__construct();

        // Set site id shortcut
        $this->site_id = ee()->config->item('site_id');
    }

    // --------------------------------------------------------------------

    /**
     * Sets table, PK and attributes
     *
     * @access      protected
     * @param       string    Table name
     * @param       string    Primary Key name
     * @param       array     Attributes
     * @return      void
     */
    protected function initialize($table, $pk, $attributes)
    {
        // Check table prefix
        $prefix = ee()->db->dbprefix;

        // Add prefix to table name if not there
        if (substr($table, 0, strlen($prefix)) != $prefix) {
            $table = $prefix . $table;
        }

        // Set the values
        $this->_table = $table;
        $this->_pk = $pk;
        $this->_attributes = $attributes;
    }

    // --------------------------------------------------------------------

    /**
     * Return table name
     *
     * @access      public
     * @return      string
     */
    public function table()
    {
        return $this->_table;
    }

    // --------------------------------------------------------------------

    /**
     * Return primary key
     *
     * @access      public
     * @return      string
     */
    public function pk()
    {
        return $this->_pk;
    }

    // --------------------------------------------------------------------

    /**
     * Return array of attributes, sans PK
     *
     * @access      public
     * @return      array
     */
    public function attributes()
    {
        return array_keys($this->_attributes);
    }

    // --------------------------------------------------------------------

    /**
     * Return one record by primary key or attribute
     *
     * @access      public
     * @param       int       id of the record to fetch
     * @param       string    attribute to check
     * @return      array
     */
    public function get_one($id, $attr = false)
    {
        if ($attr === false) {
            $attr = $this->_pk;
        }

        return ee()->db->where($attr, $id)->get($this->_table)->row_array();
    }

    // --------------------------------------------------------------------

    /**
     * Return multiple records
     *
     * @access      public
     * @return      array
     */
    public function get_all()
    {
        return ee()->db->get($this->_table)->result_array();
    }

    // --------------------------------------------------------------------

    /**
     * Return an empty row for data initialisation
     *
     * @access      public
     * @return      array
     */
    public function empty_row()
    {
        $row = array_merge(array($this->_pk), $this->attributes());
        $row = array_combine($row, array_fill(0, count($row), ''));

        return $row;
    }

    // --------------------------------------------------------------------

    /**
     * Insert record into DB
     *
     * @access      public
     * @param       array     data to insert
     * @return      int
     */
    public function insert($data = array())
    {
        if (empty($data)) {
            // loop through attributes to get posted data
            foreach ($this->attributes() as $attr) {
                if (($val = ee()->input->post($attr)) !== false) {
                    $data[$attr] = $val;
                }
            }
        }

        // Insert data and return inserted id
        ee()->db->insert($this->_table, $data);

        return ee()->db->insert_id();
    }

    // --------------------------------------------------------------------

    /**
     * Update record into DB
     *
     * @access      public
     * @param       mixed
     * @param       array     data to insert
     * @return      void
     */
    public function update($id, $data = array())
    {
        if (empty($data)) {
            // loop through attributes to get posted data
            foreach ($this->attributes() as $attr) {
                if (($val = ee()->input->post($attr)) !== false) {
                    $data[$attr] = $val;
                }
            }
        }

        $where = is_array($id) ? 'where_in' : 'where';

        // Update the table
        ee()->db->$where($this->_pk, $id);
        ee()->db->update($this->_table, $data);
    }

    // --------------------------------------------------------------------

    /**
     * Delete record
     *
     * @access      public
     * @param       array     data to insert
     * @param       string    optional attribute to delete records by
     * @return      void
     */
    public function delete($id, $attr = false)
    {
        if (! is_array($id)) {
            $id = array($id);
        }

        if ($attr === false) {
            $attr = $this->_pk;
        }

        ee()->db->where_in($attr, $id)->delete($this->_table);
    }

    // --------------------------------------------------------------------

    /**
     * Installs given table
     *
     * @access      public
     * @return      void
     */
    public function install()
    {
        // Begin composing SQL query
        $pk = $rows = array();

        // Add primary key -- is it an array?
        if (is_array($this->_pk)) {
            foreach ($this->_pk as $key => $val) {
                $pk[] = is_numeric($key) ? $val : $key;
                $rows[] = is_numeric($key)
                    ? $val . ' int(10) unsigned NOT NULL'
                    : $key . ' ' . $val;
            }
        } elseif (is_string($this->_pk)) {
            // or default string
            $pk[] = $this->_pk;
            $rows[] = $this->_pk . ' int(10) unsigned NOT NULL AUTO_INCREMENT';
        }

        // compose attributes
        foreach ($this->_attributes as $attr => $props) {
            $rows[] = $attr . ' ' . $props;
        }

        // Set PK
        if ($pk) {
            $rows[] = sprintf('PRIMARY KEY (%s)', implode(',', $pk));
        }

        // And character set
        $sql = sprintf(
            "CREATE TABLE IF NOT EXISTS %s (\n%s) CHARACTER SET utf8 COLLATE utf8_general_ci;",
            $this->_table,
            implode(",\n", $rows)
        );

        // Execute query
        ee()->db->query($sql);
    }

    // --------------------------------------------------------------------

    /**
     * Uninstalls given table
     *
     * @access      public
     * @return      void
     */
    public function uninstall()
    {
        ee()->db->query("DROP TABLE IF EXISTS {$this->_table}");
    }

    // --------------------------------------------------------------------
}
// End of file model.pro_search.php

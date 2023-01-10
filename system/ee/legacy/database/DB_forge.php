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
 * Database Utility Class
 */
class CI_DB_forge
{
    public $fields = array();
    public $keys = array();
    public $primary_keys = array();
    public $db_char_set = '';

    /**
     * Constructor
     *
     * Grabs the CI super object instance so we can access it.
     *
     */
    public function __construct()
    {
        $this->db = ee('db');
    }

    /**
     * Create database
     *
     * @access	public
     * @param	string $db_name The database name
     * @return	CI_DB_result The query result object
     */
    public function create_database($db_name)
    {
        $sql = $this->_create_database($db_name);

        if (is_bool($sql)) {
            return $sql;
        }

        return $this->db->query($sql);
    }

    /**
     * Drop database
     *
     * @access	public
     * @param	string $db_name The database name
     * @return	CI_DB_result The query result object
     */
    public function drop_database($db_name)
    {
        $sql = $this->_drop_database($db_name);

        if (is_bool($sql)) {
            return $sql;
        }

        return $this->db->query($sql);
    }

    /**
     * Add Key
     *
     * @access	public
     * @param	string $key The name of field to add a key for
     * @param	string $primary Set to TRUE to make this a PRIMARY KEY
     * @return	void
     */
    public function add_key($key, $primary = false)
    {
        if ($primary && is_array($key)) {
            foreach ($key as $one) {
                $this->add_key($one, $primary);
            }

            return;
        }

        if ($key == '') {
            show_error('Key information is required for that operation.');
        }

        if ($primary === true) {
            $this->primary_keys[] = $key;
        } else {
            $this->keys[] = $key;
        }
    }

    /**
     * Add Field
     *
     * @access	public
     * @param	array $field The field definition as a multidimensional
     *                       associative array
     * @return	void
     */
    public function add_field($field)
    {
        if (empty($field)) {
            show_error('Field information is required.');
        }

        if (is_string($field)) {
            if ($field == 'id') {
                $this->add_field(array(
                    'id' => array(
                        'type' => 'INT',
                        'constraint' => 9,
                        'auto_increment' => true
                    )
                ));
                $this->add_key('id', true);
            } else {
                if (strpos($field, ' ') === false) {
                    show_error('Field information is required for that operation.');
                }

                $this->fields[] = $field;
            }
        }

        if (is_array($field)) {
            $this->fields = array_merge($this->fields, $field);
        }
    }

    /**
     * Create Table
     *
     * @access	public
     * @param	string $table The name of the table to create
     * @param   boolean $if_not_exists Set to TRUE to create it if it doesn't
     *                                 exist
     * @return	CI_DB_result The query result object
     */
    public function create_table($table, $if_not_exists = false)
    {
        if (empty($table)) {
            show_error('A table name is required for that operation.');
        }

        if (count($this->fields) == 0) {
            show_error('Field information is required.');
        }

        $sql = $this->_create_table($this->db->dbprefix . $table, $this->fields, $this->primary_keys, $this->keys, $if_not_exists);

        // Update the db data cache
        if (! array_search($this->db->dbprefix . $table, $this->db->list_tables())) {
            $this->db->data_cache['table_names'][] = $this->db->dbprefix . $table;
        }

        $this->_reset();

        return $this->db->query($sql);
    }

    /**
     * Drop Table
     *
     * @access	public
     * @param	string $table_name The table to DROP
     * @return	CI_DB_result The query result object
     */
    public function drop_table($table_name)
    {
        $sql = $this->_drop_table($this->db->dbprefix . $table_name);

        if (is_bool($sql)) {
            return $sql;
        }

        return $this->db->query($sql);
    }

    /**
     * Rename Table
     *
     * @access	public
     * @param	string $table_name The old table name
     * @param	string $new_table_name The new table name
     * @return	CI_DB_result The query result object
     */
    public function rename_table($table_name, $new_table_name)
    {
        if ($table_name == '' or $new_table_name == '') {
            show_error('A table name is required for that operation.');
        }

        $sql = $this->_rename_table($this->db->dbprefix . $table_name, $this->db->dbprefix . $new_table_name);

        return $this->db->query($sql);
    }

    /**
     * Column Add
     *
     * @access	public
     * @param	string $table The table name to add the column to
     * @param	array $field The field definition as a multidimensional
     *                       associative array
     * @param	string $after_field The field that should come before this new
     *                              field
     * @return	bool
     */
    public function add_column($table, $field, $after_field = '')
    {
        if (empty($table)) {
            show_error('A table name is required for that operation.');
        }

        // add field info into field array, but we can only do one at a time
        // so we cycle through

        foreach ($field as $k => $v) {
            $this->add_field(array($k => $field[$k]));

            if (count($this->fields) == 0) {
                show_error('Field information is required.');
            }

            $sql = $this->_alter_table('ADD', $this->db->dbprefix . $table, $this->fields, $after_field);

            $this->_reset();

            if ($this->db->query($sql) === false) {
                return false;
            }
        }

        // Cached field names have changed
        unset($this->db->data_cache['field_names'][$table]);

        return true;
    }

    /**
     * Column Drop
     *
     * @access	public
     * @param	string $table The table that contains the column to drop
     * @param	string $column_name
     * @return	CI_DB_result The query result object
     */
    public function drop_column($table, $column_name)
    {
        if (empty($table)) {
            show_error('A table name is required for that operation.');
        }

        if (empty($column_name)) {
            show_error('A column name is required for that operation.');
        }

        $sql = $this->_alter_table('DROP', $this->db->dbprefix . $table, $column_name);

        // Cached field names have changed
        unset($this->db->data_cache['field_names'][$table]);

        return $this->db->query($sql);
    }

    /**
     * Batched Column Drop
     *
     * @access	public
     * @param	string $table The table that contains the columns to drop
     * @param	array $array An array of column names to drop
     * @return	CI_DB_result The query result object
     */
    public function drop_column_batch($table, $column_names)
    {
        if (empty($table)) {
            show_error('A table name is required for that operation.');
        }

        if (! is_array($column_names)) {
            $column_name = array($column_names);
        }

        if (empty($column_names)) {
            show_error('A column name is required for that operation.');
        }

        $sql = $this->_alter_table('DROP', $this->db->dbprefix . $table, $column_names);

        // Cached field names have changed
        unset($this->db->data_cache['field_names'][$table]);

        return $this->db->query($sql);
    }

    /**
     * Column Modify
     *
     * @access	public
     * @param	string $table The name of the table containing the column to
     *                        modify
     * @param	array $field The field definition as a multidimensional
     *                       associative array
     * @return	bool
     */
    public function modify_column($table, $field)
    {
        if (empty($table)) {
            show_error('A table name is required for that operation.');
        }

        // add field info into field array, but we can only do one at a time
        // so we cycle through

        foreach ($field as $k => $v) {
            $this->add_field(array($k => $field[$k]));

            if (count($this->fields) == 0) {
                show_error('Field information is required.');
            }

            $sql = $this->_alter_table('CHANGE', $this->db->dbprefix . $table, $this->fields);

            $this->_reset();

            if ($this->db->query($sql) === false) {
                return false;
            }
        }

        // Cached field names have changed
        unset($this->db->data_cache['field_names'][$table]);

        return true;
    }

    /**
     * Reset
     *
     * Resets table creation vars
     *
     * @access	private
     * @return	void
     */
    public function _reset()
    {
        $this->fields = array();
        $this->keys = array();
        $this->primary_keys = array();
    }
}

// EOF

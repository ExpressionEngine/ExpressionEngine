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
 * MySQLi Result
 *
 * This class extends the parent result class: CI_DB_result
 */
class CI_DB_mysqli_result extends CI_DB_result
{
    /**
     * Number of rows in the result set
     *
     * @access	public
     * @return	integer
     */
    public function num_rows()
    {
        return $this->pdo_statement->rowCount();
    }

    /**
     * Number of fields in the result set
     *
     * @access	public
     * @return	integer
     */
    public function num_fields()
    {
        return $this->pdo_statement->columnCount();
    }

    /**
     * Fetch Field Names
     *
     * Generates an array of column names
     *
     * @access	public
     * @return	array
     */
    public function list_fields()
    {
        $field_names = array();

        $num = $this->num_fields();

        for ($i = 0; $i < $num; $i++) {
            $meta = $this->pdo_statement->getColumnMeta($i);
            $field_names[] = $meta['name'];
        }

        return $field_names;
    }

    /**
     * Field data
     *
     * Generates an array of objects containing field meta-data
     *
     * PDO can do this just fine except for the type and default. The
     * default is not reported and the type is all wrong (e.g. LONG for
     * int fields) due to a complete lack of specification. So we do those
     * with an EXPLAIN. Highly recommend just using EXPLAIN directly if it
     * suits.
     *
     * @access	public
     * @return	array
     */
    public function field_data()
    {
        $total = $this->pdo_statement->columnCount();

        $tables = array();
        $column_data = array();

        for ($i = 0; $i < $total; $i++) {
            $column = $this->pdo_statement->getColumnMeta($i);

            $name = $column['name'];
            $table = $column['table'];

            $field = new stdClass();
            $field->name = $name;
            $field->max_length = $column['len'];
            $field->primary_key = in_array('primary_key', $column['flags']);

            $tables[] = $table;
            $column_data[$table . '.' . $name] = $field;
        }

        // Now desribe the involved tables and grab the mysql type and default
        $tables = array_unique($tables);

        foreach ($tables as $table) {
            $fields = ee('db')->query('DESCRIBE ' . $table)->result_array();

            foreach ($fields as $field) {
                $F = $column_data[$table . '.' . $field['Field']];

                $F->type = strstr($field['Type'] . '(', '(', true);
                $F->default = $field['Default'];
            }
        }

        return array_values($column_data);
    }

    /**
     * Free the result
     *
     * @return	null
     */
    public function free_result()
    {
        $this->pdo_statement->closeCursor();
        $this->pdo_statement = null;
    }

    /**
     * Data Seek
     *
     * Moves the internal pointer to the desired offset.  We call
     * this internally before fetching results to make sure the
     * result set starts at zero
     *
     * @access	private
     * @return	array
     */
    public function _data_seek($n = 0)
    {
        // TODO
        return mysqli_data_seek($this->result_id, $n);
    }

    /**
     * Result - associative array
     *
     * Returns the result set as an array
     *
     * @access	private
     * @return	array
     */
    public function _fetch_assoc()
    {
        return $this->pdo_statement->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Result - object
     *
     * Returns the result set as an object
     *
     * @access	private
     * @return	object
     */
    public function _fetch_object()
    {
        return $this->pdo_statement->fetch(PDO::FETCH_OBJ);
    }
}

// EOF

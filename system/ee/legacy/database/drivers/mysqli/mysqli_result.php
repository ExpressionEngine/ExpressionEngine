<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * CodeIgniter
 *
 * An open source application development framework for PHP 5.2.4 or newer
 *
 * @package		CodeIgniter
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2008 - 2016, EllisLab, Inc.
 * @license		http://codeigniter.com/user_guide/license.html
 * @link		http://codeigniter.com
 * @since		Version 1.0
 * @filesource
 */

// ------------------------------------------------------------------------

/**
 * MySQLi Result Class
 *
 * This class extends the parent result class: CI_DB_result
 *
 * @category	Database
 * @author		EllisLab Dev Team
 * @link		http://codeigniter.com/user_guide/database/
 */
class CI_DB_mysqli_result extends CI_DB_result {

	/**
	 * Number of rows in the result set
	 *
	 * @access	public
	 * @return	integer
	 */
	function num_rows()
	{
		return $this->pdo_statement->rowCount();
	}

	// --------------------------------------------------------------------

	/**
	 * Number of fields in the result set
	 *
	 * @access	public
	 * @return	integer
	 */
	function num_fields()
	{
		return $this->pdo_statement->columnCount();
	}

	// --------------------------------------------------------------------

	/**
	 * Fetch Field Names
	 *
	 * Generates an array of column names
	 *
	 * @access	public
	 * @return	array
	 */
	function list_fields()
	{
		$field_names = array();

		$num = $this->num_fields();

		for ($i = 0; $i < $num; $i++)
		{
			$meta = $this->pdo_statement->getColumnMeta($i);
			$field_names[] = $meta['name'];
		}

		return $field_names;
	}

	// --------------------------------------------------------------------

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
	function field_data()
	{
		$total = $this->pdo_statement->columnCount();

		$tables = array();
		$column_data = array();

 		for ($i = 0; $i < $total; $i++)
		{
			$column = $this->pdo_statement->getColumnMeta($i);

			$name = $column['name'];
			$table = $column['table'];

			$field = new stdClass();
			$field->name = $name;
			$field->max_length = $column['len'];
			$field->primary_key = in_array('primary_key', $column['flags']);

			$tables[] = $table;
			$column_data[$table.'.'.$name] = $field;
		}

		// Now desribe the involved tables and grab the mysql type and default
		$tables = array_unique($tables);

		foreach ($tables as $table)
		{
			$fields = ee('db')->query('DESCRIBE '.$table)->result_array();

			foreach ($fields as $field)
			{
				$F = $column_data[$table.'.'.$field['Field']];

				$F->type = strstr($field['Type'].'(', '(', TRUE);
				$F->default = $field['Default'];
			}
		}

		return array_values($column_data);
	}

	// --------------------------------------------------------------------

	/**
	 * Free the result
	 *
	 * @return	null
	 */
	function free_result()
	{
		$this->pdo_statement->closeCursor();
		$this->pdo_statement = NULL;
	}

	// --------------------------------------------------------------------

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
	function _data_seek($n = 0)
	{
		// TODO
		return mysqli_data_seek($this->result_id, $n);
	}

	// --------------------------------------------------------------------

	/**
	 * Result - associative array
	 *
	 * Returns the result set as an array
	 *
	 * @access	private
	 * @return	array
	 */
	function _fetch_assoc()
	{
		return $this->pdo_statement->fetch(PDO::FETCH_ASSOC);
	}

	// --------------------------------------------------------------------

	/**
	 * Result - object
	 *
	 * Returns the result set as an object
	 *
	 * @access	private
	 * @return	object
	 */
	function _fetch_object()
	{
		return $this->pdo_statement->fetch(PDO::FETCH_OBJ);
	}

}

// EOF

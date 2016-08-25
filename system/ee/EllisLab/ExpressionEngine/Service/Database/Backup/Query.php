<?php

namespace EllisLab\ExpressionEngine\Service\Database\Backup;

/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2016, EllisLab, Inc.
 * @license		https://expressionengine.com/license
 * @link		https://ellislab.com
 * @since		Version 4.0
 * @filesource
 */

// ------------------------------------------------------------------------

/**
 * ExpressionEngine Database Backup Class
 *
 * @package		ExpressionEngine
 * @subpackage	Database
 * @category	Service
 * @author		EllisLab Dev Team
 * @link		https://ellislab.com
 */
class Query {

	/**
	 * @var Database\Query Database Query object
	 */
	protected $query;

	public function __construct(\EllisLab\ExpressionEngine\Service\Database\Query $query)
	{
		$this->query = $query;
	}

	/**
	 * Returns an array of names of tables present in the database
	 *
	 * @return	array	Flat array of table names
	 */
	public function getTables()
	{
		static $tables;

		if (empty($tables))
		{
			$tables_result = $this->query
				->query('SHOW TABLES;')
				->result_array();

			$tables = [];
			foreach ($tables_result as $table)
			{
				$table_values = array_values($table);
				$tables[] = $table_values[0];
			}
		}

		return $tables;
	}

	/**
	 * Given a table name, generates a CREATE TABLE statement for it
	 *
	 * @param	string	$table_name	Table name
	 * @return	string	CREATE TABLE statement for the given table
	 */
	public function getCreateForTable($table_name)
	{
		$create_result = $this->query
			->query(sprintf('SHOW CREATE TABLE `%s`;', $table_name))
			->row_array();

		if ( ! isset($create_result['Create Table']))
		{
			// Complain
		}

		return $create_result['Create Table']."\n\n";
	}

	/**
	 * Given a table name, generates a DROP TABLE IF EXISTS statement for it
	 *
	 * @param	string	$table_name	Table name
	 * @return	string	DROP TABLE IF EXISTS statement for the given table
	 */
	public function getDropStatement($table_name)
	{
		return sprintf('DROP TABLE IF EXISTS `%s`;', $table_name);
	}

	/**
	 * Given a table name, queries for and caches the total rows for the table
	 *
	 * @param	string	$table_name	Table name
	 * @return	int		Total rows in table
	 */
	public function getTotalRows($table_name)
	{
		static $table_counts = [];

		if ( ! isset($table_counts[$table_name]))
		{
			$table_counts[$table_name] = $this->query->count_all_results($table_name);
		}

		return $table_counts[$table_name];
	}

	/**
	 * Given a table name, generates initial INSERT INTO statement for a compact
	 * INSERT query
	 *
	 * @param	string	$table_name	Table name
	 * @return	string	Initial INSERT INTO statement, no values, e.g.
	 *   "INSERT INTO `table` (`field_1`, `field_2`) VALUES"
	 */
	public function getInitialInsertForTable($table_name)
	{
		$data = $this->query
			->query(sprintf('DESCRIBE `%s`;', $table_name))
			->result_array();

		// Surround fields with backticks
		$fields = array_map(function($row)
		{
			return sprintf('`%s`', $row['Field']);
		}, $data);

		return "INSERT INTO `$table_name` (" . implode(', ', $fields) . ") VALUES";
	}

	/**
	 * Queries for data given a table name and offset parameters, and generates
	 * an array of values for each row to follow a VALUES statement
	 *
	 * @param	string	$table_name		Table name
	 * @param	int		$offset			Query offset
	 * @param	int		$limit			Query limit
	 * @param	boolean	$end_of_inserts	Whether or not this query will reach the
	 *                                	end of the table
	 * @return	array	Array of groups of values to follow an INSERT INTO ... VALUES statement, e.g.
	 *   [
	 *		'(1, NULL, 'some value'),',
	 *		'(2, NULL, 'another value');'
	 *   ]
	 */
	public function getInsertsForTable($table_name, $offset, $limit, $end_of_inserts)
	{
		$data = $this->query
			->offset($offset)
			->limit($limit)
			->get($table_name)
			->result_array();

		$inserts = [];
		$count = 1;
		$data_count = count($data);
		foreach ($data as $row)
		{
			$values = array_map(function($value) {
				return $this->formatValue($value);
			}, $row);

			// Last set of values for the table? End with ;
			$separator = ($end_of_inserts && $count == $data_count) ? ';' : ',';

			$inserts[] = "\t(" . implode(', ', $values) . ")" . $separator;

			$count++;
		}

		return $inserts;
	}

	/**
	 * Formats a given database value for use in a VALUES string
	 *
	 * @param	mixed	$value	Database column value
	 * @return	mixed	Typically either a string or number, but formatted for
	 *                  a VALUES string
	 */
	protected function formatValue($value)
	{
		if (is_null($value))
		{
			return 'NULL';
		}
		elseif (is_numeric($value))
		{
			return $value;
		}
		else {
			return "'" . $this->query->escape_str($value) . "'";
		}
	}
}

// EOF

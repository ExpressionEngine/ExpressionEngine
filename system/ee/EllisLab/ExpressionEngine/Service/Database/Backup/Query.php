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

	/**
	 * @var boolean When TRUE, class returns queries with no linebreaks
	 */
	protected $compact_queries = FALSE;

	/**
	 * Constructor
	 *
	 * @param	Database\Query	$query	Database query object
	 */
	public function __construct(\EllisLab\ExpressionEngine\Service\Database\Query $query)
	{
		$this->query = $query;
	}

	/**
	 * Makes the class return pretty queries with helpful whitespace formatting
	 */
	public function makePrettyQueries()
	{
		$this->compact_queries = FALSE;
	}

	/**
	 * Makes the class return queries that have no linebreaks in them
	 */
	public function makeCompactQueries()
	{
		$this->compact_queries = TRUE;
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
			throw new Exception('Could not generate CREATE TABLE statement for table ' . $table_name, 1);
		}

		$create = $create_result['Create Table'] . ';';

		if ($this->compact_queries)
		{
			$create = str_replace("\n", '', $create);
		}

		return $create;
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
	 * Queries for data given a table name and offset parameters, and generates
	 * an array of values for each row to follow a VALUES statement
	 *
	 * @param	string	$table_name		Table name
	 * @param	int		$offset			Query offset
	 * @param	int		$limit			Query limit
	 * @return	array	Array containing ull, valid INSERT INTO statement for a given
	 * range of table data, and also the number of rows that were exported, e.g.:
	 *	[
	 *		'insert_string' => 'INSERT INTO `table_name` VALUES ... ;',
	 *		'rows_exported' => 50
	 *	]
	 */
	public function getInsertsForTable($table_name, $offset, $limit)
	{
		$data = $this->query
			->query(sprintf('DESCRIBE `%s`;', $table_name))
			->result_array();

		// Surround fields with backticks
		$fields = array_map(function($row)
		{
			return sprintf('`%s`', $row['Field']);
		}, $data);

		$insert = sprintf('INSERT INTO `%s` (%s) VALUES ', $table_name, implode(', ', $fields));

		$values = $this->getValuesForTable($table_name, $offset, $limit);

		if ($this->compact_queries)
		{
			$insert .= implode(', ', $values);
		}
		else
		{
			$insert .= "\n\t" . implode(",\n\t", $values);
		}

		return [
			'insert_string' => $insert . ';',
			'rows_exported' => count($values)
		];
	}

	/**
	 * Gets values for a table formatted for a VALUES string
	 *
	 * @param	string	$table_name		Table name
	 * @param	int		$offset			Query offset
	 * @param	int		$limit			Query limit
	 * @return	array	Array of groups of values to follow an INSERT INTO ... VALUES statement, e.g.
	 *	[
	 *		'(1, NULL, 'some value')',
	 *		'(2, NULL, 'another value')'
	 *	]
	 */
	protected function getValuesForTable($table_name, $offset, $limit)
	{
		$data = $this->query
			->offset($offset)
			->limit($limit)
			->get($table_name)
			->result_array();

		$values = [];
		foreach ($data as $row)
		{
			$formatted_values = array_map(function($value) {
				return $this->formatValue($value);
			}, $row);

			$values[] = sprintf('(%s)', implode(', ', $formatted_values));
		}

		return $values;
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
		else
		{
			return sprintf("'%s'", $this->query->escape_str($value));
		}
	}
}

// EOF

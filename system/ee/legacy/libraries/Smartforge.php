<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2019, EllisLab Corp. (https://ellislab.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

/**
 * Update SmartForge
 */
class Smartforge {

	function __construct()
	{
		ee()->load->dbforge();
		ee()->load->helper('array');
	}

	/**
	 * Create table
	 *
	 * This will check to make sure the existing table doesn't exist before creating it.
	 *
	 * @access	public
	 * @param	string	the old table name
	 */
	public function create_table($table)
	{
		// Check to make sure table doesn't already exist
		if (ee()->db->table_exists($table))
		{
			ee()->logger->updater("Could not create table '".ee()->db->dbprefix."$table'. Table already exists.", TRUE);

			ee()->dbforge->_reset();

			return FALSE;
		}

		return ee()->dbforge->create_table($table);
	}

	/**
	 * Rename table
	 *
	 * This will check to make sure the existing table name actually
	 * exists and the new table name doesn't.
	 *
	 * @access	public
	 * @param	string	the old table name
	 * @param	string	the new table name
	 */
	public function rename_table($table, $new_table)
	{
		// Check to make sure table exists
		if ( ! ee()->db->table_exists($table))
		{
			ee()->logger->updater(__METHOD__." failed. Table '".ee()->db->dbprefix."$table' does not exist.", TRUE);

			return FALSE;
		}

		if ( ! ee()->db->table_exists($new_table))
		{
			return ee()->dbforge->rename_table($table, $new_table);
		}

		ee()->logger->updater("Could not rename '".ee()->db->dbprefix."$table' to '".ee()->db->dbprefix."$new_table'. Table '".ee()->db->dbprefix."$new_table' already exists.", TRUE);

		return FALSE;
	}

	/**
	 * Column Add
	 *
	 * Run through each column in the array to be added. For each, check
	 * to see if column already exists in the DB. If so, skip adding that
	 * column.
	 *
	 * @access	public
	 * @param	string	the table name
	 * @param	array	the column name with an array defining the column
	 * @param	string	the column name after which the new column will be added
	 * @return	bool
	 */
	public function add_column($table = '', $field = array(), $after_field = '')
	{
		// Check to make sure table exists
		if ( ! ee()->db->table_exists($table))
		{
			ee()->logger->updater(__METHOD__." failed. Table '".ee()->db->dbprefix."$table' does not exist.", TRUE);

			return FALSE;
		}

		$result = FALSE;

		// Reset cache for this table in case it a column has just been added
		unset(ee()->db->data_cache['field_names'][$table]);

		foreach ($field as $k => $v)
		{
			if ( ! ee()->db->field_exists($k, $table))
			{
				if (ee()->dbforge->add_column($table, array($k => $field[$k]), $after_field))
				{
					$result = TRUE;
				}
			}
			else
			{
				ee()->logger->updater("Could not add column '".ee()->db->dbprefix."$table.$k'. Column already exists.", TRUE);
			}
		}

		return $result;
	}

	/**
	 * Column Drop
	 *
	 * Drop a column in the given database table if it already exists.
	 *
	 * @access	public
	 * @param	string	the table name
	 * @param	string	the column name
	 * @return	bool
	 */
	public function drop_column($table, $column_name)
	{
		// Check to make sure table exists
		if ( ! ee()->db->table_exists($table))
		{
			ee()->logger->updater(__METHOD__." failed. Table '".ee()->db->dbprefix."$table' does not exist.", TRUE);

			return FALSE;
		}

		if (ee()->db->field_exists($column_name, $table))
		{
			return ee()->dbforge->drop_column($table, $column_name);
		}

		ee()->logger->updater("Could not drop column '".ee()->db->dbprefix."$table.$column_name'. Column does not exist.", TRUE);

		return FALSE;
	}

	/**
	 * Table Drop
	 *
	 * Drop a table from the database
	 * @param  string $table The table name
	 * @return bool          TRUE if successful, FALSE if not
	 */
	public function drop_table($table)
	{
		if ( ! ee()->db->table_exists($table))
		{
			ee()->logger->updater(__METHOD__." failed. Table '".ee()->db->dbprefix."$table' does not exist.", TRUE);

			return FALSE;
		}

		return ee()->dbforge->drop_table($table);
	}

	/**
	 * Column Modify
	 *
	 * Modify a database column (if it exists) with the added check that
	 * if the column is being renamed and both current column (A) and
	 * proposed column (B) names exist, drop column A and leave column B.
	 *
	 * If both columns exist, it's likely this update is being run again
	 * from a version further back than the point the DB is actually at
	 * (an overlay, if you will). Therefore, column B is probably the one
	 * with all the data in it, and column A has only very recently
	 * (as in, within seconds) been created.
	 *
	 * @access	public
	 * @param	string	the table name
	 * @param	string	the column name
	 * @param	string	the column definition
	 * @return	bool
	 */
	public function modify_column($table = '', $field = array())
	{
		// Check to make sure table exists
		if ( ! ee()->db->table_exists($table))
		{
			ee()->logger->updater(__METHOD__." failed. Table '".ee()->db->dbprefix."$table' does not exist.", TRUE);

			return FALSE;
		}

		$result = FALSE;

		foreach ($field as $k => &$v)
		{
			// Check to make sure the column A exists.
			if (ee()->db->field_exists($k, $table))
			{
				// If the column name wasn't included in the field's array,
				// just use the column name as defined by the key.
				if ( ! isset($v['name']))
				{
					$v['name'] = $k;
				}

				// Check to see if the column is being renamed
				// and if column B exists, too.
				if ($k !== $v['name'] AND ee()->db->field_exists($v['name'], $table))
				{
					// Drop column A.
					ee()->dbforge->drop_column($table, $k);

					ee()->logger->updater("Could not rename column '".ee()->db->dbprefix."$table.$k' to '".ee()->db->dbprefix."$table.{$v['name']}' since it already exists. Column '".ee()->db->dbprefix."$table.$k' was removed to clean up.", TRUE);
				}
				else
				{
					// Rename column A -> B.
					if (ee()->dbforge->modify_column($table, array($k => $field[$k]), array('ignore' => TRUE)))
					{
						$result = TRUE;
					}
				}

			}
			else
			{
				ee()->logger->updater("Could not modify column '".ee()->db->dbprefix."$table.$k'. Column does not exist.", TRUE);
			}


		}

		return $result;

	}

	/**
	 * Insert Set
	 *
	 * Insert values into the database, with optional unique
	 * column name/values in a given column(s).
	 *
	 * @access	public
	 * @param	string	table name
	 * @param	array	associative array of column names => row values
	 * @param	array	check for uniqueness, associative array of column
	 *                  names => row values (can only include key/value pairs from $values)
	 * @return	bool
	 */
	public function insert_set($table = '', $values = array(), $unique = array())
	{
		// Check to make sure table exists
		if ( ! ee()->db->table_exists($table))
		{
			ee()->logger->updater(__METHOD__." failed. Table '".ee()->db->dbprefix."$table' does not exist.", TRUE);

			return FALSE;
		}

		// Check to make sure $unique, if present, doesn't already exist in table
		// Checks all parts of $unique as one query rather than individually.
		if (! empty($unique))
		{
			foreach ($unique as $k => $v)
			{
				if (array_key_exists($k, $values))
				{
					ee()->db->where($k, $v);
				}
			}

			$query = ee()->db->get($table);

			if ($query->num_rows() > 0)
			{
				// If the unique field content already exists in this column
				// in the DB, return FALSE since this set of values cannot
				// be inserted.

				ee()->logger->updater("Could not insert data since data set was not unique as required.", TRUE);

				return FALSE;
			}
		}

		ee()->db->set($values);
		ee()->db->insert($table);

		return FALSE;

	}

	/**
	 * Add Key
	 *
	 * Add a new key to the given database table if it doesn't already exist.
	 *
	 * @access	public
	 * @param	string			table name
	 * @param	string/array	column to index, creates composite primary key
	 *                          if array and key name is PRIMARY
	 * @param	string			key name (optional)
	 * @return	bool
	 */
	public function add_key($table = '', $col_name = '', $key_name = '')
	{
		// Check to make sure table exists
		if ( ! ee()->db->table_exists($table))
		{
			ee()->logger->updater(__METHOD__." failed. Table '".ee()->db->dbprefix."$table' does not exist.", TRUE);

			return FALSE;
		}

		if ($key_name == '' AND ! is_array($col_name))
		{
			$key_name = $col_name;
		}

		if (is_array($col_name))
		{
			$col_name = implode("`, `", $col_name);
		}

		// Check to make sure this key doesn't already exist.
		$query = ee()->db->query("SHOW INDEX FROM ".ee()->db->dbprefix."$table WHERE Key_name = '{$key_name}'");

		if ($query->num_rows() == 0)
		{
			// Create key
			if ($key_name == 'PRIMARY')
			{
				$sql = "ALTER TABLE `".ee()->db->dbprefix."$table` ADD PRIMARY KEY (`{$col_name}`)";
			}
			else
			{
				$sql = "ALTER TABLE `".ee()->db->dbprefix."$table` ADD INDEX {$key_name} (`{$col_name}`)";
			}

			if (ee()->db->query($sql) === TRUE)
			{
				return TRUE;
			}
		}

		ee()->logger->updater("Could not create key '$key_name' on table '".ee()->db->dbprefix."$table'. Key already exists.", TRUE);

		return FALSE;
	}

	/**
	 * Drop Key
	 *
	 * Drop an key in the given database table if it exists.
	 *
	 * @access	public
	 * @param	string	table name
	 * @param	string	key name
	 * @return	bool
	 */
	public function drop_key($table = '', $key_name = '')
	{
		// Check to make sure table exists
		if ( ! ee()->db->table_exists($table))
		{
			ee()->logger->updater(__METHOD__." failed. Table '".ee()->db->dbprefix."$table' does not exist.", TRUE);

			return FALSE;
		}

		// Check to make sure this key exists.
		$query = ee()->db->query("SHOW INDEX FROM ".ee()->db->dbprefix."$table WHERE Key_name = '{$key_name}'");

		if ($query->num_rows() !== 0)
		{
			// Drop Key
			if ($key_name == 'PRIMARY')
			{
				// This should be rare since MySQL requires auto-increment
				// columns to have a primary key.
				$sql = "ALTER TABLE `".ee()->db->dbprefix."$table` DROP PRIMARY KEY";
			}
			else
			{
				$sql = "ALTER TABLE `".ee()->db->dbprefix."$table` DROP KEY {$key_name}";
			}

			if (ee()->db->query($sql) === TRUE)
			{
				return TRUE;
			}
		}

		ee()->logger->updater("Could not drop key '$key_name' from table '".ee()->db->dbprefix."$table'. Key does not exist.", TRUE);

		return FALSE;
	}

}

// END SmartForge class

// EOF

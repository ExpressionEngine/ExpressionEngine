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
 * Unicode Database Conversion
 */
class Utf8_db_convert {

	/**
	 * Constructor
	 */
	public function __construct()
	{
		@set_time_limit(0);
        ee()->db->save_queries = FALSE;

        // make sure STRICT MODEs aren't in use,
		// at least on servers that don't default to that
        ee()->db->query('SET SESSION sql_mode=""');
	}

	/**
	 * Do conversion to Unicode
	 *
	 * This method is being abstracted out from the 200 update file.
	 * The app forces UTF-8, but it seems that when a table is created
	 * with raw SQL and the db->query() method, it can be created with
	 * DEFAULT CHARSET=latin1.  So unfortunately, we're kinda doing bits of this
	 * again, but we can ignore some of the beefier tables, eg: channel(s) as
	 * they would have been swatted into shape on the 200 update.
	 *
	 * Long story short, use this method on an update to ensure that your tables
	 * will be of UTF-8 Collation, since the MySQL default is latin1
	 *
	 * 2017-12-12 NOTE: no longer used, default collation in the app is utf8mb4
	 *
	 * @param 	array 		array of tables to convert
	 * @return 	boolean		TRUE on Success, FALSE on failure
	 */
	public function do_conversion($tables = array())
	{
		if (count($tables) === 0)
		{
			return FALSE;
		}

		foreach ($tables as $table)
		{
			$count  = ee()->db->count_all($table);
			$offset = 0;
			$batch  = 100;

			if ($count > 0)
			{
				for ($i = 0; $i < $count; $i = $i + $batch)
				{
					$query = ee()->db->get($table, $offset, $batch);
					$data = $query->result_array();
					$query->free_result();

					foreach ($data as $row)
					{
						$where = array();
						$update = FALSE;

						foreach ($row as $field => $value)
						{
							// Wet the WHERE using all numeric fields to
							// ensure accuracy since we have no clue what the
							// keys for the current table are.
							//
							// Also check to see if this row contains any fields
							// that have characters not shared between latin1
							// and utf8 (7-bit ASCII shared only).
							// If it does, then we need to update this row.
							if (is_numeric($value))
							{
								$where[$field] = $value;
							}
							elseif (preg_match('/[^\x00-\x7F]/S', $value) > 0)
							{
								$update = TRUE;
							}
						}

						if ($update === TRUE)
						{
							ee()->db->where($where);
							ee()->db->update($table, $row, $where);
						}
					}

					$offset = $offset + $batch;
				}
			}

			// finally, set the table's charset and collation in MySQL to utf8
			ee()->db->query("ALTER TABLE {$table} CONVERT TO CHARACTER
								  SET utf8 COLLATE utf8_unicode_ci");
		}

		return TRUE;
	}
}

// EOF

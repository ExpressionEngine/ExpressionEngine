<?php
/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2016, EllisLab, Inc.
 * @license		https://expressionengine.com/license
 * @link		https://ellislab.com
 * @since		Version 2.0
 * @filesource
 */

// ------------------------------------------------------------------------

/**
 * ExpressionEngine Grid Field Model
 *
 * @package		ExpressionEngine
 * @subpackage	Core
 * @category	Model
 * @author		EllisLab Dev Team
 * @link		https://ellislab.com
 */
class Grid_model extends CI_Model {

	protected $_table = 'grid_columns';
	protected $_table_prefix = 'grid_field_';
	protected $_grid_data = array();
	protected $_columns = array();

	/**
	 * Performs fieldtype install
	 *
	 * Beware! Changes here also need to be made in mysql_schema.
	 *
	 * @return	void
	 */
	public function install()
	{
		$columns = array(
			'col_id' => array(
				'type'				=> 'int',
				'constraint'		=> 10,
				'unsigned'			=> TRUE,
				'auto_increment'	=> TRUE
			),
			'field_id' => array(
				'type'				=> 'int',
				'constraint'		=> 10,
				'unsigned'			=> TRUE
			),
			'content_type' => array(
				'type'				=> 'varchar',
				'constraint'		=> 50
			),
			'col_order' => array(
				'type'				=> 'int',
				'constraint'		=> 3,
				'unsigned'			=> TRUE
			),
			'col_type' => array(
				'type'				=> 'varchar',
				'constraint'		=> 50
			),
			'col_label' => array(
				'type'				=> 'varchar',
				'constraint'		=> 50
			),
			'col_name' => array(
				'type'				=> 'varchar',
				'constraint'		=> 32
			),
			'col_instructions' => array(
				'type'				=> 'text'
			),
			'col_required' => array(
				'type'				=> 'char',
				'constraint'		=> 1
			),
			'col_search' => array(
				'type'				=> 'char',
				'constraint'		=> 1
			),
			'col_width' => array(
				'type'				=> 'int',
				'constraint'		=> 3,
				'unsigned'			=> TRUE
			),
			'col_settings' => array(
				'type'				=> 'text'
			)
		);

		ee()->load->dbforge();
		ee()->dbforge->add_field($columns);
		ee()->dbforge->add_key('col_id', TRUE);
		ee()->dbforge->add_key('field_id');
		ee()->dbforge->add_key('content_type');
		ee()->dbforge->create_table($this->_table);

		ee()->db->insert('content_types', array('name' => 'grid'));
	}

	// ------------------------------------------------------------------------

	/**
	 * Performs fieldtype uninstall
	 *
	 * @return	void
	 */
	public function uninstall()
	{
		// Get field IDs to drop corresponding field table
		$grid_fields = ee()->db->select('field_id')
			->distinct()
			->get($this->_table)
			->result_array();

		// Drop grid_field_n tables
		foreach ($grid_fields as $row)
		{
			$this->delete_field($row['field_id'], $row['content_type']);
		}

		// Drop grid_columns table
		ee()->load->dbforge();
		ee()->dbforge->drop_table($this->_table);

		ee()->db->delete('content_types', array('name' => 'grid'));
	}

	// ------------------------------------------------------------------------

	/**
	 * Creates data table for a new Grid field
	 *
	 * @param	int		Field ID of field to create a data table for
	 * @return	boolean	Whether or not a table was created
	 */
	public function create_field($field_id, $content_type)
	{
		$table_name = $this->_data_table($content_type, $field_id);

		if ( ! ee()->db->table_exists($table_name))
		{
			ee()->load->dbforge();

			// Every field table needs these two rows, we'll start here and
			// add field columns as necessary
			$db_columns = array(
				'row_id' => array(
					'type'				=> 'int',
					'constraint'		=> 10,
					'unsigned'			=> TRUE,
					'auto_increment'	=> TRUE
				),
				'entry_id' => array(
					'type'				=> 'int',
					'constraint'		=> 10,
					'unsigned'			=> TRUE
				),
				'row_order' => array(
					'type'				=> 'int',
					'constraint'		=> 10,
					'unsigned'			=> TRUE
				)
			);

			ee()->dbforge->add_field($db_columns);
			ee()->dbforge->add_key('row_id', TRUE);
			ee()->dbforge->add_key('entry_id');
			ee()->dbforge->create_table($table_name);

			return TRUE;
		}

		return FALSE;
	}

	// ------------------------------------------------------------------------

	/**
	 * Performs cleanup on our end if a Grid field is deleted from a channel:
	 * drops field's table, removes column settings from grid_columns table
	 *
	 * @param	int		Field ID of field to delete
	 * @return	void
	 */
	public function delete_field($field_id, $content_type)
	{
		$table_name = $this->_data_table($content_type, $field_id);

		if (ee()->db->table_exists($table_name))
		{
			ee()->load->dbforge();
			ee()->dbforge->drop_table($table_name);
		}

		ee()->db->delete($this->_table, array('field_id' => $field_id));
	}

	// ------------------------------------------------------------------------

	/**
	 * Performs cleanup on our end if a grid field's parent content type is deleted.
	 * Removes all associated tables and drops all entry rows.
	 *
	 * @param	string  Name of the content type that was removed
	 * @return	void
	 */
	public function delete_content_of_type($content_type)
	{
		$tables = ee()->db->list_tables($content_type . $this->_table_prefix);

		ee()->load->dbforge();

		foreach ($tables as $table_name)
		{
			ee()->dbforge->drop_table($table_name);
		}

		ee()->db->delete($this->_table, array('content_type' => $content_type));
	}

	// ------------------------------------------------------------------------

	/**
	 * Adds a new column to the columns table or updates an existing one; also
	 * manages columns in the field's respective data table
	 *
	 * @param	array	Column data
	 * @param	int		Column ID to update, or FALSE if new column
	 * @return	int		Column ID
	 */
	public function save_col_settings($column, $col_id = FALSE, $content_type = 'channel')
	{
		// Existing column
		if ($col_id)
		{
			// Make any column modifications necessary
			ee()->api_channel_fields->edit_datatype(
				$col_id,
				$column['col_type'],
				json_decode($column['col_settings'], TRUE),
				$this->_get_ft_api_settings($column['field_id'], $content_type)
			);

			ee('db')->where('col_id', $col_id)
				->update($this->_table, $column);
		}
		// New column
		else
		{
			$db = ee('db');
			$db->insert($this->_table, $column);
			$col_id = $db->insert_id();

			// Add the fieldtype's columns to our data table
			ee()->api_channel_fields->setup_handler($column['col_type']);
			ee()->api_channel_fields->set_datatype(
				$col_id,
				json_decode($column['col_settings'], TRUE),
				array(),
				TRUE,
				FALSE,
				$this->_get_ft_api_settings($column['field_id'], $content_type)
			);
		}

		return $col_id;
	}

	// ------------------------------------------------------------------------

	/**
	 * Deletes columns from grid settings and drops columns from their
	 * respective field tables
	 *
	 * @param	array	Column IDs to delete
	 * @param	array	Column types
	 * @param	int		Field ID
	 */
	public function delete_columns($column_ids, $column_types, $field_id, $content_type)
	{
		if ( ! is_array($column_ids))
		{
			$column_ids = array($column_ids);
		}

		ee()->db->where_in('col_id', $column_ids);
		ee()->db->delete($this->_table);

		foreach ($column_ids as $col_id)
		{
			// Delete columns from data table
			ee()->api_channel_fields->setup_handler($column_types[$col_id]);
			ee()->api_channel_fields->delete_datatype(
				$col_id,
				array(),
				$this->_get_ft_api_settings($field_id, $content_type)
			);
		}
	}

	// ------------------------------------------------------------------------

	/**
	 * Typically used when a fieldtype is uninstalled, removes all columns of
	 * a certain fieldtype across all Grid fields and content types
	 *
	 * @param	string 	$field_type	Fieldtype short name
	 */
	public function delete_columns_of_type($field_type)
	{
		$grid_cols = ee()->db->where('col_type', $field_type)
			->get('grid_columns')
			->result_array();

		$cols_to_fieldtypes = array();
		$fields_to_columns = array();
		$fields_to_contenttypes = array();
		foreach ($grid_cols as $column)
		{
			$cols_to_fieldtypes[$column['col_id']] = $column['col_type'];
			$fields_to_columns[$column['field_id']][] = $column['col_id'];
			$fields_to_contenttypes[$column['field_id']] = $column['content_type'];
		}

		foreach ($fields_to_columns as $field_id => $col_ids)
		{
			$this->delete_columns(
				$col_ids,
				$cols_to_fieldtypes,
				$field_id,
				$fields_to_contenttypes[$field_id]
			);
		}
	}

	// ------------------------------------------------------------------------

	/**
	 * Returns the row data for a single entry ID and field ID
	 *
	 * @param	int 	Entry ID
	 * @param	int		Field ID to get row data for
	 * @param	string	Content type to get data for
	 * @return	array	Row data
	 */
	public function get_entry($entry_id, $field_id, $content_type)
	{
		$table = $this->_data_table($content_type, $field_id);
		ee()->db->where('entry_id', $entry_id);
		return ee()->db->get($table)->result_array();
	}

	// ------------------------------------------------------------------------

	/**
	 * Returns entry row data for a given entry ID and field ID, caches data
	 * it has already queried for
	 *
	 * @param	array	Entry IDs to get row data for
	 * @param	int		Field ID to get row data for
	 * @param	string	Name of content type
	 * @param	array	Options for the query, often filled by tag parameters
	 * @param	boolean	Whether or not to get fresh data on this call instead of from the _grid_data cache
	 * @return	array	Row data
	 */
	public function get_entry_rows($entry_ids, $field_id, $content_type, $options = array(), $reset_cache = FALSE)
	{
		if ( ! is_array($entry_ids))
		{
			$entry_ids = array($entry_ids);
		}

		// Validate the passed parameters and create a unique marker for these
		// specific parameters so we know not to query for them again
		$options = $this->_validate_params($options, $field_id, $content_type);
		$marker = $this->_get_tag_marker($options);

		foreach ($entry_ids as $key => $entry_id)
		{
			// If we already have data for this particular tag configuation
			// and entry ID, we don't need to get it again
			if ($reset_cache === FALSE && isset($this->_grid_data[$content_type][$field_id][$marker][$entry_id]))
			{
				unset($entry_ids[$key]);
			}
		}

		$this->_grid_data[$content_type][$field_id][$marker]['params'] = $options;

		if ( ! empty($entry_ids))
		{
			// Insert a blank array for each entry ID in case the query returns
			// no results, we don't want the cache check to fail and we keep
			// querying for data that doesn't exist
			foreach ($entry_ids as $entry_id)
			{
				$this->_grid_data[$content_type][$field_id][$marker][$entry_id] = array();
			}

			// fixed_order parameter
			if (isset($options['fixed_order']) && ! empty($options['fixed_order']))
			{
				ee()->functions->ar_andor_string($options['fixed_order'], 'row_id');
				ee()->db->order_by(
						'FIELD(row_id, '.implode(', ', explode('|', $options['fixed_order'])).')',
						element('sort', $options, 'asc'),
						FALSE
					);
			}

			// search:field parameter
			if (isset($options['search']) && ! empty($options['search']))
			{
				$this->_field_search($options['search'], $field_id, $content_type);
			}

			ee()->load->helper('array_helper');

			$orderby = element('orderby', $options);
			if ($orderby == 'random' || empty($orderby))
			{
				$orderby = 'row_order';
			}

			ee()->db->where_in('entry_id', $entry_ids)
				->order_by($orderby, element('sort', $options, 'asc'));

			// -------------------------------------------
			// 'grid_query' hook.
			// - Allows developers to modify and run the query for Grid data
			//
				if (ee()->extensions->active_hook('grid_query') === TRUE)
				{
					$rows = ee()->extensions->call(
						'grid_query',
						$entry_ids,
						$field_id,
						$content_type,
						$this->_data_table($content_type, $field_id),
						ee()->db->_compile_select(FALSE, FALSE)
					);
				}
				else
				{
					$rows = ee()->db->get(
						$this->_data_table($content_type, $field_id)
					)->result_array();
				}
			//
			// -------------------------------------------

			// Add these rows to the cache
			foreach ($rows as $row)
			{
				$this->_grid_data[$content_type][$field_id][$marker][$row['entry_id']][$row['row_id']] = $row;
			}
		}

		return isset($this->_grid_data[$content_type][$field_id][$marker]) ? $this->_grid_data[$content_type][$field_id][$marker] : FALSE;
	}

	// --------------------------------------------------------------------

	/**
	 * Assigns some default parameters and makes sure parameters can be
	 * safely used in an SQL query or otherwise used to help parsing
	 *
	 * @param	array	Array of parameters from Functions::assign_parameters
	 * @param	int		Field ID of field being parsed so we can make sure
	 *					the orderby parameter is ordering via a real column
	 * @return	array	Array of validated and default parameters to use for parsing
	 */
	protected function _validate_params($params, $field_id, $content_type)
	{
		ee()->load->helper('array_helper');

		if (is_string($params))
		{
			$params = ee()->functions->assign_parameters($params);
		}

		// dynamic_parameters
		if (($dynamic_params = element('dynamic_parameters', $params)) != FALSE)
		{
			foreach (explode('|', $dynamic_params) as $param)
			{
				// Add its value to the params array if exists in POST
				if (($value = ee()->input->post($param)) !== FALSE)
				{
					$params[$param] = $value;
				}
			}
		}

		// Gather params and defaults
		$sort			= element('sort', $params);
		$orderby		= element('orderby', $params);
		$limit			= element('limit', $params, 100);
		$offset			= element('offset', $params, 0);
		$backspace		= element('backspace', $params, 0);
		$row_id			= element('row_id', $params, 0);
		$fixed_order	= element('fixed_order', $params, 0);

		// Validate sort parameter, only 'asc' and 'desc' allowed, default to 'asc'
		if ( ! in_array($sort, array('asc', 'desc')))
		{
			$sort = 'asc';
		}

		$columns = $this->get_columns_for_field($field_id, $content_type);

		$sortable_columns = array();
		foreach ($columns as $col)
		{
			$sortable_columns[$col['col_name']] = $col['col_id'];
		}

		// orderby parameter can only order by the columns available to it,
		// default to 'row_id'
		if ($orderby != 'random')
		{
			if ( ! in_array($orderby, array_keys($sortable_columns)))
			{
				$orderby = 'row_order';
			}
			// Convert the column name to its matching table column name to hand
			// off to the query for proper sorting
			else
			{
				$orderby = 'col_id_'.$sortable_columns[$orderby];
			}
		}

		// Gather search:field_name parameters
		$search = array();
		if ($params !== FALSE)
		{
			foreach ($params as $key => $val)
			{
				if (strncmp($key, 'search:', 7) == 0)
				{
					$search[substr($key, 7)] = $val;
				}
			}
		}

		return compact(
			'sort', 'orderby', 'limit', 'offset', 'search',
			'backspace', 'row_id', 'fixed_order'
		);
	}

	// --------------------------------------------------------------------

	/**
	 * Creates a unique marker for this tag configuration based on its
	 * parameters so we can match up the field data later in parse();
	 * if there are no parameters, we'll just use 'data'
	 *
	 * @param	array	Unvalidated params
	 * @return	string	Marker
	 */
	private function _get_tag_marker($params)
	{
		ee()->load->helper('array_helper');

		// These are the only parameters that affect the DB query so we'll
		// only check against these; we could put some of these other
		// parameters in the code later on  so that even more tags could
		// use the same data set
		$db_params = array(
			'fixed_order'	=> element('fixed_order', $params),
			'search'		=> element('search', $params),
			'orderby'		=> element('orderby', $params),
			'sort'			=> element('sort', $params),
		);

		return md5(json_encode($db_params));
	}

	// ------------------------------------------------------------------------

	/**
	 * Constructs query for search params and adds it to the current
	 * Active Record call
	 *
	 * @param	array	Array of field names mapped to search terms
	 * @param	int		Field ID to get column data for
	 */
	protected function _field_search($search_terms, $field_id, $content_type = 'channel')
	{
		if (empty($search_terms))
		{
			return;
		}

		ee()->load->model('channel_model');

		$columns = $this->get_columns_for_field($field_id, $content_type);

		// We'll need to map column names to field IDs so we know which column
		// to search
		foreach ($columns as $col)
		{
			$column_ids[$col['col_name']] = $col['col_id'];
		}

		foreach ($search_terms as $col_name => $terms)
		{
			$terms = trim($terms);

			// Empty search param or invalid field name? Bail out
			if (empty($search_terms) ||
				$search_terms === '=' ||
				! isset($column_ids[$col_name]))
			{
				continue;
			}

			// We'll search on this column name
			$field_name = 'col_id_'.$column_ids[$col_name];

			$search_sql = ee()->channel_model->field_search_sql($terms, $field_name);

			ee()->db->where('('.$search_sql.')');
		}
	}

	// ------------------------------------------------------------------------

	/**
	 * Public getter for $_grid_data property
	 *
	 * @return	array
	 */
	public function get_grid_data()
	{
		return $this->_grid_data;
	}

	// ------------------------------------------------------------------------

	/**
	 * Gets array of all columns and settings for a given field ID
	 *
	 * @param	int		Field ID to get columns for
	 * @param	boolean	Skip the cache and get a fresh set of columns
	 * @return	array	Settings from grid_columns table
	 */
	public function get_columns_for_field($field_ids, $content_type, $cache = TRUE)
	{
		$multi_column = is_array($field_ids);

		if ($multi_column && $cache)
		{
			$cached = array();

			// Only get the colums for the field IDs we don't already have
			foreach ($field_ids as $key => $field_id)
			{
				if (isset($this->_columns[$content_type][$field_id]) && $cache)
				{
					$cached[$field_id] = $this->_columns[$content_type][$field_id];
					unset($field_ids[$key]);
				}
			}

			// If there are no field IDs to query, great!
			if (empty($field_ids))
			{
				return $cached;
			}
		}
		else
		{
			// Return fron cache if exists and allowed
			if (isset($this->_columns[$content_type][$field_ids]) && $cache)
			{
				return $this->_columns[$content_type][$field_ids];
			}

			$field_ids = array($field_ids);
		}

		$columns = ee('db')->where_in('field_id', $field_ids)
			->where('content_type', $content_type)
			->order_by('col_order')
			->get($this->_table)
			->result_array();

		foreach ($columns as &$column)
		{
			$column['col_settings'] = json_decode($column['col_settings'], TRUE);
			$this->_columns[$content_type][$column['field_id']][$column['col_id']] = $column;
		}

		foreach ($field_ids as $field_id)
		{
			if ( ! isset($this->_columns[$content_type][$field_id]))
			{
				$this->_columns[$content_type][$field_id] = array();
			}
		}

		return ($multi_column) ? $this->_columns[$content_type] : $this->_columns[$content_type][$field_id];
	}

	// ------------------------------------------------------------------------

	/**
	 * Returns settings we need to pass along to the channel fields API when
	 * working with managing the data columns for our fieldtypes
	 *
	 * @param	int		Current field ID
	 * @return	array
	 */
	protected function _get_ft_api_settings($field_id, $content_type = 'channel')
	{
		return array(
			'id_field'				=> 'col_id',
			'type_field'			=> 'col_type',
			'col_settings_method'	=> 'grid_settings_modify_column',
			'col_prefix'			=> 'col',
			'fields_table'			=> $this->_table,
			'data_table'			=> $this->_data_table($content_type, $field_id),
		);
	}

	// ------------------------------------------------------------------------

	/**
	 * Saves an data for a given Grid field using an array generated by the
	 * Grid libary's data processing method
	 *
	 * @param	array	Field data array
	 * @param	int	Field ID of field we're saving
	 * @param	int	Entry ID to assign the row to
	 * @return	array	IDs of rows to be deleted
	 */
	public function save_field_data($data, $field_id, $content_type, $entry_id)
	{
		// Keep track of which rows are updated and which are new, and the
		// order they are received
		$updated_rows = array();
		$new_rows = array();
		$order = 0;

		// Log existing row IDs so we can delete all others related to this
		// field and entry
		$row_ids = array(0);

		foreach ($data as $row_id => $columns)
		{
			// Each row gets its order updated
			$columns['row_order'] = $order;

			// New rows
			if (strpos($row_id, 'new_row_') !== FALSE)
			{
				$columns['entry_id'] = $entry_id;
				$new_rows[] = $columns;
			}
			// Existing rows
			elseif (strpos($row_id, 'row_id_') !== FALSE)
			{
				$columns['row_id'] = str_replace('row_id_', '', $row_id);
				$row_ids[] = $columns['row_id'];

				$updated_rows[] = $columns;
			}

			$order++;
		}

		$table_name = $this->_data_table($content_type, $field_id);

		// If there are other existing rows for this entry that weren't in
		// the data array, they are to be deleted
		$deleted_rows = ee()->db->select('row_id')
			->where('entry_id', $entry_id)
			->where_not_in('row_id', $row_ids)
			->get($table_name)
			->result_array();

		// Put rows into an array for easy passing and returning for the hook
		$data = array(
			'new_rows' => $new_rows,
			'updated_rows' => $updated_rows,
			'deleted_rows' => $deleted_rows
		);

		// -------------------------------------------
		// 'grid_save' hook.
		//  - Allow developers to modify or add to the Grid data array before saving
		//
			if (ee()->extensions->active_hook('grid_save') === TRUE)
			{
				$data = ee()->extensions->call(
					'grid_save',
					$entry_id,
					$field_id,
					$content_type,
					$table_name,
					$data
				);
			}
		//
		// -------------------------------------------

		// Batch update and insert rows to save queries
		if ( ! empty($data['updated_rows']))
		{
			ee()->db->update_batch($table_name, $data['updated_rows'], 'row_id');
		}

		if ( ! empty($data['new_rows']))
		{
			ee()->db->insert_batch($table_name, $data['new_rows']);
		}

		// Return deleted row IDs
		return $data['deleted_rows'];
	}

	// ------------------------------------------------------------------------

	/**
	 * Deletes Grid data for given row IDs
	 *
	 * @param	array	Row IDs to delete data for
	 */
	public function delete_rows($row_ids, $field_id, $content_type)
	{
		if ( ! empty($row_ids))
		{
			ee()->db->where_in('row_id', $row_ids)
				->delete($this->_data_table($content_type, $field_id));
		}
	}

	// ------------------------------------------------------------------------

	/**
	 * Create the data table name given the content type and field id.
	 *
	 * @param string	Content type (typically 'channel')
	 * @param string	Field id
	 * @return string   Table name of format <content_type>_grid_field_<id>
	 */
	protected function _data_table($content_type, $field_id)
	{
		return $content_type .'_'. $this->_table_prefix . $field_id;
	}
}

// EOF

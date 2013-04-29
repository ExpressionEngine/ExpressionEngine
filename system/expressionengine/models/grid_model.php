<?php
/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2013, EllisLab, Inc.
 * @license		http://ellislab.com/expressionengine/user-guide/license.html
 * @link		http://ellislab.com
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
 * @link		http://ellislab.com
 */
class Grid_model extends CI_Model {
	
	protected $_table = 'grid_columns';
	protected $_table_prefix = 'grid_field_';
	protected $_columns = array();

 	/**
 	 * Performs fieldtype install
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
 		ee()->dbforge->create_table($this->_table);
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
 		$grid_fields = ee()->db->distinct('field_id')
 			->get($this->_table)
 			->result_array();

 		// Drop grid_field_n tables
 		foreach ($grid_fields as $row)
 		{
 			$this->delete_field($row['field_id']);
 		}

 		// Drop grid_columns table
 		ee()->load->dbforge();
 		ee()->dbforge->drop_table($this->_table);
 	}

 	// ------------------------------------------------------------------------
 	
 	/**
 	 * Creates data table for a new Grid field
 	 * 
 	 * @param	int		Field ID of field to create a data table for
 	 * @return	boolean	Whether or not a table was created
 	 */
 	public function create_field($field_id)
 	{
 		$table_name = $this->_table_prefix . $field_id;

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
 	public function delete_field($field_id)
 	{
 		$table_name = $this->_table_prefix . $field_id;

 		if (ee()->db->table_exists($table_name))
 		{
 			ee()->load->dbforge();
 			ee()->dbforge->drop_table($table_name);
 		}

 		ee()->db->delete($this->_table, array('field_id' => $field_id));
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
 	public function save_col_settings($column, $col_id = FALSE)
 	{
 		// Existing column
 		if ($col_id)
 		{
 			ee()->db->where('col_id', $col_id);
 			ee()->db->update($this->_table, $column);

 			// Make any column modifications necessary
 			ee()->api_channel_fields->edit_datatype(
 				$col_id,
 				$column['col_type'],
 				json_decode($column['col_settings'], TRUE),
 				$this->_get_ft_api_settings($column['field_id'])
 			);
 		}
 		// New column
 		else
 		{
 			ee()->db->insert($this->_table, $column);
 			$col_id = ee()->db->insert_id();

 			// Add the fieldtype's columns to our data table
 			ee()->api_channel_fields->setup_handler($column['col_type']);
 			ee()->api_channel_fields->set_datatype(
 				$col_id,
 				json_decode($column['col_settings'], TRUE),
 				array(),
 				TRUE,
 				FALSE,
 				$this->_get_ft_api_settings($column['field_id'])
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
 	public function delete_columns($column_ids, $column_types, $field_id)
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
 				$this->_get_ft_api_settings($field_id)
 			);
 		}
 	}

 	// ------------------------------------------------------------------------
 	
 	/**
 	 * Returns entry row data for a given entry ID and field ID
 	 *
 	 * @param	int		Entry ID to get row data for
 	 * @param	int		Field ID to get row data for
 	 * @return	array	Row data
 	 */
 	public function get_entry_rows($entry_id, $field_id)
 	{
 		// TODO: Will likely need to optimize to handle multiple entries
 		// and fields for better publish screen performance and front-end
 		// rendering performace; one query is better than 50
 		return ee()->db->where('entry_id', $entry_id)
 			->order_by('row_order')
 			->get($this->_table_prefix . $field_id)
 			->result_array();
 	}

 	// ------------------------------------------------------------------------
 	
 	/**
 	 * Gets array of all columns and settings for a given field ID
 	 *
 	 * @param	int		Field ID to get columns for
 	 * @param	boolean	Skip the cache and get a fresh set of columns
 	 * @return	array	Settings from grid_columns table
 	 */
 	public function get_columns_for_field($field_id, $cache = TRUE)
 	{
 		if (isset($this->_columns[$field_id]) && $cache)
 		{
 			return $this->_columns[$field_id];
 		}

 		$columns = ee()->db->where('field_id', $field_id)
 			->order_by('col_order')
 			->get($this->_table)
 			->result_array();

 		$this->_columns[$field_id] = array();
 		foreach ($columns as &$column)
 		{
 			$column['col_settings'] = json_decode($column['col_settings'], TRUE);
 			$this->_columns[$field_id][$column['col_id']] = $column;
 		}
 		
 		return $this->_columns[$field_id];
 	}

 	// ------------------------------------------------------------------------

 	/**
 	 * Returns settings we need to pass along to the channel fields API when
	 * working with managing the data columns for our fieldtypes
	 * 
 	 * @param	int 	Current field ID
 	 * @return	array
 	 */
 	protected function _get_ft_api_settings($field_id)
 	{
 		return array(
 			'id_field'				=> 'col_id',
 			'type_field'			=> 'col_type',
 			'col_settings_method'	=> 'grid_settings_modify_column',
 			'col_prefix'			=> 'col',
 			'fields_table'			=> $this->_table,
 			'data_table'			=> $this->_table_prefix . $field_id,
 		);
 	}
}
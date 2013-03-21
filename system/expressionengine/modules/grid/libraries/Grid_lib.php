<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

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
 * ExpressionEngine Grid Field Library 
 *
 * @package		ExpressionEngine
 * @subpackage	Libraries
 * @category	Modules
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */

class Grid_lib {

	private $_fieldtypes = array();
	private $_col_table = 'grid_columns';
	private $_table_prefix = 'grid_field_';
	
	public function __construct()
	{
		$this->EE =& get_instance();
	}

	// ------------------------------------------------------------------------

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
			'col_settings' => array(
				'type'				=> 'text'
			)
		);

		$this->EE->load->dbforge();
		$this->EE->dbforge->add_field($columns);
		$this->EE->dbforge->add_key('col_id', TRUE);
		$this->EE->dbforge->create_table($this->_col_table);
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
		$grid_fields = $this->EE->db->distinct('field_id')
			->get($this->_col_table)
			->result_array();

		// Drop grid_field_n tables
		foreach ($grid_fields as $row)
		{
			$this->delete_field($row['field_id']);
		}

		// Drop grid_columns table
		$this->EE->load->dbforge();
		$this->EE->dbforge->drop_table($this->_col_table);
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

		if ($this->EE->db->table_exists($table_name))
		{
			$this->EE->load->dbforge();
			$this->EE->dbforge->drop_table($table_name);
		}

		$this->EE->db->delete($this->_col_table, array('field_id' => $field_id));
	}

	// ------------------------------------------------------------------------

	/**
	 * Gets a list of installed fieldtypes and filters them for ones enabled
	 * for Grid
	 *
	 * @return	array	Array of Grid-enabled fieldtypes
	 */
	public function get_grid_fieldtypes()
	{
		if ( ! empty($this->_fieldtypes))
		{
			return $this->_fieldtypes;
		}

		// Shorten some line lengths
		$ft_api = $this->EE->api_channel_fields;

		$this->_fieldtypes = $ft_api->fetch_installed_fieldtypes();

		foreach ($this->_fieldtypes as $field_name => $data)
		{
			$ft_api->setup_handler($field_name);

			// We'll check the existence of certain methods to determine whether
			// or not this fieldtype is ready for Grid
			if ( ! $ft_api->check_method_exists('grid_display_settings'))
			{
				unset($this->_fieldtypes[$field_name]);
			}
		}

		return $this->_fieldtypes;
	}

	// ------------------------------------------------------------------------
	
	/**
	 * Given POSTed column settings, adds new columns to the database and
	 * figures out if any columns need deleting
	 *
	 * @param	array	POSTed column settings from field settings page
	 * @return	void
	 */
	public function apply_settings($settings)
	{
		$table_name = $this->_table_prefix . $settings['field_id'];
		$ft_api = $this->EE->api_channel_fields;

		// Create field table if it doesn't exist
		if ( ! $this->EE->db->table_exists($table_name))
		{
			// Every field table needs these two rows, we'll start here and
			// add field columns as necessary
			$db_columns = array(
				'row_id' => array(
					'type'				=> 'int',
					'constraint'		=> 10,
					'unsigned'			=> TRUE,
					'auto_increment'	=> TRUE
				),
				'row_order' => array(
					'type'				=> 'int',
					'constraint'		=> 10,
					'unsigned'			=> TRUE
				)
			);

			$this->EE->load->dbforge();
			$this->EE->dbforge->add_field($db_columns);
			$this->EE->dbforge->add_key('row_id', TRUE);
			$this->EE->dbforge->create_table($table_name);
		}

		// We'll use the order of the posted fields to determine the column order
		$count = 0;

		// Go through ALL posted columns for this field
		foreach ($settings['grid']['cols'] as $col_field => $column)
		{
			// Look for a column field name prefixed with "new_" to see if we're
			// creating a new column here or modifying an existing one
			$modify = (strpos($col_field, 'new_') === FALSE);

			// Handle checkboxes
			$column['required'] = isset($column['required']) ? 'y' : 'n';
			$column['searchable'] = isset($column['searchable']) ? 'y' : 'n';

			$column_data = array(
				'field_id'			=> $settings['field_id'],
				'col_order'			=> $count,
				'col_type'			=> $column['type'],
				'col_label'			=> $column['label'],
				'col_name'			=> $column['name'],
				'col_instructions'	=> $column['instr'],
				'col_required'		=> $column['required'],
				'col_search'		=> $column['searchable'],
				'col_settings'		=> json_encode($column['settings'])
			);

			$col_id = 0;

			// Update existing column with new settings
			if ($modify)
			{
				$col_id = str_replace('col_id_', '', $col_field);
				$this->EE->db->where('col_id', $col_id);
				$this->EE->db->update($this->_col_table, $column_data);
			}
			// This is a new field, insert it into the columns table and get
			// the new column ID
			else
			{
				$this->EE->db->insert($this->_col_table, $column_data);
				$col_id = $this->EE->db->insert_id();
			}

			$db_columns = array();

			// Just as we do with regular fieldtypes, Grid fieldtypes can
			// specify their own column settings
			$ft_api->setup_handler($column['type']);

			if ($ft_api->check_method_exists('grid_settings_modify_column'))
			{
				$settings['col_id'] = $col_id;

				$db_columns = array_merge(
					$db_columns,
					$ft_api->apply('grid_settings_modify_column', array($settings))
				);
			}

			// Add default columns if they weren't supplied by fieldtype
			if ( ! isset($db_columns['col_id_'.$col_id]))
			{
				$db_columns['col_id_'.$col_id] = array(
					'type' => 'text',
					'null' => TRUE
				);
			}

			if ( ! isset($db_columns['col_ft_'.$col_id]))
			{
				$db_columns['col_ft_'.$col_id] = array(
					'type' => 'tinytext',
					'null' => TRUE
				);
			}

			$this->EE->load->dbforge();

			// Modify columns if this is an existing column
			if ($modify)
			{
				// Modify_column requires a name key
				foreach ($db_columns as $key => $value)
				{
					$db_columns[$key]['name'] = $key;
				}
				
				$this->EE->dbforge->modify_column($table_name, $db_columns);
			}
			// Otherwise, add columns to this field's table
			else
			{
				$this->EE->load->dbforge();
				$this->EE->dbforge->add_column($table_name, $db_columns);
			}

			$count++;
		}
	}

	// ------------------------------------------------------------------------
	
	/**
	 * Gets array of all columns and settings for a given field ID
	 *
	 * @param	int		Field ID to get columns for
	 * @return	array	Settings from grid_columns table
	 */
	public function get_columns_for_field($field_id)
	{
		$columns = $this->EE->db->get_where(
			$this->_col_table,
			array('field_id' => $field_id))
		->result_array();

		foreach ($columns as &$column)
		{
			$column['col_settings'] = json_decode($column['col_settings'], TRUE);
		}

		return $columns;
	}

	// ------------------------------------------------------------------------
	
	/**
	 * Returns rendered HTML for a column on the field settings page
	 *
	 * @param	array	Array of single column settings from the grid_columns table
	 * @return	string	Rendered column view for settings page
	 */
	public function get_column_view($column = NULL)
	{
		$fieldtypes = $this->get_grid_fieldtypes();

		// Create a dropdown-frieldly array of available fieldtypes
		$fieldtypes_dropdown = array();
		foreach ($fieldtypes as $key => $value)
		{
			$fieldtypes_dropdown[$key] = $value['name'];
		}

		$field_name = (empty($column)) ? '[new_0]' : '[col_id_'.$column['col_id'].']';

		$column['settings_form'] = (empty($column))
			? $this->get_settings_form('text') : $this->get_settings_form($column['col_type'], $column);

		return $this->EE->load->view(
			'col_tmpl',
			array(
				'field_name'	=> $field_name,
				'column'		=> $column,
				'fieldtypes'	=> $fieldtypes_dropdown
			),
			TRUE
		);
	}

	// ------------------------------------------------------------------------

	/**
	 * Returns rendered HTML for the custom settings form of a grid column type
	 *
	 * @param	string	Name of fieldtype to get settings form for
	 * @param	array	Column data from database to populate settings form
	 * @return	array	Rendered HTML settings form for given fieldtype and
	 * 					column data
	 */
	public function get_settings_form($type, $column = NULL)
	{
		$ft_api = $this->EE->api_channel_fields;

		$ft_api->setup_handler($type);

		// Returns blank settings form for a specific fieldtype
		if (empty($column))
		{
			$ft_api->setup_handler($type);

			return $this->_view_for_col_settings(
				$type,
				$ft_api->apply('grid_display_settings', array(array()))
			);
		}

		// Otherwise, return the prepopulated settings form based on column settings
		return $this->_view_for_col_settings(
			$type,
			$ft_api->apply('grid_display_settings', array($column['col_settings'])),
			$column['col_id']
		);
	}

	// ------------------------------------------------------------------------

	/**
	 * Returns rendered HTML for the custom settings form of a grid column type,
	 * helper method for Grid_lib::get_settings_form
	 *
	 * @param	string	Name of fieldtype to get settings form for
	 * @param	array	Column data from database to populate settings form
	 * @param	int		Column ID for field naming
	 * @return	array	Rendered HTML settings form for given fieldtype and
	 * 					column data
	 */
	private function _view_for_col_settings($col_type, $col_settings, $col_id = NULL)
	{
		$settings_view = $this->EE->load->view(
			'col_settings_tmpl',
			array(
				'col_type'		=> $col_type,
				'col_settings'	=> $col_settings
			),
			TRUE
		);
		
		$col_id = (empty($col_id)) ? '[new_0]' : '[col_id_'.$col_id.']';

		// Namespace form field names
		return preg_replace(
			'/(<[input|select][^>]*)name=["\']([^"]*)["\']/',
			'$1name="grid[cols]'.$col_id.'[settings][$2]"',
			$settings_view
		);
	}
}

/* End of file Grid_lib.php */
/* Location: ./system/expressionengine/modules/grid/libraries/Grid_lib.php */
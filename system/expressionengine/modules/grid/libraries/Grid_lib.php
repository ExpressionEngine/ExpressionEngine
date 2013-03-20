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
	private $_table_prefix = 'grid_field_';
	
	public function __construct()
	{
		$this->EE =& get_instance();
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
	 * Constructs an array of fieltype short names correllated with the HTML
	 * for each item in their grid settings forms
	 *
	 * @param	string	Name of fieldtype to get settings form for
	 * @param	array	Column data from database to populate settings form
	 * @return	array	Rendered HTML settings form for given fieldtype and
	 * 					column data, or blank settings forms for all fieldtypes
	 * 					if parameter left blank
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
	
	public function apply_settings($settings)
	{
		$table_name = $this->_table_prefix . $settings['field_id'];
		$ft_api = $this->EE->api_channel_fields;

		// Create field table if it doesn't exist
		if ( ! $this->EE->db->table_exists($table_name))
		{
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

		$count = 0;

		foreach ($settings['grid']['cols'] as $col_field => $column)
		{
			$db_columns = array();

			$modify = (strpos($col_field, 'new_') === FALSE);

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

			if ($modify)
			{
				$col_id = str_replace('col_id_', '', $col_field);
				$this->EE->db->where('col_id', $col_id);
				$this->EE->db->update('grid_columns', $column_data);
			}
			else
			{
				$this->EE->db->insert('grid_columns', $column_data);
				$col_id = $this->EE->db->insert_id();
			}

			$ft_api->setup_handler($column['type']);

			if ($ft_api->check_method_exists('grid_settings_modify_column'))
			{
				$settings['col_id'] = $col_id;

				$db_columns = array_merge(
					$db_columns,
					$ft_api->apply('grid_settings_modify_column', array($settings))
				);
			}
			else
			{
				$db_columns['col_id_'.$col_id] = array(
					'type' => 'text',
					'null' => TRUE
				);
				$db_columns['col_ft_'.$col_id] = array(
					'type' => 'tinytext',
					'null' => TRUE
				);
			}

			$this->EE->load->dbforge();

			if ($modify)
			{
				foreach ($db_columns as $key => $value)
				{
					$db_columns[$key]['name'] = $key;
				}
				
				$this->EE->dbforge->modify_column($table_name, $db_columns);
			}
			else
			{
				$this->EE->load->dbforge();
				$this->EE->dbforge->add_column($table_name, $db_columns);
			}

			$count++;
		}
	}

	public function get_columns_for_field($field_id, $settings_forms = FALSE)
	{
		$columns = $this->EE->db->get_where(
			'grid_columns',
			array('field_id' => $field_id))
		->result_array();

		foreach ($columns as &$column)
		{
			$column['col_settings'] = json_decode($column['col_settings'], TRUE);

			if ($settings_forms)
			{
				$column['settings_form'] = $this->get_settings_form($column['col_type'], $column);
			}
		}

		return $columns;
	}

	public function view_for_column($column = NULL, $field_name = NULL)
	{
		$fieldtypes = $this->get_grid_fieldtypes();

		// Create a dropdown-frieldly array of available fieldtypes
		$fieldtypes_dropdown = array();
		foreach ($fieldtypes as $key => $value)
		{
			$fieldtypes_dropdown[$key] = $value['name'];
		}

		$field_name = (empty($column)) ? '[new_0]' : '[col_id_'.$column['col_id'].']';

		if (empty($column))
		{
			$column['settings_form'] = $this->get_settings_form('text');
		}

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
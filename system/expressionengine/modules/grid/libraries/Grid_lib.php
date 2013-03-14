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
	 * TODO: Work with actual saved settings
	 *
	 * @return	array	Array of settings for each Grid-enabled fieldtype
	 */
	public function get_grid_fieldtype_settings_forms()
	{
		$ft_api = $this->EE->api_channel_fields;

		$settings = array();
		foreach ($this->get_grid_fieldtypes() as $field_name => $data)
		{
			$ft_api->setup_handler($field_name);

			// Call grid_display_settings() on each field type
			$settings[$field_name] = $ft_api->apply('grid_display_settings', array('something'));
		}

		return $settings;
	}

	// ------------------------------------------------------------------------
	
	public function apply_settings($settings)
	{
		$table_name = $this->_table_prefix . $settings['field_id'];

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

		foreach ($settings['grid']['cols']['new'] as $key => $column)
		{
			$this->_add_column_to_field($column, $settings['field_id']);
		}
	}

	private function _add_column_to_field($column, $field_id)
	{
		$ft_api = $this->EE->api_channel_fields;
		$ft_api->setup_handler($column['type']);

		$db_columns = array();

		// TODO: insert into columns table, get insert_id for col_id
		$col_id = 1;

		if ($ft_api->check_method_exists('grid_settings_modify_column'))
		{
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
	}
}

/* End of file Grid_lib.php */
/* Location: ./system/expressionengine/modules/grid/libraries/Grid_lib.php */
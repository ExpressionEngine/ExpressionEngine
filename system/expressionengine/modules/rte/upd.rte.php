<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2013, EllisLab, Inc.
 * @license		http://ellislab.com/expressionengine/user-guide/license.html
 * @link		http://ellislab.com
 * @since		Version 2.5
 * @filesource
 */

// --------------------------------------------------------------------

/**
 * ExpressionEngine Rich Text Editor Module
 *
 * @package		ExpressionEngine
 * @subpackage	Modules
 * @category	Modules
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */

class Rte_upd {

	private $name	= 'Rte';
	public $version	= '1.0.1';

	public function __construct()
	{
		$this->EE =& get_instance();
		ee()->load->dbforge();
	}

	// --------------------------------------------------------------------

	/**
	 * Module Installer
	 *
	 * @return	bool
	 */
	public function install()
	{
		// module
		ee()->db->insert(
			'modules',
			array(
				'module_name'		=> $this->name,
				'module_version'	=> $this->version,
				'has_cp_backend'	=> 'y'
			)
		);

		// Actions
		ee()->db->insert_batch(
			'actions',
			array(
				// Build the Toolset JS
				array(
					'class'		=> $this->name,
					'method'	=> 'get_js'
				)
			)
		);

		// RTE Toolsets Table
		$fields = array(
			'toolset_id'	=> array(
				'type'				=> 'int',
				'constraint'		=> '10',
				'unsigned'			=> TRUE,
				'auto_increment'	=> TRUE
			),
			'member_id'			=> array(
				'type'				=> 'int',
				'constraint'		=> '10',
				'default'			=> '0'
			),
			'name'				=> array(
				'type'				=> 'varchar',
				'constraint'		=> '100'
			),
			'tools'				=> array(
				'type'				=> 'text'
			),
			'enabled'			=> array(
				'type'				=> 'char',
				'constraint'		=> '1',
				'default'			=> 'y'
			)
		);
		ee()->dbforge->add_field($fields);
		ee()->dbforge->add_key('toolset_id', TRUE);
		ee()->dbforge->add_key(array('member_id','enabled'));
		ee()->dbforge->create_table('rte_toolsets');

		// RTE Tools Table
		$fields = array(
			'tool_id'	=> array(
				'type'				=> 'int',
				'constraint'		=> '10',
				'unsigned'			=> TRUE,
				'auto_increment'	=> TRUE
			),
			'name'			=> array(
				'type'				=> 'varchar',
				'constraint'		=> '75'
			),
			'class'			=> array(
				'type'				=> 'varchar',
				'constraint'		=> '75'
			),
			'enabled'		=> array(
				'type'				=> 'char',
				'constraint'		=> '1',
				'default'			=> 'y'
			)
		);
		ee()->dbforge->add_field($fields);
		ee()->dbforge->add_key('tool_id', TRUE);
		ee()->dbforge->add_key(array('enabled'));
		ee()->dbforge->create_table('rte_tools');

		// Load the default toolsets
		ee()->load->model('rte_toolset_model');
		ee()->rte_toolset_model->load_default_toolsets();

		// Install the extension
		ee()->db->insert_batch(
			'extensions',
			array(
				array(
					'class'    => $this->name.'_ext',
					'hook'     => 'myaccount_nav_setup',
					'method'   => 'myaccount_nav_setup',
					'settings' => '',
					'priority' => 10,
					'version'  => $this->version,
					'enabled'  => 'y'
				),
				array(
					'class'    => $this->name.'_ext',
					'hook'     => 'cp_menu_array',
					'method'   => 'cp_menu_array',
					'settings' => '',
					'priority' => 10,
					'version'  => $this->version,
					'enabled'  => 'y'
				)
			)
		);

		// Add the member columns
		ee()->dbforge->add_column(
			'members',
			array(
				'rte_enabled'		=> array(
					'type'		=> 'CHAR(1)',
					'null'		=> FALSE,
					'default'	=> 'y'
				),
				'rte_toolset_id'	=> array(
					'type'		=> 'INT(10)',
					'null'		=> FALSE,
					'default'	=> '0'
				)
			)
		);

		// Update the config
		ee()->config->update_site_prefs(
			array(
				'rte_enabled' 				=> 'y',
				'rte_default_toolset_id' 	=> 1
			),
			'all' // Update all sites
		);

		return TRUE;
	}

	// --------------------------------------------------------------------

	/**
	 * Module Uninstaller
	 *
	 * @access	public
	 * @return	bool
	 */
	public function uninstall()
	{
		$module_id = ee()->db->select('module_id')
			->get_where('modules', array( 'module_name' => $this->name ))
			->row('module_id');

		// Member access
		ee()->db->delete(
			'module_member_groups',
			array('module_id' => $module_id)
		);

		// Module
		ee()->db->delete(
			'modules',
			array('module_name' => $this->name)
		);

		// Actions
		ee()->db->where('class', $this->name)
			->or_where('class', $this->name . '_mcp')
			->delete('actions');

		// Extension
		ee()->db->delete(
			'extensions',
			array('class' => $this->name.'_ext')
		);

		// Tables
		ee()->dbforge->drop_table('rte_toolsets');
		ee()->dbforge->drop_table('rte_tools');

		// Remove the member columns
		ee()->dbforge->drop_column('members', 'rte_enabled');
		ee()->dbforge->drop_column('members', 'rte_toolset_id');

		// Update the config
		ee()->config->update_site_prefs(
			array(
				'rte_enabled' 				=> 'n',
				'rte_default_toolset_id' 	=> 1
			),
			'all' // Update all sites
		);

		return TRUE;
	}

	// --------------------------------------------------------------------

	/**
	 * Module Updater
	 *
	 * @access	public
	 * @return	bool
	 */

	public function update($current='')
	{
		// Remove RTE's usage of publish_form_entry_data hook, it is unused
		if (version_compare($current, '1.0.1', '<'))
		{
			ee()->db->delete(
				'extensions',
				array(
					'class'	=> $this->name.'_ext',
					'hook'	=> 'publish_form_entry_data'
				)
			);
		}

		return TRUE;
	}

}
// END CLASS

/* End of file upd.rte.php */
/* Location: ./system/expressionengine/modules/rte/upd.rte.php */
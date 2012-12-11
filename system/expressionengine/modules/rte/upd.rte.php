<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2012, EllisLab, Inc.
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
	public $version	= '1.0';

	public function __construct()
	{
		$this->EE =& get_instance();
		$this->EE->load->dbforge();
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
		$this->EE->db->insert(
			'modules',
			array(
				'module_name'		=> $this->name,
				'module_version'	=> $this->version,
				'has_cp_backend'	=> 'y'
			)
		);
		
		// Actions
		$this->EE->db->insert_batch(
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
		$this->EE->dbforge->add_field($fields);
		$this->EE->dbforge->add_key('toolset_id', TRUE);
		$this->EE->dbforge->add_key(array('member_id','enabled'));
		$this->EE->dbforge->create_table('rte_toolsets');
		
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
		$this->EE->dbforge->add_field($fields);
		$this->EE->dbforge->add_key('tool_id', TRUE);
		$this->EE->dbforge->add_key(array('enabled'));
		$this->EE->dbforge->create_table('rte_tools');
		
		// Load the default toolsets
		$this->EE->load->model('rte_toolset_model');
		$this->EE->rte_toolset_model->load_default_toolsets();
		
		// Install the extension
		$this->EE->db->insert_batch(
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
				),
				array(
					'class'    => $this->name.'_ext',
					'hook'     => 'publish_form_entry_data',
					'method'   => 'publish_form_entry_data',
					'settings' => '',
					'priority' => 10,
					'version'  => $this->version,
					'enabled'  => 'y'
				)
			)
		);
		
		// Add the member columns
		$this->EE->dbforge->add_column(
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
		$this->EE->config->update_site_prefs(
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
		$module_id = $this->EE->db->select('module_id')
			->get_where('modules', array( 'module_name' => $this->name ))
			->row('module_id');
		
		// Member access
		$this->EE->db->delete(
			'module_member_groups',
			array('module_id' => $module_id)
		);		
		
		// Module
		$this->EE->db->delete(
			'modules',
			array('module_name' => $this->name)
		);

		// Actions
		$this->EE->db->where('class', $this->name)
			->or_where('class', $this->name . '_mcp')
			->delete('actions');
		
		// Extension
		$this->EE->db->delete(
			'extensions',
			array('class' => $this->name.'_ext')
		);
		
		// Tables
		$this->EE->dbforge->drop_table('rte_toolsets');
		$this->EE->dbforge->drop_table('rte_tools');

		// Remove the member columns
		$this->EE->dbforge->drop_column('members', 'rte_enabled');
		$this->EE->dbforge->drop_column('members', 'rte_toolset_id');
				
		// Update the config
		$this->EE->config->update_site_prefs(
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
		return TRUE;
	}
	
}
// END CLASS

/* End of file upd.rte.php */
/* Location: ./system/expressionengine/modules/rte/upd.rte.php */
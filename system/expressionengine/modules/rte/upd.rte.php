<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		ExpressionEngine Dev Team
 * @copyright	Copyright (c) 2003 - 2011, EllisLab, Inc.
 * @license		http://expressionengine.com/user_guide/license.html
 * @link		http://expressionengine.com
 * @since		Version 2.0
 * @filesource
 */

// --------------------------------------------------------------------

/**
 * ExpressionEngine Rich Text Editor Module
 *
 * @package		ExpressionEngine
 * @subpackage	Modules
 * @category	Modules
 * @author		Aaron Gustafson
 * @link		http://easy-designs.net
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
		# module
		$this->EE->db->insert(
			'modules',
			array(
				'module_name'		=> $this->name,
				'module_version'	=> $this->version,
				'has_cp_backend'	=> 'y'
			)
		);
		
		# Actions
		$this->EE->db->insert_batch(
			'actions',
			array(
				// Build the Toolset JS
				array(
					'class'		=> $this->name . '_mcp',
					'method'	=> 'build_toolset_js'
				)
			)
		);
		
		# RTE Toolsets Table
		$fields = array(
			'rte_toolset_id'	=> array(
				'type'				=> 'int',
				'constraint'		=> '10',
				'unsigned'			=> TRUE,
				'auto_increment'	=> TRUE
				),
			'site_id'			=> array(
				'type'				=> 'int',
				'constraint'		=> '4'
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
			'rte_tools'			=> array(
				'type'				=> 'text'
				),
			'enabled'			=> array(
				'type'				=> 'char',
				'constraint'		=> '1',
				'default'			=> 'y'
				)
		);
		$this->EE->dbforge->add_field($fields);
		$this->EE->dbforge->add_key('rte_toolset_id', TRUE);
		$this->EE->dbforge->add_key(array('site_id','member_id','enabled'));
		$this->EE->dbforge->create_table('rte_toolsets');
		
		# RTE Tools Table
		$fields = array(
			'rte_tool_id'	=> array(
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
		$this->EE->dbforge->add_key('rte_tool_id', TRUE);
		$this->EE->dbforge->add_key(array('enabled'));
		$this->EE->dbforge->create_table('rte_tools');
		
		# Load the default toolsets
		$this->EE->load->model('rte_toolset_model');
		$this->EE->rte_toolset_model->load_default_toolsets();
		
		# Install the extension
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
		
		#  Add the member columns
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
				
		#  Update the config
		$this->EE->config->_update_config(
			array(
				'rte_enabled' => 'y',
				'rte_forum_enabled' => 'n',
				'rte_default_toolset_id' => '1'
			)
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
		$module_id = $this->EE->db
						->select('module_id')
						->get_where('modules', array( 'module_name' => $this->name ))
						->row('module_id');
		
		# Member access
		$this->EE->db
			->where('module_id', $module_id)
			->delete('module_member_groups');
		
		# Module
		$this->EE->db
			->where('module_name', $this->name)
			->delete('modules');

		# Actions
		$this->EE->db
			->where('class', $this->name)
			->or_where('class', $this->name . '_mcp')
			->delete('actions');
		
		# Extension
		$this->EE->db
			->where('class', $this->name.'_ext')
			->delete('extensions');
		
		# Tables
		$this->EE->dbforge->drop_table('rte_toolsets');
		$this->EE->dbforge->drop_table('rte_tools');

		#  Remove the member columns
		$this->EE->dbforge->drop_column( 'members', 'rte_enabled' );
		$this->EE->dbforge->drop_column( 'members', 'rte_toolset_id' );
				
		#  Update the config
		$this->EE->config->_update_config(
			array(),
			array(
				'rte_enabled' => '',
				'rte_forum_enabled' => '',
				'rte_default_toolset_id' => ''
			)
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
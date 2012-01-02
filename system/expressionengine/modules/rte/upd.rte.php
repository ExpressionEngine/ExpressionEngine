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
		// module
		$data = array(
			'module_name'		=> $this->name,
			'module_version'	=> $this->version,
			'has_cp_backend'	=> 'y'
		);
		$this->EE->db->insert('modules', $data);

		// enable/disable at user level - Ajax call from the Publish/Edit page
		$data = array(
			'class'		=> $this->name . '_mcp',
			'method'	=> 'member_enable'
		);
		$this->EE->db->insert('actions', $data);
		$data = array(
			'class'		=> $this->name . '_mcp',
			'method'	=> 'member_disable'
		);
		$this->EE->db->insert('actions', $data);
		
		// Settings
		$data = array(
			'class'		=> $this->name . '_mcp',
			'method'	=> 'index'
		);
		$this->EE->db->insert('actions', $data);
		
		// Toolsets
		$data = array(
			'class'		=> $this->name . '_mcp',
			'method'	=> 'toolset_builder'
		);
		$this->EE->db->insert('actions', $data);
		
		// RTE Toolsets Table
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
		
		// RTE Tools Table
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
			'version'		=> array(
				'type'				=> 'varchar',
				'constraint'		=> '10'
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
		
		// TODO: Update the tools table
		
		// TODO: Insert the default toolset
				
		//  Update the config
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
		$this->EE->db->select('module_id');
		$query = $this->EE->db->get_where('modules', array('module_name' => $this->name));
		
		// Member access
		$this->EE->db->where('module_id', $query->row('module_id'));
		$this->EE->db->delete('module_member_groups');
		
		// Module
		$this->EE->db->where('module_name', $this->name);
		$this->EE->db->delete('modules');

		// Actions
		$this->EE->db->where('class', $this->name);
		$this->EE->db->delete('actions');
		$this->EE->db->where('class', $this->name . '_mcp');
		$this->EE->db->delete('actions');
		
		// Tables
		$this->EE->dbforge->drop_table('rte_toolsets');
		$this->EE->dbforge->drop_table('rte_tools');

		//  Update the config
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
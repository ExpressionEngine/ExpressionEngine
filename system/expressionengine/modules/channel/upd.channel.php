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
 * ExpressionEngine Channel Module
 *
 * @package		ExpressionEngine
 * @subpackage	Modules
 * @category	Modules
 * @author		ExpressionEngine Dev Team
 * @link		http://expressionengine.com
 */

class Channel_upd {

	var $version		= '2.0.1';

	function Channel_upd()
	{
		// Make a local reference to the ExpressionEngine super object
		$this->EE =& get_instance();
	}

	// --------------------------------------------------------------------

	/**
	 * Module Installer
	 *
	 * @access	public
	 * @return	bool
	 */
	function install()
	{
		$data = array(
					'module_name' => 'Channel',
					'module_version' => $this->version,
					'has_cp_backend' => 'n'
					);

		$this->EE->db->insert('modules', $data);

		$data = array(
					'class' => 'Channel',
					'method' => 'insert_new_entry'
					);
					
		$this->EE->db->insert('actions', $data);			
					
		$data = array(
					'class' => 'Channel',
					'method' => 'filemanager_endpoint'
					);
					
		$this->EE->db->insert('actions', $data);
		
		$data = array(
					'class' => 'Channel',
					'method' => 'smiley_pop'
					);
					
		$this->EE->db->insert('actions', $data);

		return TRUE;
	}

	// --------------------------------------------------------------------

	/**
	 * Module Uninstaller
	 *
	 * @access	public
	 * @return	bool
	 */
	function uninstall()
	{
		$this->EE->db->select('module_id');
		$this->EE->db->from('modules');
		$this->EE->db->where('module_name', 'Channel');
		$query = $this->EE->db->get();

		$this->EE->db->delete('module_member_groups', array('module_id' => $query->row('module_id')));
		$this->EE->db->delete('modules', array('module_name' => 'Channel'));
		$this->EE->db->delete('actions', array('class' => 'Channel'));
		$this->EE->db->delete('actions', array('class' => 'Channel_mcp'));

		return TRUE;
	}

	// --------------------------------------------------------------------

	/**
	 * Module Updater
	 *
	 * @access	public
	 * @return	bool
	 */
	function update()
	{
		return TRUE;
	}

}
// END CLASS

/* End of file upd.channel.php */
/* Location: ./system/expressionengine/modules/channel/upd.channel.php */
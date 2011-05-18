<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
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
 
// ------------------------------------------------------------------------

/**
 * ExpressionEngine Query Module
 *
 * @package		ExpressionEngine
 * @subpackage	Modules
 * @category	Update File
 * @author		ExpressionEngine Dev Team
 * @link		http://expressionengine.com
 */

class Query_upd {

	var $version = '2.0';

	function Query_upd()
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
			'module_name' 	 => 'Query',
			'module_version' => $this->version,
			'has_cp_backend' => 'n'
		);

		$this->EE->db->insert('modules', $data);
		
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
		$query = $this->EE->db->get_where('modules', array('module_name' => 'Query'));
		$module_id = $query->row('module_id');
				
		$this->EE->db->where('module_id', $module_id);
		$this->EE->db->delete('module_member_groups');

		$this->EE->db->where('module_name', 'Query');
		$this->EE->db->delete('modules');

		$this->EE->db->where('class', 'Query');
		$this->EE->db->delete('actions');

		$this->EE->db->where('class', 'Query_mcp');
		$this->EE->db->delete('actions');

		return TRUE;
	}
	
	// --------------------------------------------------------------------

	/**
	 * Module Updater
	 *
	 * @access	public
	 * @return	bool
	 */	
	function update($current='')
	{
		return FALSE;
	}

}
// END CLASS

/* End of file upd.query.php */
/* Location: ./system/expressionengine/modules/query/upd.query.php */
<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

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

// --------------------------------------------------------------------

/**
 * ExpressionEngine RSS Module
 *
 * @package		ExpressionEngine
 * @subpackage	Modules
 * @category	Modules
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */
class Rss_upd {

	var $version = '2.0';

	public function __construct()
	{
		$this->EE =& get_instance();
	}

	// --------------------------------------------------------------------

	/**
	 * Module Installer
	 *
	 * @return	bool
	 */	
	public function install()
	{
		$sql[] = "INSERT INTO exp_modules (module_name, module_version, has_cp_backend) VALUES ('Rss', '$this->version', 'n')";
	
		foreach ($sql as $query)
		{
			ee()->db->query($query);
		}
		
		return TRUE;
	}
	
	// --------------------------------------------------------------------

	/**
	 * Module Uninstaller
	 *
	 * @return	bool
	 */	
	public function uninstall()
	{
		$query = ee()->db->query("SELECT module_id FROM exp_modules WHERE module_name = 'Rss'"); 
				
		$sql[] = "DELETE FROM exp_module_member_groups WHERE module_id = '".$query->row('module_id') ."'";		
		$sql[] = "DELETE FROM exp_modules WHERE module_name = 'Rss'";
		$sql[] = "DELETE FROM exp_actions WHERE class = 'Rss'";
		$sql[] = "DELETE FROM exp_actions WHERE class = 'Rss_mcp'";

		foreach ($sql as $query)
		{
			ee()->db->query($query);
		}

		return TRUE;
	}
	
	// --------------------------------------------------------------------

	/**
	 * Module Updater
	 *
	 * @return	bool
	 */	
	
	public function update($current='')
	{
		return FALSE;
	}

}
// END CLASS

/* End of file upd.rss.php */
/* Location: ./system/expressionengine/modules/rss/upd.rss.php */
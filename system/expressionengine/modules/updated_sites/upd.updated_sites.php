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
 * ExpressionEngine Updated Sites Module
 *
 * @package		ExpressionEngine
 * @subpackage	Modules
 * @category	Update File
 * @author		ExpressionEngine Dev Team
 * @link		http://expressionengine.com
 */

class Updated_sites_upd {

	var $version = '2.0';
	
	function Updated_sites_upd()
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
		$sql[] = "INSERT INTO exp_modules 
				  (module_name, module_version, has_cp_backend) 
				  VALUES 
				  ('Updated_sites', '$this->version', 'y')";
				  
		$sql[] = "INSERT INTO exp_actions (class, method) VALUES ('Updated_sites', 'incoming')";
		
		$sql[] = "CREATE TABLE IF NOT EXISTS `exp_updated_sites` (
				 `updated_sites_id` int(5) unsigned NOT NULL auto_increment,
				 `updated_sites_pref_name` varchar(80) NOT NULL default '',
				 `updated_sites_short_name` varchar(60) NOT NULL default '',
				 `updated_sites_allowed` text NOT NULL,
				 `updated_sites_prune` int(6) NOT NULL default '0',
				 PRIMARY KEY `updated_sites_id` (`updated_sites_id`))
				DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci;";	
				 
		$sql[] = "CREATE TABLE IF NOT EXISTS `exp_updated_site_pings` (
				 `ping_id` int(10) unsigned NOT NULL auto_increment,
				 `ping_site_name` varchar(80) NOT NULL default '',
				 `ping_site_url` varchar(80) NOT NULL default '',
				 `ping_site_check` varchar(80) NOT NULL default '',
				 `ping_site_rss` varchar(80) NOT NULL default '',
				 `ping_date` int(10) NOT NULL default '0',
				 `ping_ipaddress` varchar(16) NOT NULL default '',
				 `ping_config_id` int(4) NOT NULL default '1',
				 PRIMARY KEY `ping_id` (`ping_id`),
				 KEY `ping_config_id` (`ping_config_id`))
				DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci;";	
				 
 		$sql[] = "INSERT INTO exp_updated_sites 
 				  (updated_sites_pref_name, updated_sites_short_name, updated_sites_allowed, updated_sites_prune) 
 				  VALUES ('Default', 'default', '', '500')";

		foreach ($sql as $query)
		{
			$this->EE->db->query($query);
		}
		
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
		$query = $this->EE->db->query("SELECT module_id FROM exp_modules WHERE module_name = 'Updated_sites'"); 
				
		$sql[] = "DELETE FROM exp_module_member_groups WHERE module_id = '".$query->row('module_id') ."'";		
		$sql[] = "DELETE FROM exp_modules WHERE module_name = 'Updated_sites'";
		$sql[] = "DELETE FROM exp_actions WHERE class = 'Updated_sites'";
		$sql[] = "DROP TABLE IF EXISTS exp_updated_sites";
		$sql[] = "DROP TABLE IF EXISTS exp_updated_site_pings";

		foreach ($sql as $query)
		{
			$this->EE->db->query($query);
		}

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
		return TRUE;
	}
}


/* End of file upd.updated_sites.php */
/* Location: ./system/expressionengine/modules/updated_sites/upd.updated_sites.php */
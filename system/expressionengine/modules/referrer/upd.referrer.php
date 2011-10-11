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
 * ExpressionEngine Referrer Module
 *
 * @package		ExpressionEngine
 * @subpackage	Modules
 * @category	Update File
 * @author		ExpressionEngine Dev Team
 * @link		http://expressionengine.com
 */

class Referrer_upd {

	var $version = '2.0';
	
	function Referrer_upd()
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
		$this->EE->load->dbforge();
		
		$fields = array(
						'ref_id'			  => array(	'type' 			 => 'int',
														'constraint'	 => '10',
														'unsigned'		 => TRUE,
														'auto_increment' => TRUE),
						'site_id'			  => array(	'type'			=> 'int',
														'constraint'	=> '4',
														'default'		=> 1),
						'ref_from'			  => array(	'type'			=> 'varchar',
														'constraint'	=> '150'),
						'ref_to'			  => array(	'type'			=> 'varchar',
														'constraint'	=> '120'),
						'ref_ip'			  => array('type' => 'varchar' , 'constraint' => '16'),
						'ref_date'			  => array(	'type' 			 => 'int',
														'constraint'	 => '10',
														'unsigned'		 => TRUE,
														'default'		 => 0),
						'ref_agent'			  => array('type' => 'varchar' , 'constraint' => '100'),
		);
		
		$this->EE->dbforge->add_field($fields);
		$this->EE->dbforge->add_key(array('ref_id'), TRUE);
		$this->EE->dbforge->add_key(array('site_id'));
		$this->EE->dbforge->create_table('referrers');
		
		$sql[] = "INSERT INTO exp_modules (module_name, module_version, has_cp_backend) VALUES ('Referrer', '$this->version', 'y')";
	
		foreach ($sql as $query)
		{
			$this->EE->db->query($query);
		}
		
		// turn on referrer tracking
		if ($this->EE->config->item('site_id') === FALSE)
		{
			// site_id will not be defined in the application installation wizard
			$this->EE->config->update_site_prefs(array('log_referrers' => 'y'), 1);
		}
		else
		{
			$this->EE->config->update_site_prefs(array('log_referrers' => 'y'));
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
		$this->EE->load->dbforge();
		
		$this->EE->dbforge->drop_table('referrers');
		
		$query = $this->EE->db->query("SELECT module_id FROM exp_modules WHERE module_name = 'Referrer'"); 
				
		$sql[] = "DELETE FROM exp_module_member_groups WHERE module_id = '".$query->row('module_id') ."'";
		$sql[] = "DELETE FROM exp_modules WHERE module_name = 'Referrer'";
		$sql[] = "DELETE FROM exp_actions WHERE class = 'Referrer'";
		$sql[] = "DELETE FROM exp_actions WHERE class = 'Referrer_mcp'";

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
		if (version_compare($current, $this->version, '=='))
		{
			return FALSE;
		}
		
		if (version_compare($current, '2.0', '<'))
		{
			$this->EE->db->query("ALTER TABLE `exp_referrers` DROP COLUMN `user_blog`");
			$this->EE->db->query("ALTER TABLE `exp_referrers` CHANGE `ref_from` `ref_from` VARCHAR(150) NOT NULL");
		}
		
		return TRUE;
	}
}
// END CLASS

/* End of file upd.referrer.php */
/* Location: ./system/expressionengine/modules/referrer/upd.referrer.php */
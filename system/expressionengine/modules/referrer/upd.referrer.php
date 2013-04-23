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
 * ExpressionEngine Referrer Module
 *
 * @package		ExpressionEngine
 * @subpackage	Modules
 * @category	Update File
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */

class Referrer_upd {

	var $version = '2.1.1';
	
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
		ee()->load->dbforge();
		
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
						'ref_ip'			  => array('type' => 'varchar' , 'constraint' => '45'),
						'ref_date'			  => array(	'type' 			 => 'int',
														'constraint'	 => '10',
														'unsigned'		 => TRUE,
														'default'		 => 0),
						'ref_agent'			  => array('type' => 'varchar' , 'constraint' => '100'),
		);
		
		ee()->dbforge->add_field($fields);
		ee()->dbforge->add_key(array('ref_id'), TRUE);
		ee()->dbforge->add_key(array('site_id'));
		ee()->dbforge->create_table('referrers');
		
		$sql[] = "INSERT INTO exp_modules (module_name, module_version, has_cp_backend) VALUES ('Referrer', '$this->version', 'y')";
	
		foreach ($sql as $query)
		{
			ee()->db->query($query);
		}
		
		// turn on referrer tracking
		if (ee()->config->item('site_id') === FALSE)
		{
			// site_id will not be defined in the application installation wizard
			ee()->config->update_site_prefs(array('log_referrers' => 'y'), 1);
		}
		else
		{
			ee()->config->update_site_prefs(array('log_referrers' => 'y'));
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
		ee()->load->dbforge();
		
		ee()->dbforge->drop_table('referrers');
		
		$query = ee()->db->query("SELECT module_id FROM exp_modules WHERE module_name = 'Referrer'"); 
				
		$sql[] = "DELETE FROM exp_module_member_groups WHERE module_id = '".$query->row('module_id') ."'";
		$sql[] = "DELETE FROM exp_modules WHERE module_name = 'Referrer'";
		$sql[] = "DELETE FROM exp_actions WHERE class = 'Referrer'";
		$sql[] = "DELETE FROM exp_actions WHERE class = 'Referrer_mcp'";

		foreach ($sql as $query)
		{
			ee()->db->query($query);
		}

		// turn off referrer tracking
   		ee()->config->update_site_prefs(array('log_referrers' => 'n'), 'all');

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
			ee()->db->query("ALTER TABLE `exp_referrers` DROP COLUMN `user_blog`");
			ee()->db->query("ALTER TABLE `exp_referrers` CHANGE `ref_from` `ref_from` VARCHAR(150) NOT NULL");
		}
	
		if (version_compare($current, '2.1.1', '<'))
		{
			// Update ip_address column
			ee()->dbforge->modify_column(
				'referrers',
				array(
					'ref_ip' => array(
						'name' 			=> 'ref_ip',
						'type' 			=> 'varchar',
						'constraint'	=> '45'
					)
				)
			);
		}

		return TRUE;
	}
}
// END CLASS

/* End of file upd.referrer.php */
/* Location: ./system/expressionengine/modules/referrer/upd.referrer.php */
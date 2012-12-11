<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2012, EllisLab, Inc.
 * @license		http://ellislab.com/expressionengine/user-guide/license.html
 * @link		http://ellislab.com
 * @since		Version 2.0
 * @filesource
 */
 
// ------------------------------------------------------------------------

/**
 * ExpressionEngine IP To Nation Module
 *
 * @package		ExpressionEngine
 * @subpackage	Modules
 * @category	Update File
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */

class Ip_to_nation_upd {

	var $version = '3.0';

	/**
	  * Constructor
	  */
	function __construct()
	{
		$this->EE =& get_instance();
		$this->EE->load->dbforge();
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
		$this->EE->dbforge->drop_table('ip2nation');

		$fields = array(
			'ip_range_low' => array(
				'type'			=> 'VARBINARY',
				'constraint'	=> 16,
				'null'			=> FALSE,
				'default'		=> 0
			),
			'ip_range_high' => array(
				'type'			=> 'VARBINARY',
				'constraint'	=> 16,
				'null'			=> FALSE,
				'default'		=> 0
			),
			'country' => array(
				'type' 			=> 'char',
				'constraint'	=> 2,
				'null'			=> FALSE,
				'default'		=> ''
			)
		);

		$this->EE->dbforge->add_field('id');
		$this->EE->dbforge->add_field($fields);
		$this->EE->dbforge->add_key(array('ip_range_low', 'ip_range_high'));
		$this->EE->dbforge->create_table('ip2nation');

		$this->EE->dbforge->drop_table('ip2nation_countries');

		$fields = array(
			'code'	=> array(
				'type'			=> 'varchar',
				'constraint'	=> 2,
				'null'			=> FALSE,
				'default'		=> ''
			),
			'banned'  => array(
				'type' 			=> 'varchar',
				'constraint'	=> 1,
				'null'			=> FALSE,
				'default'		=> 'n'
			)
		);

		$this->EE->dbforge->add_field($fields);
		$this->EE->dbforge->add_key('code', TRUE);
		$this->EE->dbforge->create_table('ip2nation_countries');

		$data = array(
			'module_name' 	 => 'Ip_to_nation',
			'module_version' => $this->version,
			'has_cp_backend' => 'y'
		);

		$this->EE->db->insert('modules', $data);

		$this->EE->config->_update_config(array(
			'ip2nation' => 'y',
			'ip2nation_db_date' => 1335677198
		));

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
		$query = $this->EE->db->get_where('modules', array('module_name' => 'Ip_to_nation'));
		$module_id_row = $query->row();
		$module_id = $module_id_row->module_id;

		$this->EE->db->where('module_id', $module_id);
		$this->EE->db->delete('module_member_groups');

		$this->EE->db->where('module_name', 'Ip_to_nation');
		$this->EE->db->delete('modules');

		$this->EE->db->where('class', 'Ip_to_nation');
		$this->EE->db->delete('actions');

		$this->EE->db->where('class', 'Ip_to_nation');
		$this->EE->db->delete('actions');

		$this->EE->dbforge->drop_table('ip2nation');
		$this->EE->dbforge->drop_table('ip2nation_countries');

		//  Remove a couple items from the file
		
		$this->EE->config->_update_config(
			array(),
			array(
				'ip2nation' => '',
				'ip2nation_db_date' => ''
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
	function update($current='')
	{
		if ($current == '' OR version_compare($current, $this->version, '=='))
		{
			return FALSE;
		}

		if (version_compare($current, '2.0', '<'))
		{
			// can't use this column as a Primary Key because the ip2nation db has duplicate values in the ip column ::sigh::
//			$this->EE->db->query("ALTER TABLE `exp_ip2nation` DROP KEY `ip`");
//			$this->EE->db->query("ALTER TABLE `exp_ip2nation` ADD PRIMARY KEY `ip` (`ip`)");
			$this->EE->db->query("ALTER TABLE `exp_ip2nation_countries` DROP KEY `code`");
			$this->EE->db->query("ALTER TABLE `exp_ip2nation_countries` ADD PRIMARY KEY `code` (`code`)");
		}

		// Version 2.2 (02/27/2010) and 2.3 (11/19/2010) used an included sql file from ip2nation.com
		// File is no longer included and table truncated in 3.0, so removing that code
		// They should update IP lists via CP going forward


		// Version 3 switches to the MaxMind Geolite dataset for
		// IPv6 ip address support. This requires a significant schema
		// change to efficiently split the data.
		if (version_compare($current, '3.0', '<'))
		{
			// clear the ip data
			$this->EE->db->truncate('ip2nation');

			// next, change the ip column to support IPv6 sizes
			// and change the name since we now do range queries
			$this->EE->dbforge->modify_column('ip2nation', array(
				'ip' => array(
					'name' => 'ip_range_low',
					'type' => 'VARBINARY',
					'constraint' => 16,
					'null' => FALSE,
					'default' => 0
				)
			));

			// and add a column for the upper end of the range
			$this->EE->dbforge->add_column('ip2nation', array(
				'ip_range_high' => array(
					'type' => 'VARBINARY',
					'constraint' => 16,
					'null' => FALSE,
					'default' => 0
				)
			));
		}
		
		return TRUE;
	}

}
// END CLASS

/* End of file upd.ip_to_nation.php */
/* Location: ./system/expressionengine/modules/ip_to_nation/upd.ip_to_nation.php */
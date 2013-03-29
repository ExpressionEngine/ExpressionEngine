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
		ee()->load->dbforge();
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
		ee()->dbforge->drop_table('ip2nation');

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

		ee()->dbforge->add_field('id');
		ee()->dbforge->add_field($fields);
		ee()->dbforge->add_key(array('ip_range_low', 'ip_range_high'));
		ee()->dbforge->create_table('ip2nation');

		ee()->dbforge->drop_table('ip2nation_countries');

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

		ee()->dbforge->add_field($fields);
		ee()->dbforge->add_key('code', TRUE);
		ee()->dbforge->create_table('ip2nation_countries');

		$data = array(
			'module_name' 	 => 'Ip_to_nation',
			'module_version' => $this->version,
			'has_cp_backend' => 'y'
		);

		ee()->db->insert('modules', $data);

		ee()->config->_update_config(array(
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
		ee()->db->select('module_id');
		$query = ee()->db->get_where('modules', array('module_name' => 'Ip_to_nation'));
		$module_id_row = $query->row();
		$module_id = $module_id_row->module_id;

		ee()->db->where('module_id', $module_id);
		ee()->db->delete('module_member_groups');

		ee()->db->where('module_name', 'Ip_to_nation');
		ee()->db->delete('modules');

		ee()->db->where('class', 'Ip_to_nation');
		ee()->db->delete('actions');

		ee()->db->where('class', 'Ip_to_nation');
		ee()->db->delete('actions');

		ee()->dbforge->drop_table('ip2nation');
		ee()->dbforge->drop_table('ip2nation_countries');

		//  Remove a couple items from the file
		
		ee()->config->_update_config(
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
//			ee()->db->query("ALTER TABLE `exp_ip2nation` DROP KEY `ip`");
//			ee()->db->query("ALTER TABLE `exp_ip2nation` ADD PRIMARY KEY `ip` (`ip`)");
			ee()->db->query("ALTER TABLE `exp_ip2nation_countries` DROP KEY `code`");
			ee()->db->query("ALTER TABLE `exp_ip2nation_countries` ADD PRIMARY KEY `code` (`code`)");
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
			ee()->db->truncate('ip2nation');

			// next, change the ip column to support IPv6 sizes
			// and change the name since we now do range queries
			ee()->dbforge->modify_column('ip2nation', array(
				'ip' => array(
					'name' => 'ip_range_low',
					'type' => 'VARBINARY',
					'constraint' => 16,
					'null' => FALSE,
					'default' => 0
				)
			));

			// and add a column for the upper end of the range
			ee()->dbforge->add_column('ip2nation', array(
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
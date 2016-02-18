<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2016, EllisLab, Inc.
 * @license		https://expressionengine.com/license
 * @link		https://ellislab.com
 * @since		Version 1.0
 * @filesource
 */

// --------------------------------------------------------------------

/**
 * ExpressionEngine Search Module
 *
 * @package		ExpressionEngine
 * @subpackage	Modules
 * @category	Modules
 * @author		EllisLab Dev Team
 * @link		https://ellislab.com
 */

class Search_upd {

	var $version = '2.2.2';

	/**
	 * Module Installer
	 *
	 * @access	public
	 * @return	bool
	 */
	function install()
	{
		$sql[] = "INSERT INTO exp_modules (module_name, module_version, has_cp_backend) VALUES ('Search', '$this->version', 'n')";
		$sql[] = "INSERT INTO exp_actions (class, method, csrf_exempt) VALUES ('Search', 'do_search', 1)";
		$sql[] = "CREATE TABLE IF NOT EXISTS exp_search (
					 search_id varchar(32) NOT NULL,
					 site_id INT(4) NOT NULL DEFAULT 1,
					 search_date int(10) NOT NULL,
					 keywords varchar(60) NOT NULL,
					 member_id int(10) unsigned NOT NULL,
					 ip_address varchar(45) NOT NULL,
					 total_results int(6) NOT NULL,
					 per_page tinyint(3) unsigned NOT NULL,
					 query mediumtext NULL DEFAULT NULL,
					 custom_fields mediumtext NULL DEFAULT NULL,
					 result_page varchar(70) NOT NULL,
					 PRIMARY KEY `search_id` (`search_id`),
					 KEY `site_id` (`site_id`)
					) CHARACTER SET utf8 COLLATE utf8_unicode_ci";

		$sql[] = "CREATE TABLE IF NOT EXISTS exp_search_log (
					id int(10) NOT NULL auto_increment,
					site_id INT(4) UNSIGNED NOT NULL DEFAULT 1,
					member_id int(10) unsigned NOT NULL,
					screen_name varchar(50) NOT NULL,
					ip_address varchar(45) default '0' NOT NULL,
					search_date int(10) NOT NULL,
					search_type varchar(32) NOT NULL,
					search_terms varchar(200) NOT NULL,
					PRIMARY KEY `id` (`id`),
					KEY `site_id` (`site_id`)
					) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci";

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
	 * @access	public
	 * @return	bool
	 */
	function uninstall()
	{
		ee()->load->dbforge();

		$query = ee()->db->query("SELECT module_id FROM exp_modules WHERE module_name = 'Search'");

		$sql[] = "DELETE FROM exp_module_member_groups WHERE module_id = '".$query->row('module_id') ."'";
		$sql[] = "DELETE FROM exp_modules WHERE module_name = 'Search'";
		$sql[] = "DELETE FROM exp_actions WHERE class = 'Search'";
		$sql[] = "DELETE FROM exp_actions WHERE class = 'Search_mcp'";

		ee()->dbforge->drop_table('search');
		ee()->dbforge->drop_table('search_log');

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
	 * @access	public
	 * @return	bool
	 */

	function update($current='')
	{
		if (version_compare($current, '2.1', '<'))
		{
			ee()->load->library('utf8_db_convert');

			ee()->utf8_db_convert->do_conversion(array(
				'exp_search_log', 'exp_search'
			));
		}

		if (version_compare($current, '2.2', '<'))
		{
			// Update ip_address column
			ee()->load->dbforge();

			$tables = array('search', 'search_log');

			foreach ($tables as $table)
			{
				$column_settings = array(
					'ip_address' => array(
						'name' 			=> 'ip_address',
						'type' 			=> 'varchar',
						'constraint'	=> '45',
						'default'		=> '0',
						'null'			=> FALSE
					)
				);

				if ($table == 'search')
				{
					unset($column_settings['ip_address']['default']);
				}

				ee()->dbforge->modify_column($table, $column_settings);
			}
		}

		if (version_compare($current, '2.2.1', '<'))
		{
			ee()->load->library('smartforge');

			$fields = array(
				'site_id'		=> array('type' => 'int',		'constraint' => '4',	'null' => FALSE,	'default' => 1),
				'per_page'		=> array('type' => 'tinyint',	'constraint' => '3',	'unsigned' => TRUE,	'null' => FALSE),
			);

			ee()->smartforge->modify_column('search', $fields);

			ee()->smartforge->add_key('search', 'site_id');
		}

		if (version_compare($current, '2.2.2', '<'))
		{
			// Make searches exempt from CSRF check.
			ee()->db->where('class', 'Search')
				->where('method', 'do_search')
				->update('actions', array('csrf_exempt' => 1));
		}

		return TRUE;
	}

}
// END CLASS

// EOF

<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2018, EllisLab, Inc. (https://ellislab.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

/**
 * Blacklist update class
 */
class Blacklist_upd {

	var $version	= '3.0.1';

	/**
	 * Module Installer
	 *
	 * @access	public
	 * @return	bool
	 */

	function install()
	{
		ee()->load->dbforge();

		$data = array(
			'module_name' 	 => 'Blacklist',
			'module_version' => $this->version,
			'has_cp_backend' => 'y'
		);

		ee()->db->insert('modules', $data);

		$fields = array(
			'blacklisted_id'	=> array(
				'type'				=> 'int',
				'constraint'		=> 10,
				'unsigned'			=> TRUE,
				'auto_increment'	=> TRUE
			),
			'blacklisted_type'  => array(
				'type' 				=> 'varchar',
				'constraint'		=> '20',
			),
			'blacklisted_value' => array(
				'type'				=> 'longtext'
			)
		);

		ee()->dbforge->add_field($fields);
		ee()->dbforge->add_key('blacklisted_id', TRUE);
		ee()->dbforge->create_table('blacklisted');

		$fields = array(
			'whitelisted_id'	=> array(
				'type'				=> 'int',
				'constraint'		=> 10,
				'unsigned'			=> TRUE,
				'auto_increment'	=> TRUE
			),
			'whitelisted_type'  => array(
				'type' 				=> 'varchar',
				'constraint'		=> '20',
			),
			'whitelisted_value' => array(
				'type'				=> 'longtext'
			)
		);

		ee()->dbforge->add_field($fields);
		ee()->dbforge->add_key('whitelisted_id', TRUE);
		ee()->dbforge->create_table('whitelisted');

		return TRUE;
	}

	/**
	 * Module Uninstaller
	 *
	 * @access	public
	 * @return	bool
	 */
	function uninstall()
	{
		ee()->load->dbforge();

		ee()->db->select('module_id');
		$query = ee()->db->get_where('modules', array('module_name' => 'Blacklist'));
		$module_id_row = $query->row();
		$module_id = $module_id_row->module_id;

		ee()->db->where('module_id', $module_id);
		ee()->db->delete('module_member_groups');

		ee()->db->where('module_name', 'Blacklist');
		ee()->db->delete('modules');

		ee()->db->where('class', 'Blacklist');
		ee()->db->delete('actions');

		ee()->db->where('class', 'Blacklist_mcp');
		ee()->db->delete('actions');

		ee()->dbforge->drop_table('blacklisted');
		ee()->dbforge->drop_table('whitelisted');

		return TRUE;
	}

	/**
	 * Module Updater
	 *
	 * @access	public
	 * @return	bool
	 */
	function update($current='')
	{
		if (version_compare($current, '3.0.1', '<'))
		{
			ee()->load->dbforge();

			foreach (array('blacklisted', 'whitelisted') as $table_name)
			{
				if (ee()->db->table_exists($table_name))
				{
					$fields = array(
						$table_name.'_value' => array(
							'name' => $table_name.'_value',
							'type' => 'LONGTEXT'
						)
					);

					ee()->dbforge->modify_column($table_name, $fields);
				}
			}
		}

		if (version_compare($current, '3.0', '<'))
		{
			ee()->load->dbforge();

			$sql = array();

			//if the are using a very old version this table won't exist at all
			if ( ! ee()->db->table_exists('whitelisted'))
			{
				$fields = array(
					'whitelisted_id'	=> array(
						'type'				=> 'int',
						'constraint'		=> 10,
						'unsigned'			=> TRUE,
						'auto_increment'	=> TRUE
					),
					'whitelisted_type'  => array(
						'type' 		 => 'varchar',
						'constraint' => '20',
					),
					'whitelisted_value' => array(
						'type' => 'text'
					)
				);

				ee()->dbforge->add_field($fields);
				ee()->dbforge->add_key('whitelisted_id', TRUE);
				ee()->dbforge->create_table('whitelisted');
			}
			else
			{
				$sql[] = "ALTER TABLE `exp_blacklisted` ADD COLUMN `blacklisted_id` int(10) UNSIGNED AUTO_INCREMENT NOT NULL PRIMARY KEY FIRST";
				$sql[] = "ALTER TABLE `exp_whitelisted` ADD COLUMN `whitelisted` int(10) UNSIGNED AUTO_INCREMENT NOT NULL PRIMARY KEY FIRST";
			}

			foreach($sql as $query)
			{
				ee()->db->query($query);
			}
		}

		return TRUE;
	}
}

// END CLASS

// EOF

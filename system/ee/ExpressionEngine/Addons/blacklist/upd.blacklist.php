<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2020, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

use ExpressionEngine\Service\Addon\Installer;

/**
 * Blacklist update class
 */
class Blacklist_upd extends Installer
{
	public $has_cp_backend = 'y';

    public function __construct()
    {
        parent::__construct();
    }

	/**
	 * Module Installer
	 *
	 * @access	public
	 * @return	bool
	 */

	function install()
	{
		$installed = parent::install();
        if ($installed) {

			ee()->load->dbforge();

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

		}
        return $installed;
	}

	/**
	 * Module Uninstaller
	 *
	 * @access	public
	 * @return	bool
	 */
	function uninstall()
	{
		$uninstalled = parent::uninstall();
        if ($uninstalled) {
			ee()->load->dbforge();
			ee()->dbforge->drop_table('blacklisted');
			ee()->dbforge->drop_table('whitelisted');
        }
        return $uninstalled;
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

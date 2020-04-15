<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2019, EllisLab Corp. (https://ellislab.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

use EllisLab\ExpressionEngine\Service\Addon\Installer;

/**
 * Rich Text Editor Module update class
 */
class Rte_upd extends Installer
{
	public $has_cp_backend = 'y';

	public $actions = [
        [
            'method' => 'get_js'
        ]
	];

	public $methods = [
		[
			'hook' => 'myaccount_nav_setup'
		],
		[
			'hook' => 'cp_menu_array'
		]
	];

	public function __construct()
	{
		parent::__construct();

		ee()->load->dbforge();
	}

	/**
	 * Module Installer
	 *
	 * @return	bool
	 */
	public function install()
	{
		parent::install();

		// RTE Toolsets Table
		$fields = array(
			'toolset_id'	=> array(
				'type'				=> 'int',
				'constraint'		=> '10',
				'unsigned'			=> TRUE,
				'auto_increment'	=> TRUE
			),
			'member_id'			=> array(
				'type'				=> 'int',
				'constraint'		=> '10',
				'default'			=> '0'
			),
			'name'				=> array(
				'type'				=> 'varchar',
				'constraint'		=> '100'
			),
			'tools'				=> array(
				'type'				=> 'text'
			),
			'enabled'			=> array(
				'type'				=> 'char',
				'constraint'		=> '1',
				'default'			=> 'y'
			)
		);
		ee()->dbforge->add_field($fields);
		ee()->dbforge->add_key('toolset_id', TRUE);
		ee()->dbforge->add_key(array('member_id','enabled'));
		ee()->dbforge->create_table('rte_toolsets');

		// RTE Tools Table
		$fields = array(
			'tool_id'	=> array(
				'type'				=> 'int',
				'constraint'		=> '10',
				'unsigned'			=> TRUE,
				'auto_increment'	=> TRUE
			),
			'name'			=> array(
				'type'				=> 'varchar',
				'constraint'		=> '75'
			),
			'class'			=> array(
				'type'				=> 'varchar',
				'constraint'		=> '75'
			),
			'enabled'		=> array(
				'type'				=> 'char',
				'constraint'		=> '1',
				'default'			=> 'y'
			)
		);
		ee()->dbforge->add_field($fields);
		ee()->dbforge->add_key('tool_id', TRUE);
		ee()->dbforge->add_key(array('enabled'));
		ee()->dbforge->create_table('rte_tools');

		// Load the default toolsets
		ee()->load->model('rte_toolset_model');
		ee()->rte_toolset_model->load_default_toolsets();

		// Install the extension
		parent::activate_extension();

		// Update the config
		ee()->config->update_site_prefs(
			array(
				'rte_enabled' 				=> 'y',
				'rte_default_toolset_id' 	=> 1
			),
			'all' // Update all sites
		);

		return TRUE;
	}

	/**
	 * Module Uninstaller
	 *
	 * @access	public
	 * @return	bool
	 */
	public function uninstall()
	{
		parent::uninstall();

		parent::disable_extension();

		// Tables
		ee()->dbforge->drop_table('rte_toolsets');
		ee()->dbforge->drop_table('rte_tools');

		// Update the config
		ee()->config->update_site_prefs(
			array(
				'rte_enabled' 				=> 'n',
				'rte_default_toolset_id' 	=> 1
			),
			'all' // Update all sites
		);

		return TRUE;
	}

	/**
	 * Module Updater
	 *
	 * @access	public
	 * @return	bool
	 */

	public function update($current='')
	{
		// Remove RTE's usage of publish_form_entry_data hook, it is unused
		if (version_compare($current, '1.0.1', '<'))
		{
			ee()->db->delete(
				'extensions',
				array(
					'class'	=> $this->name.'_ext',
					'hook'	=> 'publish_form_entry_data'
				)
			);
		}

		return TRUE;
	}

}
// END CLASS

// EOF

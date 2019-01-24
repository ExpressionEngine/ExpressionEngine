<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2019, EllisLab Corp. (https://ellislab.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace EllisLab\ExpressionEngine\Updater\Version_3_4_0;

/**
 * Update
 */
class Updater {

	var $version_suffix = '';

	// This isn't complete, more for testing
	public $affected_tables = ['member_groups', 'channels'];

	/**
	 * Do Update
	 *
	 * @return TRUE
	 */
	public function do_update()
	{
		$steps = new \ProgressIterator(
			array(
				'add_can_view_homepage_news_permission',
				'add_menu_tables',
				'add_channel_max_entries_columns',
				'fix_channel_total_entries_count',
				'extend_max_username_length'
			)
		);

		foreach ($steps as $k => $v)
		{
			$this->$v();
		}

		return TRUE;
	}

	private function add_can_view_homepage_news_permission()
	{
		ee()->smartforge->add_column(
			'member_groups',
			array(
				'can_view_homepage_news' => array(
					'type'       => 'char',
					'constraint' => 1,
					'default'    => 'y',
					'null'       => FALSE
				)
			)
		);
	}

	/**
	 * Adds the max_entries and total_records column to the exp_channels table
	 * for the new Max Entries feature for Channels
	 */
	private function add_channel_max_entries_columns()
	{
		ee()->smartforge->add_column(
			'channels',
			array(
				'max_entries'      => array(
					'type'         => 'int',
					'null'         => FALSE,
					'unsigned'     => TRUE,
					'default'      => 0
				),
			)
		);

		ee()->smartforge->add_column(
			'channels',
			array(
				'total_records'    => array(
					'type'         => 'mediumint',
					'constraint'   => 8,
					'null'         => FALSE,
					'unsigned'     => TRUE,
					'default'      => 0
				),
			),
			'total_entries'
		);
	}

	/**
	 * The total_entries column in the Channel table has been calculated
	 * incorrectly. This loops through each channel and ensures its correct
	 * and also populates our new total_records column.
	 */
	private function fix_channel_total_entries_count()
	{
		// Fix for running this update file in a >= 4.0 context, status_id column
		// must be present to access ChannelEntry model in updateEntryStats() below
		ee()->smartforge->add_column(
			'channel_titles',
			array(
				'status_id' => array(
					'type'       => 'int',
					'constraint' => 4,
					'unsigned'   => TRUE,
					'null'       => FALSE
				)
			),
			'status'
		);

		// Fix for running this update routine in a >= 4.1 context, preview_url
		// column must be present to access Channel model below
		ee()->smartforge->add_column(
			'channels',
			array(
				'preview_url' => array(
					'type'    => 'VARCHAR(100)',
					'null'    => TRUE,
				)
			)
		);

		foreach (ee('Model')->get('Channel')->all() as $channel)
		{
			$channel->updateEntryStats();
		}
	}

	/**
	 * Modify username and screen_name columns to be their new max length of 75
	 * characters
	 */
	private function extend_max_username_length()
	{
		ee()->smartforge->modify_column(
			'members',
			array(
				'username' => array(
					'name'			=> 'username',
					'type'			=> 'varchar',
					'constraint'	=> USERNAME_MAX_LENGTH,
					'null'			=> FALSE
				)
			)
		);

		ee()->smartforge->modify_column(
			'members',
			array(
				'screen_name' => array(
					'name'			=> 'screen_name',
					'type'			=> 'varchar',
					'constraint'	=> USERNAME_MAX_LENGTH,
					'null'			=> FALSE
				)
			)
		);
	}

	/**
	 * Add menu_set_id to members and create tables: menu_sets, menu_set_items
	 */
	private function add_menu_tables()
	{
		ee()->smartforge->add_column(
			'member_groups',
			array(
				'menu_set_id'      => array(
					'type'         => 'int',
					'null'         => FALSE,
					'unsigned'     => TRUE,
					'default'      => 1
				),
			)
		);

		ee()->dbforge->add_field(
			array(
				'item_id' => array(
					'type'			 => 'int',
					'constraint'     => 10,
					'null'			 => FALSE,
					'unsigned'		 => TRUE,
					'auto_increment' => TRUE
				),
				'parent_id' => array(
					'type'			=> 'int',
					'constraint'    => 10,
					'unsigned'		=> TRUE,
					'null'			=> FALSE,
					'default'		=> 0
				),
				'set_id' => array(
					'type'			=> 'int',
					'constraint'    => 10,
					'unsigned'		=> TRUE,
					'null'			=> TRUE
				),
				'name' => array(
					'type'			=> 'varchar',
					'constraint'    => 50,
					'null'			=> TRUE
				),
				'data' => array(
					'type'			=> 'varchar',
					'constraint'    => 255,
					'null'			=> TRUE
				),
				'type' => array(
					'type'			=> 'varchar',
					'constraint'    => 10,
					'null'			=> TRUE
				),
				'sort' => array(
					'type'			=> 'int',
					'constraint'    => 5,
					'unsigned'		=> TRUE,
					'null'			=> FALSE,
					'default'       => 0
				)
			)
		);

		ee()->dbforge->add_key('item_id', TRUE);
		ee()->dbforge->add_key('set_id');
		ee()->smartforge->create_table('menu_items');

		ee()->dbforge->add_field(
			array(
				'set_id' => array(
					'type'			 => 'int',
					'constraint'     => 10,
					'null'			 => FALSE,
					'unsigned'		 => TRUE,
					'auto_increment' => TRUE
				),
				'name' => array(
					'type'			=> 'varchar',
					'constraint'    => 50,
					'null'			=> TRUE
				)
			)
		);

		ee()->dbforge->add_key('set_id', TRUE);

		if (ee()->smartforge->create_table('menu_sets'))
		{
			ee()->db->insert('menu_sets', array('name' => 'Default'));
		}
	}
}

// EOF

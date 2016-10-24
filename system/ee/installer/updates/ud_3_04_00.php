<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2016, EllisLab, Inc.
 * @license		https://ellislab.com/expressionengine/user-guide/license.html
 * @link		http://ellislab.com
 * @since		Version 3.40
 * @filesource
 */

// ------------------------------------------------------------------------

/**
 * ExpressionEngine Update Class
 *
 * @package		ExpressionEngine
 * @subpackage	Core
 * @category	Core
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */
class Updater {

	var $version_suffix = '';

	/**
	 * Do Update
	 *
	 * @return TRUE
	 */
	public function do_update()
	{
		$steps = new ProgressIterator(
			array(
				'add_can_view_homepage_news_permission',
				'add_menu_tables',
				'add_channel_max_entries_columns',
				'fix_channel_total_entries_count',
				'add_missing_default_status_groups'
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
		foreach (ee('Model')->get('Channel')->all() as $channel)
		{
			$channel->updateEntryStats();
		}
	}

	/**
	 * Loops through all our sites and adds the default status group to any
	 * site that does not already have one.
	 */
	private function add_missing_default_status_groups()
	{
		foreach (ee('Model')->get('Site')->all() as $site)
		{
			$site->createDefaultStatuses();
		}
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

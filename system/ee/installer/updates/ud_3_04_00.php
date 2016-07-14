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
				'add_channel_max_entries_columns',
				'fix_channel_total_entries_count',
				'add_missing_default_status_groups',
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
}

// EOF

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
				'add_channel_max_entries_column',
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
	 * Adds the max_entries column to the exp_channels table
	 */
	private function add_channel_max_entries_column()
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
}

// EOF

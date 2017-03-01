<?php

namespace EllisLab\ExpressionEngine\Controller\Members\Profile;

if ( ! defined('BASEPATH')) exit('No direct script access allowed');

use CP_Controller;

/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2016, EllisLab, Inc.
 * @license		https://expressionengine.com/license
 * @link		https://ellislab.com
 * @since		Version 3.0
 * @filesource
 */

// ------------------------------------------------------------------------

/**
 * ExpressionEngine CP Member Profile View Activity Class
 *
 * @package		ExpressionEngine
 * @subpackage	Control Panel
 * @category	Control Panel
 * @author		EllisLab Dev Team
 * @link		https://ellislab.com
 */
class Activity extends Profile {

	private $base_url = 'members/profile/activity';

	/**
	 * View Activity
	 */
	public function index()
	{
		$this->base_url = ee('CP/URL')->make($this->base_url, $this->query_string);

		$items = array(
			'join_date'         => ee()->localize->human_time(ee()->session->userdata('join_date')),
			'last_visit'        =>ee()->localize->human_time(ee()->session->userdata('last_visit')),
			'last_activity'     =>ee()->localize->human_time(ee()->session->userdata('last_activity')),
			'last_entry_date'   =>ee()->localize->human_time(ee()->session->userdata('last_entry_date')),
			'total_entries'     => ee()->session->userdata('total_entries'),
			'total_comments'    => ee()->session->userdata('total_comments'),
			'total_forum_posts' => ee()->session->userdata('total_forum_posts'),
			'total_forum_posts' => ee()->session->userdata('total_forum_posts'),
		);

		if (get_bool_from_string($this->member->MemberGroup->can_access_cp))
		{
			$log_url = ee('CP/URL')->make('cp/logs/cp', array('filter_by_username' => $this->member->member_id));
			$items['cp_log'] = '<a href="'.$log_url.'">'.sprintf(lang('view_cp_logs'), $this->member->username).'</a>';
		}

		ee()->view->base_url = $this->base_url;
		ee()->view->cp_page_title = lang('view_activity');
		ee()->cp->render('members/view_activity', array('items' => $items));
	}
}
// END CLASS

// EOF

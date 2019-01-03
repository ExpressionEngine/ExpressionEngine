<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2019, EllisLab Corp. (https://ellislab.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace EllisLab\ExpressionEngine\Controller\Members\Profile;

if ( ! defined('BASEPATH')) exit('No direct script access allowed');

use CP_Controller;

/**
 * Member Profile Activity Controller
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
			'join_date'         => $this->getHumanDateOrFalse($this->member->join_date),
			'last_visit'        => $this->getHumanDateOrFalse($this->member->last_visit),
			'last_activity'     => $this->getHumanDateOrFalse($this->member->last_activity),
			'last_entry_date'   => $this->getHumanDateOrFalse($this->member->last_entry_date),
			'total_entries'     => $this->member->total_entries,
			'total_comments'    => $this->member->total_comments
		);

		ee()->load->model('addons_model');
		$forum_installed = ee()->addons_model->module_installed('forum');

		if ($forum_installed)
		{
			$items['total_forum_topics'] = $this->member->total_forum_topics;
			$items['total_forum_replies'] = $this->member->total_forum_posts;
		}

		if (get_bool_from_string($this->member->MemberGroup->can_access_cp))
		{
			$log_url = ee('CP/URL')->make('cp/logs/cp', array('filter_by_username' => $this->member->member_id));
			$items['cp_log'] = '<a href="'.$log_url.'">'.sprintf(lang('view_cp_logs'), $this->member->username).'</a>';
		}

		ee()->view->base_url = $this->base_url;
		ee()->view->cp_page_title = lang('view_activity');
		ee()->cp->render('members/view_activity', array('items' => $items));
	}

	/**
	 * returns a human-readable date, or if the timestamp is false-ish
	 * from PHP's loose type handling, we return FALSE. This way we don't display
	 * a date at the start of the Unix Epoch for empty values. Profile activity
	 * dates will never legitimately have a timestamp of "", NULL, or 0.
	 *
	 * @param  int $timestamp Unix timestamp to format
	 * @return string Human-formatted date, or FALSE for "empty" dates.
	 */
	private function getHumanDateOrFalse($timestamp)
	{
		return ($timestamp) ? ee()->localize->human_time($timestamp) : FALSE;
	}
}
// END CLASS

// EOF

<?php

namespace EllisLab\ExpressionEngine\Controllers\Logs;

if ( ! defined('BASEPATH')) exit('No direct script access allowed');

use EllisLab\ExpressionEngine\Library\CP\Pagination;

/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2014, EllisLab, Inc.
 * @license		http://ellislab.com/expressionengine/user-guide/license.html
 * @link		http://ellislab.com
 * @since		Version 2.0
 * @filesource
 */

// ------------------------------------------------------------------------

/**
 * ExpressionEngine CP Home Page Class
 *
 * @package		ExpressionEngine
 * @subpackage	Control Panel
 * @category	Control Panel
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */
class Search extends Logs {

	/**
	 * View Search Log
	 *
	 * Shows a log of recent search terms
	 *
	 * @access	public
	 * @return	mixed
	 */
	public function index()
	{
		if ( ! ee()->cp->allowed_group('can_access_tools', 'can_access_logs'))
		{
			show_error(lang('unauthorized_access'));
		}

		$this->base_url->path = 'logs/search';
		ee()->view->cp_page_title = lang('view_search_log');
		$this->filters(array('username', 'site', 'date', 'perpage'));

		$page = ee()->input->get('page') ? ee()->input->get('page') : 1;
		$page = ($page > 0) ? $page : 1;

		$offset = ($page - 1) * $this->params['perpage']; // Offset is 0 indexed

		$logs = ee()->api->get('SearchLog')->with('Site');

		if ( ! empty($this->params['filter_by_username']))
		{
			$logs = $logs->filter('member_id', $this->params['filter_by_username']);
		}

		if ( ! empty($this->params['filter_by_site']))
		{
			$logs = $logs->filter('site_id', $this->params['filter_by_site']);
		}

		if ( ! empty($this->params['filter_by_date']))
		{
			$logs = $logs->filter('search_date', '>=', ee()->localize->now - $this->params['filter_by_date']);
		}

		if ( ! empty(ee()->view->filter_by_phrase_value))
		{
			$logs = $logs->filterGroup()
			               ->filter('screen_name', 'LIKE', '%' . ee()->view->filter_by_phrase_value . '%')
			               ->orFilter('ip_address', 'LIKE', '%' . ee()->view->filter_by_phrase_value . '%')
			               ->orFilter('search_type', 'LIKE', '%' . ee()->view->filter_by_phrase_value . '%')
			               ->orFilter('search_terms', 'LIKE', '%' . ee()->view->filter_by_phrase_value . '%')
			               ->orFilter('Site.site_label', 'LIKE', '%' . ee()->view->filter_by_phrase_value . '%')
						 ->endFilterGroup();
		}

		$count = $logs->count();

		$logs = $logs->order('search_date', 'desc')
			->limit($this->params['perpage'])
			->offset($offset)
			->all();

		$rows = array();
		foreach ($logs as $log)
		{
			if ($log->member_id == 0)
			{
				$username = '--';
			}
			else
			{
				$username = "<a href='" . cp_url('myaccount', array('id' => $log->member_id)) . "'>{$log->screen_name}</a>";
			}

			$rows[] = array(
				'id'				=> $log->id,
				'username'			=> $username,
				'ip_address'		=> $log->ip_address,
				'site_label' 		=> $log->getSite()->site_label,
				'search_date'		=> $this->localize->human_time($log->search_date),
				'search_type' 		=> $log->search_type,
				'search_terms'		=> $log->search_terms
			);
		}

		$pagination = new Pagination($this->params['perpage'], $count, $page);
		$links = $pagination->cp_links($this->base_url);

		$vars = array(
			'rows' => $rows,
			'pagination' => $links
		);

		ee()->cp->render('logs/search', $vars);
	}

	// --------------------------------------------------------------------

	/**
	 * Deletes log entries, either all at once, or one at a time
	 *
	 * @param mixed  $id	Either the id to delete or "all"
	 */
	public function delete($id = 'all')
	{
		if ( ! ee()->cp->allowed_group('can_access_tools', 'can_access_logs'))
		{
			show_error(lang('unauthorized_access'));
		}

		$query = ee()->api->get('SearchLog');

		$success_flashdata = lang('cleared_logs');
		if (strtolower($id) != 'all')
		{
			$query = $query->filter('id', $id);
			$success_flashdata = lang('logs_deleted');
		}

		$query->all()->delete();

		ee()->view->set_message('success', $success_flashdata, '', TRUE);
		ee()->functions->redirect(cp_url('logs/search'));
	}
}
// END CLASS

/* End of file Search.php */
/* Location: ./system/expressionengine/controllers/cp/Logs/Search.php */
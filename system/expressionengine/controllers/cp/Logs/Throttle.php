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
class Throttle extends Logs {

	/**
	 * View Throttle Log
	 *
	 * Shows a list of ips that are currently throttled
	 *
	 * @access	public
	 * @return	mixed
	 */
	public function index()
	{
		if ( ! $this->cp->allowed_group('can_access_tools', 'can_access_logs'))
		{
			show_error(lang('unauthorized_access'));
		}

		$this->base_url->path = 'logs/throttle';
		$this->view->cp_page_title = lang('view_throttle_log');
		$this->filters(array('perpage'));

		$rows = array();
		$links = array();
		$throttling_disabled = TRUE;

		if (ee()->config->item('enable_throttling') == 'y')
		{
			$throttling_disabled = FALSE;
			$page = ee()->input->get('page') ? ee()->input->get('page') : 1;
			$page = ($page > 0) ? $page : 1;

			$offset = ($page - 1) * $this->params['perpage']; // Offset is 0 indexed

			$logs = ee()->api->get('Throttle');

			$count = $logs->count();

			$logs = $logs->order('last_activity', 'desc')
				->limit($this->params['perpage'])
				->offset($offset)
				->all();

			foreach ($logs as $log)
			{
				$rows[] = array(
					'throttle_id'		=> $log->throttle_id,
					'ip_address'		=> $log->ip_address,
					'last_activity'		=> $this->localize->human_time($log->last_activity),
					'hits'				=> $log->hits,
					'locked_out'		=> $log->locked_out
				);
			}

			$pagination = new Pagination($this->params['perpage'], $count, $page);
			$links = $pagination->cp_links($this->base_url);
		}

		$vars = array(
			'rows' => $rows,
			'pagination' => $links,
			'disabled' => $throttling_disabled
		);

		$this->cp->render('logs/throttle', $vars);
	}

	// --------------------------------------------------------------------

	/**
	 * Deletes log entries, either all at once, or one at a time
	 *
	 * @param mixed  $id	Either the id to delete or "all"
	 */
	public function delete($id = 'all')
	{
		if ( ! $this->cp->allowed_group('can_access_tools', 'can_access_logs'))
		{
			show_error(lang('unauthorized_access'));
		}

		$query = ee()->api->get('Throttle');

		$success_flashdata = lang('cleared_logs');
		if (strtolower($id) != 'all')
		{
			$query = $query->filter('throttle_id', $id);
			$success_flashdata = lang('logs_deleted');
		}

		$query->all()->delete();

		ee()->view->set_message('success', $success_flashdata, '', TRUE);
		ee()->functions->redirect(cp_url('logs/throttle'));
	}
}
// END CLASS

/* End of file Throttle.php */
/* Location: ./system/expressionengine/controllers/cp/Logs/Throttle.php */
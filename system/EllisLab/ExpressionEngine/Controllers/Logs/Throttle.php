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
		if ( ! ee()->cp->allowed_group('can_access_tools', 'can_access_logs'))
		{
			show_error(lang('unauthorized_access'));
		}

		if (ee()->input->post('delete'))
		{
			$this->delete('Throttle', lang('throttle_log'));
			if (strtolower(ee()->input->post('delete')) == 'all')
			{
				return ee()->functions->redirect(cp_url('logs/throttle'));
			}
		}

		$this->base_url->path = 'logs/throttle';
		ee()->view->cp_page_title = lang('view_throttle_log');

		$rows   = array();
		$modals = array();
		$links  = array();
		$throttling_disabled = TRUE;

		if (ee()->config->item('enable_throttling') == 'y')
		{
			$throttling_disabled = FALSE;
			$page = ee()->input->get('page') ? ee()->input->get('page') : 1;
			$page = ($page > 0) ? $page : 1;

			$offset = ($page - 1) * $this->params['perpage']; // Offset is 0 indexed

			$max_page_loads = 10;
			$lockout_time	= 30;

			if (is_numeric($this->config->item('max_page_loads')))
			{
				$max_page_loads = $this->config->item('max_page_loads');
			}

			if (is_numeric($this->config->item('lockout_time')))
			{
				$lockout_time = $this->config->item('lockout_time');
			}

			$logs = ee()->api->get('Throttle')
				->filterGroup()
					->filter('hits', '>=', $max_page_loads)
					->orFilterGroup()
						->filter('locked_out', 'y')
						->filter('last_activity', '>', $lockout_time)
					->endFilterGroup()
				->endFilterGroup();

			if ( ! empty(ee()->view->search_value))
			{
				$logs = $logs->filterGroup()
				               ->filter('ip_address', 'LIKE', '%' . ee()->view->search_value . '%')
				               ->orFilter('hits', 'LIKE', '%' . ee()->view->search_value . '%')
							 ->endFilterGroup();
			}

			$count = $logs->count();

			if ($count > 10)
			{
				$this->filters(array('perpage'));
			}

			// Set the page heading
			if ( ! empty(ee()->view->search_value))
			{
				ee()->view->cp_heading = sprintf(lang('search_results_heading'), $count, ee()->view->search_value);
			}

			$logs = $logs->order('last_activity', 'desc')
				->limit($this->params['perpage'])
				->offset($offset)
				->all();

			foreach ($logs as $log)
			{
				$rows[] = array(
					'throttle_id'		=> $log->throttle_id,
					'ip_address'		=> $log->ip_address,
					'last_activity'		=> ee()->localize->human_time($log->last_activity),
					'hits'				=> $log->hits,
					'locked_out'		=> $log->locked_out
				);

				$modal_vars = array(
					'form_url'	=> $this->base_url,
					'hidden'	=> array(
						'delete'	=> $log->throttle_id
					),
					'checklist'	=> array(
						array(
							'kind' => lang('view_throttle_log'),
							'desc' => $log->ip_address . ' ' . lang('hits') . ': ' . $log->hits
						)
					)
				);

				$modals['modal-confirm-' . $log->id] = ee()->view->render('_shared/modal-confirm', $modal_vars, TRUE);
			}

			$pagination = new Pagination($this->params['perpage'], $count, $page);
			$links = $pagination->cp_links($this->base_url);
		}

		$modal_vars = array(
			'form_url'	=> $this->base_url,
			'hidden'	=> array(
				'delete'	=> 'all'
			),
			'checklist'	=> array(
				array(
					'kind' => lang('view_throttle_log'),
					'desc' => lang('all')
				)
			)
		);

		$modals['modal-confirm-all'] = ee()->view->render('_shared/modal-confirm', $modal_vars, TRUE);

		$vars = array(
			'rows' => $rows,
			'pagination' => $links,
			'disabled' => $throttling_disabled,
			'form_url' => $this->base_url->compile(),
			'modals' => $modals
		);

		ee()->cp->render('logs/throttle', $vars);
	}
}
// END CLASS

/* End of file Throttle.php */
/* Location: ./system/EllisLab/ExpressionEngine/Controllers/Logs/Throttle.php */
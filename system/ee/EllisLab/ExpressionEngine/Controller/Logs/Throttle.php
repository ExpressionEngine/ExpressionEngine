<?php

namespace EllisLab\ExpressionEngine\Controller\Logs;

if ( ! defined('BASEPATH')) exit('No direct script access allowed');

use EllisLab\ExpressionEngine\Service\CP\Filter\FilterFactory;
use EllisLab\ExpressionEngine\Service\CP\Filter\FilterRunner;

/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2016, EllisLab, Inc.
 * @license		https://expressionengine.com/license
 * @link		https://ellislab.com
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
 * @link		https://ellislab.com
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
		if ( ! ee()->cp->allowed_group('can_access_logs'))
		{
			show_error(lang('unauthorized_access'));
		}

		ee('CP/Alert')->makeDeprecationNotice()->now();

		if (ee()->input->post('delete'))
		{
			$this->delete('Throttle', lang('throttle_log'));
			if (strtolower(ee()->input->post('delete')) == 'all')
			{
				return ee()->functions->redirect(ee('CP/URL')->make('logs/throttle'));
			}
		}

		$this->base_url->path = 'logs/throttle';
		ee()->view->cp_page_title = lang('view_throttle_log');

		$logs = array();
		$pagination = '';
		$throttling_disabled = TRUE;

		if (ee()->config->item('enable_throttling') == 'y')
		{
			$throttling_disabled = FALSE;
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

			$logs = ee('Model')->get('Throttle')
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
				$filters = ee('CP/Filter')
					->add('Perpage', $count, 'all_throttle_logs');
				ee()->view->filters = $filters->render($this->base_url);
				$this->params = $filters->values();
				$this->base_url->addQueryStringVariables($this->params);
			}

			$page = ((int) ee()->input->get('page')) ?: 1;
			$offset = ($page - 1) * $this->params['perpage']; // Offset is 0 indexed

			// Set the page heading
			if ( ! empty(ee()->view->search_value))
			{
				ee()->view->cp_heading = sprintf(lang('search_results_heading'), $count, ee()->view->search_value);
			}

			$logs = $logs->order('last_activity', 'desc')
				->limit($this->params['perpage'])
				->offset($offset)
				->all();

			$pagination = ee('CP/Pagination', $count)
				->perPage($this->params['perpage'])
				->currentPage($page)
				->render($this->base_url);
		}

		ee()->view->header = array(
			'title' => lang('system_logs'),
			'form_url' => $this->base_url->compile(),
			'search_button_value' => lang('search_logs_button')
		);

		$vars = array(
			'logs' => $logs,
			'pagination' => $pagination,
			'disabled' => $throttling_disabled,
			'form_url' => $this->base_url->compile(),
		);

		ee()->cp->render('logs/throttle', $vars);
	}
}
// END CLASS

// EOF

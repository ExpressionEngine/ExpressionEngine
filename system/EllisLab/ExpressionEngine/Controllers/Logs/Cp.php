<?php

namespace EllisLab\ExpressionEngine\Controllers\Logs;

if ( ! defined('BASEPATH')) exit('No direct script access allowed');

use EllisLab\ExpressionEngine\Library\CP\Pagination;
use EllisLab\ExpressionEngine\Service\CP\Filter;
use EllisLab\ExpressionEngine\Service\CP\Filter\FilterRunner;

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
class Cp extends Logs {

	/**
	 * View Control Panel Log Files
	 *
	 * Shows the control panel action log
	 *
	 * @access	public
	 * @return	mixed
	 */
	public function index()
	{
		if (ee()->input->post('delete'))
		{
			$this->delete('CpLog', lang('cp_log'));
			if (strtolower(ee()->input->post('delete')) == 'all')
			{
				return ee()->functions->redirect(cp_url('logs/cp'));
			}
		}

		$this->base_url->path = 'logs/cp';
		ee()->view->cp_page_title = lang('view_cp_log');

		$logs = ee()->api->get('CpLog')->with('Site');

		if ( ! empty(ee()->view->search_value))
		{
			$logs = $logs->filterGroup()
			               ->filter('action', 'LIKE', '%' . ee()->view->search_value . '%')
			               ->orFilter('username', 'LIKE', '%' . ee()->view->search_value . '%')
			               ->orFilter('ip_address', 'LIKE', '%' . ee()->view->search_value . '%')
			               ->orFilter('Site.site_label', 'LIKE', '%' . ee()->view->search_value . '%')
						 ->endFilterGroup();
		}

		if ($logs->count() > 10)
		{
			$fr = new FilterRunner($this->base_url, array(
				new Filter\Username,
				new Filter\Site,
				new Filter\Date,
				new Filter\Perpage($logs->count(), 'all_cp_logs')
			));
			ee()->view->filters = $fr->render();
			$this->base_url = $fr->getUrl();
			$this->params = $fr->getParameters();
		}

		$page = ((int) ee()->input->get('page')) ?: 1;
		$offset = ($page - 1) * $this->params['perpage']; // Offset is 0 indexed

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
			if (is_array($this->params['filter_by_date']))
			{
				$logs = $logs->filter('act_date', '>=', $this->params['filter_by_date'][0]);
				$logs = $logs->filter('act_date', '<', $this->params['filter_by_date'][1]);
			}
			else
			{
				$logs = $logs->filter('act_date', '>=', ee()->localize->now - $this->params['filter_by_date']);
			}
		}

		$count = $logs->count();

		// Set the page heading
		if ( ! empty(ee()->view->search_value))
		{
			ee()->view->cp_heading = sprintf(lang('search_results_heading'), $count, ee()->view->search_value);
		}

		$logs = $logs->order('act_date', 'desc')
			->limit($this->params['perpage'])
			->offset($offset)
			->all();

		$rows   = array();
		$modals = array();

		foreach ($logs as $log)
		{
			$rows[] = array(
				'id'		 => $log->id,
				'member_id'	 => $log->member_id,
				'username'	 => "<a href='" . cp_url('myaccount', array('id' => $log->member_id)) . "'>{$log->username}</a>",
				'ip_address' => $log->ip_address,
				'act_date'	 => ee()->localize->human_time($log->act_date),
				'site_label' => $log->getSite()->site_label,
				'action'	 => $log->action
			);

			$modal_vars = array(
				'form_url'	=> $this->base_url,
				'hidden'	=> array(
					'delete'	=> $log->id
				),
				'checklist'	=> array(
					array(
						'kind' => lang('view_cp_log'),
						'desc' => $log->username . ' ' . $log->action
					)
				)
			);

			$modals['modal-confirm-' . $log->id] = ee()->view->render('_shared/modal_confirm_remove', $modal_vars, TRUE);
		}

		$pagination = new Pagination($this->params['perpage'], $count, $page);
		$links = $pagination->cp_links($this->base_url);

		$modal_vars = array(
			'form_url'	=> $this->base_url,
			'hidden'	=> array(
				'delete'	=> 'all'
			),
			'checklist'	=> array(
				array(
					'kind' => lang('view_cp_log'),
					'desc' => lang('all')
				)
			)
		);

		$modals['modal-confirm-all'] = ee()->view->render('_shared/modal_confirm_remove', $modal_vars, TRUE);

		$vars = array(
			'rows' => $rows,
			'pagination' => $links,
			'form_url' => $this->base_url->compile(),
			'modals' => $modals
		);

		ee()->cp->render('logs/cp', $vars);
	}
}
// END CLASS

/* End of file CP.php */
/* Location: ./system/EllisLab/ExpressionEngine/Controllers/Logs/Cp.php */

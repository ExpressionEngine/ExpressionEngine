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
class Email extends Logs {

	/**
	 * View Email Log
	 *
	 * Displays emails logged
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
			return $this->delete(ee()->input->post('delete'));
		}
		$this->base_url->path = 'logs/email';
		ee()->view->cp_page_title = lang('view_email_logs');

		if (ee()->api->get('EmailConsoleCache')->count() > 10)
		{
			ee()->db->_reset_select();
			$this->filters(array('username', 'date', 'perpage'));
		}

		$page = ee()->input->get('page') ? ee()->input->get('page') : 1;
		$page = ($page > 0) ? $page : 1;

		$offset = ($page - 1) * $this->params['perpage']; // Offset is 0 indexed

		$logs = ee()->api->get('EmailConsoleCache');

		if ( ! empty($this->params['filter_by_username']))
		{
			$logs = $logs->filter('member_id', $this->params['filter_by_username']);
		}

		if ( ! empty($this->params['filter_by_date']))
		{
			if (is_array($this->params['filter_by_date']))
			{
				$logs = $logs->filter('cache_date', '>=', $this->params['filter_by_date'][0]);
				$logs = $logs->filter('cache_date', '<', $this->params['filter_by_date'][1]);
			}
			else
			{
				$logs = $logs->filter('cache_date', '>=', ee()->localize->now - $this->params['filter_by_date']);
			}
		}

		if ( ! empty(ee()->view->search_value))
		{
			$logs = $logs->filterGroup()
			               ->filter('member_name', 'LIKE', '%' . ee()->view->search_value . '%')
			               ->orFilter('ip_address', 'LIKE', '%' . ee()->view->search_value . '%')
			               ->orFilter('recipient', 'LIKE', '%' . ee()->view->search_value . '%')
			               ->orFilter('recipient_name', 'LIKE', '%' . ee()->view->search_value . '%')
			               ->orFilter('subject', 'LIKE', '%' . ee()->view->search_value . '%')
			               ->orFilter('message', 'LIKE', '%' . ee()->view->search_value . '%')
						 ->endFilterGroup();
		}

		$count = $logs->count();

		$logs = $logs->order('cache_date', 'desc')
			->limit($this->params['perpage'])
			->offset($offset)
			->all();

		$rows   = array();
		$modals = array();

		foreach ($logs as $log)
		{
			$rows[] = array(
				'cache_id'			=> $log->cache_id,
				'username'			=> "<a href='" . cp_url('myaccount', array('id' => $log->member_id)) . "'>{$log->member_name}</a>",
				'ip_address'		=> $log->ip_address,
				'cache_date'		=> ee()->localize->human_time($log->cache_date),
				'subject' 			=> $log->subject,
				'recipient_name'	=> $log->recipient_name
			);

			$modal_vars = array(
				'form_url'	=> $this->base_url,
				'hidden'	=> array(
					'delete'	=> $log->cache_id
				),
				'checklist'	=> array(
					array(
						'kind' => lang('view_email_logs'),
						'desc' => lang('sent_to') . ' ' . $log->recipient_name . ', ' . lang('subject') . ': ' . $log->subject
					)
				)
			);

			$modals['modal-confirm-' . $log->id] = ee()->view->render('_shared/modal-confirm', $modal_vars, TRUE);
		}

		$pagination = new Pagination($this->params['perpage'], $count, $page);
		$links = $pagination->cp_links($this->base_url);

		// Set the page heading
		if ( ! empty(ee()->view->search_value))
		{
			ee()->view->cp_heading = sprintf(lang('search_results_heading'), $count, ee()->view->search_value);
		}

		$modal_vars = array(
			'form_url'	=> $this->base_url,
			'hidden'	=> array(
				'delete'	=> 'all'
			),
			'checklist'	=> array(
				array(
					'kind' => lang('view_email_logs'),
					'desc' => lang('all')
				)
			)
		);

		$modals['modal-confirm-all'] = ee()->view->render('_shared/modal-confirm', $modal_vars, TRUE);

		$vars = array(
			'rows' => $rows,
			'pagination' => $links,
			'form_url' => $this->base_url->compile(),
			'modals' => $modals
		);

		ee()->cp->render('logs/email/list.php', $vars);
	}

	// --------------------------------------------------------------------

	/**
	 * View Single Email
	 *
	 * @access	public
	 * @return	mixed
	 */
	public function view($id)
	{
		if ( ! ee()->cp->allowed_group('can_access_tools', 'can_access_logs'))
		{
			show_error(lang('unauthorized_access'));
		}

		$email = ee()->api->get('EmailConsoleCache', $id)->first();

		if (is_null($email))
		{
			ee()->lang->load('communicate');
			ee()->view->set_message('issue', lang('no_cached_email'), '', TRUE);
			$this->functions->redirect(cp_url('logs/email'));
		}

		ee()->view->cp_page_title = lang('email_log') . ': ' . $email->subject;
		ee()->view->cp_breadcrumbs = array(
			cp_url('logs/email') => lang('view_email_logs')
		);
		ee()->view->email = $email;
		ee()->cp->render('logs/email/detail');
	}

	// --------------------------------------------------------------------

	/**
	 * Deletes log entries, either all at once, or one at a time
	 *
	 * @param mixed  $id	Either the id to delete or "all"
	 */
	private function delete($id = 'all')
	{
		if ( ! ee()->cp->allowed_group('can_access_tools', 'can_access_logs'))
		{
			show_error(lang('unauthorized_access'));
		}

		$query = ee()->api->get('EmailConsoleCache');

		$success_flashdata = lang('cleared_logs');
		if (strtolower($id) != 'all')
		{
			$query = $query->filter('cache_id', $id);
			$success_flashdata = lang('logs_deleted');
		}

		$query->all()->delete();

		ee()->view->set_message('success', $success_flashdata, '', TRUE);
		ee()->functions->redirect(cp_url('logs/email'));
	}
}
// END CLASS

/* End of file Email.php */
/* Location: ./system/expressionengine/controllers/cp/Logs/Email.php */
<?php

namespace EllisLab\ExpressionEngine\Controllers\Logs;

if ( ! defined('BASEPATH')) exit('No direct script access allowed');

use CP_Controller;
use EllisLab\ExpressionEngine\Library\CP;

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
class Logs extends CP_Controller {

	var $perpage		= 50;
	var $params			= array();
	var $base_url;

	/**
	 * Constructor
	 */
	public function __construct()
	{
		parent::__construct();

		ee()->lang->loadfile('logs');

		if ( ! ee()->cp->allowed_group('can_access_logs'))
		{
			show_error(lang('unauthorized_access'));
		}

		$this->base_url = new CP\URL('logs', ee()->session->session_id());

		// Sidebar Menu
		$menu = array(
			'logs',
			array(
				'developer_log' => cp_url('logs/developer'),
				'cp_log'        => cp_url('logs/cp'),
				'throttle_log'  => cp_url('logs/throttle'),
				'email_log'     => cp_url('logs/email'),
				'search_log'    => cp_url('logs/search'),
			)
		);

		if (ee()->session->userdata('group_id') != 1)
		{
			unset($menu[1]['developer_log']);
		}

		ee()->menu->register_left_nav($menu);
	}

	// --------------------------------------------------------------------

	/**
	 * Adds filters to the view and sets parameter values for the models
	 *
	 * @param array $filters	A list of filters to show, defaults to:
	 *    'username',
	 *    'site',
	 *    'date',
	 *    'perpage'
	 * @return void
	 */
	protected function filters($filters = NULL)
	{
		if ( ! is_array($filters))
		{
			$filters = array();
		}

		$view_filters = array();

		// By Username
		if (in_array('username', $filters))
		{
			$usernames = array('' => '-- '.lang('by_username').' --');
			ee()->load->model('member_model');
			$members = ee()->member_model->get_members();
			if ($members)
			{
				foreach ($members->result_array() as $member)
				{
					$usernames[$member['member_id']] = $member['username'];
				}
			}

			$this->params['filter_by_username'] = ee()->input->get_post('filter_by_username');
			$view_filters[] = form_dropdown('filter_by_username', $usernames, $this->params['filter_by_username']);
		}

		// By Site
		if (in_array('site', $filters))
		{
			if (ee()->config->item('multiple_sites_enabled') === 'y' && ! IS_CORE)
			{
				$sites = array('' => '-- '.lang('by_site').' --');

				// Since the keys are numeric array_merge() is the wrong solution
				foreach (ee()->session->userdata('assigned_sites') as $site_id => $site_label)
				{
					$sites[$site_id] = $site_label;
				}

				$this->params['filter_by_site'] = ee()->input->get_post('filter_by_site');
				$view_filters[] = form_dropdown('filter_by_site', $sites, $this->params['filter_by_site']);
			}
		}

		// By Date
		if (in_array('date', $filters))
		{
			$dates = array(
				''          => '-- '.lang('by_date').' --',
				'86400'     => ucwords(lang('last').' 24 '.lang('hours')),
				'604800'    => ucwords(lang('last').' 7 '.lang('days')),
				'2592000'   => ucwords(lang('last').' 30 '.lang('days')),
				'15552000'  => ucwords(lang('last').' 180 '.lang('days')),
				'31536000'  => ucwords(lang('last').' 365 '.lang('days')),
			);

			$this->params['filter_by_date'] = ee()->input->get_post('filter_by_date');
			$view_filters[] = form_dropdown('filter_by_date', $dates, $this->params['filter_by_date']);
		}

		// Limit per page
		if (in_array('perpage', $filters))
		{
			$perpages = array(
				''    => '-- '.lang('limit_by').' --',
				'25'  => '25 '.lang('results'),
				'50'  => '50 '.lang('results'),
				'75'  => '75 '.lang('results'),
				'100' => '100 '.lang('results'),
				'150' => '150 '.lang('results')
			);

			$this->params['perpage'] = ee()->input->get_post('perpage') ? (int) ee()->input->get_post('perpage') : $this->perpage;
			$view_filters[] = form_dropdown('perpage', $perpages, $this->params['perpage']);
		}

		// Maintain the filters in the URL
		foreach ($this->params as $key => $value)
		{
			if ( ! empty($value))
			{
				$this->base_url->setQueryStringVariable($key, $value);
			}
		}

		// Make the filters available to the view
		ee()->view->filters = $view_filters;

		// Add in any submitted search phrase
		ee()->view->filter_by_phrase_value = ee()->input->get_post('filter_by_phrase');

		if ( ! empty(ee()->view->filter_by_phrase_value))
		{
			$this->base_url->setQueryStringVariable('filter_by_phrase', ee()->view->filter_by_phrase_value);
		}
	}

	/**
	 * Index function
	 *
	 * @access	public
	 * @return	void
	 */
	public function index()
	{
		if (ee()->session->userdata('group_id') == 1)
		{
			ee()->functions->redirect(cp_url('logs/developer'));
		}
		else
		{
			ee()->functions->redirect(cp_url('logs/cp'));
		}
	}
}
// END CLASS

/* End of file Logs.php */
/* Location: ./system/expressionengine/controllers/cp/Logs/Logs.php */
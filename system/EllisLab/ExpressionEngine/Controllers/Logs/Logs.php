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
		$this->params['perpage'] = $this->perpage; // Set a default

		// Maintain the search if necissary
		if (ee()->input->get_post('search'))
		{
			$this->base_url->setQueryStringVariable('search', ee()->input->get_post('search'));
		}

		// Maintain the filters in the URL
		$all_filters = array(
			'filter_by_username'	=> 'username',
			'filter_by_site'		=> 'site',
			'filter_by_date'		=> 'date',
			'perpage'				=> 'perpage'
		);

		foreach ($all_filters as $key => $filter)
		{
			if (in_array($filter, $filters))
			{

				$value = (ee()->input->post($key)) ?: ee()->input->get($key);
				if ($value)
				{
					$this->base_url->setQueryStringVariable($key, $value);
					$this->params[$key] = $value;
				}
			}
		}

		// By Username
		if (in_array('username', $filters))
		{
			$base_url = clone $this->base_url;

			$filter = array(
				'label'		=> 'username',
				'name'		=> 'filter_by_username',
				'value'		=> '',
				'options'	=> array()
			);

			ee()->load->model('member_model');
			$members = ee()->member_model->get_members();
			if ($members)
			{
				foreach ($members->result_array() as $member)
				{
					if (isset($this->params['filter_by_username']) &&
						$this->params['filter_by_username'] == $member['member_id'])
					{
						$filter['value'] = $member['username'];
					}

					$base_url->setQueryStringVariable('filter_by_username', $member['member_id']);
					$filter['options'][$base_url->compile()] = $member['username'];
				}
			}

			$view_filters[] = $filter;
		}

		// By Site
		if (in_array('site', $filters))
		{
			if (ee()->config->item('multiple_sites_enabled') === 'y' && ! IS_CORE)
			{
				$base_url = clone $this->base_url;

				$filter = array(
					'label'		=> 'site',
					'name'		=> 'filter_by_site',
					'value'		=> '',
					'options'	=> array()
				);

				foreach (ee()->session->userdata('assigned_sites') as $site_id => $site_label)
				{
					if (isset($this->params['filter_by_site']) &&
						$this->params['filter_by_site'] == $site_id)
					{
						$filter['value'] = $site_label;
					}

					$base_url->setQueryStringVariable('filter_by_site', $site_id);
					$filter['options'][$base_url->compile()] = $site_label;
				}

				$view_filters[] = $filter;
			}
		}

		// By Date
		if (in_array('date', $filters))
		{
			$base_url = clone $this->base_url;

			$filter = array(
				'label'		=> 'date',
				'name'		=> 'filter_by_date',
				'value'		=> '',
				'options'	=> array()
			);

			$dates = array(
				'86400'     => ucwords(lang('last').' 24 '.lang('hours')),
				'604800'    => ucwords(lang('last').' 7 '.lang('days')),
				'2592000'   => ucwords(lang('last').' 30 '.lang('days')),
				'15552000'  => ucwords(lang('last').' 180 '.lang('days')),
				'31536000'  => ucwords(lang('last').' 365 '.lang('days')),
			);

			if (isset($this->params['filter_by_date']))
			{
				$filter['value'] = $dates[$this->params['filter_by_date']];
			}

			foreach ($dates as $seconds => $label)
			{
				$base_url->setQueryStringVariable('filter_by_date', $seconds);
				$filter['options'][$base_url->compile()] = $label;
			}

			$view_filters[] = $filter;
		}

		// Limit per page
		if (in_array('perpage', $filters))
		{
			$base_url = clone $this->base_url;

			$filter = array(
				'label'		=> 'show',
				'name'		=> 'perpage',
				'value'		=> $this->params['perpage'],
				'options'	=> array()
			);

			$perpages = array(
				'25'  => '25 '.lang('results'),
				'50'  => '50 '.lang('results'),
				'75'  => '75 '.lang('results'),
				'100' => '100 '.lang('results'),
				'150' => '150 '.lang('results')
			);

			foreach ($perpages as $show => $label)
			{
				$base_url->setQueryStringVariable('perpage', $show);
				$filter['options'][$base_url->compile()] = $label;
			}

			$view_filters[] = $filter;
		}

		// Make the filters available to the view
		ee()->view->filters = $view_filters;

		// Add in any submitted search phrase
		ee()->view->search_value = ee()->input->get_post('search');

		if ( ! empty(ee()->view->search_value))
		{
			$this->base_url->setQueryStringVariable('search', ee()->view->search_value);
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
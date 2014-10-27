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

	var $perpage		= 20;
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

		$this->params['perpage'] = $this->perpage; // Set a default

		// Add in any submitted search phrase
		ee()->view->search_value = ee()->input->get_post('search');
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
				'label'			=> 'username',
				'name'			=> 'filter_by_username',
				'value'			=> '',
				'custom_value'	=> ee()->input->post('filter_by_username'),
				'placeholder'	=> lang('filter_by_username'),
				'options'		=> array()
			);

			$members = ee()->api->get('Member')->all();
			if ($members)
			{
				if (isset($this->params['filter_by_username']))
				{
					if (is_numeric($this->params['filter_by_username']))
					{
						$member = ee()->api->get('Member', $this->params['filter_by_username'])->first();
						if ($member)
						{
							$filter['value'] = $member->username;
						}
					}
					else
					{
						$filter['value'] = $this->params['filter_by_username'];
						$member = ee()->api->get('Member')->filter('username', $this->params['filter_by_username'])->first();
						if ($member)
						{
							$this->params['filter_by_username'] = $member->member_id;
						}
					}
				}

				foreach ($members as $member)
				{
					$base_url->setQueryStringVariable('filter_by_username', $member->member_id);
					$filter['options'][$base_url->compile()] = $member->username;
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
					'label'			=> 'site',
					'name'			=> 'filter_by_site',
					'value'			=> '',
					'custom_value'	=> ee()->input->post('filter_by_site'),
					'placeholder'	=> lang('filter_by_site'),
					'options'		=> array()
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
			$date_format = ee()->session->userdata('date_format', ee()->config->item('date_format'));

			ee()->javascript->set_global('date.date_format', $date_format);
			ee()->javascript->set_global('lang.date.months.full', array(
				lang('january'),
				lang('february'),
				lang('march'),
				lang('april'),
				lang('may'),
				lang('june'),
				lang('july'),
				lang('august'),
				lang('september'),
				lang('october'),
				lang('november'),
				lang('december')
			));
			ee()->javascript->set_global('lang.date.months.abbreviated', array(
				lang('jan'),
				lang('feb'),
				lang('mar'),
				lang('apr'),
				lang('may'),
				lang('june'),
				lang('july'),
				lang('aug'),
				lang('sept'),
				lang('oct'),
				lang('nov'),
				lang('dec')
			));
			ee()->javascript->set_global('lang.date.days', array(
				lang('su'),
				lang('mo'),
				lang('tu'),
				lang('we'),
				lang('th'),
				lang('fr'),
				lang('sa'),
			));
			ee()->cp->add_js_script(array(
				'file' => array('cp/v3/date-picker'),
			));

			$base_url = clone $this->base_url;

			$filter = array(
				'label'			=> 'date',
				'name'			=> 'filter_by_date',
				'value'			=> '',
				'custom_value'	=> ee()->input->post('filter_by_date'),
				'placeholder'	=> lang('custom_date'),
				'attributes'	=> array(
					'rel' 		=> 'date-picker',
				),
				'options'		=> array()
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
				if (array_key_exists($this->params['filter_by_date'], $dates))
				{
					$filter['value'] = $dates[$this->params['filter_by_date']];
				}
				else
				{
					$date = ee()->localize->string_to_timestamp($this->params['filter_by_date']);
					$filter['attributes']['data-timestamp'] = $date;

					$filter['value'] = ee()->localize->format_date($date_format, $date);
					$this->params['filter_by_date'] = array($date, $date+86400);
				}
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
				'label'			=> 'show',
				'name'			=> 'perpage',
				'value'			=> $this->params['perpage'],
				'custom_value'	=> ee()->input->post('perpage'),
				'placeholder'	=> lang('custom_limit'),
				'options'		=> array()
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

	// --------------------------------------------------------------------

	/**
	 * Deletes log entries, either all at once, or one at a time
	 *
	 * @param string	$model		The name of the model to pass to
	 *								ee()->api->get()
	 * @param string	$log_type	The text used in the delete message
	 *								describing the type of log deleted
	 */
	protected function delete($model, $log_type)
	{
		if ( ! ee()->cp->allowed_group('can_access_tools', 'can_access_logs'))
		{
			show_error(lang('unauthorized_access'));
		}

		$id = ee()->input->post('delete');

		$flashdata = FALSE;
		if (strtolower($id) == 'all')
		{
			$id = NULL;
			$flashdata = TRUE;
		}

		$query = ee()->api->get($model, $id);

		$count = $query->count();
		$query->all()->delete();

		$message = sprintf(lang('logs_deleted_desc'), $count, lang($log_type));

		ee()->view->set_message('success', lang('logs_deleted'), $message, $flashdata);
	}
}
// END CLASS

/* End of file Logs.php */
/* Location: ./system/EllisLab/ExpressionEngine/Controllers/Logs/Logs.php */
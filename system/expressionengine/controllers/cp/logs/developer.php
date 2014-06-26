<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

use EllisLab\ExpressionEngine\Library\CP;
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
class Developer extends CP_Controller {

	var $perpage		= 50;
	var $params			= array();
	var $base_url;

	/**
	 * Constructor
	 */
	public function __construct()
	{
		parent::__construct();

		if ( ! $this->cp->allowed_group('can_access_logs'))
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
	private function filters($filters = NULL)
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
		$this->view->filters = $view_filters;

		// Add in any submitted search phrase
		$this->view->filter_by_phrase_value = ee()->input->get_post('filter_by_phrase');
	}

	// --------------------------------------------------------------------

	/**
	 * Shows Developer Log page
	 *
	 * @access public
	 * @return void
	 */
	public function index()
	{
		if ($this->session->userdata('group_id') != 1)
		{
			show_error(lang('unauthorized_access'));
		}

		$this->base_url->path = 'logs/developer';
		$this->view->cp_page_title = lang('view_developer_log');
		$this->filters(array('date', 'perpage'));

		$page = ee()->input->get('page') ? ee()->input->get('page') : 1;
		$page = ($page > 0) ? $page : 1;

		$offset = ($page - 1) * $this->params['perpage']; // Offset is 0 indexed

		$logs = ee()->api->get('DeveloperLog');

		if ( ! empty($this->params['filter_by_date']))
		{
			$logs = $logs->filter('timestamp', '>=', ee()->localize->now - $this->params['filter_by_date']);
		}

		// if ( ! empty($this->view->filter_by_phrase_value))
		// {
		// 	$logs = $logs->filter('action', 'LIKE', '%' . $this->view->filter_by_phrase_value . '%');
		// }

		$count = $logs->count();

		$logs = $logs->order('timestamp', 'desc')
			->limit($this->params['perpage'])
			->offset($offset)
			->all();

		$rows = array();
		foreach ($logs as $log)
		{
			if ( ! $log->function)
			{
				$description = '<p>'.$log->description.'</p>';
			}
			else
			{
				$description = '<p>';

				// "Deprecated function %s called"
				$description .= sprintf(lang('deprecated_function'), $log->function);

				// "in %s on line %d."
				if ($log->file && $log->line)
				{
					$description .= NBS.sprintf(lang('deprecated_on_line'), '<code>'.$log->file.'</code>', $log->line);
				}

				$description .= '</p>';

				// "from template tag: %s in template %s"
				if ($log->addon_module && $log->addon_method)
				{
					$description .= '<p>';
					$description .= sprintf(
						lang('deprecated_template'),
						'<code>exp:'.strtolower($log->addon_module).':'.$log->addon_method.'</code>',
						'<a href="'.cp_url('design/edit_template/'.$log->template_id).'">'.$log->template_group.'/'.$log->template_name.'</a>'
					);

					if ($log->snippets)
					{
						$snippets = explode('|', $log->snippets);

						foreach ($snippets as &$snip)
						{
							$snip = '<a href="'.cp_url('design/snippets_edit', array('snippet' => $snip)).'">{'.$snip.'}</a>';
						}

						$description .= '<br>';
						$description .= sprintf(lang('deprecated_snippets'), implode(', ', $snippets));
					}
					$description .= '</p>';
				}

				if ($log->deprecated_since
					|| $log->use_instead)
				{
					// Add a line break if there is additional information
					$description .= '<p>';

					// "Deprecated since %s."
					if ($log->deprecated_since)
					{
						$description .= sprintf(lang('deprecated_since'), $log->deprecated_since);
					}

					// "Use %s instead."
					if ($log->use_instead)
					{
						$description .= NBS.sprintf(lang('deprecated_use_instead'), $log->use_instead);
					}
					$description .= '</p>';
				}
			}

			$rows[] = array(
				'log_id'			=> $log->log_id,
				'timestamp'			=> $this->localize->human_time($log->timestamp),
				'description' 		=> $description
			);
		}

		$pagination = new Pagination($this->params['perpage'], $count, $page);
		$links = $pagination->cp_links($this->base_url);

		$vars = array(
			'rows' => $rows,
			'pagination' => $links
		);

		$this->cp->render('logs/developer', $vars);
	}

	// --------------------------------------------------------------------

	/**
	 * Deletes log entries, either all at once, or one at a time
	 *
	 * @param string $type	The type of log (developer, cp, throttle, email, search)
	 * @param mixed  $id	Either the id to delete or "all"
	 */
	public function delete($type = NULL, $id = 'all')
	{
		if (is_null($type))
		{
			show_404();
		}

		if ( ! $this->cp->allowed_group('can_access_tools', 'can_access_logs'))
		{
			show_error(lang('unauthorized_access'));
		}

		$query = ee()->api->get('DeveloperLog');

		$success_flashdata = lang('cleared_logs');
		if (strtolower($id) != 'all')
		{
			$query = $query->filter('log_id', $id);
			$success_flashdata = lang('logs_deleted');
		}

		$query->all()->delete();

		ee()->view->set_message('success', $success_flashdata, '', TRUE);
		ee()->functions->redirect(cp_url('logs/'.$type));
	}
}
// END CLASS

/* End of file tools_logs.php */
/* Location: ./system/expressionengine/controllers/cp/tools_logs.php */
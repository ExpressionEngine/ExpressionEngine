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

		// Filters
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

		$sites = FALSE;
		if (ee()->config->item('multiple_sites_enabled') === 'y' && ! IS_CORE)
		{
			$sites = array('' => '-- '.lang('by_site').' --');

			// Since the keys are numeric array_merge() is the wrong solution
			foreach (ee()->session->userdata('assigned_sites') as $site_id => $site_label)
			{
				$sites[$site_id] = $site_label;
			}
		}

		$dates = array(
			''          => '-- '.lang('by_date').' --',
			'86400'     => ucwords(lang('last').' 24 '.lang('hours')),
			'604800'    => ucwords(lang('last').' 7 '.lang('days')),
			'2592000'   => ucwords(lang('last').' 30 '.lang('days')),
			'15552000'  => ucwords(lang('last').' 180 '.lang('days')),
			'31536000'  => ucwords(lang('last').' 365 '.lang('days')),
		);

		$perpages = array(
			''    => '-- '.lang('limit_by').' --',
			'25'  => '25 '.lang('results'),
			'50'  => '50 '.lang('results'),
			'75'  => '75 '.lang('results'),
			'100' => '100 '.lang('results'),
			'150' => '150 '.lang('results')
		);

		$filter_defaults = array();

		foreach (array('filter_by_username', 'filter_by_site', 'filter_by_date') as $input_var)
		{
			if (ee()->input->get_post($input_var) !== FALSE)
			{
				$this->params[$input_var] = ee()->input->get_post($input_var);
				$filter_defaults[$input_var] = ee()->input->get_post($input_var);
			}
			else
			{
				$filter_defaults[$input_var] = array();
			}
		}

		$this->params['perpage'] = ee()->input->get_post('perpage') ? (int) ee()->input->get_post('perpage') : $this->perpage;

		// Maintain the filters in the URL
		foreach ($this->params as $key => $value)
		{
			$this->base_url->setQueryStringVariable($key, $value);
		}

		$filters = array();

		$filters[] = form_dropdown('filter_by_username', $usernames, $filter_defaults['filter_by_username']);
		if ($sites) {
			$filters[] = form_dropdown('filter_by_site', $sites, $filter_defaults['filter_by_site']);
		}
		$filters[] = form_dropdown('filter_by_date', $dates, $filter_defaults['filter_by_date']);
		$filters[] = form_dropdown('perpage', $perpages, $this->params['perpage']);

		$this->view->filters = $filters;
		$this->view->filter_by_phrase_value = ee()->input->get_post('filter_by_phrase');
	}

	// --------------------------------------------------------------------

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
			$this->developer();
		}

		$this->cp();
	}

	// --------------------------------------------------------------------

	/**
	 * View Control Panel Log Files
	 *
	 * Shows the control panel action log
	 *
	 * @access	public
	 * @return	mixed
	 */
	public function cp()
	{
		$this->base_url->path = 'logs/cp';
		$this->view->cp_page_title = lang('view_cp_log');

		$page = ee()->input->get('page') ? ee()->input->get('page') : 1;
		$page = ($page > 0) ? $page : 1;

		$offset = ($page - 1) * $this->params['perpage']; // Offset is 0 indexed

		$logs = ee()->api->get('CpLog')->with('Site');

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
			$logs = $logs->filter('act_date', '>=', ee()->localize->now - $this->params['filter_by_date']);
		}

		if ( ! empty($this->view->filter_by_phrase_value))
		{
			$logs = $logs->filter('action', 'LIKE', '%' . $this->view->filter_by_phrase_value . '%');
		}

		$count = $logs->count();

		$logs = $logs->order('act_date', 'desc')
			->limit($this->params['perpage'])
			->offset($offset)
			->all();

		$rows = array();
		foreach ($logs as $log)
		{
			$rows[] = array(
				'id'		 => $log->id,
				'member_id'	 => $log->member_id,
				'username'	 => "<a href='" . cp_url('myaccount', array('id' => $log->member_id)) . "'>{$log->username}</a>",
				'ip_address' => $log->ip_address,
				'act_date'	 => $this->localize->human_time($log->act_date),
				'site_label' => $log->getSite()->site_label,
				'action'	 => $log->action
			);
		}

		$pagination = new Pagination($this->params['perpage'], $count, $page);
		$links = $pagination->cp_links($this->base_url);

		$vars = array(
			'rows' => $rows,
			'pagination' => $links
		);

		$this->cp->render('logs/cp', $vars);
	}

	// --------------------------------------------------------------------

	/**
	 * View Search Log
	 *
	 * Shows a log of recent search terms
	 *
	 * @access	public
	 * @return	mixed
	 */
	public function search()
	{
		if ( ! $this->cp->allowed_group('can_access_tools', 'can_access_logs'))
		{
			show_error(lang('unauthorized_access'));
		}

		$this->base_url->path = 'logs/search';
		$this->view->cp_page_title = lang('view_search_log');

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

		// if ( ! empty($this->view->filter_by_phrase_value))
		// {
		// 	$logs = $logs->filter('action', 'LIKE', '%' . $this->view->filter_by_phrase_value . '%');
		// }

		$count = $logs->count();

		$logs = $logs->order('search_date', 'desc')
			->limit($this->params['perpage'])
			->offset($offset)
			->all();

		$rows = array();
		foreach ($logs as $log)
		{
			$rows[] = array(
				'id'				=> $log->id,
				'username'			=> "<a href='" . cp_url('myaccount', array('id' => $log->member_id)) . "'>{$log->screen_name}</a>",
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

		$this->cp->render('logs/search', $vars);
	}

	// --------------------------------------------------------------------

	/**
	 * View Throttle Log
	 *
	 * Shows a list of ips that are currently throttled
	 *
	 * @access	public
	 * @return	mixed
	 */
	public function throttle()
	{
		if ( ! $this->cp->allowed_group('can_access_tools', 'can_access_logs'))
		{
			show_error(lang('unauthorized_access'));
		}

		$this->base_url->path = 'logs/throttle';
		$this->view->cp_page_title = lang('view_throttle_log');

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

			// if ( ! empty($this->params['filter_by_username']))
			// {
			// 	$logs = $logs->filter('member_id', $this->params['filter_by_username']);
			// }
			//
			// if ( ! empty($this->params['filter_by_site']))
			// {
			// 	$logs = $logs->filter('site_id', $this->params['filter_by_site']);
			// }
			//
			if ( ! empty($this->params['filter_by_date']))
			{
				$logs = $logs->filter('last_activity', '>=', ee()->localize->now - $this->params['filter_by_date']);
			}
			//
			// if ( ! empty($this->view->filter_by_phrase_value))
			// {
			// 	$logs = $logs->filter('action', 'LIKE', '%' . $this->view->filter_by_phrase_value . '%');
			// }

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
	 * View Email Log
	 *
	 * Displays emails logged
	 *
	 * @access	public
	 * @return	mixed
	 */
	public function email()
	{
		if ( ! $this->cp->allowed_group('can_access_tools', 'can_access_logs'))
		{
			show_error(lang('unauthorized_access'));
		}

		$this->base_url->path = 'logs/email';
		$this->view->cp_page_title = lang('view_email_logs');

		$page = ee()->input->get('page') ? ee()->input->get('page') : 1;
		$page = ($page > 0) ? $page : 1;

		$offset = ($page - 1) * $this->params['perpage']; // Offset is 0 indexed

		$logs = ee()->api->get('EmailConsoleCache');

		if ( ! empty($this->params['filter_by_username']))
		{
			$logs = $logs->filter('member_id', $this->params['filter_by_username']);
		}

		// if ( ! empty($this->params['filter_by_site']))
		// {
		// 	$logs = $logs->filter('site_id', $this->params['filter_by_site']);
		// }

		if ( ! empty($this->params['filter_by_date']))
		{
			$logs = $logs->filter('cache_date', '>=', ee()->localize->now - $this->params['filter_by_date']);
		}

		// if ( ! empty($this->view->filter_by_phrase_value))
		// {
		// 	$logs = $logs->filter('action', 'LIKE', '%' . $this->view->filter_by_phrase_value . '%');
		// }

		$count = $logs->count();

		$logs = $logs->order('cache_date', 'desc')
			->limit($this->params['perpage'])
			->offset($offset)
			->all();

		$rows = array();
		foreach ($logs as $log)
		{
			$rows[] = array(
				'cache_id'			=> $log->cache_id,
				'username'			=> "<a href='" . cp_url('myaccount', array('id' => $log->member_id)) . "'>{$log->member_name}</a>",
				'ip_address'		=> $log->ip_address,
				'cache_date'		=> $this->localize->human_time($log->cache_date),
				'subject' 			=> $log->subject,
				'recipient_name'	=> $log->recipient_name
			);
		}

		$pagination = new Pagination($this->params['perpage'], $count, $page);
		$links = $pagination->cp_links($this->base_url);

		$vars = array(
			'rows' => $rows,
			'pagination' => $links
		);

		$this->cp->render('logs/email', $vars);
	}

	// --------------------------------------------------------------------

	/**
	 * Shows Developer Log page
	 *
	 * @access public
	 * @return void
	 */
	public function developer()
	{
		if ($this->session->userdata('group_id') != 1)
		{
			show_error(lang('unauthorized_access'));
		}

		$this->base_url->path = 'logs/developer';
		$this->view->cp_page_title = lang('view_developer_log');

		$page = ee()->input->get('page') ? ee()->input->get('page') : 1;
		$page = ($page > 0) ? $page : 1;

		$offset = ($page - 1) * $this->params['perpage']; // Offset is 0 indexed

		$logs = ee()->api->get('DeveloperLog');

		// if ( ! empty($this->params['filter_by_username']))
		// {
		// 	$logs = $logs->filter('member_id', $this->params['filter_by_username']);
		// }
		//
		// if ( ! empty($this->params['filter_by_site']))
		// {
		// 	$logs = $logs->filter('site_id', $this->params['filter_by_site']);
		// }

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
					|| $log->deprecated_use_instead)
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

		$model = '';

		switch($type)
		{
			case 'developer':
				$model = 'DeveloperLog';
				$id_field = 'log_id';
				break;
			case 'cp':
				$model = 'CpLog';
				$id_field = 'id';
				break;
			case 'throttle':
				$model = 'Throttle';
				$id_field = 'throttle_id';
				break;
			case 'email':
				$model = 'EmailConsoleCache';
				$id_field = 'cache_id';
				break;
			case 'search':
				$model = 'SearchLog';
				$id_field = 'id';
				break;
		}

		$query = ee()->api->get($model);

		$success_flashdata = lang('cleared_logs');
		if (strtolower($id) != 'all')
		{
			$query = $query->filter($id_field, $id);
			$success_flashdata = lang('logs_deleted');
		}

		$query->all()->delete();

		ee()->view->set_message('success', $success_flashdata, '', TRUE);
		ee()->functions->redirect(cp_url('logs/'.$type));
	}

	// --------------------------------------------------------------------

	/**
	 * View Single Email
	 *
	 * @access	public
	 * @return	mixed
	 */
	public function view_email()
	{
		if ( ! $this->cp->allowed_group('can_access_tools', 'can_access_logs'))
		{
			show_error(lang('unauthorized_access'));
		}

		$id = $this->input->get_post('id');

		$query = $this->db->query("SELECT subject, message, recipient, recipient_name, member_name, ip_address FROM exp_email_console_cache WHERE cache_id = '$id' ");

		if ($query->num_rows() == 0)
		{
			$this->session->set_flashdata('message_failure', lang('no_cached_email'));
			$this->functions->redirect(BASE.AMP.'C=tools_logs'.AMP.'M=view_email_log');
		}

		$this->cp->render('tools/view_email', $query->row_array());
	}

	// --------------------------------------------------------------------

	/**
	 * Blacklist Throttled IPs
	 *
	 * @access	public
	 * @return	mixed
	 */
	public function blacklist_throttled_ips()
	{
		if ( ! $this->cp->allowed_group('can_access_tools', 'can_access_logs'))
		{
			show_error(lang('unauthorized_access'));
		}

		if ($this->config->item('enable_throttling') == 'n')
		{
			show_error(lang('throttling_disabled'));
		}

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

		$throttled = $this->tools_model->get_throttle_log($max_page_loads, $lockout_time);

		$ips = array();

		foreach($throttled->result() as $row)
		{
			$ips[] = $row->ip_address;
		}

		$this->tools_model->blacklist_ips($ips);

		$this->lang->loadfile('blacklist');

		// The blacklist module takes care of the htaccess
		if ($this->session->userdata['group_id'] == 1 && $this->config->item('htaccess_path') !== FALSE && file_exists($this->config->item('htaccess_path')) && is_writable($this->config->item('htaccess_path')))
 		{
			if ( ! class_exists('Blacklist'))
	 		{
	 			require PATH_MOD.'blacklist/mcp.blacklist.php';
	 		}

	 		$MOD = new Blacklist_mcp();

 			$_POST['htaccess_path'] = $this->config->item('htaccess_path');
 			$MOD->write_htaccess(FALSE);
 		}

		$this->session->set_flashdata('message_success', lang('blacklist_updated'));
		$this->functions->redirect(BASE.AMP.'C=tools_logs'.AMP.'M=view_throttle_log');
	}
}
// END CLASS

/* End of file tools_logs.php */
/* Location: ./system/expressionengine/controllers/cp/tools_logs.php */
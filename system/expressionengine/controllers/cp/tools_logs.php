<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
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
class Tools_logs extends CP_Controller {

	var $perpage		= 50;

	/**
	 * Constructor
	 */
	function __construct()
	{
		parent::__construct();

		if ( ! $this->cp->allowed_group('can_access_tools', 'can_access_logs'))
		{
			show_error(lang('unauthorized_access'));
		}

		$this->load->model('tools_model');
		$this->lang->loadfile('tools');

		$this->load->vars(array('controller' => 'tools/tools_logs'));
	}

	// --------------------------------------------------------------------

	/**
	 * Index function
	 *
	 * @access	public
	 * @return	void
	 */
	function index()
	{
		if ( ! $this->cp->allowed_group('can_access_tools', 'can_access_logs'))
		{
			show_error(lang('unauthorized_access'));
		}

		$this->cp->set_breadcrumb(BASE.AMP.'C=tools', lang('tools'));

		$this->view->cp_page_title = lang('tools_logs');
		$this->cp->render('_shared/overview');
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
	function view_cp_log()
	{
		if ( ! $this->cp->allowed_group('can_access_tools', 'can_access_logs'))
		{
			show_error(lang('unauthorized_access'));
		}

		$this->load->library('table');

		$this->table->set_base_url('C=tools_logs'.AMP.'M=view_cp_log');
		$this->table->set_columns(array(
			'member_id'		=> array('html' => FALSE),
			'username'		=> array(),
			'ip_address'	=> array('html' => FALSE),
			'act_date'		=> array('html' => FALSE, 'header' => lang('date')),
			'site_label'	=> array('html' => FALSE, 'header' => lang('site_search')),
			'action'		=> array('html' => FALSE)
		));


		$initial_state = array(
			'sort'	=> array('act_date' => 'desc')
		);

		$params = array(
			'perpage'	=> $this->perpage
		);

		$vars = $this->table->datasource('_cp_log_filter', $initial_state, $params);

		$this->view->cp_page_title = lang('view_cp_log');

		// a bit of a breadcrumb override is needed
		$this->view->cp_breadcrumbs = array(
			BASE.AMP.'C=tools' => lang('tools'),
			BASE.AMP.'C=tools_logs'=> lang('tools_logs')
		);

		$this->cp->render('tools/view_cp_log', $vars);
	}

	// --------------------------------------------------------------------

	/**
	 * Ajax filter for CP log
	 *
	 * Filters CP log data
	 *
	 * @access	public
	 * @return	void
	 */
	function _cp_log_filter($state, $params)
	{
		$log_q = $this->tools_model->get_cp_log($params['perpage'], $state['offset'], $state['sort']);

		$rows = array();
		$logs = $log_q->result_array();

		while ($log = array_shift($logs))
		{
			$rows[] = array(
				'member_id'	 => $log['member_id'],
				'username'	 => "<strong><a href='".BASE.AMP.'C=myaccount'.AMP.'id='.$log['member_id']."'>{$log['username']}</a></strong>",
				'ip_address' => $log['ip_address'],
				'act_date'	 => $this->localize->human_time($log['act_date']),
				'site_label' => $log['site_label'],
				'action'	 => $log['action']
			);
		}

		return array(
			'rows' => $rows,
			'no_results' => '<p>'.lang('no_search_results').'</p>',
			'pagination' => array(
				'per_page' => $params['perpage'],
				'total_rows' => $this->db->count_all('cp_log')
			)
		);
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
	function view_search_log()
	{
		if ( ! $this->cp->allowed_group('can_access_tools', 'can_access_logs'))
		{
			show_error(lang('unauthorized_access'));
		}

		$this->load->library('table');

		$this->table->set_base_url('C=tools_logs'.AMP.'M=view_search_log');
		$this->table->set_columns(array(
			'screen_name'	=> array(),
			'ip_address'	=> array(),
			'search_date'	=> array('html' => FALSE, 'header' => lang('date')),
			'site_label'	=> array('html' => FALSE, 'header' => lang('site')),
			'search_type'	=> array('html' => FALSE, 'header' => lang('searched_in')),
			'search_terms'	=> array('html' => FALSE, 'header' => lang('search_terms'))
		));

		$initial_state = array(
			'sort'	=> array('search_date' => 'desc')
		);

		$params = array(
			'perpage'	=> $this->perpage
		);

		$vars = $this->table->datasource('_search_log_filter', $initial_state, $params);

		$this->view->cp_page_title = lang('view_search_log');

		// a bit of a breadcrumb override is needed
		$this->view->cp_breadcrumbs = array(
			BASE.AMP.'C=tools' => lang('tools'),
			BASE.AMP.'C=tools_logs'=> lang('tools_logs')
		);

		$this->cp->render('tools/view_search_log', $vars);
	}

	// --------------------------------------------------------------------

	/**
	 * Ajax filter for Search log
	 *
	 * Filters Search log data
	 *
	 * @access	public
	 * @return	void
	 */
	function _search_log_filter($state, $params)
	{
		$search_q = $this->tools_model->get_search_log(
			$params['perpage'], $state['offset'], $state['sort']
		);
		$searches = $search_q->result_array();

		$rows = array();

		while ($log = array_shift($searches))
		{
			$screen_name = ($log['screen_name'] != '') ? '<a href="'.BASE.AMP.'C=myaccount'.AMP.'id='. $log['member_id'].'">'.$log['screen_name'].'</a>' : ' -- ';

			$rows[] = array(
				'screen_name'	=> $screen_name,
				'ip_address'	=> $log['ip_address'],
				'search_date'	=> $this->localize->human_time($log['search_date']),
				'site_label'	=> $log['site_label'],
				'search_type'	=> $log['search_type'],
				'search_terms'	=> $log['search_terms']
			);
		}

		return array(
			'rows' => $rows,
			'no_results' => '<p>'.lang('no_search_results').'</p>',
			'pagination' => array(
				'per_page' => $params['perpage'],
				'total_rows' => $this->db->count_all('search_log')
			)
		);
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
	function view_throttle_log()
	{
		if ( ! $this->cp->allowed_group('can_access_tools', 'can_access_logs'))
		{
			show_error(lang('unauthorized_access'));
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

		$this->load->library('table');

		$this->table->set_base_url('C=tools_logs'.AMP.'M=view_throttle_log');
		$this->table->set_columns(array(
			'ip_address'	=> array('html' => FALSE),
			'hits'			=> array('html' => FALSE),
			'last_activity'	=> array('html' => FALSE)
		));

		$initial_state = array(
			'sort'	=> array('ip_address' => 'desc')
		);

		$params = array(
			'perpage'	=> $this->perpage
		);

		$data = $this->table->datasource('_throttle_log_filter', $initial_state, $params);

		$this->view->cp_page_title = lang('view_throttle_log');

		// a bit of a breadcrumb override is needed
		$this->view->cp_breadcrumbs = array(
			BASE.AMP.'C=tools' => lang('tools'),
			BASE.AMP.'C=tools_logs'=> lang('tools_logs')
		);

		// Blacklist Installed?
		$this->db->where('module_name', 'Blacklist');
		$count = $this->db->count_all_results('modules');

		$data['blacklist_installed'] = ($count > 0);

		$this->cp->render('tools/view_throttle_log', $data);
	}

	// --------------------------------------------------------------------

	/**
	 * Ajax filter for Throttle log
	 *
	 * Filters Throttle log data
	 *
	 * @access	public
	 * @return	void
	 */
	function _throttle_log_filter($state, $params)
	{
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

		$throttle_q = $this->tools_model->get_throttle_log(
			$max_page_loads, $lockout_time, $params['perpage'], $state['offset'], $state['sort']
		);

		$throttled = $throttle_q->result_array();

		$rows = array();

		while ($log = array_shift($throttled))
		{
			$rows[] = array(
				'ip_address'	=> $log['ip_address'],
				'hits'			=> $log['hits'],
				'last_activity'	=> $this->localize->human_time($log['last_activity'])
			);
		}

		$this->db->where('(hits >= "'.$max_page_loads.'" OR (locked_out = "y" AND last_activity > "'.$lockout_time.'"))', NULL, FALSE);
		$this->db->from('throttle');
		$total = $this->db->count_all_results();

		return array(
			'rows' => $rows,
			'no_results' => '<p>'.lang('no_throttle_logs').'</p>',
			'pagination' => array(
				'per_page' => $params['perpage'],
				'total_rows' => $total
			)
		);
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
	function view_email_log()
	{
		if ( ! $this->cp->allowed_group('can_access_tools', 'can_access_logs'))
		{
			show_error(lang('unauthorized_access'));
		}

		$this->load->library('table');
		$this->lang->loadfile('members');


		$this->table->set_base_url('C=tools_logs'.AMP.'M=view_email_log');
		$this->table->set_columns(array(
			'subject'		=> array('header' => lang('email_title')),
			'member_name'	=> array('header' => lang('from')),
			'recipient_name'=> array('header' => lang('to'), 'html' => FALSE),
			'cache_date'	=> array('header' => lang('date')),
			'_check'		=> array(
				'header' => form_checkbox(
					array(
						'id'		=>'toggle_all',
						'name'		=>'toggle_all',
						'value'		=>'toggle_all',
						'checked'	=> FALSE
					)
				)
			)
		));

		$initial_state = array(
			'sort'	=> array('cache_date' => 'desc')
		);

		$params = array(
			'perpage'	=> $this->perpage
		);

		$data = $this->table->datasource('_email_log_filter', $initial_state, $params);

		$this->view->cp_page_title = lang('view_email_logs');

		// a bit of a breadcrumb override is needed
		$this->view->cp_breadcrumbs = array(
			BASE.AMP.'C=tools' => lang('tools'),
			BASE.AMP.'C=tools_logs'=> lang('tools_logs')
		);

		$this->javascript->output('
			$("#toggle_all").toggle(
				function(){
					$("input[class=toggle_email]").each(function() {
						this.checked = true;
					});
				}, function (){
					$("input[class=toggle_email]").each(function() {
						this.checked = false;
					});
				}
			);
		');

		if (count($data['rows']))
		{
			$this->cp->set_right_nav(array(
				'clear_logs' => BASE.AMP.'C=tools_logs'.AMP.'M=clear_log_files'.AMP.'type=email'
			));
		}

		$this->cp->render('tools/view_email_log', $data);
	}

	// --------------------------------------------------------------------

	/**
	 * Ajax filter for Email log
	 *
	 * Filters Email log data
	 *
	 * @access	public
	 * @return	void
	 */
	function _email_log_filter($state, $params)
	{
		$email_q = $this->tools_model->get_email_logs(
			FALSE, $params['perpage'], $state['offset'], $state['sort']
		);

		$emails = $email_q->result_array();

		$rows = array();

		while ($log = array_shift($emails))
		{
			$rows[] = array(
				'subject'		 => '<a href="'.BASE.AMP.'C=tools_logs'.AMP.'M=view_email'.AMP.'id='.$log['cache_id'].'">'.$log['subject'].'</a>',
				'member_name'	 => '<a href="'.BASE.AMP.'C=myaccount'.AMP.'id='. $log['member_id'].'">'.$log['member_name'].'</a>',
				'recipient_name' => $log['recipient_name'],
				'cache_date'	 => $this->localize->human_time($log['cache_date']),
				'_check'		 => form_checkbox(array(
					'id'	=>'delete_box_'.$log['cache_id'],
					'name'	=>'toggle[]',
					'value'	=>$log['cache_id'],
					'class'	=>'toggle_email',
					'checked' =>FALSE
				))
			);
		}

		return array(
			'rows' => $rows,
			'no_results' => '<p>'.lang('no_cached_email').'</p>',
			'pagination' => array(
				'per_page' => $params['perpage'],
				'total_rows' => $this->db->count_all('email_console_cache')
			)
		);
	}

	// --------------------------------------------------------------------

	/**
	 * Shows Developer Log page
	 *
	 * @access public
	 * @return void
	 */
	function view_developer_log()
	{
		if ($this->session->userdata('group_id') != 1)
		{
			show_error(lang('unauthorized_access'));
		}

		$this->load->library('table');
		$this->table->set_base_url('C=tools_logs'.AMP.'M=view_developer_log');
		$this->table->set_columns(array(
			'log_id'		=> array('header' => lang('log_id')),
			'timestamp'		=> array('header' => lang('date')),
			'description'	=> array('header' => lang('log_message')),
			'_check'		=> array(
				'header' => form_checkbox(
					array(
						'id'		=>'toggle_all',
						'name'		=>'toggle_all',
						'value'		=>'toggle_all',
						'checked'	=> FALSE
					)
				)
			)
		));

		$initial_state = array(
			'sort'	=> array('timestamp' => 'desc')
		);

		$params = array(
			'perpage'	=> $this->perpage
		);

		$vars = $this->table->datasource('_developer_log_filter', $initial_state, $params);

		$this->view->cp_page_title = lang('view_developer_log');

		// a bit of a breadcrumb override is needed
		$this->view->cp_breadcrumbs = array(
			BASE.AMP.'C=tools' => lang('tools'),
			BASE.AMP.'C=tools_logs'=> lang('tools_logs')
		);

		$this->load->library('logger');

		// Now that we've gotten the logs we're going to show, mark them as viewed;
		// note since we already have the logs array, this change won't be visible on
		// this particular page load, which is what we want. Next time the page loads,
		// the logs will appear as viewed.
		$this->tools_model->mark_developer_logs_as_viewed($vars['rows']);

		// Set JS globals for "What does this mean?" modal
		$this->javascript->set_global(
			array(
				'developer_log' => array(
					'dev_log_help'			=> lang('dev_log_help'),
					'deprecation_meaning'	=> lang('deprecated_meaning')
				)
			)
		);

		$this->cp->render('tools/view_developer_log', $vars);
	}

	// --------------------------------------------------------------------

	/**
	 * Ajax filter for Email log
	 *
	 * Filters Email log data
	 *
	 * @access	public
	 * @return	void
	 */
	function _developer_log_filter($state, $params)
	{
		$dev_logs_query = $this->tools_model->get_developer_log(
			$params['perpage'], $state['offset'], $state['sort']
		);

		$dev_logs = $dev_logs_query->result_array();

		$rows = array();

		while ($log = array_shift($dev_logs))
		{
			$new = ($log['viewed'] == 'n') ? 'new' : '';

			$rows[] = array(
				'log_id' => array(
					'data' 	=> $log['log_id'],
					'class'	=> $new
				),
				'timestamp' => array(
					'data'	=> date('Y-m-d h:i A', $log['timestamp']),
					'class'	=> $new
				),
				'description' => array(
					'data'	=> $this->logger->build_deprecation_language($log),
					'class'	=> $new
				),
				'viewed' => $log['viewed'],
				'_check' => array(
					'data' => form_checkbox(
						array(
							'id'		=>'delete_box_'.$log['log_id'],
							'name'		=>'toggle[]',
							'value'		=>$log['log_id'],
							'class'		=>'toggle_email',
							'checked'	=> FALSE
						)
					),
					'class'	=> $new
				)
			);
		}

		return array(
			'rows' => $rows,
			'no_results' => '<p>'.lang('no_search_results').'</p>',
			'pagination' => array(
				'per_page' => $params['perpage'],
				'total_rows' => $this->db->count_all('developer_log')
			)
		);
	}

	// --------------------------------------------------------------------

	/**
	 * Clear Logs Files, or deletes individual logs if given log IDs
	 *
	 * @access	public
	 * @return	mixed
	 */
	function clear_log_files()
	{
		if ( ! $this->cp->allowed_group('can_access_tools', 'can_access_logs'))
		{
			show_error(lang('unauthorized_access'));
		}

		$type = $this->input->get_post('type');
		$toggle = $this->input->get_post('toggle');

		$table = FALSE;
		$id_field = FALSE;

		switch($type)
		{
			case 'cp':
				$table = 'cp_log';
				$id_field = 'id';
				break;
			case 'search':
				$table = 'search_log';
				$id_field = 'id';
				break;
			case 'email':
				$table = 'email_console_cache';
				$id_field = 'cache_id';
				break;
			case 'developer':
				$table = 'developer_log';
				$id_field = 'log_id';
				break;
			default: //nothing
		}

		if ($table)
		{
			$success_flashdata = lang('cleared_logs');
			$log_ids = array();

			// If we were passed any log IDs, create an array of those
			if ( ! empty($toggle))
			{
				foreach ($_POST['toggle'] as $key => $val)
				{
					$log_ids[] = $this->db->escape_str($val);
				}

				$success_flashdata = lang('logs_deleted');
			}

			// Clear logs, or delete logs if $log_ids is populated
			$this->tools_model->delete_logs($table, $id_field, $log_ids);

			// Redirect to where we came from
			$view_page = 'view_'.$type.'_log';

			$this->session->set_flashdata('message_success', $success_flashdata);
			$this->functions->redirect(BASE.AMP.'C=tools_logs'.AMP.'M='.$view_page);
		}

		// No log type selected - page doesn't exist
		show_404();
	}

	// --------------------------------------------------------------------

	/**
	 * View Single Email
	 *
	 * @access	public
	 * @return	mixed
	 */
	function view_email()
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
	function blacklist_throttled_ips()
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
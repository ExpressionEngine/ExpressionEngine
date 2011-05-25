<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		ExpressionEngine Dev Team
 * @copyright	Copyright (c) 2003 - 2011, EllisLab, Inc.
 * @license		http://expressionengine.com/user_guide/license.html
 * @link		http://expressionengine.com
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
 * @author		ExpressionEngine Dev Team
 * @link		http://expressionengine.com
 */
class Tools_logs extends CI_Controller {
	
	var $perpage		= 50;
	var $pipe_length	= 3;

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

		$this->cp->set_variable('cp_page_title', lang('tools_logs'));
		$this->cp->set_breadcrumb(BASE.AMP.'C=tools', lang('tools'));

		$this->javascript->compile();

		$this->load->view('_shared/overview');
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

		$this->cp->set_variable('cp_page_title', lang('view_cp_log'));

		// a bit of a breadcrumb override is needed
		$this->cp->set_variable('cp_breadcrumbs', array(
			BASE.AMP.'C=tools' => lang('tools'),
			BASE.AMP.'C=tools_logs'=> lang('tools_logs')
		));

		$this->cp->add_js_script(array('plugin' => 'dataTables'));
		
		$this->javascript->output($this->ajax_filters('view_cp_ajax_filter', 6));

		$this->javascript->compile();
		
		$total = $this->db->count_all('cp_log');
		
		$row = ( ! $this->input->get_post('per_page')) ? 0 : $this->input->get_post('per_page');
		$vars['pagination'] = FALSE;

		if ($total > $this->perpage)
		{
			$this->load->library('pagination');
			
			$config['base_url'] = BASE.AMP.'C=tools_logs'.AMP.'M=view_cp_log';
			$config['total_rows'] = $total;
			$config['per_page'] = $this->perpage;
			$config['page_query_string'] = TRUE;
			$config['first_link'] = lang('pag_first_link');
			$config['last_link'] = lang('pag_last_link');
						
			$this->pagination->initialize($config);	
			$vars['pagination'] = $this->pagination->create_links();
		}

		$vars['cp_data'] = $this->tools_model->get_cp_log($this->perpage, $row);
		
		$this->load->view('tools/view_cp_log', $vars);
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
	function view_cp_ajax_filter()
	{
		if ( ! $this->cp->allowed_group('can_access_tools', 'can_access_logs'))
		{
			show_error(lang('unauthorized_access'));
		}

		$this->output->enable_profiler(FALSE);
		
		$col_map = array('member_id', 'username', 'ip_address', 'act_date', 'site_label', 'action');
		
		// Note- we pipeline the js, so pull more data than are displayed on the page		
		$perpage = $this->input->get_post('iDisplayLength');
		$offset = ($this->input->get_post('iDisplayStart')) ? $this->input->get_post('iDisplayStart') : 0; // Display start point
		$sEcho = $this->input->get_post('sEcho');	

		/* Ordering */
		$order = array();
		
		if ($this->input->get('iSortCol_0') !== FALSE)
		{
			for ( $i=0; $i < $this->input->get('iSortingCols'); $i++ )
			{
				if (isset($col_map[$this->input->get('iSortCol_'.$i)]))
				{
					$order[$col_map[$this->input->get('iSortCol_'.$i)]] = ($this->input->get('sSortDir_'.$i) == 'asc') ? 'asc' : 'desc';
				}
			}
		}
		
		$query = $this->tools_model->get_cp_log($perpage, $offset, $order);
		
		$total = $this->db->count_all('cp_log');


		$j_response['sEcho'] = $sEcho;
		$j_response['iTotalRecords'] = $total;
		$j_response['iTotalDisplayRecords'] = $total;
		
		$tdata = array();
		$i = 0;
		
		foreach ($query->result_array() as $log)
		{
			$m[] = $log['member_id'];
			$m[] = "<strong><a href='".BASE.AMP.'C=myaccount'.AMP.'id='.$log['member_id']."'>{$log['username']}</a></strong>";
			$m[] = $log['ip_address'];
			$m[] = $this->localize->set_human_time($log['act_date']);
			$m[] = $log['site_label'];	
			$m[] = $log['action'];	

			$tdata[$i] = $m;
			$i++;
			unset($m);
		}		

		$j_response['aaData'] = $tdata;	
		$sOutput = $this->javascript->generate_json($j_response, TRUE);
	
		exit($sOutput);
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

		$this->cp->set_variable('cp_page_title', lang('view_search_log'));

		// a bit of a breadcrumb override is needed
		$this->cp->set_variable('cp_breadcrumbs', array(
			BASE.AMP.'C=tools' => lang('tools'),
			BASE.AMP.'C=tools_logs'=> lang('tools_logs')
		));

		$this->cp->add_js_script(array('plugin' => 'dataTables'));
		
		$this->javascript->output($this->ajax_filters('view_search_ajax_filter', 6));

		$this->javascript->compile();
		
		$total = $this->db->count_all('search_log');
		
		$row = ( ! $this->input->get_post('per_page')) ? 0 : $this->input->get_post('per_page');
		$vars['pagination'] = FALSE;

		if ($total > $this->perpage)
		{
			$this->load->library('pagination');
			
			$config['base_url'] = BASE.AMP.'C=tools_logs'.AMP.'M=view_search_log';
			$config['total_rows'] = $total;
			$config['per_page'] = $this->perpage;
			$config['page_query_string'] = TRUE;
			$config['first_link'] = lang('pag_first_link');
			$config['last_link'] = lang('pag_last_link');
			
			$this->pagination->initialize($config);	
			$vars['pagination'] = $this->pagination->create_links();
		}

		$vars['search_data'] = $this->tools_model->get_search_log($this->perpage, $row);

		$this->load->view('tools/view_search_log', $vars);
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
	function view_search_ajax_filter()
	{
		if ( ! $this->cp->allowed_group('can_access_tools', 'can_access_logs'))
		{
			show_error(lang('unauthorized_access'));
		}

		$this->output->enable_profiler(FALSE);
		
		$col_map = array('screen_name', 'ip_address', 'search_date', 'site_label', 'search_type', 'search_terms');
		
		// Note- we pipeline the js, so pull more data than are displayed on the page		
		$perpage = $this->input->get_post('iDisplayLength');
		$offset = ($this->input->get_post('iDisplayStart')) ? $this->input->get_post('iDisplayStart') : 0; // Display start point
		$sEcho = $this->input->get_post('sEcho');	

		/* Ordering */
		$order = array();
		
		if ($this->input->get('iSortCol_0') !== FALSE)
		{
			for ( $i=0; $i < $this->input->get('iSortingCols'); $i++ )
			{
				if (isset($col_map[$this->input->get('iSortCol_'.$i)]))
				{
					$order[$col_map[$this->input->get('iSortCol_'.$i)]] = ($this->input->get('sSortDir_'.$i) == 'asc') ? 'asc' : 'desc';
				}
			}
		}
		
		$query = $this->tools_model->get_search_log($perpage, $offset, $order);
		
		$total = $this->db->count_all('search_log');


		$j_response['sEcho'] = $sEcho;
		$j_response['iTotalRecords'] = $total;
		$j_response['iTotalDisplayRecords'] = $total;
		
		$tdata = array();
		$i = 0;
		
		foreach ($query->result_array() as $log)
		{
			$screen_name = ($log['screen_name'] != '') ? '<a href="'.BASE.AMP.'C=myaccount'.AMP.'id='. $log['member_id'].'">'.$log['screen_name'].'</a>' : ' -- ';
			
			$m[] = $screen_name;
			$m[] = $log['ip_address'];
			$m[] = $this->localize->set_human_time($log['search_date']);
			$m[] = $log['site_label'];	
			$m[] = $log['search_type'];	
			$m[] = $log['search_terms'];	
			
			$tdata[$i] = $m;
			$i++;
			unset($m);
		}		

		$j_response['aaData'] = $tdata;	
		$sOutput = $this->javascript->generate_json($j_response, TRUE);
	
		exit($sOutput);
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

		$this->cp->set_variable('cp_page_title', lang('view_throttle_log'));

		// a bit of a breadcrumb override is needed
		$this->cp->set_variable('cp_breadcrumbs', array(
			BASE.AMP.'C=tools' => lang('tools'),
			BASE.AMP.'C=tools_logs'=> lang('tools_logs')
		));

		$this->cp->add_js_script(array('plugin' => 'dataTables'));
		
		$this->javascript->output($this->ajax_filters('view_throttle_ajax_filter', 3));

		$this->javascript->compile();
		
		$this->db->where('(hits >= "'.$max_page_loads.'" OR (locked_out = "y" AND last_activity > "'.$lockout_time.'"))', NULL, FALSE);
		$this->db->from('throttle');
		$total = $this->db->count_all_results();		
		
		$row = ( ! $this->input->get_post('per_page')) ? 0 : $this->input->get_post('per_page');
		$vars['pagination'] = FALSE;

		if ($total > $this->perpage)
		{
			$this->load->library('pagination');
			
			$config['base_url'] = BASE.AMP.'C=tools_logs'.AMP.'M=view_throttle_log';
			$config['total_rows'] = $total;
			$config['per_page'] = $this->perpage;
			$config['page_query_string'] = TRUE;
			$config['first_link'] = lang('pag_first_link');
			$config['last_link'] = lang('pag_last_link');
						
			$this->pagination->initialize($config);	
			$vars['pagination'] = $this->pagination->create_links();
		}
		
		// Blacklist Installed?
		$this->db->where('module_name', 'Blacklist');
		$count = $this->db->count_all_results('modules');

		$vars['blacklist_installed'] = ($count > 0);

		$vars['throttle_data'] = $this->tools_model->get_throttle_log($max_page_loads, $lockout_time, $this->perpage, $row);

		$this->load->view('tools/view_throttle_log', $vars);
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
	function view_throttle_ajax_filter()
	{
		if ( ! $this->cp->allowed_group('can_access_tools', 'can_access_logs'))
		{
			show_error(lang('unauthorized_access'));
		}

		$this->output->enable_profiler(FALSE);
		
		$col_map = array('ip_address', 'hits', 'last_activity');
		
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
		
		// Note- we pipeline the js, so pull more data than are displayed on the page		
		$perpage = $this->input->get_post('iDisplayLength');
		$offset = ($this->input->get_post('iDisplayStart')) ? $this->input->get_post('iDisplayStart') : 0; // Display start point
		$sEcho = $this->input->get_post('sEcho');	

		/* Ordering */
		$order = array();
		
		if ($this->input->get('iSortCol_0') !== FALSE)
		{
			for ( $i=0; $i < $this->input->get('iSortingCols'); $i++ )
			{
				if (isset($col_map[$this->input->get('iSortCol_'.$i)]))
				{
					$order[$col_map[$this->input->get('iSortCol_'.$i)]] = ($this->input->get('sSortDir_'.$i) == 'asc') ? 'asc' : 'desc';
				}
			}
		}
		
		$query = $this->tools_model->get_throttle_log($max_page_loads, $lockout_time, $perpage, $offset, $order);
		
		$this->db->where('(hits >= "'.$max_page_loads.'" OR (locked_out = "y" AND last_activity > "'.$lockout_time.'"))', NULL, FALSE);
		$this->db->from('throttle');
		$total = $this->db->count_all_results();


		$j_response['sEcho'] = $sEcho;
		$j_response['iTotalRecords'] = $total;
		$j_response['iTotalDisplayRecords'] = $total;
		
		$tdata = array();
		$i = 0;
		
		foreach ($query->result_array() as $log)
		{
		
			$m[] = $log['ip_address'];
			$m[] = $log['hits'];
			$m[] = $this->localize->set_human_time($log['last_activity']);

			$tdata[$i] = $m;
			$i++;
			unset($m);
		}		

		$j_response['aaData'] = $tdata;	
		$sOutput = $this->javascript->generate_json($j_response, TRUE);
	
		exit($sOutput);
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
		$this->load->helper('form');
		$this->lang->loadfile('members');

		$this->cp->set_variable('cp_page_title', lang('view_email_logs'));

		// a bit of a breadcrumb override is needed
		$this->cp->set_variable('cp_breadcrumbs', array(
			BASE.AMP.'C=tools' => lang('tools'),
			BASE.AMP.'C=tools_logs'=> lang('tools_logs')
		));

		$this->cp->add_js_script(array('plugin' => 'dataTables'));

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

		$this->javascript->output($this->ajax_filters('view_email_ajax_filter', 4, TRUE));

		$this->javascript->compile();
		
		$total = $this->db->count_all('email_console_cache');
		
		$row = ( ! $this->input->get_post('per_page')) ? 0 : $this->input->get_post('per_page');
		$vars['pagination'] = FALSE;

		if ($total > $this->perpage)
		{
			$this->load->library('pagination');
			
			$config['base_url'] = BASE.AMP.'C=tools_logs'.AMP.'M=view_email_log';
			$config['total_rows'] = $total;
			$config['per_page'] = $this->perpage;
			$config['page_query_string'] = TRUE;
			$config['first_link'] = lang('pag_first_link');
			$config['last_link'] = lang('pag_last_link');
			
			$this->pagination->initialize($config);	
			$vars['pagination'] = $this->pagination->create_links();
		}


		$vars['emails']	= $this->tools_model->get_email_logs(FALSE, $this->perpage, $row);
		$vars['emails_count'] = $total;

        if ($vars['emails_count'] != 0)
        {
            $this->cp->set_right_nav(array(
                    'clear_logs' => BASE.AMP.'C=tools_logs'.AMP.'M=clear_log_files'.AMP.'type=email'));
        }

		$this->load->view('tools/view_email_log', $vars);
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
	function view_email_ajax_filter()
	{
		if ( ! $this->cp->allowed_group('can_access_tools', 'can_access_logs'))
		{
			show_error(lang('unauthorized_access'));
		}

		$this->output->enable_profiler(FALSE);
		
		$col_map = array('subject', 'member_name', 'recipient_name', 'cache_date');
		
		// Note- we pipeline the js, so pull more data than are displayed on the page		
		$perpage = $this->input->get_post('iDisplayLength');
		$offset = ($this->input->get_post('iDisplayStart')) ? $this->input->get_post('iDisplayStart') : 0; // Display start point
		$sEcho = $this->input->get_post('sEcho');	

		/* Ordering */
		$order = array();
		
		if ($this->input->get('iSortCol_0') !== FALSE)
		{
			for ( $i=0; $i < $this->input->get('iSortingCols'); $i++ )
			{
				if (isset($col_map[$this->input->get('iSortCol_'.$i)]))
				{
					$order[$col_map[$this->input->get('iSortCol_'.$i)]] = ($this->input->get('sSortDir_'.$i) == 'asc') ? 'asc' : 'desc';
				}
			}
		}		
		
		$query = $this->tools_model->get_email_logs(FALSE, $perpage, $offset, $order);
		
		$total = $this->db->count_all('email_console_cache');


		$j_response['sEcho'] = $sEcho;
		$j_response['iTotalRecords'] = $total;
		$j_response['iTotalDisplayRecords'] = $total;
		
		$tdata = array();
		$i = 0;
		
		foreach ($query->result_array() as $log)
		{
			$m[] = '<a href="'.BASE.AMP.'C=tools_logs'.AMP.'M=view_email'.AMP.'id='.$log['cache_id'].'">'.$log['subject'].'</a>';
			$m[] = '<a href="'.BASE.AMP.'C=myaccount'.AMP.'id='. $log['member_id'].'">'.$log['member_name'].'</a>';
			$m[] = $log['recipient_name'];
			$m[] = $this->localize->set_human_time($log['cache_date']);
			$m[] = form_checkbox(array('id'=>'delete_box_'.$log['cache_id'],'name'=>'toggle[]','value'=>$log['cache_id'], 'class'=>'toggle_email', 'checked'=>FALSE));
			
			$tdata[$i] = $m;
			$i++;
			unset($m);
		}		

		$j_response['aaData'] = $tdata;	
		$sOutput = $this->javascript->generate_json($j_response, TRUE);
	
		exit($sOutput);
	}

	// --------------------------------------------------------------------

	/**
	 * Clear Logs Files
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
		
		$table = FALSE;
		
		switch($type)
		{
			case 'cp':
					$table = 'cp_log';
				break;
			case 'search':
					$table = 'search_log';
				break;
			case 'email':
					$table = 'email_console_cache';
				break;
			default: //nothing
		}
		
		if ($table)
		{
			$this->db->empty_table($table);
			
			// Redirect to where we came from
			$view_page = 'view_'.$type.'_log';
			
			$this->session->set_flashdata('message_success', lang('cleared_logs'));
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
		
		$this->load->view('tools/view_email', $query->row_array());
	}

	// --------------------------------------------------------------------

	/**
	 * Delete Specific Emails
	 *
	 * @access	public
	 * @return	mixed
	 */
	function delete_email()
	{
		if ( ! $this->cp->allowed_group('can_access_tools', 'can_access_logs'))
		{
			show_error(lang('unauthorized_access'));
		}
		
		if ( ! $this->input->post('toggle'))
		{
			$this->functions->redirect(BASE.AMP.'C=tools_logs'.AMP.'M=email_console_logs');
		}

		$ids = array();
				
		foreach ($_POST['toggle'] as $key => $val)
		{		
			$ids[] = "cache_id = '".$this->db->escape_str($val)."'";
		}
		
		$IDS = implode(" OR ", $ids);
		
		$this->db->query("DELETE FROM exp_email_console_cache WHERE ".$IDS);
	
		$this->session->set_flashdata('message_success', lang('email_deleted'));
		$this->functions->redirect(BASE.AMP.'C=tools_logs'.AMP.'M=view_email_log');
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
	
	function ajax_filters($ajax_method = '', $cols = '', $final_check = FALSE)
	{
		if ($ajax_method == '')
		{
			return;
		}
		
		$col_defs = '';
		if ($cols != '')
		{
			$col_defs .= '"aoColumns": [ ';
			$i = 1;
			
			while ($i <= $cols)
			{
				$col_defs .= 'null, ';
				$i++;
			}
			
			$col_defs = rtrim($col_defs, ', '); // IE chokes on trailing commas in JSON
			
			if ($final_check == TRUE)
			{
				$col_defs .= '{ "bSortable" : false } ],';
			}
			else
			{
				$col_defs .= ' ],';
			}
		}
		
		$js = '
var oCache = {
	iCacheLower: -1
};

function fnSetKey( aoData, sKey, mValue )
{
	for ( var i=0, iLen=aoData.length ; i<iLen ; i++ )
	{
		if ( aoData[i].name == sKey )
		{
			aoData[i].value = mValue;
		}
	}
}

function fnGetKey( aoData, sKey )
{
	for ( var i=0, iLen=aoData.length ; i<iLen ; i++ )
	{
		if ( aoData[i].name == sKey )
		{
			return aoData[i].value;
		}
	}
	return null;
}

function fnDataTablesPipeline ( sSource, aoData, fnCallback ) {
	var iPipe = '.$this->pipe_length.';  /* Ajust the pipe size */
	
	var bNeedServer = false;
	var sEcho = fnGetKey(aoData, "sEcho");
	var iRequestStart = fnGetKey(aoData, "iDisplayStart");
	var iRequestLength = fnGetKey(aoData, "iDisplayLength");
	var iRequestEnd = iRequestStart + iRequestLength;
	oCache.iDisplayStart = iRequestStart;
	
	/* outside pipeline? */
	if ( oCache.iCacheLower < 0 || iRequestStart < oCache.iCacheLower || iRequestEnd > oCache.iCacheUpper )
	{
		bNeedServer = true;
	}
	
	/* sorting etc changed? */
	if ( oCache.lastRequest && !bNeedServer )
	{
		for( var i=0, iLen=aoData.length ; i<iLen ; i++ )
		{
			if ( aoData[i].name != "iDisplayStart" && aoData[i].name != "iDisplayLength" && aoData[i].name != "sEcho" )
			{
				if ( aoData[i].value != oCache.lastRequest[i].value )
				{
					bNeedServer = true;
					break;
				}
			}
		}
	}
	
	/* Store the request for checking next time around */
	oCache.lastRequest = aoData.slice();
	
	if ( bNeedServer )
	{
		if ( iRequestStart < oCache.iCacheLower )
		{
			iRequestStart = iRequestStart - (iRequestLength*(iPipe-1));
			if ( iRequestStart < 0 )
			{
				iRequestStart = 0;
			}
		}
		
		oCache.iCacheLower = iRequestStart;
		oCache.iCacheUpper = iRequestStart + (iRequestLength * iPipe);
		oCache.iDisplayLength = fnGetKey( aoData, "iDisplayLength" );
		fnSetKey( aoData, "iDisplayStart", iRequestStart );
		fnSetKey( aoData, "iDisplayLength", iRequestLength*iPipe );
		
		$.getJSON( sSource, aoData, function (json) { 
			/* Callback processing */
			oCache.lastJson = jQuery.extend(true, {}, json);
			
			if ( oCache.iCacheLower != oCache.iDisplayStart )
			{
				json.aaData.splice( 0, oCache.iDisplayStart-oCache.iCacheLower );
			}
			json.aaData.splice( oCache.iDisplayLength, json.aaData.length );
			
			fnCallback(json)
		} );
	}
	else
	{
		json = jQuery.extend(true, {}, oCache.lastJson);
		json.sEcho = sEcho; /* Update the echo for each response */
		json.aaData.splice( 0, iRequestStart-oCache.iCacheLower );
		json.aaData.splice( iRequestLength, json.aaData.length );
		fnCallback(json);
		return;
	}
}

	var time = new Date().getTime();

	oTable = $(".mainTable").dataTable( {	
			"sPaginationType": "full_numbers",
			"bLengthChange": false,
			"aaSorting": [],
			"bFilter": false,
			"sWrapper": false,
			"sInfo": false,
			"bAutoWidth": false,
			"iDisplayLength": '.$this->perpage.', 
			
			'.$col_defs.'
					
		"oLanguage": {
			"sZeroRecords": "'.lang('invalid_entries').'",
			
			"oPaginate": {
				"sFirst": "<img src=\"'.$this->cp->cp_theme_url.'images/pagination_first_button.gif\" width=\"13\" height=\"13\" alt=\"&lt; &lt;\" />",
				"sPrevious": "<img src=\"'.$this->cp->cp_theme_url.'images/pagination_prev_button.gif\" width=\"13\" height=\"13\" alt=\"&lt; &lt;\" />",
				"sNext": "<img src=\"'.$this->cp->cp_theme_url.'images/pagination_next_button.gif\" width=\"13\" height=\"13\" alt=\"&lt; &lt;\" />", 
				"sLast": "<img src=\"'.$this->cp->cp_theme_url.'images/pagination_last_button.gif\" width=\"13\" height=\"13\" alt=\"&lt; &lt;\" />"
			}
		},
		
			"bProcessing": true,
			"bServerSide": true,
			"sAjaxSource": EE.BASE+"&C=tools_logs&M='.$ajax_method.'&time=" + time,
			"fnServerData": fnDataTablesPipeline

	} );';

		return $js;
		
	}
	
	
	
}
// END CLASS

/* End of file tools_logs.php */
/* Location: ./system/expressionengine/controllers/cp/tools_logs.php */
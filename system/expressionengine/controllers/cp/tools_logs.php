<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		ExpressionEngine Dev Team
 * @copyright	Copyright (c) 2003 - 2010, EllisLab, Inc.
 * @license		http://expressionengine.com/docs/license.html
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
class Tools_logs extends Controller {


	function Tools_logs()
	{
		// Call the Controller constructor.  
		// Without this, the world as we know it will end!
		parent::Controller();

		// Does the "core" class exist?  Normally it's initialized
		// automatically via the autoload.php file.  If it doesn't
		// exist it means there's a problem.
		if ( ! isset($this->core) OR ! is_object($this->core))
		{
			show_error('The ExpressionEngine Core was not initialized.  Please make sure your autoloader is correctly set up.');
		}

		if ( ! $this->cp->allowed_group('can_access_tools') OR ! $this->cp->allowed_group('can_access_logs'))
		{
			show_error($this->lang->line('unauthorized_access'));
		}

		$this->load->model('tools_model');
		$this->lang->loadfile('tools');

		$this->load->vars(array('controller'=>'tools/tools_logs'));

		$this->load->vars(array('cp_page_id'=>'tools'));
		$this->javascript->compile();
	}
	
	// --------------------------------------------------------------------

	/**
	 * Index function
	 * 
	 * Every controller must have an index function, which gets called
	 * automatically by CodeIgniter when the URI does not contain a call to
	 * a specific method call
	 *
	 * @access	public
	 * @return	mixed
	 */	
	function index()
	{
		if ( ! $this->cp->allowed_group('can_access_tools') OR ! $this->cp->allowed_group('can_access_logs'))
		{
			show_error($this->lang->line('unauthorized_access'));
		}

		$this->cp->set_variable('cp_page_title', $this->lang->line('tools_logs'));
		$this->cp->set_breadcrumb(BASE.AMP.'C=tools', $this->lang->line('tools'));

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
		if ( ! $this->cp->allowed_group('can_access_tools') OR ! $this->cp->allowed_group('can_access_logs'))
		{
			show_error($this->lang->line('unauthorized_access'));
		}
		
		$this->load->library('table');

		$this->cp->set_variable('cp_page_title', $this->lang->line('view_cp_log'));

		// a bit of a breadcrumb override is needed
		$this->cp->set_variable('cp_breadcrumbs', array(
			BASE.AMP.'C=tools' => $this->lang->line('tools'),
			BASE.AMP.'C=tools_logs'=> $this->lang->line('tools_logs')
		));

		$this->cp->add_js_script(array('plugin' => 'tablesorter'));

		$this->jquery->tablesorter('.mainTable', '{widgets: ["zebra"]}');

		$this->javascript->compile();

		$vars['cp_data'] = $this->tools_model->get_cp_log();

		$this->load->view('tools/view_cp_log', $vars);
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
		if ( ! $this->cp->allowed_group('can_access_tools') OR ! $this->cp->allowed_group('can_access_logs'))
		{
			show_error($this->lang->line('unauthorized_access'));
		}
		
		$this->load->library('table');

		$this->cp->set_variable('cp_page_title', $this->lang->line('view_search_log'));

		// a bit of a breadcrumb override is needed
		$this->cp->set_variable('cp_breadcrumbs', array(
			BASE.AMP.'C=tools' => $this->lang->line('tools'),
			BASE.AMP.'C=tools_logs'=> $this->lang->line('tools_logs')
		));

		$this->cp->add_js_script(array('plugin' => 'tablesorter'));
		$this->jquery->tablesorter('.mainTable', '{widgets: ["zebra"]}');

		$this->javascript->compile();

		$vars['search_data'] = $this->tools_model->get_search_log();

		$this->load->view('tools/view_search_log', $vars);
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
		if ( ! $this->cp->allowed_group('can_access_tools') OR ! $this->cp->allowed_group('can_access_logs'))
		{
			show_error($this->lang->line('unauthorized_access'));
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

		$this->cp->set_variable('cp_page_title', $this->lang->line('view_throttle_log'));

		// a bit of a breadcrumb override is needed
		$this->cp->set_variable('cp_breadcrumbs', array(
			BASE.AMP.'C=tools' => $this->lang->line('tools'),
			BASE.AMP.'C=tools_logs'=> $this->lang->line('tools_logs')
		));

		$this->cp->add_js_script(array('plugin' => 'tablesorter'));

		$this->jquery->tablesorter('.mainTable', '{widgets: ["zebra"]}');

		$this->javascript->compile();
		
		// Blacklist Installed?
		$this->db->where('module_name', 'Blacklist');
		$count = $this->db->count_all_results('modules');

		$vars['blacklist_installed'] = ($count > 0);

		$vars['throttle_data'] = $this->tools_model->get_throttle_log($max_page_loads, $lockout_time);

		$this->load->view('tools/view_throttle_log', $vars);
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
		if ( ! $this->cp->allowed_group('can_access_tools') OR ! $this->cp->allowed_group('can_access_logs'))
		{
			show_error($this->lang->line('unauthorized_access'));
		}
		
		$this->load->library('table');
		$this->load->helper('form');
		$this->lang->loadfile('members');

		$this->cp->set_variable('cp_page_title', $this->lang->line('view_email_logs'));

		// a bit of a breadcrumb override is needed
		$this->cp->set_variable('cp_breadcrumbs', array(
			BASE.AMP.'C=tools' => $this->lang->line('tools'),
			BASE.AMP.'C=tools_logs'=> $this->lang->line('tools_logs')
		));

		$this->cp->add_js_script(array('plugin' => 'tablesorter'));
		$this->jquery->tablesorter('.mainTable', '{headers: {4: {sorter: false}},	widgets: ["zebra"]}');

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

		$this->javascript->compile();

		$vars['emails']	= $this->tools_model->get_email_logs();
		$vars['emails_count'] = $vars['emails']->num_rows();

        if ($vars['emails_count'] != 0)
        {
            $this->cp->set_right_nav(array(
                    'clear_logs' => BASE.AMP.'C=tools_logs'.AMP.'M=clear_log_files'.AMP.'type=email'));
        }

		$this->load->view('tools/view_email_log', $vars);
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
		if ( ! $this->cp->allowed_group('can_access_tools') OR ! $this->cp->allowed_group('can_access_logs'))
		{
			show_error($this->lang->line('unauthorized_access'));
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
			
			$this->session->set_flashdata('message_success', $this->lang->line('cleared_logs'));
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
		if ( ! $this->cp->allowed_group('can_access_tools') OR ! $this->cp->allowed_group('can_access_logs'))
		{
			show_error($this->lang->line('unauthorized_access'));
		}

		$id = $this->input->get_post('id');

		$query = $this->db->query("SELECT subject, message, recipient, recipient_name, member_name, ip_address FROM exp_email_console_cache WHERE cache_id = '$id' ");
		
		if ($query->num_rows() == 0)
		{
			$this->session->set_flashdata('message_failure', $this->lang->line('no_cached_email'));
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
		if ( ! $this->cp->allowed_group('can_access_tools') OR ! $this->cp->allowed_group('can_access_logs'))
		{
			show_error($this->lang->line('unauthorized_access'));
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
	
		$this->session->set_flashdata('message_success', $this->lang->line('email_deleted'));
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
		if ( ! $this->cp->allowed_group('can_access_tools') OR ! $this->cp->allowed_group('can_access_logs'))
		{
			show_error($this->lang->line('unauthorized_access'));
		}
		
		if ($this->config->item('enable_throttling') == 'n')
		{
			show_error($this->lang->line('throttling_disabled'));
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
	 			require PATH_MOD.'blacklist/mcp.blacklist'.EXT;
	 		}

	 		$MOD = new Blacklist_mcp();

 			$_POST['htaccess_path'] = $this->config->item('htaccess_path');
 			$MOD->write_htaccess(FALSE);
 		}
		
		$this->session->set_flashdata('message_success', $this->lang->line('blacklist_updated'));
		$this->functions->redirect(BASE.AMP.'C=tools_logs'.AMP.'M=view_throttle_log');
	}
}
// END CLASS

/* End of file tools_logs.php */
/* Location: ./system/expressionengine/controllers/cp/tools_logs.php */
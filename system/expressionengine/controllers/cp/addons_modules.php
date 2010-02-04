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
 * ExpressionEngine Modules Administration Class
 *
 * @package		ExpressionEngine
 * @subpackage	Control Panel
 * @category	Control Panel
 * @author		ExpressionEngine Dev Team
 * @link		http://expressionengine.com
 */
class Addons_modules extends Controller {

	var $_mcp_reference;


	function Addons_modules()
	{
		parent::Controller();

		// Does the "core" class exist?  Normally it's initialized automatically
		// via the autoload.php file.  If it doesn't exist it means there's a problem.
		if ( ! isset($this->core) OR ! is_object($this->core))
		{
			show_error('The ExpressionEngine Core was not initialized.  Please make sure your autoloader is correctly set up.');
		}

		if ( ! $this->cp->allowed_group('can_access_addons') OR ! $this->cp->allowed_group('can_access_modules'))
		{
			show_error($this->lang->line('unauthorized_access'));
		}

		$this->load->model('addons_model');
		
		$this->lang->loadfile('modules');
		
		$this->load->vars(array('cp_page_id'=>'addons'));
	}

	// --------------------------------------------------------------------

	/**
	 * Module Section Homepage
	 *
	 * @access	public
	 * @return	string
	 */
	function index()
	{
		if ( ! $this->cp->allowed_group('can_access_addons') OR ! $this->cp->allowed_group('can_access_modules'))
		{
			show_error($this->lang->line('unauthorized_access'));
		}

		// Set access status
		$can_admin = ( ! $this->cp->allowed_group('can_admin_modules')) ? FALSE : TRUE;

		$this->load->library('table');
		$this->load->library('addons');
		$this->load->helper('directory');
		
		//  Fetch all module names from "modules" folder
		$modules = $this->addons->get_files();

		foreach($modules as $module => $info)
		{
			// @confirm - lang override still needed?
			$this->lang->loadfile(( ! isset($this->lang_overrides[$module])) ? $module : $this->lang_overrides[$module]);
		}
		
		$this->installed_modules = $this->addons->get_installed();
	
		// Fetch allowed Modules for a particular user

		$sql = "SELECT exp_modules.module_name 
				FROM exp_modules, exp_module_member_groups
				WHERE exp_module_member_groups.group_id = '".$this->session->userdata['group_id']."'
				AND exp_modules.module_id = exp_module_member_groups.module_id
				ORDER BY module_name";

		$query = $this->db->query($sql);

		$allowed_mods = array();

		if ($query->num_rows() == 0 AND ! $can_admin)
		{
			show_error($this->lang->line('module_no_access'));
		}

		foreach ($query->result_array() as $row)
		{
			$allowed_mods[] = strtolower($row['module_name']);
		}

		$vars['table_headings'] = array(
										'',
										$this->lang->line('module_name'),
										$this->lang->line('module_description'),
										$this->lang->line('module_version'),
										$this->lang->line('module_status'),
										$this->lang->line('module_action')
										);

		$modcount = 1;

		$vars['modules'] = array();

		foreach ($modules as $module => $module_info)
		{
			if ( ! $can_admin)
			{
				if ( ! in_array($module, $allowed_mods))
				{
					continue;
				}
			}

			$vars['modules'][$modcount][] = $modcount;

			// Module Name
			$name = ($this->lang->line(strtolower($module).'_module_name') != FALSE) ? $this->lang->line(strtolower($module).'_module_name') : $module_info['name'];

			if (isset($this->installed_modules[$module]) AND $this->installed_modules[$module]['has_cp_backend'] == 'y')
			{
				$cp_theme = ($this->session->userdata['cp_theme'] == '') ? $this->config->item('cp_theme') : $this->session->userdata['cp_theme'];
				$name = '<a href="'.BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module='.strtolower($module).'"><strong>'.$name.'</strong></a>';
			}

			$vars['modules'][$modcount][] = $name;

			// Module Description
			$vars['modules'][$modcount][] = $this->lang->line(strtolower($module).'_module_description');

			// Module Version
			$version = ( ! isset($this->installed_modules[$module])) ?  '--' : $this->installed_modules[$module]['module_version'];
			$vars['modules'][$modcount][] = $version;

			// Module Status
			// @todo: get rid of dsp class down there...
			$status = ( ! isset($this->installed_modules[$module]) ) ? 'not_installed' : 'installed';
			$in_status = str_replace(" ", "&nbsp;", $this->lang->line($status));
			$show_status = ($status == 'not_installed') ? $this->dsp->qspan('notice', $in_status) : $this->dsp->qspan('go_notice', $in_status);
			$vars['modules'][$modcount][] = $show_status;

			// Module Action
			$action = ($status == 'not_installed') ? 'install' : 'deinstall';
			if ( ! $can_admin)
			{
				$show_action = '--';
			}
			elseif ($status == 'not_installed')
			{
				$show_action = '<a class="less_important_link" href="'.BASE.AMP.'C=addons_modules'.AMP.'M=module_installer'.AMP.'module='.$module.'" title="'.$this->lang->line('install').'">'.$this->lang->line('install').'</a>';

			}
			else
			{
				$show_action = '<a class="less_important_link" href="'.BASE.AMP.'C=addons_modules'.AMP.'M=module_uninstaller'.AMP.'module='.$module.'" title="'.$this->lang->line('deinstall').'">'.$this->lang->line('deinstall').'</a>';
			}

			$vars['modules'][$modcount][] = $show_action;

			$modcount++;
		}

		$this->cp->set_variable('cp_page_title', $this->lang->line('modules'));
		$this->cp->set_breadcrumb(BASE.AMP.'C=addons', $this->lang->line('addons'));

		$this->javascript->compile();
		$this->load->view('addons/modules', $vars);
	}

	// --------------------------------------------------------------------

	/**
	 * Show Module CP
	 *
	 * Used as the router / gateway to module control panel back ends
	 *
	 * @access	public
	 * @param	string
	 * @return	void
	 */
	function show_module_cp()
	{
		if ( ! $this->cp->allowed_group('can_access_addons') OR ! $this->cp->allowed_group('can_access_modules'))
		{
			show_error($this->lang->line('unauthorized_access'));
		}
		
		// These can be overriden by individual modules
		$this->cp->set_variable('cp_page_title', $this->lang->line('modules'));
		$this->cp->set_breadcrumb(BASE.AMP.'C=addons_modules', $this->lang->line('modules'));


		// a bit of a breadcrumb override is needed
		$this->cp->set_variable('cp_breadcrumb', array(
			BASE.AMP.'C=addons' => $this->lang->line('addons'),
			BASE.AMP.'C=addons_modules'=> $this->lang->line('addons_modules')
		));

		$module = $this->input->get_post('module');
		$module = $this->functions->sanitize_filename(strtolower($module));
		$this->load->library('addons');
		
		$installed = $this->addons->get_installed();

		if ($this->session->userdata['group_id'] != 1)
		{
			// Do they have access to this module?
			if ( ! isset($installed[$module]) OR ! isset($this->session->userdata['assigned_modules'][$installed[$module]['module_id']]) OR  $this->session->userdata['assigned_modules'][$installed[$module]['module_id']] !== TRUE)
			{
				show_error($this->lang->line('unauthorized_access'));
			}
		}
		else
		{
			if ( ! isset($installed[$module]))
			{
				show_error($this->lang->line('requested_module_not_installed'));
			}
		}

		$this->lang->loadfile($module);

		// Update Module
		// Send version to update class and let it do any required work
		if (file_exists($installed[$module]['path'].'upd.'.$module.EXT))
		{
			require $installed[$module]['path'].'upd.'.$module.EXT;

			$class = ucfirst($module).'_upd';
			$version = $installed[$module]['module_version'];

			$UPD = new $class;
			$UPD->_ee_path = APPPATH;

			if ($UPD->version > $version && method_exists($UPD, 'update') && $UPD->update($version) !== FALSE)
			{
				$this->db->update('modules', array('module_version' => $UPD->version), array('module_name' => ucfirst($module)));
			}
		}
		
		
		$view_folder = 'views';
		
		/* -------------------------------------------
		/*	Hidden Configuration Variable
		/*	- use_mobile_control_panel => Automatically use mobile cp theme when accessed with a mobile device? (y/n)
		/* -------------------------------------------*/
		
		if ($this->agent->is_mobile() && $this->config->item('use_mobile_control_panel') != 'n')
		{
			// iphone, ipod, blackberry, palm, etc.
			$agent = array_search($this->agent->mobile(), $this->agent->mobiles);
			$agent = $this->security->sanitize_filename($agent);

			if (is_dir($installed[$module]['path'].'mobile_'.$agent))
			{
				$view_folder = 'mobile_'.$agent;
			}
			elseif (is_dir($installed[$module]['path'].'mobile'))
			{
				$view_folder = 'mobile';
			}
		}
				
		// set view path, package path, and the controller
		define('MODULE_VIEWS', $installed[$module]['path'].$view_folder.'/');
		$this->load->add_package_path($installed[$module]['path']);
		require_once $installed[$module]['path'].$installed[$module]['file'];

		// instantiate the module cp class
		$mod = new $installed[$module]['class'];
		$mod->_ee_path = APPPATH;
		
		
		// add validation callback support to the mcp class (see EE_form_validation for more info)
		$this->_mcp_reference =& $mod; 

		$method = ($this->input->get('method') !== FALSE) ? $this->input->get('method') : 'index';

		// switch the view path temporarily to the module's view folder
		$orig_view_path = $this->load->_ci_view_path;
		$this->load->_ci_view_path = MODULE_VIEWS;

		// its possible that a module will try to call a method that does not exist
		// either by accident (ie: a missed function) or by deliberate user url hacking
		if (method_exists($mod, $method))
		{
			$vars['_module_cp_body'] = $mod->$method();
		}
		else
		{
			$vars['_module_cp_body'] = $this->lang->line('requested_page_not_found');
		}
		
		// unset reference
		unset($this->_mcp_reference);

		// switch the view path back to the original, remove package path
		$this->load->_ci_view_path = $orig_view_path;
		$this->load->remove_package_path($installed[$module]['path']);

		$this->javascript->compile();
	
		$this->load->view('addons/module_cp_container', $vars);
	}

	// --------------------------------------------------------------------

	/**
	 * Module Installer
	 *
	 * @access	public
	 * @return	void
	 */
	function module_installer()
	{
		if ( ! $this->cp->allowed_group('can_access_addons') OR ! $this->cp->allowed_group('can_access_modules'))
		{
			show_error($this->lang->line('unauthorized_access'));
		}

		$module = $this->input->get_post('module');
		$module = $this->functions->sanitize_filename(strtolower($module));

		$this->load->library('addons/addons_installer');
		$this->lang->loadfile($module);

		if ($this->addons_installer->install($module, 'module'))
		{
			$name = ($this->lang->line($module.'_module_name') == FALSE) ? ucfirst($module) : $this->lang->line($module.'_module_name');
			$cp_message = $this->lang->line('module_has_been_installed').NBS.'<a href="'.BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module='.$module.'">'.$name.'</a>';
			
			$this->session->set_flashdata('message_success', $cp_message);
			$this->functions->redirect(BASE.AMP.'C=addons_modules');
		}
	}

	// --------------------------------------------------------------------

	/**
	 * Module Uninstaller Confirmation
	 *
	 * @access	public
	 * @return	void
	 */

	function delete_module_confirm()
	{
		if ( ! $this->cp->allowed_group('can_access_addons') OR ! $this->cp->allowed_group('can_access_modules'))
		{
			show_error($this->lang->line('unauthorized_access'));
		}

		$module = $this->input->get_post('module');

		if ( ! $this->cp->allowed_group('can_admin_modules') OR $module === FALSE)
		{
			show_error($this->lang->line('unauthorized_access'));
		}

		$this->load->helper('form');
		
		$vars['form_action'] = 'C=addons_modules'.AMP.'M=module_uninstaller';
		$vars['form_hidden'] = array('module' => $module, 'confirm' => 'delete');
		$vars['module_name'] = ucfirst(str_replace('_', ' ', $module));

		$this->cp->set_variable('cp_page_title', $this->lang->line('delete_module'));
		
		$this->cp->set_variable('cp_breadcrumbs', array(
			BASE.AMP.'C=addons' => $this->lang->line('addons'),
			BASE.AMP.'C=addons_modules'=> $this->lang->line('modules')
		));
		
		$this->javascript->compile();
		$this->load->view('addons/module_delete_confirm', $vars);
	}

	// --------------------------------------------------------------------

	/**
	 * Module Uninstaller
	 *
	 * @access	public
	 * @return	void
	 */
	function module_uninstaller()
	{
		if ( ! $this->cp->allowed_group('can_access_addons') OR ! $this->cp->allowed_group('can_access_modules'))
		{
			show_error($this->lang->line('unauthorized_access'));
		}

		$module = $this->input->get_post('module');
		$confirm = $this->input->get_post('confirm');
				
		if ($module === FALSE OR $confirm === FALSE)
		{
			return $this->delete_module_confirm();
		}
		
		$module = $this->functions->sanitize_filename(strtolower($module));

		$this->load->library('addons/addons_installer');
		$this->lang->loadfile($module);

		if ($this->addons_installer->uninstall($module, 'module'))
		{
			$name = ($this->lang->line($module.'_module_name') == FALSE) ? ucfirst($module) : $this->lang->line($module.'_module_name');
			
			$this->session->set_flashdata('message_success', $this->lang->line('module_has_been_removed').NBS.$name);
			$this->functions->redirect(BASE.AMP.'C=addons_modules');
		}
	}
}

// END CLASS

/* End of file addons_modules.php */
/* Location: ./system/expressionengine/controllers/cp/addons_modules.php */
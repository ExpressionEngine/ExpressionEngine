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
 * ExpressionEngine Modules Administration Class
 *
 * @package		ExpressionEngine
 * @subpackage	Control Panel
 * @category	Control Panel
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */
class Addons_modules extends CP_Controller {

	var $_mcp_reference;

	/**
	 * Constructor
	 */
	function __construct()
	{
		parent::__construct();

		if ( ! $this->cp->allowed_group('can_access_addons', 'can_access_modules'))
		{
			show_error(lang('unauthorized_access'));
		}

		$this->load->model('addons_model');

		$this->lang->loadfile('modules');
	}

	// --------------------------------------------------------------------

	/**
	 * Module Section Homepage
	 *
	 * @access	public
	 * @return	void
	 */
	function index()
	{
		// Set access status
		$can_admin = ( ! $this->cp->allowed_group('can_admin_modules')) ? FALSE : TRUE;

		$this->load->library(array('addons', 'table', 'typography'));
		$this->load->helper('directory');

		$this->cp->set_right_nav(array('update_modules' => BASE.AMP.'C=addons_modules'.AMP.'check_updates=y'));

		$this->jquery->tablesorter('.mainTable', '{
        	textExtraction: "complex",
			widgets: ["zebra"]
		}');

		//  Fetch all module names from "modules" folder
		$modules = $this->addons->get_files();

		foreach($modules as $module => $info)
		{
			$this->lang->loadfile(( ! isset($this->lang_overrides[$module])) ? $module : $this->lang_overrides[$module]);
		}

		$this->installed_modules = $this->addons->get_installed();

		// Fetch allowed Modules for a particular user
		$this->db->select('modules.module_name');
		$this->db->from('modules, module_member_groups');
		$this->db->where('module_member_groups.group_id', $this->session->userdata('group_id'));
		$this->db->where('modules.module_id = '.$this->db->dbprefix('module_member_groups').'.module_id', NULL, FALSE);
		$this->db->order_by('module_name');

		$query = $this->db->get();


		$allowed_mods = array();

		if ($query->num_rows() == 0 AND ! $can_admin)
		{
			show_error(lang('module_no_access'));
		}

		foreach ($query->result_array() as $row)
		{
			$allowed_mods[] = strtolower($row['module_name']);
		}

		$vars['table_headings'] = array(
			lang('module_name'),
			lang('module_description'),
			lang('module_version'),
			lang('module_status'),
			lang('module_action')
		);

		$modcount = 1;

		$vars['modules'] = array();
		$names	 = array();
		$data	 = array();
		$updated = array();

		foreach ($modules as $module => $module_info)
		{
			if (IS_CORE && in_array($module, $this->core->standard_modules))
			{
				continue;
			}

			if ( ! $can_admin)
			{
				if ( ! in_array($module, $allowed_mods))
				{
					continue;
				}
			}

			// Module Name
			$name = (lang(strtolower($module).'_module_name') != FALSE) ? lang(strtolower($module).'_module_name') : $module_info['name'];

			$names[$modcount] = strtolower($name);

			if (isset($this->installed_modules[$module]) AND $this->installed_modules[$module]['has_cp_backend'] == 'y')
			{
				$cp_theme = ($this->session->userdata['cp_theme'] == '') ? $this->config->item('cp_theme') : $this->session->userdata['cp_theme'];
				$name = '<a href="'.BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module='.strtolower($module).'"><strong>'.$name.'</strong></a>';
			}

			$data[$modcount][] = $name;


			// Module Description
			$data[$modcount][] = $this->typography->parse_type(
				lang(strtolower($module).'_module_description'),
				array(
					'text_format'	=> 'none',
					'html_format'	=> 'safe',
					'auto_links'	=> 'y'
				)
			);

			// Module Version
			$version = ( ! isset($this->installed_modules[$module])) ?  '--' : $this->installed_modules[$module]['module_version'];
			$data[$modcount][] = $version;

			// Module Status
			$status = ( ! isset($this->installed_modules[$module]) ) ? 'not_installed' : 'installed';
			$in_status = str_replace(" ", "&nbsp;", lang($status));
			$show_status = ($status == 'not_installed') ? '<span class="notice">'.$in_status.'</span>' : '<span class="go_notice">'.$in_status.'</span>';
			$data[$modcount][] = $show_status;

			// Module Action
			$action = ($status == 'not_installed') ? 'install' : 'deinstall';
			if ( ! $can_admin)
			{
				$show_action = '--';
			}
			elseif ($status == 'not_installed')
			{
				$show_action = '<a class="less_important_link" href="'.BASE.AMP.'C=addons_modules'.AMP.'M=module_installer'.AMP.'module='.$module.'" title="'.lang('install').'">'.lang('install').'</a>';
			}
			else
			{
				$show_action = '<a class="less_important_link" href="'.BASE.AMP.'C=addons_modules'.AMP.'M=module_uninstaller'.AMP.'module='.$module.'" title="'.lang('deinstall').'">'.lang('deinstall').'</a>';
			}

			$data[$modcount][] = $show_action;

			$modcount++;

			// Check for updates to module
			// Send version to update class and let it do any required work
			if ($this->input->get('check_updates') && $status == 'installed' && file_exists($this->installed_modules[$module]['path'].'upd.'.$module.'.php'))
			{
				require $this->installed_modules[$module]['path'].'upd.'.$module.'.php';

				$class = ucfirst($module).'_upd';
				$version = $this->installed_modules[$module]['module_version'];

				$this->load->add_package_path($this->installed_modules[$module]['path']);

				$UPD = new $class;
				$UPD->_ee_path = APPPATH;

				if (version_compare($UPD->version, $version, '>')
					&& method_exists($UPD, 'update')
					&& $UPD->update($version) !== FALSE)
				{
					$this->db->update('modules', array('module_version' => $UPD->version), array('module_name' => ucfirst($module)));
					$updated[] = $name.': '.lang('updated_to_version').' '.$UPD->version;
				}

				$this->load->remove_package_path($this->installed_modules[$module]['path']);
			}
		}

		// if we were running an update check, redirect with the appropriate message
		if ($this->input->get('check_updates'))
		{
			if (count($updated) > 0)
			{
				$flashmsg = '<strong>'.lang('updated').'</strong>:<br />'.implode('<br />', $updated);
			}
			else
			{
				$flashmsg = lang('all_modules_up_to_date');
			}

			$this->session->set_flashdata('message_success', $flashmsg);

			$this->functions->redirect(BASE.AMP.'C=addons_modules');
		}

		// Let's order by name just in case
		asort($names);

		$id = 1;
		foreach ($names as $k => $v)
		{
			$vars['modules'][$id] = $data[$k];
			$id++;
		}


		$this->view->cp_page_title = lang('modules');
		$this->cp->set_breadcrumb(BASE.AMP.'C=addons', lang('addons'));

		$this->cp->render('addons/modules', $vars);
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
		$this->load->library('addons');

		// These can be overriden by individual modules
		$this->view->cp_page_title = lang('modules');

		// a bit of a breadcrumb override is needed
		$this->view->cp_breadcrumbs = array(
			BASE.AMP.'C=addons' => lang('addons'),
			BASE.AMP.'C=addons_modules'=> lang('addons_modules')
		);

		$module = $this->input->get_post('module');
		$module = $this->security->sanitize_filename(strtolower($module));

		$installed = $this->addons->get_installed();

		if ($this->session->userdata['group_id'] != 1)
		{
			// Do they have access to this module?
			if ( ! isset($installed[$module]) OR ! isset($this->session->userdata['assigned_modules'][$installed[$module]['module_id']]) OR  $this->session->userdata['assigned_modules'][$installed[$module]['module_id']] !== TRUE)
			{
				show_error(lang('unauthorized_access'));
			}
		}
		else
		{
			if ( ! isset($installed[$module]))
			{
				show_error(lang('requested_module_not_installed').NBS.$module);
			}
		}

		$this->lang->loadfile($module);

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

		// set the view path
		define('MODULE_VIEWS', $installed[$module]['path'].$view_folder.'/');


		// Add the helper/library load path and temporarily
		// switch the view path to the module's view folder
		$this->load->add_package_path($installed[$module]['path'], FALSE);

		// Update Module
		// Send version to update class and let it do any required work
		if (file_exists($installed[$module]['path'].'upd.'.$module.'.php'))
		{
			require $installed[$module]['path'].'upd.'.$module.'.php';

			$class = ucfirst($module).'_upd';
			$version = $installed[$module]['module_version'];

			$UPD = new $class;
			$UPD->_ee_path = APPPATH;

			if ($UPD->version > $version && method_exists($UPD, 'update') && $UPD->update($version) !== FALSE)
			{
				$this->db->update('modules', array('module_version' => $UPD->version), array('module_name' => ucfirst($module)));
			}
		}

		require_once $installed[$module]['path'].$installed[$module]['file'];

		// instantiate the module cp class
		$mod = new $installed[$module]['class'];
		$mod->_ee_path = APPPATH;


		// add validation callback support to the mcp class (see EE_form_validation for more info)
		$this->_mcp_reference =& $mod;

		$method = ($this->input->get('method') !== FALSE) ? $this->input->get('method') : 'index';

		// its possible that a module will try to call a method that does not exist
		// either by accident (ie: a missed function) or by deliberate user url hacking
		if (method_exists($mod, $method))
		{
			$vars['_module_cp_body'] = $mod->$method();
		}
		else
		{
			$vars['_module_cp_body'] = lang('requested_page_not_found');
		}

		// unset reference
		unset($this->_mcp_reference);

		// remove package paths
		$this->load->remove_package_path($installed[$module]['path']);

		$this->cp->render('addons/module_cp_container', $vars);
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
		$module = $this->input->get_post('module');
		$module = $this->security->sanitize_filename(strtolower($module));

		$this->load->library('addons/addons_installer');
		$this->lang->loadfile($module);

		if ($this->addons_installer->install($module, 'module'))
		{
			$name = (lang($module.'_module_name') == FALSE) ? ucfirst($module) : lang($module.'_module_name');
			$cp_message = lang('module_has_been_installed').NBS.$name;

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
		$module = $this->input->get_post('module');

		if ( ! $this->cp->allowed_group('can_admin_modules') OR $module === FALSE)
		{
			show_error(lang('unauthorized_access'));
		}

		$this->lang->loadfile($module);

		$vars['form_action'] = 'C=addons_modules'.AMP.'M=module_uninstaller';
		$vars['form_hidden'] = array('module' => $module, 'confirm' => 'delete');
		$vars['module_name'] = (lang($module.'_module_name') == FALSE) ? ucwords(str_replace('_', ' ', $module)) : lang($module.'_module_name');

		$this->view->cp_page_title = lang('delete_module');

		$this->view->cp_breadcrumbs = array(
			BASE.AMP.'C=addons' => lang('addons'),
			BASE.AMP.'C=addons_modules'=> lang('modules')
		);

		$this->cp->render('addons/module_delete_confirm', $vars);
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
		$module = $this->input->get_post('module');
		$confirm = $this->input->get_post('confirm');

		if ($module === FALSE OR $confirm === FALSE)
		{
			return $this->delete_module_confirm();
		}

		$module = $this->security->sanitize_filename(strtolower($module));

		$this->load->library('addons/addons_installer');
		$this->lang->loadfile($module);

		if ($this->addons_installer->uninstall($module, 'module'))
		{
			$name = (lang($module.'_module_name') == FALSE) ? ucfirst($module) : lang($module.'_module_name');

			$this->session->set_flashdata('message_success', lang('module_has_been_removed').NBS.$name);
			$this->functions->redirect(BASE.AMP.'C=addons_modules');
		}
	}
}

// END CLASS

/* End of file addons_modules.php */
/* Location: ./system/expressionengine/controllers/cp/addons_modules.php */
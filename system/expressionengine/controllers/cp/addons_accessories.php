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
class Addons_accessories extends CP_Controller {

	var $human_names = array();

	// Note: the ignored_controllers array is treated as static by the installer

	var $parent_controllers = array('addons', 'admin', 'content', 'tools');

	/**
	 * Constructor
	 */
	public function __construct()
	{
		parent::__construct();

		$this->load->library('accessories');
		$this->human_names = $this->_fetch_human_names();

		$this->load->library('addons');
		$files = $this->addons->get_files();
	}

	// --------------------------------------------------------------------

	/**
	 * Index function
	 *
	 * @return	void
	 */
	public function index()
	{
		if ( ! $this->cp->allowed_group('can_access_addons')
			OR ! $this->cp->allowed_group('can_access_accessories'))
		{
			show_error(lang('unauthorized_access'));
		}

		$this->load->library('table');
		$this->load->helper('html');

		$this->view->cp_page_title = lang('accessories');

		$this->cp->set_breadcrumb(BASE.AMP.'C=addons', lang('addons'));

		$this->jquery->tablesorter('.mainTable', '{
        	textExtraction: "complex",
			widgets: ["zebra"]
		}');

		$this->load->library('addons');

		$accessories = $this->addons->get_files('accessories');
		$installed = $this->addons->get_installed('accessories');

		$data = $this->human_names;
		$num_all_member_groups = count($data['member_groups']);
		$num_all_controllers = count($data['controllers']);

		foreach ($accessories as $name => $info)
		{
			// Grab the version and description
			if ( ! class_exists($accessories[$name]['class']))
			{
				include $accessories[$name]['path'].$accessories[$name]['file'];
			}

			// add the package and view paths
			$path = PATH_THIRD.strtolower($name).'/';

			$this->load->add_package_path($path, FALSE);

			$ACC = new $accessories[$name]['class']();

			$this->load->remove_package_path($path);

			$accessories[$name]['name'] = $ACC->name;
			$accessories[$name]['version'] = $ACC->version;
			$accessories[$name]['description'] = $ACC->description;

			if (isset($installed[$name]))
			{
				$accessories[$name]['acc_pref_url'] = BASE.AMP.'C=addons_accessories'.AMP.'M=edit_prefs'.AMP.'accessory='.$name;
				$accessories[$name]['acc_install'] = anchor(
					BASE.AMP.'C=addons_accessories'.AMP.'M=uninstall'.AMP.'accessory='.$name,
					lang('uninstall')
				);

				// Work out the human names (if needed)
				$installed[$name]['member_groups'] = explode('|', $installed[$name]['member_groups']);
				$num_member_groups = count($installed[$name]['member_groups']);

				if ($num_member_groups == 0)
				{
					// no member groups selected
					$accessories[$name]['acc_member_groups'] = lang('none');
				}
				elseif ($num_member_groups == $num_all_member_groups)
				{
					// every member group selected, shorten the list to simply say "all"
					$accessories[$name]['acc_member_groups'] = lang('all');
				}
				elseif ($num_member_groups < 4)
				{
					// there's less then 4, show them all
					$accessories[$name]['acc_member_groups'] = array_map(array($this, '_get_human_name'), $installed[$name]['member_groups']);
				}
				else
				{
					// over 3 listed, and this starts to get a bit out of hand, so we'll show the first 3, and say how
					// many others there are, and offer the option of looking at them
					$member_groups = array_map(array($this, '_get_human_name'), $installed[$name]['member_groups']);
					$member_groups = array_slice($member_groups, 0, 3);
					$member_groups[] = '<a href="'.$accessories[$name]['acc_pref_url'].'">'.str_replace("%x", ($num_member_groups-3), lang('and_more')).'</a>';

					$accessories[$name]['acc_member_groups'] = $member_groups;
				}

				// work out controller names (if needed)
				$installed[$name]['controllers'] = explode('|', $installed[$name]['controllers']);
				$num_controllers = count($installed[$name]['controllers']);

				if ($num_controllers == 0)
				{
					// no controllers
					$accessories[$name]['acc_controller'] = lang('none');
				}
				elseif ($num_controllers == $num_all_controllers)
				{
					// all controllers selected, let's just say "all"
					$accessories[$name]['acc_controller'] = lang('all');
				}
				elseif ($num_controllers < 4)
				{
					// less then 4, list them all
					$accessories[$name]['acc_controller'] = array_map(array($this, '_get_human_name'), $installed[$name]['controllers']);
				}
				else
				{
					// over 3 listed, and this starts to get a bit out of hand, so we'll show the first 3, and say how
					// many others there are, and offer the option of looking at them
					$controllers = array_map(array($this, '_get_human_name'), $installed[$name]['controllers']);
					$controllers = array_slice($controllers, 0, 3);
					$controllers[] = '<a href="'.$accessories[$name]['acc_pref_url'].'">'.str_replace("%x", ($num_controllers-3), lang('and_more')).'</a>';

					$accessories[$name]['acc_controller'] = $controllers;
				}
			}
			else
			{
				$accessories[$name]['acc_pref_url'] = '';
				$accessories[$name]['acc_install'] = anchor(
					BASE.AMP.'C=addons_accessories'.AMP.'M=install'.AMP.'accessory='.$name,
					lang('install')
				);
				$accessories[$name]['acc_member_groups'] = '--';
				$accessories[$name]['acc_controller'] = '--';
			}
		}

		$this->view->accessories = $accessories;
		$this->cp->render('addons/accessories');
	}

	// --------------------------------------------------------------------

	/**
	 * Process Request
	 *
	 * Process a request for an Accessory
	 *
	 * @param	string
	 * @return	void
	 */
	public function process_request()
	{
		// only methods beginning with 'process_' are allowed to be called via this method
		if (($name = $this->input->get_post('accessory')) === FALSE
			OR ($method = $this->input->get_post('method')) === FALSE
			OR strncmp($method, 'process_', 8) != 0)
		{
			$this->functions->redirect(BASE.AMP.'C=addons_accessories');

		}

		$class = $this->accessories->_get_accessory_class($name);

		// add the package and view paths
		$path = PATH_THIRD.strtolower($name).'/';

		$this->load->add_package_path($path, FALSE);
		$this->load->library('accessories');

		$ACC = new $class();

		// execute the requested method
		if ( ! method_exists($ACC, $method))
		{
			$this->functions->redirect(BASE.AMP.'C=addons_accessories');
		}

		$return = $ACC->$method();

		// remove package path
		$this->load->remove_package_path($path);

		return $return;
	}

	// --------------------------------------------------------------------

	/**
	 * Install
	 *
	 * @return	void
	 */
	public function install()
	{
		if ( ! $this->cp->allowed_group('can_access_addons')
			OR ! $this->cp->allowed_group('can_access_accessories'))
		{
			show_error(lang('unauthorized_access'));
		}

		$this->load->library('addons/addons_installer');

		$accessory = $this->input->get_post('accessory');

		if ($this->addons_installer->install($accessory, 'accessory'))
		{
			$this->session->set_flashdata('message_success', lang('installed'));
			$this->functions->redirect(BASE.AMP.'C=addons_accessories');
		}
	}

	// --------------------------------------------------------------------

	/**
	 * Uninstall Accessory
	 *
	 * @return	void
	 */
	public function uninstall()
	{
		if ( ! $this->cp->allowed_group('can_access_addons')
			OR ! $this->cp->allowed_group('can_access_accessories'))
		{
			show_error(lang('unauthorized_access'));
		}

		$this->load->library('addons/addons_installer');

		$accessory = $this->input->get_post('accessory');

		if ($this->addons_installer->uninstall($accessory, 'accessory'))
		{
			$this->session->set_flashdata('message_success', lang('uninstalled'));
			$this->functions->redirect(BASE.AMP.'C=addons_accessories');
		}
	}

	// --------------------------------------------------------------------

	/**
	 * Edit visibility preferences
	 *
	 * @return	void
	 */
	public function edit_prefs()
	{
		if ( ! $this->cp->allowed_group('can_access_addons')
			OR ! $this->cp->allowed_group('can_access_accessories'))
		{
			show_error(lang('unauthorized_access'));
		}

		if ( ! $name = $this->input->get_post('accessory'))
		{
			$this->functions->redirect(BASE.AMP.'C=addons_accessories');
		}

		$class = ucfirst($name).'_acc';

		$this->db->where('class', $class);

		if ($this->db->count_all_results('accessories') == 0)
		{
			$this->functions->redirect(BASE.AMP.'C=addons_accessories');
		}

		if ($accessory = $this->accessories->fetch_path_by_name($name))
		{
			@include_once($accessory);
			$acc = new $class();
		}

		$this->view->cp_page_title = lang('edit_accessory_preferences').': '.$acc->name;

		// a bit of a breadcrumb override is needed
		$this->view->cp_breadcrumbs = array(
			BASE.AMP.'C=addons' => lang('addons'),
			BASE.AMP.'C=addons_accessories'=> lang('addons_accessories')
		);

		$this->load->library('table');
		$this->load->model('member_model');

		$this->cp->add_js_script('file', 'cp/addons/accessories');

		$vars['member_groups'] = $this->human_names['member_groups'];
		$controllers = $this->human_names['controllers'];
		$parent_controllers = $this->parent_controllers;

		$vars['controllers'] = array();

		foreach ($controllers as $file => $c_name)
		{
			$vars['controllers'][$file]['file'] = $file;
			$vars['controllers'][$file]['name'] = $c_name;
			$vars['controllers'][$file]['class'] = (in_array($file, $this->parent_controllers)) ? $file : 'sub_controller sub_'.substr($file, 0, 6);
		}

		// Info for this accessory
		$vars['name'] = $name;

		$installed = $this->addons->get_installed('accessories');

		$vars['acc_controllers'] = explode('|', $installed[$name]['controllers']);
		$vars['acc_member_groups'] = explode('|', $installed[$name]['member_groups']);

		$this->cp->render('addons/accessory_preferences', $vars);
	}

	// --------------------------------------------------------------------

	/**
	 * Update Preferences
	 *
	 * @param	type	description
	 * @return	description
	 */
	public function update_prefs()
	{
		if ( ! $this->cp->allowed_group('can_access_addons')
			OR ! $this->cp->allowed_group('can_access_accessories'))
		{
			show_error(lang('unauthorized_access'));
		}

		if ( ! $name = $this->input->get_post('accessory'))
		{
			$this->functions->redirect(BASE.AMP.'C=addons_accessories');
		}

		$class = ucfirst(strtolower(str_replace(' ', '_', $name))).'_acc';

		$this->db->where('class', $class);

		if ($this->db->count_all_results('accessories') == 0)
		{
			$this->functions->redirect(BASE.AMP.'C=addons_accessories');
		}

		$member_groups = $this->input->post('groups');
		$controllers = $this->input->post('controllers');

		$this->accessories->update_placement($class, $member_groups, $controllers);

		$this->session->set_flashdata('message_success', lang('preferences_updated'));
		$this->functions->redirect(BASE.AMP.'C=addons_accessories');
	}

	// --------------------------------------------------------------------

	/**
	 * Get arrays for controllers or groups, create human readable
	 * controller names on the fly.
	 *
	 * @return	mixed	final array
	 */
	private function _fetch_human_names()
	{
		$this->load->helper('directory');

		$data['controllers'] = array();
		$data['member_groups'] = array();

		// Controllers

		foreach(directory_map(APPPATH.'controllers/cp') as $file)
		{
			if (in_array($file, $this->accessories->ignored_controllers))
			{
				continue;
			}

			$file = str_replace('.php', '', $file);
			$name = str_replace('_', ' - ', $file);
			$data['controllers'][$file] = ucwords($name);
		}

		ksort($data['controllers']);

		// Member Groups
		$this->db->select("group_id, group_title");
		$this->db->from("member_groups");
		$this->db->where("site_id", $this->config->item('site_id'));
		$this->db->where_not_in('group_id', $this->accessories->ignored_member_groups);
		$this->db->order_by('group_id');

		$member_groups = $this->db->get();
		$member_groups = $member_groups->result();

		foreach ($member_groups as $group)
		{
			$data['member_groups'][$group->group_id] = $group->group_title;
		}

		return $data;
	}


	// --------------------------------------------------------------------

	/**
	 * Get a human name
	 *
	 * Callback for array_map
	 *
	 * @param	string
	 * @return	string
	 */
	private function _get_human_name($key)
	{
		if (is_numeric($key))
		{
			return isset($this->human_names['member_groups'][$key]) ? $this->human_names['member_groups'][$key] : $key;
		}
		return isset($this->human_names['controllers'][$key]) ? $this->human_names['controllers'][$key] : $key;
	}

	// --------------------------------------------------------------------
}
// END CLASS

/* End of file accessories.php */
/* Location: ./system/expressionengine/controllers/cp/accessories.php */

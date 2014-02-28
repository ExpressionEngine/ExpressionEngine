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
class Addons extends CP_Controller {

	/**
	 * Constructor
	 */
	public function __construct()
	{
		parent::__construct();

		// Can't access addons? Can't see this page!
		if ( ! $this->cp->allowed_group('can_access_addons'))
		{
			show_error(lang('unauthorized_access'));
		}

		$this->lang->loadfile('addons');
		$this->load->model('addons_model');
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
		$this->view->cp_page_title = lang('addons');
		$this->view->controller = 'addons';

		$this->cp->render('_shared/overview');
	}

	// --------------------------------------------------------------------

	/**
	 * Package Editor
	 *
	 * Install and remove package components
	 *
	 * @access	public
	 * @return	mixed
	 */
	function package_settings()
	{
		$this->load->library('addons');
		$this->load->library('table');

		$this->load->model('addons_model');
		$this->lang->loadfile('modules');

		$return = $this->input->get_post('return');
		$package = $this->input->get_post('package');
		$required = array();

		if ( ! $package OR ! $this->addons->is_package($package))
		{
			show_error(lang('unauthorized_access'));
		}

		$this->view->cp_page_title = lang('package_settings');

		$components = $this->addons->_packages[$package];

		// Ignore RTE Tools if the module is not installed
		$this->db->from('modules')->where('module_name', 'Rte');
		if ($this->db->count_all_results() <= 0)
		{
			unset($components['rte_tool']);
		}

		if (isset($components['plugin']))
		{
			unset($components['plugin']);
		}

		if (count($_POST))
		{
			$install = array();
			$uninstall = array();

			// Do we need to enable extensions?
			if (ee()->input->post('enable_extensions') === 'yes')
			{
				ee()->config->_update_config(array('allow_extensions' => 'y'));
			}

			foreach($components as $type => $info)
			{
				if ($new_state = $this->input->get_post('install_'.$type))
				{
					// Addon_installer does it's own "is installed" check when
					// installing/uninstalling fieldtypes, so we can safely add
					// FTs at this point without checking the installation status
					if ($type === 'fieldtype')
					{
						if ($new_state === 'uninstall')
						{
							$uninstall[] = $type;
						}
						elseif ($new_state === 'install')
						{
							$install[] = $type;
						}

						continue;
					}

					$installed_f = $type.'_installed';

					if (method_exists($this->addons_model, $installed_f))
					{
						$is_installed = $this->addons_model->$installed_f($package);

						if ($is_installed && ($new_state == 'uninstall'))
						{
							$uninstall[] = $type;
						}
						elseif ( ! $is_installed && ($new_state == 'install'))
						{
							$install[] = $type;
						}
					}
				}
			}

			$this->load->library('addons/addons_installer');

			$this->addons_installer->install($package, $install, FALSE);
			$this->addons_installer->uninstall($package, $uninstall, FALSE);
			$this->functions->redirect(BASE.AMP.'C='.$_GET['return']);
		}


		$vars = array();

		//whether or not this is an installation or an uninstallation
		$is_package_installed = FALSE;

		foreach($components as $type => $info)
		{
			//fieldtypes are given the install status of the whole package
			//so don't bother checking install status of fieldtypes
			if ($type === 'fieldtype')
			{
				continue;
			}

			$inst_func = $type.'_installed';

			$is_package_installed = $components[$type]['installed'] = $this->addons_model->$inst_func($package);

			if ($type == 'extension')
			{
				include_once($info['path'].$info['file']);
				$class = $info['class'];
				$this->load->add_package_path($info['path']);
				$out = new $class;

				if (isset($out->required_by) && is_array($out->required_by))
				{
					$required[$type] = $out->required_by;
				}

				$this->load->remove_package_path($info['path']);
			}
		}

		// since fieldtypes can be uninstalled one-by-one without uninstalling
		// the whole package set the "installed" status to whether the other
		// components (ext, mod, acc) are installed
		if (isset($components['fieldtype']))
		{
			$components['fieldtype']['installed'] = $is_package_installed;
		}

		$vars['form_action'] = 'C=addons'.AMP.'M=package_settings'.AMP.'package='.$package.AMP.'return='.$return;
		$vars['package'] = ucfirst(str_replace('_', ' ', $package));
		$vars['components'] = $components;
		$vars['required'] = $required;

		// Show the user a confirmation dialog to turn extensions on if they
		// want to install an add-on with an extension and they have extensions
		// disabled
		if (ee()->config->item('allow_extensions') !== 'y'
			&& isset($components['extension'])
			&& isset($components['extension']['installed'])
			&& $components['extension']['installed'] === FALSE)
		{
			ee()->javascript->set_global(array(
				'extensions_disabled'			=> TRUE,
				'extensions_disabled_warning'	=> lang('extensions_disabled_warning')
			));
			ee()->cp->add_js_script(array('file' => 'cp/addons/package_settings'));
		}

		ee()->cp->render('addons/package_settings', $vars);
	}
}
// END CLASS

/* End of file addons.php */
/* Location: ./system/expressionengine/controllers/cp/addons.php */
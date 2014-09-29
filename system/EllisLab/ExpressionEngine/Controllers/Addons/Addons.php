<?php

namespace EllisLab\ExpressionEngine\Controllers\Addons;

if ( ! defined('BASEPATH')) exit('No direct script access allowed');

use CP_Controller;
use EllisLab\ExpressionEngine\Library\CP\Pagination;
use EllisLab\ExpressionEngine\Library\CP\Table;
use EllisLab\ExpressionEngine\Library\CP\URL;

/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2014, EllisLab, Inc.
 * @license		http://ellislab.com/expressionengine/user-guide/license.html
 * @link		http://ellislab.com
 * @since		Version 3.0
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

	var $perpage		= 20;
	var $params			= array();
	var $base_url;

	/**
	 * Constructor
	 */
	public function __construct()
	{
		parent::__construct();

		ee()->lang->loadfile('addons');

		if ( ! ee()->cp->allowed_group('can_access_addons'))
		{
			show_error(lang('unauthorized_access'));
		}

		// Sidebar Menu
		$menu = array(
			'all_addons' 		=> cp_url('addons'),
			'manage_extensions'	=> cp_url('addons/extensions')
		);

		ee()->menu->register_left_nav($menu);

		$this->params['perpage'] = $this->perpage; // Set a default

		// Add in any submitted search phrase
		ee()->view->search_value = ee()->input->get_post('search');

		$this->base_url = new URL('addons', ee()->session->session_id());

		ee()->load->library('addons');
		ee()->load->helper(array('file', 'directory'));
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
		if (ee()->input->post('bulk_action') == 'install')
		{
			return $this->install(ee()->input->post('selection'));
		}
		elseif (ee()->input->post('bulk_action') == 'remove')
		{
			return $this->remove(ee()->input->post('selection'));
		}

		ee()->view->cp_page_title = lang('addon_manager');
		ee()->view->cp_heading = lang('all_addons');

		$vars = array();

		// Filters
		$view_filters = array();

		// Sumitted values
		$all_filters = array(
			'filter_by_status'		=> 'status',
			'filter_by_developer'	=> 'developer',
			'perpage'				=> 'perpage'
		);
		foreach ($all_filters as $key => $filter)
		{
			$value = (ee()->input->post($key)) ?: ee()->input->get($key);
			if ($value)
			{
				$this->base_url->setQueryStringVariable($key, $value);
				$this->params[$key] = $value;
			}
		}

		// Status
		$base_url = clone $this->base_url;

		$filter = array(
			'label'			=> 'status',
			'name'			=> 'filter_by_status',
			'value'			=> '',
			'options'		=> array()
		);
		$statuses = array(
			'installed'		=> strtolower(lang('installed')),
			'uninstalled'	=> strtolower(lang('uninstalled'))
		);

		foreach ($statuses as $show => $label)
		{
			if (isset($this->params['filter_by_status']) &&
				$this->params['filter_by_status'] == $show)
			{
				$filter['value'] = $label;
			}

			$base_url->setQueryStringVariable('filter_by_status', $show);
			$filter['options'][$base_url->compile()] = $label;
		}
		$view_filters[] = $filter;

		// Developer
		$base_url = clone $this->base_url;

		$filter = array(
			'label'			=> 'developer',
			'name'			=> 'filter_by_developer',
			'value'			=> '',
			'options'		=> array()
		);
		$developers = array(
			'native'		=> 'EllisLab',
			'third_party'	=> 'Third Party',
		);

		foreach ($developers as $show => $label)
		{
			if (isset($this->params['filter_by_developer']) &&
				$this->params['filter_by_developer'] == $show)
			{
				$filter['value'] = $label;
			}

			$base_url->setQueryStringVariable('filter_by_developer', $show);
			$filter['options'][$base_url->compile()] = $label;
		}
		$view_filters[] = $filter;

		// Perpage
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
			'25'  => '25 '.lang('entries'),
			'50'  => '50 '.lang('entries'),
			'75'  => '75 '.lang('entries'),
			'100' => '100 '.lang('entries'),
			'150' => '150 '.lang('entries')
		);

		foreach ($perpages as $show => $label)
		{
			$base_url->setQueryStringVariable('perpage', $show);
			$filter['options'][$base_url->compile()] = $label;
		}

		$view_filters[] = $filter;
		ee()->view->filters = $view_filters;

		if ( ! empty(ee()->view->search_value))
		{
			$this->base_url->setQueryStringVariable('search', ee()->view->search_value);
		}

		$data = array();

		$modules = $this->getModules();
		$accessories = $this->getAccessories();
		$plugins = $this->getPlugins();
		$fieldtypes = $this->getFieldtypes();

		foreach(array_merge($fieldtypes, $plugins, $accessories, $modules) as $addon => $info)
		{
			// Filter based on status
			if (isset($this->params['filter_by_status']))
			{
				if ((strtolower($this->params['filter_by_status']) == 'installed' &&
					$info['installed'] == FALSE) ||
					(strtolower($this->params['filter_by_status']) == 'uninstalled' &&
					$info['installed'] == TRUE))
				{
					continue;
				}
			}

			// Filter based on developer
			if (isset($this->params['filter_by_developer']))
			{
				if (strtolower($this->params['filter_by_developer']) != strtolower($info['developer']))
				{
					continue;
				}
			}

			$toolbar = array(
				'install' => array(
					'href' => cp_url('addons/install/' . $info['package']),
					'title' => lang('install'),
					'class' => 'add'
				)
			);

			$attrs = array('class' => 'not-installed');

			if ($info['installed'])
			{
				if ($info['settings_url'])
				{
					$toolbar = array(
						'settings' => array(
							'href' => $info['settings_url'],
							'title' => lang('settings'),
						)
					);
				}
				else
				{
					$toolbar = array();
				}
				$attrs = array();
			}

			if (strpos($info['version'], '.') !== FALSE)
			{
				$parts = explode('.', $info['version']);
				$parts[0] = '<b>' . $parts[0] . '</b>';
				$version = implode('.', $parts);
			}
			else
			{
				$version = $info['version'];
			}

			$data[] = array(
				'attrs' => $attrs,
				'columns' => array(
					'addon' => $info['name'],
					'version' => $version,
					array('toolbar_items' => $toolbar),
					array(
						'name' => 'selection[]',
						'value' => $info['package']
					)
				)
			);
		}

		$table = Table::create(array('autosort' => TRUE, 'autosearch' => TRUE, 'limit' => $this->params['perpage']));
		$table->setColumns(
			array(
				'addon',
				'version',
				'manage' => array(
					'type'	=> Table::COL_TOOLBAR
				),
				array(
					'type'	=> Table::COL_CHECKBOX
				)
			)
		);
		$table->setNoResultsText('no_addon_search_results');
		$table->setData($data);

		$vars['table'] = $table->viewData($this->base_url);
		$vars['form_url'] = $vars['table']['base_url'];

		if ( ! empty($vars['table']['data']))
		{
			// Paginate!
			$pagination = new Pagination(
				$vars['table']['limit'],
				$vars['table']['total_rows'],
				$vars['table']['page']
			);
			$vars['pagination'] = $pagination->cp_links($this->base_url);
		}

		// Set search results heading
		if ( ! empty($vars['table']['search']))
		{
			ee()->view->cp_heading = sprintf(
				lang('search_results_heading'),
				$vars['table']['total_rows'],
				$vars['table']['search']
			);
		}

		ee()->cp->render('addons/index', $vars);
	}

	// --------------------------------------------------------------------

	/**
	 * Installs a module or accessory
	 *
	 * @access	public
	 * @param	str|array	$addon	The name(s) of add-ons to install
	 * @return	void
	 */
	public function install($addons)
	{
		if ( ! is_array($addons))
		{
			$addons = array($addons);
		}

		$this->load->library('addons/addons_installer');

		$installed = array();

		foreach ($addons as $addon)
		{
			$module = $this->getModules($addon);
			if ( ! empty($module) && $module['installed'] === FALSE)
			{
				$name = $this->installModule($addon);
				if ($name)
				{
					$installed[$addon] = $name;
				}
			}

			$accessory = $this->getAccessories($addon);
			if ( ! empty($accessory) && $accessory['installed'] === FALSE)
			{
				$name = $this->installAccessory($addon);
				if ($name && ! isset($installed[$addon]))
				{
					$installed[$addon] = $name;
				}
			}

			$fieldtype = $this->getFieldtypes($addon);
			if ( ! empty($fieldtype) && $fieldtype['installed'] === FALSE)
			{
				$name = $this->installFieldtype($addon);
				if ($name && ! isset($installed[$addon]))
				{
					$installed[$addon] = $name;
				}
			}
		}

		if ( ! empty($installed))
		{
			ee()->view->set_message('success', lang('addons_installed'), lang('addons_installed_desc') . implode(', ', $installed), TRUE);
		}
		ee()->functions->redirect(cp_url('addons'));
	}

	// --------------------------------------------------------------------

	/**
	 * Uninstalls a module or accessory
	 *
	 * @access	public
	 * @param	str|array	$addon	The name(s) of add-ons to uninstall
	 * @return	void
	 */
	public function remove($addons)
	{
		if ( ! is_array($addons))
		{
			$addons = array($addons);
		}

		$this->load->library('addons/addons_installer');

		$uninstalled = array();

		foreach ($addons as $addon)
		{
			$module = $this->getModules($addon);
			if ( ! empty($module) && $module['installed'] === TRUE)
			{
				$name = $this->uninstallModule($addon);
				if ($name)
				{
					$uninstalled[$addon] = $name;
				}
			}

			$accessory = $this->getAccessories($addon);
			if ( ! empty($accessory) && $accessory['installed'] === TRUE)
			{
				$name = $this->uninstallAccessory($addon);
				if ($name && ! isset($installed[$addon]))
				{
					$uninstalled[$addon] = $name;
				}
			}

			$fieldtype = $this->getFieldtypes($addon);
			if ( ! empty($fieldtype) && $fieldtype['installed'] === TRUE)
			{
				$name = $this->uninstallFieldtype($addon);
				if ($name && ! isset($installed[$addon]))
				{
					$uninstalled[$addon] = $name;
				}
			}
		}

		if ( ! empty($uninstalled))
		{
			ee()->view->set_message('success', lang('addons_uninstalled'), lang('addons_uninstalled_desc') . implode(', ', $uninstalled), TRUE);
		}
		ee()->functions->redirect(cp_url('addons'));
	}

	// --------------------------------------------------------------------

	/**
	 * Display add-on settings/info
	 *
	 * @access	public
	 * @param	str	$addon	The name of add-on whose settings to display
	 * @return	void
	 */
	public function settings($addon)
	{
		$addon = ee()->security->sanitize_filename(strtolower($addon));

		$installed = $this->addons->get_installed();
		$module = $this->getModules($addon);

		if (ee()->session->userdata['group_id'] != 1)
		{
			// Do they have access to this module?
			if ( ! isset($installed[$addon]) OR
				 ! isset(ee()->session->userdata['assigned_modules'][$installed[$addon]['module_id']]) OR
				ee()->session->userdata['assigned_modules'][$installed[$addon]['module_id']] !== TRUE)
			{
				show_error(lang('unauthorized_access'));
			}
		}
		else
		{
			if ( ! isset($installed[$addon]))
			{
				show_error(lang('requested_module_not_installed').NBS.$addon);
			}
		}

		$view_folder = 'views';

		// set the view path
		define('MODULE_VIEWS', $installed[$addon]['path'].$view_folder.'/');

		// Add the helper/library load path and temporarily
		// switch the view path to the module's view folder
		ee()->load->add_package_path($installed[$addon]['path'], FALSE);

		// Update Module
		// Send version to update class and let it do any required work
		if (file_exists($installed[$addon]['path'].'upd.'.$addon.'.php'))
		{
			require $installed[$addon]['path'].'upd.'.$addon.'.php';

			$class = ucfirst($addon).'_upd';
			$version = $installed[$addon]['module_version'];

			$UPD = new $class;
			$UPD->_ee_path = APPPATH;

			if ($UPD->version > $version && method_exists($UPD, 'update') && $UPD->update($version) !== FALSE)
			{
				ee()->db->update('modules', array('module_version' => $UPD->version), array('module_name' => ucfirst($addon)));
			}
		}

		require_once $installed[$addon]['path'].$installed[$addon]['file'];

		// instantiate the module cp class
		$mod = new $installed[$addon]['class'];
		$mod->_ee_path = APPPATH;


		// add validation callback support to the mcp class (see EE_form_validation for more info)
		ee()->_mcp_reference =& $mod;

		$method = (ee()->input->get('method') !== FALSE) ? ee()->input->get('method') : 'index';

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
		unset(ee()->_mcp_reference);

		// remove package paths
		ee()->load->remove_package_path($installed[$addon]['path']);

		ee()->view->cp_page_title = $module['name'] . ' ' . lang('configuration');
		ee()->view->cp_breadcrumbs = array(
			cp_url('addons') => lang('addon_manager')
		);

		ee()->cp->render('addons/settings', $vars);
	}

	// --------------------------------------------------------------------

	/**
	 * Get a list of modules
	 *
	 * @access	private
	 * @param	str	$name	(optional) Limit the return to this add-on
	 * @return	array		Add-on data in the following format:
	 *   e.g. 'developer'	 => 'native',
	 *        'version'		 => '--',
	 *        'installed'	 => FALSE,
	 *        'name'		 => 'FooBar',
	 *        'package'		 => 'foobar',
	 *        'type'		 => 'module',
	 *        'settings_url' => ''
	 */
	private function getModules($name = NULL)
	{
		$modules = array();
		$installed = ee()->addons->get_installed();

		foreach(ee()->addons->get_files() as $module => $info)
		{
			// ee()->lang->loadfile(( ! isset(ee()->lang_overrides[$module])) ? $module : ee()->lang_overrides[$module]);

			$data = array(
				'developer'		=> $info['type'],
				'version'		=> '--',
				'installed'		=> FALSE,
				'name'			=> $info['name'],
				'package'		=> $module,
				'type'			=> 'module',
				'settings_url'	=> ''
			);

			if (isset($installed[$module]))
			{
				$data['version'] = $installed[$module]['module_version'];
				$data['installed'] = TRUE;
				if ($installed[$module]['has_cp_backend'] == 'y')
				{
					$data['settings_url'] = cp_url('addons/settings/' . $module);
				}
			}

			if (is_null($name))
			{
				$modules[$module] = $data;
			}
			elseif ($name == $module)
			{
				return $data;
			}
		}

		return $modules;
	}

	// --------------------------------------------------------------------

	/**
	 * Get a list of accessories
	 *
	 * @access	private
	 * @param	str	$name	(optional) Limit the return to this add-on
	 * @return	array		Add-on data in the following format:
	 *   e.g. 'developer'	 => 'native',
	 *        'version'		 => '--',
	 *        'installed'	 => FALSE,
	 *        'name'		 => 'FooBar',
	 *        'package'		 => 'foobar',
	 *        'type'		 => 'accessory',
	 *        'settings_url' => ''
	 */
	private function getAccessories($name = NULL)
	{
		$accessories = array();
		$installed = ee()->addons->get_installed('accessories');

		foreach(ee()->addons->get_files('accessories') as $accessory => $info)
		{
			// Grab the version and description
			if ( ! class_exists($info['class']))
			{
				include $info['path'].$info['file'];
			}

			// add the package and view paths
			$path = PATH_THIRD.strtolower($accessory).'/';

			$this->load->add_package_path($path, FALSE);

			$ACC = new $info['class']();

			$this->load->remove_package_path($path);

			$developer = (isset($info['type'])) ? $info['type'] : 'native';

			$data = array(
				'developer'		=> $developer,
				'version'		=> $ACC->version,
				'installed'		=> FALSE,
				'name'			=> $info['name'],
				'package'		=> $accessory,
				'type'			=> 'accessory',
				'settings_url'	=> ''
			);

			if (isset($installed[$accessory]))
			{
				$data['installed'] = TRUE;
				$data['settings_url'] = cp_url('addons/settings/' . $accessory);
			}

			if (is_null($name))
			{
				$accessories[$accessory] = $data;
			}
			elseif ($name == $accessory)
			{
				return $data;
			}
		}

		return $accessories;
	}

	// --------------------------------------------------------------------

	/**
	 * Get a list of plugins
	 *
	 * @access	private
	 * @param	str	$name	(optional) Limit the return to this add-on
	 * @return	array		Add-on data in the following format:
	 *   e.g. 'developer'	 => 'native',
	 *        'version'		 => '--',
	 *        'installed'	 => FALSE,
	 *        'name'		 => 'FooBar',
	 *        'package'		 => 'foobar',
	 *        'type'		 => 'plugin',
	 *        'settings_url' => ''
	 */
	private function getPlugins($name = NULL)
	{
		$ext_len = strlen('.php');

		$plugin_files = array();
		$plugins = array();

		// Get a list of all plugins
		// first party first!
		if (($list = get_filenames(PATH_PI)) !== FALSE)
		{
			foreach ($list as $file)
			{
				if (strncasecmp($file, 'pi.', 3) == 0 &&
					substr($file, -$ext_len) == '.php' &&
					strlen($file) > 7 &&
					in_array(substr($file, 3, -$ext_len), ee()->core->native_plugins))
				{
					$plugin_files[$file] = PATH_PI.$file;
				}
			}
		}

		// third party, in packages
		if (($map = directory_map(PATH_THIRD, 2)) !== FALSE)
		{
			foreach ($map as $pkg_name => $files)
			{
				if ( ! is_array($files))
				{
					$files = array($files);
				}

				foreach ($files as $file)
				{
					if (is_array($file))
					{
						// we're only interested in the top level files for the addon
						continue;
					}

					// we gots a plugin?
					if (strncasecmp($file, 'pi.', 3) == 0 &&
						substr($file, -$ext_len) == '.php' &&
						strlen($file) > strlen('pi.'.'.php'))
					{
						if (substr($file, 3, -$ext_len) == $pkg_name)
						{
							$plugin_files[$file] = PATH_THIRD.$pkg_name.'/'.$file;
						}
					}
				}
			}
		}

		ksort($plugin_files);

		// Grab the plugin data
		foreach ($plugin_files as $file => $path)
		{
			// Used as a fallback name and url identifier
			$filename = substr($file, 3, -$ext_len);

			if ($temp = $this->magpieCheck($filename, $path))
			{
				$plugin_info = $temp;
			};

			@include_once($path);

			if (isset($plugin_info) && is_array($plugin_info))
			{
				// Third party?
				$plugin_info['installed_path'] = $path;

				// fallback on the filename if no name is given
				if ( ! isset($plugin_info['pi_name']) OR $plugin_info['pi_name'] == '')
				{
					$plugin_info['pi_name'] = $filename;
				}

				if ( ! isset($plugin_info['pi_version']))
				{
					$plugin_info['pi_version'] = '--';
				}

				$developer = (strpos($plugin_info['installed_path'], 'third_party') === FALSE) ? 'native' : 'third_party';

				$data = array(
					'developer'		=> $developer,
					'version'		=> $plugin_info['pi_version'],
					'installed'		=> TRUE,
					'name'			=> $plugin_info['pi_name'],
					'package'		=> $filename,
					'type'			=> 'plugin',
					'settings_url'	=> cp_url('addons/settings/' . $filename)
				);

				if (is_null($name))
				{
					$plugins[$filename] = $data;
				}
				elseif ($name == $filename)
				{
					return $data;
				}
			}
			else
			{
				log_message('error', "Invalid Plugin Data: {$filename}");
			}

			unset($plugin_info);
		}

		return $plugins;
	}

	// --------------------------------------------------------------------

	/**
	 * Get a list of fieldtypes
	 *
	 * @access	private
	 * @param	str	$name	(optional) Limit the return to this add-on
	 * @return	array		Add-on data in the following format:
	 *   e.g. 'developer'	 => 'native',
	 *        'version'		 => '--',
	 *        'installed'	 => FALSE,
	 *        'name'		 => 'FooBar',
	 *        'package'		 => 'foobar',
	 *        'type'		 => 'fieldtype',
	 *        'settings_url' => ''
	 */
	private function getFieldtypes($name = NULL)
	{
		ee()->legacy_api->instantiate('channel_fields');

		$fieldtypes = array();
		$installed = ee()->addons->get_installed('fieldtypes');

		foreach (ee()->api_channel_fields->fetch_all_fieldtypes() as $fieldtype => $info)
		{
			$data = array(
				'developer'		=> $info['type'],
				'version'		=> $info['version'],
				'installed'		=> FALSE,
				'name'			=> $info['name'],
				'package'		=> $fieldtype,
				'type'			=> 'fieldtype',
				'settings_url'	=> ''
			);

			if (isset($installed[$fieldtype]))
			{
				$data['installed'] = TRUE;
			}

			if (is_null($name))
			{
				$fieldtypes[$fieldtype] = $data;
			}
			elseif ($name == $fieldtype)
			{
				return $data;
			}
		}

		return $fieldtypes;
	}

	// -------------------------------------------------------------------------

	/**
	 * Check for usage of the magpie plugin and get the plugin_info manually
	 *
	 * @access private
	 * @param  string $filename The filename to check
	 * @param  String $path     Path where the file exists
	 * @return Mixed            Returns $plugin_info if it's MagPie, otherwise
	 *                          nothing
	 */
	private function magpieCheck($filename, $path)
	{
		// Magpie maight already be in use for an accessory or other function
		// If so, we still need the $plugin_info, so we'll open it up and
		// harvest what we need. This is a special exception for Magpie.
		if ($filename == 'magpie' AND in_array($path, get_included_files()) AND class_exists('Magpie'))
		{
			$contents = file_get_contents($path);
			$start = strpos($contents, '$plugin_info');
			$length = strpos($contents, 'Class Magpie') - $start;
			return eval(substr($contents, $start, $length));
		}
	}

	// --------------------------------------------------------------------

	/**
	 * Installs a module
	 *
	 * @access private
	 * @param  str	$module	The add-on to install
	 * @return str			The name of the add-on just installed
	 */
	private function installModule($module)
	{
	 	$name = NULL;
		$module = ee()->security->sanitize_filename(strtolower($module));
		ee()->lang->loadfile($module);

		if (ee()->addons_installer->install($module, 'module', FALSE))
		{
			$name = (lang($module.'_module_name') == FALSE) ? ucfirst($module) : lang($module.'_module_name');
		}

		return $name;
	}

	// --------------------------------------------------------------------

	/**
	 * Uninstalls a module
	 *
	 * @access private
	 * @param  str	$module	The add-on to uninstall
	 * @return str			The name of the add-on just uninstalled
	 */
	private function uninstallModule($module)
	{
		$name = NULL;
		$module = ee()->security->sanitize_filename(strtolower($module));
		ee()->lang->loadfile($module);

		if (ee()->addons_installer->uninstall($module, 'module', FALSE))
		{
			$name = (lang($module.'_module_name') == FALSE) ? ucfirst($module) : lang($module.'_module_name');
		}

		return $name;
	}

	// --------------------------------------------------------------------

	/**
	 * Installs an accessory
	 *
	 * @access private
	 * @param  str	$module	The add-on to install
	 * @return str			The name of the add-on just installed
	 */
	private function installAccessory($accessory)
	{
		$name = NULL;
		$accessory = ee()->security->sanitize_filename(strtolower($accessory));

		if (ee()->addons_installer->install($accessory, 'accessory', FALSE))
		{
			$installed = ee()->addons->get_installed('accessories');
			$name = $installed[$accessory]['name'];
		}

		return $name;
	}

	// --------------------------------------------------------------------

	/**
	 * Uninstalls a accessory
	 *
	 * @access private
	 * @param  str	$module	The add-on to uninstall
	 * @return str			The name of the add-on just uninstalled
	 */
	private function uninstallAccessory($accessory)
	{
		$name = NULL;
		$accessory = ee()->security->sanitize_filename(strtolower($accessory));

		if (ee()->addons_installer->uninstall($accessory, 'accessory', FALSE))
		{
			$data = $this->getAccessories($accessory);
			$name = $data['name'];
		}

		return $name;
	}

	// --------------------------------------------------------------------

	/**
	 * Installs a fieldtype
	 *
	 * @access private
	 * @param  str	$module	The add-on to install
	 * @return str			The name of the add-on just installed
	 */
	private function installFieldtype($fieldtype)
	{
		$name = NULL;
		$fieldtype = ee()->security->sanitize_filename(strtolower($fieldtype));

		if (ee()->addons_installer->install($fieldtype, 'fieldtype', FALSE))
		{
			$installed = ee()->addons->get_installed('fieldtype');
			$name = $installed[$fieldtype]['name'];
		}

		return $name;
	}

	// --------------------------------------------------------------------

	/**
	 * Uninstalls a fieldtype
	 *
	 * @access private
	 * @param  str	$module	The add-on to uninstall
	 * @return str			The name of the add-on just uninstalled
	 */
	private function uninstallFieldtype($fieldtype)
	{
		$name = NULL;
		$accessory = ee()->security->sanitize_filename(strtolower($fieldtype));

		if (ee()->addons_installer->uninstall($fieldtype, 'fieldtype', FALSE))
		{
			$data = $this->getFieldtypes($fieldtype);
			$name = $data['name'];
		}

		return $name;
	}
}
// END CLASS

/* End of file Addons.php */
/* Location: ./system/expressionengine/controllers/cp/Addons/Addons.php */
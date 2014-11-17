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

		if ( ! ee()->cp->allowed_group('can_access_addons'))
		{
			show_error(lang('unauthorized_access'));
		}

		ee()->lang->loadfile('addons');

		$this->params['perpage'] = $this->perpage; // Set a default

		// Add in any submitted search phrase
		ee()->view->search_value = ee()->input->get_post('search');

		$this->base_url = new URL('addons', ee()->session->session_id());

		ee()->load->library('addons');
		ee()->load->helper(array('file', 'directory'));
	}

	// --------------------------------------------------------------------

	/**
	 * Sets up the display filters
	 *
	 * @param int	$total	The total number of add-ons (used in the show filter)
	 * @return	void
	 */
	private function filters($total)
	{
		// Status
		$status = ee('Filter')->make('filter_by_status', 'status', array(
			'installed'		=> strtolower(lang('installed')),
			'uninstalled'	=> strtolower(lang('uninstalled'))
		));
		$status->disableCustomValue();

		// Developer
		$developer = ee('Filter')->make('filter_by_developer', 'developer', array(
			'native'		=> 'EllisLab',
			'third_party'	=> 'Third Party',
		));
		$developer->disableCustomValue();

		$filters = ee('Filter')
			->add($status)
			->add($developer)
			->add('Perpage', $total, 'show_all_addons');

		ee()->view->filters = $filters->render($this->base_url);
		$this->params = $filters->values();
		$this->base_url->addQueryStringVariables($this->params);
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
			$this->install(ee()->input->post('selection'));
		}
		elseif (ee()->input->post('bulk_action') == 'remove')
		{
			$this->remove(ee()->input->post('selection'));
		}

		ee()->view->cp_page_title = lang('addon_manager');
		ee()->view->cp_heading = lang('all_addons');

		$vars = array();

		if ( ! empty(ee()->view->search_value))
		{
			$this->base_url->setQueryStringVariable('search', ee()->view->search_value);
		}

		$data = array();

		$modules = $this->getModules();
		$plugins = $this->getPlugins();
		$fieldtypes = $this->getFieldtypes();

		$addons = array_merge($fieldtypes, $plugins, $modules);

		$this->filters(count($addons));

		foreach($addons as $addon => $info)
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
					'href' => cp_url('addons/install/' . $info['package'], array('return' => base64_encode(ee()->cp->get_safe_refresh()))),
					'title' => lang('install'),
					'class' => 'add'
				)
			);

			$attrs = array('class' => 'not-installed');

			if ($info['installed'])
			{
				$toolbar = array();

				if (isset($info['settings_url']))
				{
					$toolbar['settings'] = array(
						'href' => $info['settings_url'],
						'title' => lang('settings'),
					);
				}

				if (isset($info['manual_url']))
				{
					$toolbar['manual'] = array(
						'href' => $info['manual_url'],
						'title' => lang('manual'),
					);
				}

				if (isset($info['upgrade']))
				{
					$toolbar['txt-only'] = array(
						'href' => cp_url('addons/update/' . $info['package'], array('return' => base64_encode(ee()->cp->get_safe_refresh()))),
						'title' => lang('update'),
						'class' => 'add',
						'content' => sprintf(lang('update_to_version'), $this->formatVersionNumber($info['upgrade']))
					);
				}

				$attrs = array();
			}

			$data[] = array(
				'attrs' => $attrs,
				'columns' => array(
					'addon' => $info['name'],
					'version' => $this->formatVersionNumber($info['version']),
					array('toolbar_items' => $toolbar),
					array(
						'name' => 'selection[]',
						'value' => $info['package'],
						'data'	=> array(
							'confirm' => lang('addon') . ': <b>' . $info['name'] . '</b>'
						)
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

		$modal_vars = array(
			'form_url'	=> $vars['form_url'],
			'hidden'	=> array(
				'bulk_action'	=> 'remove'
			),
			'checklist'	=> array(
				array(
					'kind' => '',
					'desc' => ''
				)
			)
		);

		$vars['modals']['modal-confirm-all'] = ee()->view->render('_shared/modal_confirm_remove', $modal_vars, TRUE);

		ee()->javascript->set_global('lang.remove_confirm', lang('addon') . ': <b>### ' . lang('addons') . '</b>');
		ee()->cp->add_js_script(array(
			'file' => array('cp/addons/index'),
		));

		ee()->cp->render('addons/index', $vars);
	}

	// --------------------------------------------------------------------

	/**
	 * Installs an add-on
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
			ee()->view->set_message('success', lang('addons_installed'), lang('addons_installed_desc') . implode(', ', $installed));
		}

		if (ee()->input->get('return'))
		{
			$return = base64_decode(ee()->input->get('return'));
			$uri_elements = json_decode($return, TRUE);
			$return = cp_url($uri_elements['path'], $uri_elements['arguments']);

			ee()->functions->redirect($return);
		}
	}

	// --------------------------------------------------------------------

	/**
	 * Uninstalls an add-on
	 *
	 * @access	public
	 * @param	str|array	$addon	The name(s) of add-ons to uninstall
	 * @return	void
	 */
	private function remove($addons)
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
			ee()->view->set_message('success', lang('addons_uninstalled'), lang('addons_uninstalled_desc') . implode(', ', $uninstalled));
		}
	}

	// --------------------------------------------------------------------

	/**
	 * Display add-on settings
	 *
	 * @access	public
	 * @param	str	$addon	The name of add-on whose settings to display
	 * @return	void
	 */
	public function settings($addon, $method = NULL)
	{
		ee()->view->cp_page_title = lang('addon_manager');

		$vars = array();
		$breadcrumb = array(
			cp_url('addons') => lang('addon_manager')
		);

		if (is_null($method))
		{
			$method = (ee()->input->get_post('method') !== FALSE) ? ee()->input->get_post('method') : 'index';
		}

		$module = $this->getModules($addon);
		if ( ! empty($module) && $module['installed'] === TRUE)
		{
			$data = $this->getModuleSettings($addon, $method);

			if (is_array($data))
			{
				$vars['_module_cp_body'] = $data['body'];
				ee()->view->cp_heading = $data['heading'];
				$breadcrumb = $data['breadcrumb'];
			}
			else
			{
				$vars['_module_cp_body'] = $data;
				ee()->view->cp_heading = $module['name'] . ' ' . lang('configuration');
			}
		}

		if ( ! isset($vars['_module_cp_body']))
		{
			show_error(lang('requested_module_not_installed').NBS.$addon);
		}

		ee()->view->cp_breadcrumbs = $breadcrumb;

		ee()->cp->render('addons/settings', $vars);
	}

	// --------------------------------------------------------------------

	/**
	 * Display add-on settings
	 *
	 * @access	public
	 * @param	str	$addon	The name of add-on whose settings to display
	 * @return	void
	 */
	public function manual($addon)
	{
		ee()->view->cp_page_title = lang('addon_manager');

		$vars = array();

		$plugin = $this->getPluginInfo($addon);
		if ($plugin === FALSE)
		{
			show_error(lang('requested_module_not_installed').NBS.$addon);
		}

		$vars = array(
			'name'			=> $plugin['pi_name'],
			'version'		=> $this->formatVersionNumber($plugin['pi_version']),
			'author'		=> $plugin['pi_author'],
			'author_url'	=> $plugin['pi_author_url'],
			'description'	=> $plugin['pi_description'],
		);

		$vars['usage'] = array(
			'description' => '',
			'example' => $plugin['pi_usage']
		);

		if (is_array($plugin['pi_usage']))
		{
			$vars['usage']['description'] = $plugin['pi_usage']['description'];
			$vars['usage']['example'] = $plugin['pi_usage']['example'];
			$vars['parameters'] = $plugin['pi_usage']['parameters'];
		}

		ee()->view->cp_heading = $vars['name'] . ' ' . lang('usage');

		ee()->view->cp_breadcrumbs = array(
			cp_url('addons') => lang('addon_manager')
		);

		ee()->cp->render('addons/manual', $vars);
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
	 *        'upgrade'      => '2.0.4' (optional)
	 *        'installed'	 => FALSE,
	 *        'name'		 => 'FooBar',
	 *        'package'		 => 'foobar',
	 *        'type'		 => 'module',
	 *        'settings_url' => '' (optional)
	 */
	private function getModules($name = NULL)
	{
		$modules = array();
		$installed = ee()->addons->get_installed('modules', TRUE);

		foreach(ee()->addons->get_files() as $module => $info)
		{
			ee()->lang->loadfile(( ! isset(ee()->lang_overrides[$module])) ? $module : ee()->lang_overrides[$module]);
			$display_name = (lang(strtolower($module).'_module_name') != FALSE) ? lang(strtolower($module).'_module_name') : $info['name'];

			$data = array(
				'developer'		=> $info['type'],
				'version'		=> '--',
				'installed'		=> FALSE,
				'name'			=> $display_name,
				'package'		=> $module,
				'type'			=> 'module',
			);

			if (isset($installed[$module]))
			{
				if (file_exists($installed[$module]['path'].'upd.'.$module.'.php'))
				{
					require $installed[$module]['path'].'upd.'.$module.'.php';
					$class = ucfirst($module).'_upd';

					ee()->load->add_package_path($installed[$module]['path']);

					$UPD = new $class;
					$UPD->_ee_path = APPPATH;
					if (version_compare($UPD->version, $installed[$module]['module_version'], '>')
						&& method_exists($UPD, 'update'))
					{
						$data['upgrade'] = $UPD->version;
					}
				}

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
	 *        'manual_url' => ''
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
					'manual_url'	=> cp_url('addons/manual/' . $filename)
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
	 * Get plugin info
	 *
	 * Check for a plugin and get it's information
	 *
	 * @access	private
	 * @param	string	plugin filename
	 * @return	mixed	array of plugin data
	 */
	private function getPluginInfo($filename = '')
	{
		if ( ! $filename)
		{
			return FALSE;
		}

		$path = PATH_PI.'pi.'.$filename.'.php';

		if ( ! file_exists($path))
		{
			$path = PATH_THIRD.$filename.'/pi.'.$filename.'.php';

			if ( ! file_exists($path))
			{
				return FALSE;
			}
		}

		if ($temp = $this->magpieCheck($filename, $path))
		{
			$plugin_info = $temp;
		};

		include_once($path);

		if ( ! isset($plugin_info) OR ! is_array($plugin_info))
		{
			return FALSE;
		}

		// We need to clean up for display, might as
		// well do it here and keep the view tidy

		foreach ($plugin_info as $key => $val)
		{
			if ($key == 'pi_author_url')
			{
				$qm = (ee()->config->item('force_query_string') == 'y') ? '' : '?';

				$val = prep_url($val);
				$val = ee()->functions->fetch_site_index().$qm.'URL='.$val;
			}

			$plugin_info[$key] = $val;
		}

		return $plugin_info;
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
	 */
	private function getFieldtypes($name = NULL)
	{
		ee()->legacy_api->instantiate('channel_fields');

		$fieldtypes = array();
		$installed = ee()->addons->get_installed('fieldtypes', TRUE);

		foreach (ee()->api_channel_fields->fetch_all_fieldtypes() as $fieldtype => $info)
		{
			$data = array(
				'developer'		=> $info['type'],
				'version'		=> $info['version'],
				'installed'		=> FALSE,
				'name'			=> $info['name'],
				'package'		=> $fieldtype,
				'type'			=> 'fieldtype',
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
	 * Installs a fieldtype
	 *
	 * @access private
	 * @param  str	$$fieldtype	The add-on to install
	 * @return str				The name of the add-on just installed
	 */
	private function installFieldtype($fieldtype)
	{
		$name = NULL;
		$fieldtype = ee()->security->sanitize_filename(strtolower($fieldtype));

		if (ee()->addons_installer->install($fieldtype, 'fieldtype', FALSE))
		{
			$installed = ee()->addons->get_installed('fieldtype', TRUE);
			$name = $installed[$fieldtype]['name'];
		}

		return $name;
	}

	// --------------------------------------------------------------------

	/**
	 * Uninstalls a fieldtype
	 *
	 * @access private
	 * @param  str	$$fieldtype	The add-on to uninstall
	 * @return str				The name of the add-on just uninstalled
	 */
	private function uninstallFieldtype($fieldtype)
	{
		$name = NULL;
		$fieldtype = ee()->security->sanitize_filename(strtolower($fieldtype));

		if (ee()->addons_installer->uninstall($fieldtype, 'fieldtype', FALSE))
		{
			$data = $this->getFieldtypes($fieldtype);
			$name = $data['name'];
		}

		return $name;
	}

	// --------------------------------------------------------------------

	/**
	 * Render module-specific settings
	 *
	 * @access	private
	 * @param	str	$name	The name of module whose settings to display
	 * @return	str			The rendered settings (with HTML)
	 */
	public function getModuleSettings($name, $method = "index")
	{
		$addon = ee()->security->sanitize_filename(strtolower($name));
		$installed = $this->addons->get_installed('modules', TRUE);

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

		// its possible that a module will try to call a method that does not exist
		// either by accident (ie: a missed function) or by deliberate user url hacking
		if (method_exists($mod, $method))
		{
			$_module_cp_body = $mod->$method();
		}
		else
		{
			$_module_cp_body = lang('requested_page_not_found');
		}

		// unset reference
		unset(ee()->_mcp_reference);

		// remove package paths
		ee()->load->remove_package_path($installed[$addon]['path']);

		return $_module_cp_body;
	}

	// --------------------------------------------------------------------

	/**
	 * Wraps the major version number in a <b> tag
	 *
	 * @access private
	 * @param  str	$version	The version number
	 * @return str				The formatted version number
	 */
	private function formatVersionNumber($version)
	{
		if (strpos($version, '.') === FALSE)
		{
			return $version;
		}

		$parts = explode('.', $version);
		$parts[0] = '<b>' . $parts[0] . '</b>';
		return implode('.', $parts);
	}
}
// END CLASS

/* End of file Addons.php */
/* Location: ./system/EllisLab/ExpressionEngine/Controllers/Addons/Addons.php */
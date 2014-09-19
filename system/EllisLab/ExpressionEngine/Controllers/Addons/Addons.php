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
			'installed'		=> lang('installed'),
			'uninstalled'	=> lang('uninstalled')
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
			'EllisLab'		=> 'EllisLab'
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

		foreach(array_merge($plugins, $accessories, $modules) as $addon => $info)
		{
			// Filter based on status
			if (isset($this->params['filter_by_status']))
			{
				if ((strtolower($this->params['filter_by_status']) == 'installed' &&
					$info['installed'] == FALSE) ||
					(strtolower($this->params['filter_by_status']) == 'uninstalled' &&
					$info['installed'] == TRUE)) {
						continue;
					}
			}

			$toolbar = array(
				'install' => array(
					'href' => '', // @TODO
					'title' => lang('install'),
					'class' => 'add'
				)
			);

			$attrs = array('class' => 'not-installed');

			if ($info['installed'])
			{
				$toolbar = array(
					'settings' => array(
						'href' => '', // @TODO
						'title' => lang('settings'),
					)
				);
				$attrs = array();
			}

			$data[] = array(
				'attrs' => $attrs,
				'columns' => array(
					'addon' => $info['name'],
					'version' => $info['version'],
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
			$vars['pagination'] = $pagination->cp_links($base_url);
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
	 * Get a list of modules
	 *
	 * @access	private
	 * @return	array
	 */
	private function getModules()
	{
		$modules = array();
		$installed = ee()->addons->get_installed();

		foreach(ee()->addons->get_files() as $module => $info)
		{
			// ee()->lang->loadfile(( ! isset(ee()->lang_overrides[$module])) ? $module : ee()->lang_overrides[$module]);

			$data = array(
				'author'	=> NULL,
				'version'	=> '--',
				'installed'	=> FALSE,
				'name'		=> $info['name'],
				'package'	=> $module
			);

			if (isset($installed[$module]))
			{
				$data['version'] = $installed[$module]['module_version'];
				$data['installed'] = TRUE;
			}

			$modules[$module] = $data;
		}

		return $modules;
	}

	// --------------------------------------------------------------------

	/**
	 * Get a list of accessories
	 *
	 * @access	private
	 * @return	array
	 */
	private function getAccessories()
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

			$data = array(
				'author'	=> NULL,
				'version'	=> $ACC->version,
				'installed'	=> FALSE,
				'name'		=> $info['name'],
				'package'	=> $accessory
			);

			if (isset($installed[$accessory]))
			{
				$data['installed'] = TRUE;
			}

			$accessories[$accessory] = $data;
		}

		return $accessories;
	}

	// --------------------------------------------------------------------

	/**
	 * Get a list of plugins
	 *
	 * @access	private
	 * @return	array
	 */
	private function getPlugins()
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
				$plugins[$filename] = array(
					'author'	=> $plugin_info['pi_author'],
					'version'	=> $plugin_info['pi_version'],
					'installed'	=> TRUE,
					'name'		=> $plugin_info['pi_name'],
					'package'	=> $filename
				);
			}
			else
			{
				log_message('error', "Invalid Plugin Data: {$filename}");
			}

			unset($plugin_info);
		}

		return $plugins;
	}

	// -------------------------------------------------------------------------

	/**
	 * Check for usage of the magpie plugin and get the plugin_info manually
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
}
// END CLASS

/* End of file Addons.php */
/* Location: ./system/expressionengine/controllers/cp/Addons/Addons.php */
<?php

namespace EllisLab\ExpressionEngine\Controllers\Addons;

if ( ! defined('BASEPATH')) exit('No direct script access allowed');

use CP_Controller;
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
	private function filters($total, $developers)
	{
		// Status
		$status = ee('Filter')->make('filter_by_status', 'filter_by_status', array(
			'installed'   => strtolower(lang('installed')),
			'uninstalled' => strtolower(lang('uninstalled')),
			'updates'     => strtolower(lang('needs_updates'))
		));
		$status->disableCustomValue();

		// Developer
		$developer_options = array();
		foreach ($developers as $developer)
		{
			$developer_options[$this->makeDeveloperKey($developer)] = $developer;
		}
		$developer = ee('Filter')->make('filter_by_developer', 'developer', $developer_options);
		$developer->disableCustomValue();

		$filters = ee('Filter')
			->add($status)
			->add($developer)
			->add('Perpage', $total, 'show_all_addons');

		ee()->view->filters = $filters->render($this->base_url);
		$this->params = $filters->values();
		$this->base_url->addQueryStringVariables($this->params);
	}

	private function makeDeveloperKey($str)
	{
		return strtolower(str_replace(' ', '_', $str));
	}

	// --------------------------------------------------------------------

	/**
	 * Index function
	 *
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
		elseif (ee()->input->post('bulk_action') == 'update')
		{
			$this->update(ee()->input->post('selection'));
		}

		ee()->view->cp_page_title = lang('addon_manager');
		ee()->view->cp_heading = lang('all_addons');

		$vars = array();

		if ( ! empty(ee()->view->search_value))
		{
			$this->base_url->setQueryStringVariable('search', ee()->view->search_value);
		}

		$data = array();

		$addons = $this->getAllAddons();
		$developers = array_map(function($addon) { return $addon['developer']; }, $addons);
		array_unique($developers);

		// Setup the Table
		$table = ee('CP/Table', array('autosort' => TRUE, 'autosearch' => TRUE));
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

		$this->base_url->setQueryStringVariable('sort_col', $table->sort_col);
		$this->base_url->setQueryStringVariable('sort_dir', $table->sort_dir);

		$this->filters(count($addons), $developers);

		// [Re]set the table's limit
		$table->config['limit'] = $this->params['perpage'];

		foreach($addons as $addon => $info)
		{
			// Filter based on status
			if (isset($this->params['filter_by_status']))
			{
				if ((strtolower($this->params['filter_by_status']) == 'installed'
					 && $info['installed'] == FALSE)
				     ||	(strtolower($this->params['filter_by_status']) == 'uninstalled'
						 && $info['installed'] == TRUE)
				     ||	(strtolower($this->params['filter_by_status']) == 'updates'
						 && (! isset($info['update'])
						     || $info['installed'] == FALSE)))
				{
					continue;
				}
			}

			// Filter based on developer
			if (isset($this->params['filter_by_developer']))
			{
				if ($this->params['filter_by_developer'] != $this->makeDeveloperKey($info['developer']))
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

				if (isset($info['update']))
				{
					$toolbar['txt-only'] = array(
						'href' => cp_url('addons/update/' . $info['package'], array('return' => base64_encode(ee()->cp->get_safe_refresh()))),
						'title' => strtolower(lang('update')),
						'class' => 'add',
						'content' => sprintf(lang('update_to_version'), $this->formatVersionNumber($info['update']))
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

		$table->setNoResultsText('no_addon_search_results');
		$table->setData($data);

		$vars['table'] = $table->viewData($this->base_url);
		$vars['form_url'] = $vars['table']['base_url'];

		if ( ! empty($vars['table']['data']))
		{
			// Paginate!
			$vars['pagination'] = ee('CP/Pagination', $vars['table']['total_rows'])
				->perPage($vars['table']['limit'])
				->currentPage($vars['table']['page'])
				->render($this->base_url);
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

		ee()->javascript->set_global('lang.remove_confirm', lang('addon') . ': <b>### ' . lang('addons') . '</b>');
		ee()->cp->add_js_script(array(
			'file' => array('cp/v3/confirm_remove'),
		));

		ee()->cp->render('addons/index', $vars);
	}

	/**
	 * Compiles a list of all available add-ons
	 *
	 * @return array An associative array of add-on data
	 */
	private function getAllAddons()
	{
		$providers = ee('App')->getProviders();
		// Remove non-add-on providers from the list
		unset($providers['ee']);

		$addons = array();

		// @TODO move these 2 things out of "add-ons" entirely
		$uninstallable = array('channel', 'comment');

		foreach (array_keys($providers) as $name)
		{
			if (in_array($name, $uninstallable))
			{
				continue;
			}

			$addon = $this->getExtension($name);
			$addon = array_merge($addon, $this->getFieldType($name));
			$addon = array_merge($addon, $this->getPlugin($name));
			$addon = array_merge($addon, $this->getModule($name));

			if ( ! empty($addon))
			{
				$addons[$name] = $addon;
			}
		}

		return $addons;
	}

	// --------------------------------------------------------------------

	/**
	 * Updates an add-on
	 *
	 * @param str $addon The name of the add-on to update
	 * @return void
	 */
	public function update($addons)
	{
		if ( ! is_array($addons))
		{
			$addons = array($addons);
		}

		$updated = array();

		foreach ($addons as $addon)
		{
			$module = $this->getModule($addon);
			if ( ! empty($module)
				&& $module['installed'] === TRUE
				&& array_key_exists('update', $module))
			{
				$installed = ee()->addons->get_installed('modules', TRUE);

				require_once $installed[$addon]['path'].'upd.'.$addon.'.php';

				$class = ucfirst($addon).'_upd';
				$version = $installed[$addon]['module_version'];

				ee()->load->add_package_path($installed[$addon]['path']);

				$UPD = new $class;
				$UPD->_ee_path = APPPATH;

				if ($UPD->update($version) !== FALSE)
				{
					$module = ee('Model')->get('Module', $installed[$addon]['module_id'])
						->first();
					$module->module_version = $UPD->version;
					$module->save();

					$name = (lang($addon.'_module_name') == FALSE) ? ucfirst($module->module_name) : lang($addon.'_module_name');

					$updated[$addon] = $name;
				}
			}

			$fieldtype = $this->getFieldtype($addon);
			if ( ! empty($fieldtype)
				&& $fieldtype['installed'] === TRUE
				&& array_key_exists('update', $fieldtype))
			{
				$FT = ee()->api_channel_fields->setup_handler($addon, TRUE);
				if ($FT->update($fieldtype['version']) !== FALSE)
				{
					if (ee()->api_channel_fields->apply('update', array($fieldtype['version'])) !== FALSE)
					{
						$model = ee('Model')->get('Fieldtype')
							->filter('name', $addon)
							->first();

						$model->version = $FT->info['version'];
						$model->save();

						if ( ! isset($updated[$addon]))
						{
							$updated[$addon] = $fieldtype['name'];
						}
					}
				}
			}

			$extension = $this->getExtension($addon);
			if ( ! empty($extension)
				&& $extension['installed'] === TRUE
				&& array_key_exists('update', $extension))
			{
				$class_name = $extension['class'];
				$Extension = new $class_name();
				$Extension->update_extension($extension['version']);
				ee()->extensions->version_numbers[$class_name] = $Extension->version;

				if ( ! isset($updated[$addon]))
				{
					$updated[$addon] = $extension['name'];
				}
			}

			$plugin = $this->getPlugin($addon);
			if ( ! empty($plugin)
				&& $plugin['installed'] === TRUE
				&& array_key_exists('update', $plugin))
			{

				$info = ee('App')->get($addon);

				$typography = 'n';
				if ($info->get('plugin.typography'))
				{
					$typography = 'y';
				}

				$model = ee('Model')->get('Plugin')
					->filter('plugin_package', $plugin['package'])
					->first();
				$model->plugin_name = $plugin['name'];
				$model->plugin_package = $plugin['package'];
				$model->plugin_version = $info->getVersion();
				$model->is_typography_related = $typography;
				$model->save();

				if ( ! isset($updated[$addon]))
				{
					$updated[$addon] = $plugin['name'];
				}
			}
		}

		if ( ! empty($updated))
		{
			$flashdata = (ee()->input->get('return')) ? TRUE : FALSE;
			ee()->view->set_message('success', lang('addons_updated'), lang('addons_updated_desc') . implode(', ', $updated), $flashdata);
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
	 * Installs an add-on
	 *
	 * @param	str|array	$addons	The name(s) of add-ons to install
	 * @return	void
	 */
	public function install($addons)
	{
		if ( ! is_array($addons))
		{
			$addons = array($addons);
		}

		ee()->load->library('addons/addons_installer');

		$installed = array();

		foreach ($addons as $addon)
		{
			$module = $this->getModule($addon);
			if ( ! empty($module) && $module['installed'] === FALSE)
			{
				$name = $this->installModule($addon);
				if ($name)
				{
					$installed[$addon] = $name;
				}
			}

			$fieldtype = $this->getFieldtype($addon);
			if ( ! empty($fieldtype) && $fieldtype['installed'] === FALSE)
			{
				$name = $this->installFieldtype($addon);
				if ($name && ! isset($installed[$addon]))
				{
					$installed[$addon] = $name;
				}
			}

			$extension = $this->getExtension($addon);
			if ( ! empty($extension) && $extension['installed'] === FALSE)
			{
				$name = $this->installExtension($addon);
				if ($name && ! isset($installed[$addon]))
				{
					$installed[$addon] = $name;
				}
			}

			$plugin = $this->getPlugin($addon);
			if ( ! empty($plugin) && $plugin['installed'] === FALSE)
			{
				$info = ee('App')->get($addon);

				$typography = 'n';
				if ($info->get('plugin.typography'))
				{
					$typography = 'y';
				}

				$model = ee('Model')->make('Plugin');
				$model->plugin_name = $plugin['name'];
				$model->plugin_package = $plugin['package'];
				$model->plugin_version = $info->getVersion();
				$model->is_typography_related = $typography;
				$model->save();

				if ( ! isset($installed[$addon]))
				{
					$installed[$addon] = $plugin['name'];
				}
			}
		}

		if ( ! empty($installed))
		{
			$flashdata = (ee()->input->get('return')) ? TRUE : FALSE;
			ee()->view->set_message('success', lang('addons_installed'), lang('addons_installed_desc') . implode(', ', $installed), $flashdata);
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
	 * @param	str|array	$addons	The name(s) of add-ons to uninstall
	 * @return	void
	 */
	private function remove($addons)
	{
		if ( ! is_array($addons))
		{
			$addons = array($addons);
		}

		ee()->load->library('addons/addons_installer');

		$uninstalled = array();

		foreach ($addons as $addon)
		{
			$module = $this->getModule($addon);
			if ( ! empty($module) && $module['installed'] === TRUE)
			{
				$name = $this->uninstallModule($addon);
				if ($name)
				{
					$uninstalled[$addon] = $name;
				}
			}

			$fieldtype = $this->getFieldtype($addon);
			if ( ! empty($fieldtype) && $fieldtype['installed'] === TRUE)
			{
				$name = $this->uninstallFieldtype($addon);
				if ($name && ! isset($uninstalled[$addon]))
				{
					$uninstalled[$addon] = $name;
				}
			}

			$extension = $this->getExtension($addon);
			if ( ! empty($extension) && $extension['installed'] === TRUE)
			{
				$name = $this->uninstallExtension($addon);
				if ($name && ! isset($uninstalled[$addon]))
				{
					$uninstalled[$addon] = $name;
				}
			}

			$plugin = $this->getPlugin($addon);
			if ( ! empty($plugin) && $plugin['installed'] === TRUE)
			{
				ee('Model')->get('Plugin')
					->filter('plugin_package', $addon)
					->delete();

				if ( ! isset($uninstalled[$addon]))
				{
					$uninstalled[$addon] = $plugin['name'];
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

		// Module
		$module = $this->getModule($addon);
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
		else
		{
			// Fieldtype
			$fieldtype = $this->getFieldtype($addon);
			if ( ! empty($fieldtype) && $fieldtype['installed'] === TRUE)
			{
				if ($method == 'save')
				{
					$this->saveFieldtypeSettings($fieldtype);
					ee()->functions->redirect(cp_url('addons/settings/' . $addon));
				}

				$vars['_module_cp_body'] = $this->getFieldtypeSettings($fieldtype);
				ee()->view->cp_heading = $fieldtype['name'] . ' ' . lang('configuration');
			}
			else
			{
				// Extension
				$extension = $this->getExtension($addon);
				if ( ! empty($extension) && $extension['installed'] === TRUE)
				{
					if ($method == 'save')
					{
						$this->saveExtensionSettings($addon);
						ee()->functions->redirect(cp_url('addons/settings/' . $addon));
					}

					$vars['_module_cp_body'] = $this->getExtensionSettings($addon);
					ee()->view->cp_heading = $extension['name'] . ' ' . lang('configuration');
				}
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
	 * Display plugin manual/documentation
	 *
	 * @param	str	$addon	The name of plugin whose manual to display
	 * @return	void
	 */
	public function manual($addon)
	{
		ee()->view->cp_page_title = lang('addon_manager');

		$vars = array();

		$plugin = $this->getPlugin($addon);
		if (empty($plugin))
		{
			show_error(lang('requested_module_not_installed').NBS.$addon);
		}

		$info = ee('App')->get($addon);

		$vars = array(
			'name'			=> $info->getName(),
			'version'		=> $this->formatVersionNumber($info->getVersion()),
			'author'		=> $info->getAuthor(),
			'author_url'	=> $info->get('author_url'),
			'description'	=> $info->get('description')
		);

		$usage = $info->get('plugin.usage');

		$vars['usage'] = array(
			'description' => '',
			'example' => $usage
		);

		if (is_array($usage))
		{
			$vars['usage']['description'] = $usage['description'];
			$vars['usage']['example'] = $usage['example'];
			$vars['parameters'] = $usage['parameters'];
		}

		ee()->view->cp_heading = $vars['name'] . ' ' . lang('manual');

		ee()->view->cp_breadcrumbs = array(
			cp_url('addons') => lang('addon_manager')
		);

		ee()->cp->render('addons/manual', $vars);
	}

	// --------------------------------------------------------------------

	/**
	 * Get data on a module
	 *
	 * @param	str	$name	The add-on name
	 * @return	array		Add-on data in the following format:
	 *   e.g. 'developer'	 => 'native',
	 *        'version'		 => '--',
	 *        'update'       => '2.0.4' (optional)
	 *        'installed'	 => FALSE,
	 *        'name'		 => 'FooBar',
	 *        'package'		 => 'foobar',
	 *        'type'		 => 'module',
	 *        'settings_url' => '' (optional)
	 */
	private function getModule($name)
	{
		try
		{
			$info = ee('App')->get($name);
		}
		catch (\Exception $e)
		{
			show_404();
		}

		if ( ! file_exists($info->getPath() . '/mod.' . $name . '.php'))
		{
			return array();
		}

		ee()->lang->loadfile($name);
		$display_name = (lang(strtolower($name).'_module_name') != FALSE) ? lang(strtolower($name).'_module_name') : $info->getName();

		$data = array(
			'developer'		=> $info->getAuthor(),
			'version'		=> '--',
			'installed'		=> FALSE,
			'name'			=> $display_name,
			'package'		=> $name,
			'type'			=> 'module',
		);

		$module = ee('Model')->get('Module')
			->filter('module_name', $name)
			->first();

		if ($module)
		{
			$data['installed'] = TRUE;
			$data['version'] = $module->module_version;

			if ($info->get('settings_exist'))
			{
				$data['settings_url'] = ee('CP/URL', 'addons/settings/' . $name);
			}

			if (file_exists($info->getPath() . '/upd.' . $name . '.php'))
			{
				require_once $info->getPath() . '/upd.' . $name . '.php';
				$class = ucfirst($name).'_upd';

				ee()->load->add_package_path($info->getPath());

				$UPD = new $class;

				if (version_compare($info->getVersion(), $module->module_version, '>')
					&& method_exists($UPD, 'update'))
				{
					$data['update'] = $info->getVersion();
				}
			}
		}

		return $data;
	}

	// --------------------------------------------------------------------

	/**
	 * Get data on a plugin
	 *
	 * @param	str	$name	The add-on name
	 * @return	array		Add-on data in the following format:
	 *   e.g. 'developer'	 => 'native',
	 *        'version'		 => '--',
	 *        'installed'	 => FALSE,
	 *        'name'		 => 'FooBar',
	 *        'package'		 => 'foobar',
	 *        'type'		 => 'plugin',
	 *        'manual_url' => ''
	 */
	private function getPlugin($name)
	{
		try
		{
			$info = ee('App')->get($name);
		}
		catch (\Exception $e)
		{
			show_404();
		}

		if ( ! file_exists($info->getPath() . '/pi.' . $name . '.php'))
		{
			return array();
		}

		$data = array(
			'developer'		=> $info->getAuthor(),
			'version'		=> '--',
			'installed'		=> FALSE,
			'name'			=> $info->getName(),
			'package'		=> $name,
			'type'			=> 'plugin',
			'manual_url'	=> ee('CP/URL', 'addons/manual/' . $name),
		);

		$model = ee('Model')->get('Plugin')
			->filter('plugin_package', $name)
			->first();
		if ( ! is_null($model))
		{
			$data['installed'] = TRUE;
			if (version_compare($info->getVersion(), $model->plugin_version, '>'))
			{
				$data['update'] = $info->getVersion();
				$data['version'] = $model->plugin_version;
			}
		}

		return $data;
	}

	// --------------------------------------------------------------------

	/**
	 * Get data on a fieldtype
	 *
	 * @param	str	$name	The add-on name
	 * @return	array		Add-on data in the following format:
	 *   e.g. 'developer'	 => 'native',
	 *        'version'		 => '--',
	 *        'installed'	 => FALSE,
	 *        'name'		 => 'FooBar',
	 *        'package'		 => 'foobar',
	 *        'type'		 => 'fieldtype',
	 *        'settings'     => array(),
	 *        'settings_url' => '' (optional)
	 */
	private function getFieldtype($name)
	{
		try
		{
			$info = ee('App')->get($name);
		}
		catch (\Exception $e)
		{
			show_404();
		}

		if ( ! file_exists($info->getPath() . '/ft.' . $name . '.php'))
		{
			return array();
		}

		$data = array(
			'developer'		=> $info->getAuthor(),
			'version'		=> '--',
			'installed'		=> FALSE,
			'name'			=> $info->getName(),
			'package'		=> $name,
			'type'			=> 'fieldtype',
		);

		$model = ee('Model')->get('Fieldtype')
			->filter('name', $name)
			->first();

		if ($model)
		{
			$data['installed'] = TRUE;
			$data['version'] = $model->version;

			if (version_compare($info->getVersion(), $model->version, '>'))
			{
				$data['update'] = $info->getVersion();
			}

			if ($info->get('settings_exist'))
			{
				if ($model->settings)
				{
					$data['settings'] = $model->settings;
				}
				$data['settings_url'] = ee('CP/URL', 'addons/settings/' . $name);
			}
		}

		return $data;
	}

	// --------------------------------------------------------------------

	/**
	 * Get data on an extension
	 *
	 * @param	str	$name	The add-on name
	 * @return	array		Add-on data in the following format:
	 *   e.g. 'developer'	 => 'native',
	 *        'version'		 => '--',
	 *        'update'       => '2.0.4' (optional)
	 *        'installed'	 => TRUE|FALSE,
	 *        'name'		 => 'FooBar',
	 *        'package'		 => 'foobar',
	 *        'class'        => 'Foobar_ext',
	 *        'enabled'		 => NULL|TRUE|FALSE
	 *        'manual_url'	 => '' (optional),
	 *        'settings_url' => '' (optional)
	 */
	private function getExtension($name)
	{
		if (ee()->config->item('allow_extensions') != 'y')
		{
			return array();
		}

		try
		{
			$info = ee('App')->get($name);
		}
		catch (\Exception $e)
		{
			show_404();
		}

		if ( ! file_exists($info->getPath() . '/ext.' . $name . '.php'))
		{
			return array();
		}

		$class_name =ucfirst($name) . '_ext';

		$data = array(
			'developer'		=> $info->getAuthor(),
			'version'		=> '--',
			'installed'		=> FALSE,
			'enabled'		=> NULL,
			'name'			=> $info->getName(),
			'package'		=> $name,
			'class'			=> $class_name,
		);

		$extension = ee('Model')->get('Extension')
			->filter('class', $class_name)
			->first();

		if ($extension)
		{
			$data['version'] = $extension->version;
			$data['installed'] = TRUE;
			$data['enabled'] = $extension->enabled;

			ee()->load->add_package_path($info->getPath());

			if ( ! class_exists($class_name))
			{
				$file = $info->getPath() . '/ext.' . $name . '.php';
				if (ee()->config->item('debug') == 2
					OR (ee()->config->item('debug') == 1
						AND ee()->session->userdata('group_id') == 1))
				{
					include($file);
				}
				else
				{
					@include($file);
				}

				if ( ! class_exists($class_name))
				{
					trigger_error(str_replace(array('%c', '%f'), array(htmlentities($class_name), htmlentities($file)), lang('extension_class_does_not_exist')));
					continue;
				}
			}

			// Get some details on the extension
			$Extension = new $class_name();
			if (version_compare($info->getVersion(), $extension->version, '>')
				&& method_exists($Extension, 'update_extension') === TRUE)
			{
				$data['update'] = $info->getVersion();
			}

			if ($info->get('settings_exist'))
			{
				$data['settings_url'] = ee('CP/URL', 'addons/settings/' . $name);
			}

			if ($info->get('docs_url'))
			{
				$data['manual_url'] = ee()->cp->masked_url($info->get('docs_url'));
			}
		}

		return $data;
	}

	// --------------------------------------------------------------------

	/**
	 * Installs an extension
	 *
	 * @param  str	$addon	The add-on to install
	 * @return str			The name of the add-on just installed
	 */
	private function installExtension($addon)
	{
	 	$name = NULL;
		$module = ee()->security->sanitize_filename(strtolower($addon));
		$extension = $this->getExtension($addon);

		if (ee()->addons_installer->install($addon, 'extension', FALSE))
		{
			$name = $extension['name'];
		}

		return $name;
	}

	// --------------------------------------------------------------------

	/**
	 * Uninstalls a an extension
	 *
	 * @param  str	$addon	The add-on to uninstall
	 * @return str			The name of the add-on just uninstalled
	 */
	private function uninstallExtension($addon)
	{
		$name = NULL;
		$module = ee()->security->sanitize_filename(strtolower($addon));
		$extension = $this->getExtension($addon);

		if (ee()->addons_installer->uninstall($addon, 'extension', FALSE))
		{
			$name = $extension['name'];
		}

		return $name;
	}

	// -------------------------------------------------------------------------

	/**
	 * Installs a module
	 *
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
	 * @param  str	$$fieldtype	The add-on to install
	 * @return str				The name of the add-on just installed
	 */
	private function installFieldtype($fieldtype)
	{
		$name = NULL;
		$fieldtype = ee()->security->sanitize_filename(strtolower($fieldtype));

		if (ee()->addons_installer->install($fieldtype, 'fieldtype', FALSE))
		{
			$data = $this->getFieldtype($fieldtype);
			$name = $data['name'];
		}

		return $name;
	}

	// --------------------------------------------------------------------

	/**
	 * Uninstalls a fieldtype
	 *
	 * @param  str	$$fieldtype	The add-on to uninstall
	 * @return str				The name of the add-on just uninstalled
	 */
	private function uninstallFieldtype($fieldtype)
	{
		$name = NULL;
		$fieldtype = ee()->security->sanitize_filename(strtolower($fieldtype));

		if (ee()->addons_installer->uninstall($fieldtype, 'fieldtype', FALSE))
		{
			$data = $this->getFieldtype($fieldtype);
			$name = $data['name'];
		}

		return $name;
	}

	// --------------------------------------------------------------------

	/**
	 * Render module-specific settings
	 *
	 * @param	str	$name	The name of module whose settings to display
	 * @return	str			The rendered settings (with HTML)
	 */
	private function getModuleSettings($name, $method = "index")
	{
		$addon = ee()->security->sanitize_filename(strtolower($name));

		$info = ee('App')->get($name);

		$module = ee('Model')->get('Module')
			->filter('module_name', $name)
			->first();

		if (ee()->session->userdata['group_id'] != 1)
		{
			// Do they have access to this module?
			if ( ! isset($module) OR
				 ! isset(ee()->session->userdata['assigned_modules'][$module->module_id]) OR
				ee()->session->userdata['assigned_modules'][$module->module_id] !== TRUE)
			{
				show_error(lang('unauthorized_access'));
			}
		}
		else
		{
			if ( ! isset($module))
			{
				show_error(lang('requested_module_not_installed').NBS.$addon);
			}
		}

		$view_folder = 'views';

		// set the view path
		define('MODULE_VIEWS', $info->getPath() . '/' . $view_folder . '/');

		// Add the helper/library load path and temporarily
		// switch the view path to the module's view folder
		ee()->load->add_package_path($info->getPath());

		require_once $info->getPath() . '/mcp.' . $name . '.php';

		// instantiate the module cp class
		$class = ucfirst($name) . '_mcp';
		$mod = new $class;
		$mod->_ee_path = APPPATH;

		// add validation callback support to the mcp class (see EE_form_validation for more info)
		ee()->set('_mcp_reference', $mod);

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
		ee()->remove('_mcp_reference');

		// remove package paths
		ee()->load->remove_package_path($info->getPath());

		return $_module_cp_body;
	}

	private function getExtensionSettings($name)
	{
		if (ee()->config->item('allow_extensions') != 'y')
		{
			show_error(lang('unauthorized_access'));
		}

		$addon = ee()->security->sanitize_filename(strtolower($name));

		$extension = $this->getExtension($addon);

		if (empty($extension) || $extension['installed'] === FALSE)
		{
			show_error(lang('requested_module_not_installed').NBS.$addon);
		}

		ee()->lang->loadfile(strtolower($addon));

		$extension_model = ee('Model')->get('Extension')
			->filter('enabled', 'y')
			->filter('class', $extension['class'])
			->first();

		$current = strip_slashes($extension_model->settings);

		$class_name = $extension['class'];
		$OBJ = new $class_name();

		if (method_exists($OBJ, 'settings_form') === TRUE)
		{
			return $OBJ->settings_form($current);
		}

		$vars = array(
			'base_url' => cp_url('addons/settings/' . $name . '/save'),
			'cp_page_title' => $extension['name'] . ' ' . lang('configuration'),
			'save_btn_text' => 'btn_save_settings',
			'save_btn_text_working' => 'btn_saving',
			'sections' => array(array())
		);

		$settings = array();

		foreach ($OBJ->settings() as $key => $options)
		{
			$element = array(
				'title' => $key,
				'desc' => '',
				'fields' => array()
			);

			if (isset($current[$key]))
			{
				$value = $current[$key];
			}
			elseif (is_array($options))
			{
				$value = $options[2];
			}
			elseif (is_string($options))
			{
				$value = $options;
			}
			else
			{
				$value = '';
			}

			$sub = '';
			$choices = array();
			$selected = '';

			if (isset($subtext[$key]))
			{
				foreach ($subtext[$key] as $txt)
				{
					$sub .= lang($txt);
				}
			}

			$element['desc'] = $sub;

			if ( ! is_array($options))
			{
				$element['fields'][$key] = array(
					'type' => 'text',
					'value' => str_replace("\\'", "'", $value),
				);
				$vars['sections'][0][] = $element;

				continue;
			}

			switch ($options[0])
			{
				case 's':
					// Select fields
					foreach ($options[1] as $k => $v)
					{
						$choices[$k] = lang($v);
					}

					$element['fields'][$key] = array(
						'type' => 'dropdown',
						'value' => $value,
						'choices' => $choices
					);
					break;

				case 'r':
					// Radio buttons
					foreach ($options[1] as $k => $v)
					{
						$choices[$k] = lang($v);
					}

					$element['fields'][$key] = array(
						'type' => 'radio',
						'value' => $value,
						'choices' => $choices
					);
					break;

				case 'ms':
				case 'c':
					// Multi-select & Checkboxes
					foreach ($options[1] as $k => $v)
					{
						$choices[$k] = lang($v);
					}

					$element['fields'][$key] = array(
						'type' => 'checkbox',
						'value' => $value,
						'choices' => $choices
					);
					break;

				case 't':
					// Textareas
					$element['fields'][$key] = array(
						'type' => 'textarea',
						'value' => str_replace("\\'", "'", $value),
						'kill_pipes' => $options['1']['kill_pipes']
					);
					break;

				case 'i':
					// Input fields
					$element['fields'][$key] = array(
						'type' => 'text',
						'value' => str_replace("\\'", "'", $value),
					);
					break;
			}

			$vars['sections'][0][] = $element;
		}

		return ee('View')->make('_shared/form')->render($vars);
	}

	private function saveExtensionSettings($name)
	{
		if (ee()->config->item('allow_extensions') != 'y')
		{
			show_error(lang('unauthorized_access'));
		}

		$addon = ee()->security->sanitize_filename(strtolower($name));

		$extension = $this->getExtension($addon);

		if (empty($extension) || $extension['installed'] === FALSE)
		{
			show_error(lang('requested_module_not_installed').NBS.$addon);
		}

		ee()->lang->loadfile(strtolower($addon));

		$class_name = $extension['class'];
		$OBJ = new $class_name();

		if (method_exists($OBJ, 'settings_form') === TRUE)
		{
			return $OBJ->save_settings();
		}

		$settings = array();

		foreach ($OBJ->settings() as $key => $value)
		{
			if ( ! is_array($value))
			{
				$settings[$key] = (ee()->input->post($key) !== FALSE) ? ee()->input->get_post($key) : $value;
			}
			elseif (is_array($value) && isset($value['1']) && is_array($value['1']))
			{
				if(is_array(ee()->input->post($key)) OR $value[0] == 'ms' OR $value[0] == 'c')
				{
					$data = (is_array(ee()->input->post($key))) ? ee()->input->get_post($key) : array();

					$data = array_intersect($data, array_keys($value['1']));
				}
				else
				{
					if (ee()->input->post($key) === FALSE)
					{
						$data = ( ! isset($value['2'])) ? '' : $value['2'];
					}
					else
					{
						$data = ee()->input->post($key);
					}
				}

				$settings[$key] = $data;
			}
			else
			{
				$settings[$key] = (ee()->input->post($key) !== FALSE) ? ee()->input->get_post($key) : '';
			}
		}

		$extension_model = ee('Model')->get('Extension')
			->filter('enabled', 'y')
			->filter('class', $extension['class'])
			->first();

		$extension_model->settings = $settings;
		$extension_model->save();

		ee('Alert')->makeInline('shared-form')
			->asSuccess()
			->withTitle(lang('settings_saved'))
			->addToBody(sprintf(lang('settings_saved_desc'), $extension['name']))
			->defer();
	}

	private function getFieldtypeSettings($fieldtype)
	{
		if ( ! ee()->cp->allowed_group('can_access_addons', 'can_access_fieldtypes'))
		{
			show_error(lang('unauthorized_access'));
		}

		$FT = ee()->api_channel_fields->setup_handler($fieldtype['package'], TRUE);

		$FT->settings = $fieldtype['settings'];

		$fieldtype_settings = ee()->api_channel_fields->apply('display_global_settings');

		if (is_array($fieldtype_settings))
		{
			$vars = array(
				'base_url' => cp_url('addons/settings/' . $fieldtype['package'] . '/save'),
				'cp_page_title' => $fieldtype['name'] . ' ' . lang('configuration'),
				'save_btn_text' => 'btn_save_settings',
				'save_btn_text_working' => 'btn_saving',
				'sections' => array(array($fieldtype_settings))
			);
			return ee('View')->make('_shared/form')->render($vars);
		}
		else
		{
			$html = '<div class="box">';
			$html .= '<h1>' . $fieldtype['name'] . ' ' . lang('configuration') . '</h1>';
			$html .= form_open(cp_url('addons/settings/' . $fieldtype['package'] . '/save'), 'class="settings"');
			$html .= ee('Alert')->get('shared-form');
			$html .= $fieldtype_settings;
			$html .= '<fieldset class="form-ctrls">';
			$html .= cp_form_submit('btn_save_settings', 'btn_saving');
			$html .= '</fieldset>';
			$html .= form_close();
			$html .= '</div>';

			return $html;
		}
	}

	private function saveFieldtypeSettings($fieldtype)
	{
		if ( ! ee()->cp->allowed_group('can_access_addons', 'can_access_fieldtypes'))
		{
			show_error(lang('unauthorized_access'));
		}

		$FT = ee()->api_channel_fields->setup_handler($fieldtype['package'], TRUE);

		$FT->settings = $fieldtype['settings'];

		$settings = ee()->api_channel_fields->apply('save_global_settings');

		$fieldtype_model = ee('Model')->get('ChannelField')
			->filter('site_id', ee()->config->item('site_id'))
			->filter('field_name', $fieldtype['package'])
			->first();

		$fieldtype_model->field_settings = $settings;
		$fieldtype_model->save();

		ee('Alert')->makeInline('shared-form')
			->asSuccess()
			->withTitle(lang('settings_saved'))
			->addToBody(sprintf(lang('settings_saved_desc'), $fieldtype['name']))
			->defer();
	}

	/**
	 * Wraps the major version number in a <b> tag
	 *
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

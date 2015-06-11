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
		$status = ee('Filter')->make('filter_by_status', 'filter_by_status', array(
			'installed'   => strtolower(lang('installed')),
			'uninstalled' => strtolower(lang('uninstalled')),
			'updates'     => strtolower(lang('needs_updates'))
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

		$this->filters(count($addons));

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
						 && ! isset($info['update'])))
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
		$addons = array();

		$addons = $this->mergeAddonData($addons, $this->getExtensions());
		$addons = $this->mergeAddonData($addons, $this->getFieldtypes());
		$addons = $this->mergeAddonData($addons, $this->getPlugins());
		$addons = $this->mergeAddonData($addons, $this->getModules());

		return $addons;
	}

	/**
	 * Merges the data from one add-on array into another
	 *
	 * @param array $to   The addon array to merge into
	 * @param array $from The addon array to merge from
	 * @return array An associative array of add-on data
	 */
	private function mergeAddonData(array $to, array $from)
	{
		foreach ($from as $addon => $data)
		{
			if ( ! isset($to[$addon]))
			{
				$to[$addon] = $data;
			}
			else
			{
				foreach ($data as $key => $value)
				{
					if ( ! isset($to[$addon][$key])
						 OR $to[$addon][$key] != $value)
					{
						$to[$addon][$key] = $value;
					}
				}
			}
		}

		return $to;
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
			$module = $this->getModules($addon);
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

			$fieldtype = $this->getFieldtypes($addon);
			if ( ! empty($fieldtype)
				&& $fieldtype['installed'] === TRUE
				&& array_key_exists('update', $fieldtype))
			{
				$FT = ee()->api_channel_fields->setup_handler($addon, TRUE);
				if ($FT->update($fieldtype['version']) !== FALSE)
				{
					if (ee()->api_channel_fields->apply('update', array($fieldtype['version'])) !== FALSE)
					{
						// @TODO replace this with an ee('Model') implementation
						ee()->db->update('fieldtypes', array('version' => $FT->info['version']), array('name' => $addon));

						if ( ! isset($updated[$addon]))
						{
							$updated[$addon] = $fieldtype['name'];
						}
					}
				}
			}

			$extension = $this->getExtensions($addon);
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

			$plugin = $this->getPlugins($addon);
			if ( ! empty($plugin)
				&& $plugin['installed'] === TRUE
				&& array_key_exists('update', $plugin))
			{

				$info = $plugin['info'];

				$typography = 'n';
				if (array_key_exists('pi_typography', $info) && $info['pi_typography'] == TRUE)
				{
					$typography = 'y';
				}

				$model = ee('Model')->get('Plugin')
					->filter('plugin_package', $plugin['package'])
					->first();
				$model->plugin_name = $plugin['name'];
				$model->plugin_package = $plugin['package'];
				$model->plugin_version = $info['pi_version'];
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

			$extension = $this->getExtensions($addon);
			if ( ! empty($extension) && $extension['installed'] === FALSE)
			{
				$name = $this->installExtension($addon);
				if ($name && ! isset($installed[$addon]))
				{
					$installed[$addon] = $name;
				}
			}

			$plugin = $this->getPlugins($addon);
			if ( ! empty($plugin) && $plugin['installed'] === FALSE)
			{
				$info = $plugin['info'];

				$typography = 'n';
				if (array_key_exists('pi_typography', $info) && $info['pi_typography'] == TRUE)
				{
					$typography = 'y';
				}

				$model = ee('Model')->make('Plugin');
				$model->plugin_name = $plugin['name'];
				$model->plugin_package = $plugin['package'];
				$model->plugin_version = $info['pi_version'];
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
				if ($name && ! isset($uninstalled[$addon]))
				{
					$uninstalled[$addon] = $name;
				}
			}

			$extension = $this->getExtensions($addon);
			if ( ! empty($extension) && $extension['installed'] === TRUE)
			{
				$name = $this->uninstallExtension($addon);
				if ($name && ! isset($uninstalled[$addon]))
				{
					$uninstalled[$addon] = $name;
				}
			}

			$plugin = $this->getPlugins($addon);
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
		else
		{
			// Fieldtype
			$fieldtype = $this->getFieldtypes($addon);
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
				$extension = $this->getExtensions($addon);
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

		$plugin = $this->getPlugins($addon);
		if (empty($plugin))
		{
			show_error(lang('requested_module_not_installed').NBS.$addon);
		}

		$vars = array(
			'name'			=> $plugin['info']['pi_name'],
			'version'		=> $this->formatVersionNumber($plugin['info']['pi_version']),
			'author'		=> $plugin['info']['pi_author'],
			'author_url'	=> $plugin['info']['pi_author_url'],
			'description'	=> $plugin['info']['pi_description'],
		);

		$vars['usage'] = array(
			'description' => '',
			'example' => $plugin['info']['pi_usage']
		);

		if (is_array($plugin['info']['pi_usage']))
		{
			$vars['usage']['description'] = $plugin['info']['pi_usage']['description'];
			$vars['usage']['example'] = $plugin['info']['pi_usage']['example'];
			$vars['parameters'] = $plugin['info']['pi_usage']['parameters'];
		}

		ee()->view->cp_heading = $vars['name'] . ' ' . lang('manual');

		ee()->view->cp_breadcrumbs = array(
			cp_url('addons') => lang('addon_manager')
		);

		ee()->cp->render('addons/manual', $vars);
	}

	// --------------------------------------------------------------------

	/**
	 * Get a list of modules
	 *
	 * @param	str	$name	(optional) Limit the return to this add-on
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
	private function getModules($name = NULL)
	{
		$modules = array();
		$installed = ee()->addons->get_installed('modules', TRUE);

		foreach(ee()->addons->get_files() as $module => $info)
		{
			if ($name && $name != $module)
			{
				continue;
			}

			ee()->lang->loadfile($module);
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
					require_once $installed[$module]['path'].'upd.'.$module.'.php';
					$class = ucfirst($module).'_upd';

					ee()->load->add_package_path($installed[$module]['path']);

					$UPD = new $class;
					$UPD->_ee_path = APPPATH;
					if (version_compare($UPD->version, $installed[$module]['module_version'], '>')
						&& method_exists($UPD, 'update'))
					{
						$data['update'] = $UPD->version;
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
		ee()->load->model('addons_model');
		$plugins = array();

		foreach (ee()->addons_model->get_plugins($name) as $plugin => $info)
		{
			if ($name && $name != $plugin)
			{
				continue;
			}

			$developer = (strpos($info['installed_path'], PATH_ADDONS) === FALSE) ? 'native' : 'third_party';

			$data = array(
				'developer'		=> $developer,
				'version'		=> $info['pi_version'],
				'installed'		=> FALSE,
				'name'			=> $info['pi_name'],
				'package'		=> $plugin,
				'type'			=> 'plugin',
				'manual_url'	=> cp_url('addons/manual/' . $plugin),
				'info'			=> $info // Cache this
			);

			$model = ee('Model')->get('Plugin')
				->filter('plugin_package', $plugin)
				->first();
			if ( ! is_null($model))
			{
				$data['installed'] = TRUE;
				if (version_compare($info['pi_version'], $model->plugin_version, '>'))
				{
					$data['update'] = $info['pi_version'];
					$data['version'] = $model->plugin_version;
				}
			}

			if (is_null($name))
			{
				$plugins[$plugin] = $data;
			}
			elseif ($name == $plugin)
			{
				return $data;
			}
		}

		return $plugins;
	}

	// --------------------------------------------------------------------

	/**
	 * Get a list of fieldtypes
	 *
	 * @param	str	$name	(optional) Limit the return to this add-on
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
	private function getFieldtypes($name = NULL)
	{
		ee()->legacy_api->instantiate('channel_fields');

		$fieldtypes = array();
		$installed = ee()->addons->get_installed('fieldtypes', TRUE);

		foreach (ee()->api_channel_fields->fetch_all_fieldtypes() as $fieldtype => $info)
		{
			if ($name && $name != $fieldtype)
			{
				continue;
			}

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
				$data['version'] = $installed[$fieldtype]['version'];

				$FT = ee()->api_channel_fields->setup_handler($fieldtype, TRUE);

				if (version_compare($FT->info['version'], $installed[$fieldtype]['version'], '>'))
				{
					$data['update'] = $FT->info['version'];
				}

				if ($installed[$fieldtype]['has_global_settings'] == 'y')
				{
					$data['settings'] = unserialize(base64_decode($installed[$fieldtype]['settings']));
					$data['settings_url'] = cp_url('addons/settings/' . $fieldtype);
				}

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

	// --------------------------------------------------------------------

	/**
	 * Get a list of extensions
	 *
	 * @param	str	$name	(optional) Limit the return to this add-on
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
	private function getExtensions($name = NULL)
	{
		if (ee()->config->item('allow_extensions') != 'y')
		{
			return array();
		}

		ee()->load->model('addons_model');

		$extensions = array();

		$installed_ext_q = ee()->addons_model->get_installed_extensions(FALSE);
		foreach ($installed_ext_q->result_array() as $row)
		{
			// Check the meta data
			$installed[$row['class']] = $row;
		}
		$installed_ext_q->free_result();

		foreach(ee()->addons->get_files('extensions') as $ext_name => $ext)
		{
			if ($name && $name != $ext_name)
			{
				continue;
			}

			// Add the package path so things don't hork in the constructor
			ee()->load->add_package_path($ext['path']);

			// Include the file so we can grab its meta data
			$class_name = $ext['class'];

			if ( ! class_exists($class_name))
			{
				if (ee()->config->item('debug') == 2
					OR (ee()->config->item('debug') == 1
						AND ee()->session->userdata('group_id') == 1))
				{
					include($ext['path'].$ext['file']);
				}
				else
				{
					@include($ext['path'].$ext['file']);
				}

				if ( ! class_exists($class_name))
				{
					trigger_error(str_replace(array('%c', '%f'), array(htmlentities($class_name), htmlentities($ext['path'].$ext['file'])), lang('extension_class_does_not_exist')));
					unset($extension_files[$ext_name]);
					continue;
				}
			}

			// Get some details on the extension
			$Extension = new $class_name();

			$developer = (strpos($ext['path'], PATH_ADDONS) === FALSE) ? 'native' : 'third_party';

			$data = array(
				'developer'		=> $developer,
				'version'		=> $Extension->version,
				'installed'		=> FALSE,
				'enabled'		=> NULL,
				'name'			=> (isset($Extension->name)) ? $Extension->name : $ext['name'],
				'package'		=> $ext_name,
				'class'			=> $class_name,
			);

			if (isset($installed[$class_name]))
			{
				$data['version'] = $installed[$class_name]['version'];
				$data['installed'] = TRUE;
				$data['enabled'] = ($installed[$class_name]['enabled'] == 'y');

				if ($Extension->settings_exist == 'y')
				{
					$data['settings_url'] = cp_url('addons/settings/' . $ext_name);
				}

				if ($Extension->docs_url)
				{
					$data['manual_url'] = ee()->cp->masked_url($Extension->docs_url);
				}

				if (version_compare($Extension->version, $installed[$class_name]['version'], '>') && method_exists($Extension, 'update_extension') === TRUE)
				{
					$data['update'] = $Extension->version;
				}
			}

			if (is_null($name))
			{
				$extensions[$ext_name] = $data;
			}
			elseif ($name == $ext_name)
			{
				return $data;
			}
		}

		return $extensions;
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
		$extension = $this->getExtensions($addon);

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
		$extension = $this->getExtensions($addon);

		if (ee()->addons_installer->uninstall($addon, 'extension', FALSE))
		{
			$name = $extension['name'];
		}

		return $name;
	}

	// -------------------------------------------------------------------------

	/**
	 * Check for usage of the magpie plugin and get the plugin_info manually
	 *
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
			$data = $this->getFieldtypes($fieldtype);
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
			$data = $this->getFieldtypes($fieldtype);
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
		$installed = ee()->addons->get_installed('modules', TRUE);

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
		ee()->load->add_package_path($installed[$addon]['path']);

		require_once $installed[$addon]['path'].$installed[$addon]['file'];

		// instantiate the module cp class
		$mod = new $installed[$addon]['class'];
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
		ee()->load->remove_package_path($installed[$addon]['path']);

		return $_module_cp_body;
	}

	private function getExtensionSettings($name)
	{
		if (ee()->config->item('allow_extensions') != 'y')
		{
			show_error(lang('unauthorized_access'));
		}

		$addon = ee()->security->sanitize_filename(strtolower($name));

		$extension = $this->getExtensions($addon);

		if (empty($extension) || $extension['installed'] === FALSE)
		{
			show_error(lang('requested_module_not_installed').NBS.$addon);
		}

		ee()->lang->loadfile(strtolower($addon));

		$extension_model = ee('Model')->get('Extension')
			->filter('enabled', 'y')
			->filter('class', $extension['class'])
			->first();

		$current = strip_slashes(unserialize($extension_model->settings));

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

		$extension = $this->getExtensions($addon);

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

		$extension_model->settings = serialize($settings);
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
		$settings = base64_encode(serialize($settings));

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

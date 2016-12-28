<?php

namespace EllisLab\ExpressionEngine\Controller\Addons;

if ( ! defined('BASEPATH')) exit('No direct script access allowed');

use CP_Controller;
use Michelf\MarkdownExtra;
use EllisLab\ExpressionEngine\Library\CP\Table;

/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2016, EllisLab, Inc.
 * @license		https://expressionengine.com/license
 * @link		https://ellislab.com
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
 * @link		https://ellislab.com
 */
class Addons extends CP_Controller {

	var $perpage		= 25;
	var $params			= array();
	var $base_url;

	public $assigned_modules = array();

	/**
	 * Constructor
	 */
	public function __construct()
	{
		parent::__construct();

		ee('CP/Alert')->makeDeprecationNotice()->now();

		if ( ! ee()->cp->allowed_group('can_access_addons'))
		{
			// possible exception for FilePicker
			if (strncmp(ee()->uri->uri_string, 'cp/addons/settings/filepicker', 29) == 0)
			{
				if (! ee()->cp->allowed_group('can_access_files'))
				{
					show_error(lang('unauthorized_access'), 403);
				}
			}
			else
			{
				show_error(lang('unauthorized_access'), 403);
			}
		}

		ee()->lang->loadfile('addons');

		$this->params['perpage'] = $this->perpage; // Set a default

		// Add in any submitted search phrase
		ee()->view->search_value = htmlentities(ee()->input->get_post('search'), ENT_QUOTES, 'UTF-8');

		$this->base_url = ee('CP/URL')->make('addons');

		ee()->load->library('addons');
		ee()->load->helper(array('file', 'directory'));
		ee()->legacy_api->instantiate('channel_fields');

		$this->assigned_modules = ee('Model')->get('MemberGroup', ee()->session->userdata('group_id'))
			->first()
			->AssignedModules
			->pluck('module_id');

		// Make sure Filepicker is accessible for those who need it
		if (ee()->cp->allowed_group('can_access_files'))
		{
			$this->assigned_modules[] = ee('Model')->get('Module')->filter('module_name', 'Filepicker')->first()->getId();
		}
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
		// First Party Add-on Filters

		// Status
		$status = ee('CP/Filter')->make('filter_by_first_status', 'filter_by_status', array(
			'installed'   => strtolower(lang('installed')),
			'uninstalled' => strtolower(lang('uninstalled')),
			'updates'     => strtolower(lang('needs_updates'))
		));
		$status->disableCustomValue();

		$first_filters = ee('CP/Filter')
			->add($status);

		// Third Party Add-on Filters

		// Status
		$status = ee('CP/Filter')->make('filter_by_third_status', 'filter_by_status', array(
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
		$developer = ee('CP/Filter')->make('filter_by_developer', 'developer', $developer_options);
		$developer->disableCustomValue();

		$third_filters = ee('CP/Filter')
			->add($status)
			->add($developer);

		// When filtering the first party table keep the third party filter values
		$filter_base_url['first'] = clone $this->base_url;
		$filter_base_url['first']->addQueryStringVariables($third_filters->values());

		// Retain the third party page
		if (ee()->input->get('third_page'))
		{
			$filter_base_url['first']->setQueryStringVariable('third_page', ee()->input->get('third_page'));
		}

		// When filtering the third party table keep the first party filter values
		$filter_base_url['third'] = clone $this->base_url;
		$filter_base_url['third']->addQueryStringVariables($first_filters->values());

		// Retain the third party page
		if (ee()->input->get('first_page'))
		{
			$filter_base_url['third']->setQueryStringVariable('first_page', ee()->input->get('first_page'));
		}

		ee()->view->filters = array(
			'first' => $first_filters->render($filter_base_url['first']),
			'third' => $third_filters->render($filter_base_url['third'])
		);
		$this->params = array_merge($first_filters->values(), $third_filters->values());
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
		ee()->view->cp_heading = array(
			'first' => lang('addons'),
			'third' => lang('third_party_addons')
		);

		$vars = array(
			'tables' => array(
				'first' => NULL,
				'third' => NULL
			)
		);

		if ( ! empty(ee()->view->search_value))
		{
			$this->base_url->setQueryStringVariable('search', ee()->view->search_value);
		}

		$addons = $this->getAllAddons();

		// Filter list for non-super admins
		if (ee()->session->userdata('group_id') != 1)
		{
			$that = $this;
			$addons['first'] = array_filter($addons['first'], function($addon) use ($that)
			{
				return (isset($addon['module_id']) && in_array($addon['module_id'], $that->assigned_modules));
			});
			$addons['third'] = array_filter($addons['third'], function($addon) use ($that)
			{
				return (isset($addon['module_id']) && in_array($addon['module_id'], $that->assigned_modules));
			});
		}

		$developers = array_map(function($addon) { return $addon['developer']; }, $addons['third']);
		array_unique($developers);

		// Retain column sorting when filtering
		foreach (array('first', 'third') as $party)
		{
			$sort_col = $party . '_sort_col';
			if (ee()->input->get($sort_col))
			{
				$this->base_url->setQueryStringVariable($sort_col, ee()->input->get($sort_col));
			}

			$sort_dir = $party . '_sort_dir';
			if (ee()->input->get($sort_dir))
			{
				$this->base_url->setQueryStringVariable($sort_dir, ee()->input->get($sort_dir));
			}
		}

		$this->filters(array(
			'first' => count($addons['first']),
			'third' => count($addons['third'])
		), $developers);

		$return_url = ee('CP/URL')->getCurrentUrl();
		if (ee()->view->search_value)
		{
			$return_url->setQueryStringVariable('search', ee()->view->search_value);
		}

		foreach (array('first', 'third') as $party)
		{
			if ($party == 'third' && ! count($addons[$party]))
			{
				continue;
			}

			$data = array();

			// Setup the Table
			$config = array(
				'autosort' => TRUE,
				'autosearch' => TRUE,
				'sort_col' => ee()->input->get($party . '_sort_col') ?: NULL,
				'sort_col_qs_var' => $party . '_sort_col',
				'sort_dir' => ee()->input->get($party . '_sort_dir') ?: 'asc',
				'sort_dir_qs_var' => $party . '_sort_dir',
				'limit' => 0
			);

			$table = ee('CP/Table', $config);
			$columns =	array(
				'addon',
				'version' => array(
					'encode' => FALSE
				),
				'manage' => array(
					'type'	=> Table::COL_TOOLBAR
				)
			);


			if (ee()->cp->allowed_group('can_admin_addons'))
			{
				$columns[] = array(
					'type'	=> Table::COL_CHECKBOX
				);
			}

			$table->setColumns($columns);

			$table->setNoResultsText('no_addon_search_results');

			$this->base_url->setQueryStringVariable($party . '_page', $table->config['page']);

			foreach($addons[$party] as $addon => $info)
			{
				// Filter based on status
				$status_key = 'filter_by_' . $party . '_status';
				if (isset($this->params[$status_key]))
				{
					if ((strtolower($this->params[$status_key]) == 'installed'
						 && $info['installed'] == FALSE)
					     ||	(strtolower($this->params[$status_key]) == 'uninstalled'
							 && $info['installed'] == TRUE)
					     ||	(strtolower($this->params[$status_key]) == 'updates'
							 && (! isset($info['update'])
							     || $info['installed'] == FALSE)))
					{
						continue;
					}
				}

				// Filter based on developer
				if ($party == 'third'
					&& isset($this->params['filter_by_developer']))
				{
					if ($this->params['filter_by_developer'] != $this->makeDeveloperKey($info['developer']))
					{
						continue;
					}
				}

				$toolbar = array(
					'install' => array(
						'href' => '#',
						'data-post-url' => ee('CP/URL')->make(
							'addons/install/' . $info['package'],
							array(
								'return' => $return_url->encode()
							)
						),
						'title' => lang('install'),
						'content' => lang('install'),
						'type' => 'txt-only',
						'class' => 'add'
					)
				);

				$attrs = array('class' => 'not-installed');
				$addon_name = $info['name'];

				if ($info['installed'])
				{
					$toolbar = array();

					if (isset($info['settings_url']))
					{
						$toolbar['settings'] = array(
							'href' => $info['settings_url'],
							'title' => lang('settings'),
						);

						$addon_name = array(
							'content' => $addon_name,
							'href' => $info['settings_url']
						);
					}

					if (isset($info['manual_url']))
					{
						$toolbar['manual'] = array(
							'href' => $info['manual_url'],
							'title' => lang('manual'),
						);

						if ($info['manual_external'])
						{
							$toolbar['manual']['target'] = '_external';
						}
					}

					if (isset($info['update']))
					{
						$toolbar['txt-only'] = array(
							'href' => '#',
							'data-post-url' => ee('CP/URL')->make(
								'addons/update/' . $info['package'],
								array(
									'return' => $return_url->encode()
								)
							),
							'title' => strtolower(lang('update')),
							'class' => 'add',
							'content' => sprintf(lang('update_to_version'), $this->formatVersionNumber($info['update']))
						);
					}

					$attrs = array();
				}

				if ( ! ee()->cp->allowed_group('can_admin_addons'))
				{
					unset($toolbar['install']);
				}

				$row = array(
					'attrs' => $attrs,
					'columns' => array(
						'addon' => $addon_name,
						'version' => $this->formatVersionNumber($info['version']),
						array('toolbar_items' => $toolbar)
					)
				);

				if (ee()->cp->allowed_group('can_admin_addons'))
				{
					$row['columns'][] = array(
						'name' => 'selection[]',
						'value' => $info['package'],
						'data'	=> array(
							'confirm' => lang('addon') . ': <b>' . $info['name'] . '</b>'
						)
					);
				}

				$data[] = $row;
			}

			$table->setData($data);
			$vars['tables'][$party] = $table->viewData($this->base_url);
		}

		$vars['form_url'] = $this->base_url->setQueryStringVariable('return', $return_url->encode());

		// Set search results heading (first and third)
		if (ee()->input->get_post('search'))
		{
			ee()->view->cp_heading = array(
				'first' => sprintf(
					lang('search_results_heading'),
					$vars['tables']['first']['total_rows'],
					$vars['tables']['first']['search']
				),
				'third' => sprintf(
				lang('search_results_heading'),
				$vars['tables']['third']['total_rows'],
				$vars['tables']['third']['search']
			)
			);
		}

		$vars['header'] = array(
			'search_button_value' => lang('search_addons_button'),
			'title' => ee()->view->cp_page_title,
			'form_url' => $vars['form_url']
		);

		ee()->javascript->set_global('lang.remove_confirm', lang('addon') . ': <b>### ' . lang('addons') . '</b>');
		ee()->cp->add_js_script(array(
			'file' => array('cp/confirm_remove'),
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
		$addon_infos = ee('Addon')->all();

		$addons = array(
			'first' => array(),
			'third' => array()
		);

		foreach ($addon_infos as $name => $info)
		{
			$info = ee('Addon')->get($name);

			if ($info->get('built_in'))
			{
				continue;
			}

			$addon = $this->getExtension($name);
			$addon = array_merge($addon, $this->getFieldType($name));
			$addon = array_merge($addon, $this->getPlugin($name));
			$addon = array_merge($addon, $this->getModule($name));

			if ( ! empty($addon))
			{
				if (file_exists($info->getPath() . '/README.md'))
				{
					$addon['manual_url'] = ee('CP/URL')->make('addons/manual/' . $name);
					$addon['manual_external'] = FALSE;
				}
				elseif ($info->get('docs_url'))
				{
					$addon['manual_url'] = ee()->cp->masked_url($info->get('docs_url'));
					$addon['manual_external'] = TRUE;
				}

				$party = ($addon['developer'] == 'EllisLab') ? 'first' : 'third';
				$addons[$party][$name] = $addon;
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
		if ( ! ee()->cp->allowed_group('can_admin_addons') OR
			ee('Request')->method() !== 'POST')
		{
			show_error(lang('unauthorized_access'), 403);
		}

		if ( ! is_array($addons))
		{
			$addons = array($addons);
		}

		$updated = array(
			'first' => array(),
			'third' => array()
		);

		foreach ($addons as $addon)
		{
			$addon_info = ee('Addon')->get($addon);
			$party = ($addon_info->getAuthor() == 'EllisLab') ? 'first' : 'third';

			$module = $this->getModule($addon);
			if ( ! empty($module)
				&& $module['installed'] === TRUE
				&& array_key_exists('update', $module))
			{
				$installed = ee()->addons->get_installed('modules', TRUE);

				$class = $addon_info->getInstallerClass();
				$version = $installed[$addon]['module_version'];

				ee()->load->add_package_path($installed[$addon]['path']);

				$UPD = new $class;
				$UPD->_ee_path = APPPATH;

				$name = $module['name'];

				if ($UPD->update($version) !== FALSE)
				{
					$new_version = $addon_info->getVersion();
					if (version_compare($version, $new_version, '<'))
					{
						$module = ee('Model')->get('Module', $installed[$addon]['module_id'])
						->first();
						$module->module_version = $new_version;
						$module->save();

						$updated[$party][$addon] = $name;
					}
				}
			}

			$fieldtype = $this->getFieldtype($addon);
			if ( ! empty($fieldtype)
				&& $fieldtype['installed'] === TRUE
				&& array_key_exists('update', $fieldtype))
			{
				ee()->api_channel_fields->include_handler($addon);
				$FT = ee()->api_channel_fields->setup_handler($addon, TRUE);
				if (method_exists($FT, 'update') && $FT->update($fieldtype['version']) !== FALSE)
				{
					if (ee()->api_channel_fields->apply('update', array($fieldtype['version'])) !== FALSE)
					{
						$model = ee('Model')->get('Fieldtype')
							->filter('name', $addon)
							->first();

						$model->version = $addon_info->getVersion();
						$model->save();

						if ( ! isset($updated[$party][$addon]))
						{
							$updated[$party][$addon] = $fieldtype['name'];
						}
					}
				}
			}

			$extension = $this->getExtension($addon);
			if ( ! empty($extension)
				&& $extension['installed'] === TRUE
				&& array_key_exists('update', $extension))
			{
				$class = $addon_info->getExtensionClass();

				$class_name = $extension['class'];
				$Extension = new $class();
				$Extension->update_extension($extension['version']);
				ee()->extensions->version_numbers[$class_name] = $addon_info->getVersion();

				$model = ee('Model')->get('Extension')
					->filter('class', $class_name)
					->all();

				$model->version = $addon_info->getVersion();
				$model->save();

				if ( ! isset($updated[$party][$addon]))
				{
					$updated[$party][$addon] = $extension['name'];
				}
			}

			$plugin = $this->getPlugin($addon);
			if ( ! empty($plugin)
				&& $plugin['installed'] === TRUE
				&& array_key_exists('update', $plugin))
			{
				$typography = 'n';

				if ($addon_info->get('plugin.typography'))
				{
					$typography = 'y';
				}

				$model = ee('Model')->get('Plugin')
					->filter('plugin_package', $plugin['package'])
					->first();

				$model->plugin_name = $plugin['name'];
				$model->plugin_package = $plugin['package'];
				$model->plugin_version = $addon_info->getVersion();
				$model->is_typography_related = $typography;
				$model->save();

				if ( ! isset($updated[$party][$addon]))
				{
					$updated[$party][$addon] = $plugin['name'];
				}
			}
		}

		foreach (array('first', 'third') as $party)
		{
			if ( ! empty($updated[$party]))
			{
				$alert = ee('CP/Alert')->makeInline($party . '-party')
					->asSuccess()
					->withTitle(lang('addons_updated'))
					->addToBody(lang('addons_updated_desc'))
					->addToBody(array_values($updated[$party]))
					->defer();
			}
		}

		$return = $this->base_url;

		if (ee()->input->get('return'))
		{
			$return = ee('CP/URL')->decodeUrl(ee()->input->get('return'));
		}

		ee()->functions->redirect($return);
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
		if ( ! ee()->cp->allowed_group('can_admin_addons') OR
			ee('Request')->method() !== 'POST')
		{
			show_error(lang('unauthorized_access'), 403);
		}

		if ( ! is_array($addons))
		{
			$addons = array($addons);
		}

		ee()->load->library('addons/addons_installer');

		$installed = array(
			'first' => array(),
			'third' => array()
		);

		foreach ($addons as $addon)
		{
			$info = ee('Addon')->get($addon);
			ee()->load->add_package_path($info->getPath());

			$party = ($info->getAuthor() == 'EllisLab') ? 'first' : 'third';

			$module = $this->getModule($addon);
			if ( ! empty($module) && $module['installed'] === FALSE)
			{
				$name = $this->installModule($addon);
				if ($name)
				{
					$installed[$party][$addon] = $name;
				}
			}

			$fieldtype = $this->getFieldtype($addon);
			if ( ! empty($fieldtype) && $fieldtype['installed'] === FALSE)
			{
				$name = $this->installFieldtype($addon);
				if ($name && ! isset($installed[$addon]))
				{
					$installed[$party][$addon] = $name;
				}
			}

			$extension = $this->getExtension($addon);
			if ( ! empty($extension) && $extension['installed'] === FALSE)
			{
				$name = $this->installExtension($addon);
				if ($name && ! isset($installed[$addon]))
				{
					$installed[$party][$addon] = $name;
				}
			}

			$plugin = $this->getPlugin($addon);
			if ( ! empty($plugin) && $plugin['installed'] === FALSE)
			{
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
					$installed[$party][$addon] = $plugin['name'];
				}
			}

			ee()->load->remove_package_path($info->getPath());
		}

		foreach (array('first', 'third') as $party)
		{
			if ( ! empty($installed[$party]))
			{
				$alert = ee('CP/Alert')->makeInline($party . '-party')
					->asSuccess()
					->withTitle(lang('addons_installed'))
					->addToBody(lang('addons_installed_desc'))
					->addToBody(array_values($installed[$party]))
					->defer();
			}
		}

		$return = $this->base_url;

		if (ee()->input->get('return'))
		{
			$return = ee('CP/URL')->decodeUrl(ee()->input->get('return'));
		}

		ee()->functions->redirect($return);
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
		if ( ! ee()->cp->allowed_group('can_admin_addons'))
		{
			show_error(lang('unauthorized_access'), 403);
		}

		if ( ! is_array($addons))
		{
			$addons = array($addons);
		}

		ee()->load->library('addons/addons_installer');

		$uninstalled = array(
			'first' => array(),
			'third' => array()
		);

		foreach ($addons as $addon)
		{
			$info = ee('Addon')->get($addon);
			$party = ($info->getAuthor() == 'EllisLab') ? 'first' : 'third';

			$module = $this->getModule($addon);
			if ( ! empty($module) && $module['installed'] === TRUE)
			{
				$name = $this->uninstallModule($addon);
				if ($name)
				{
					$uninstalled[$party][$addon] = $name;
				}
			}

			$fieldtype = $this->getFieldtype($addon);
			if ( ! empty($fieldtype) && $fieldtype['installed'] === TRUE)
			{
				$name = $this->uninstallFieldtype($addon);
				if ($name && ! isset($uninstalled[$party][$addon]))
				{
					$uninstalled[$party][$addon] = $name;
				}
			}

			$extension = $this->getExtension($addon);
			if ( ! empty($extension) && $extension['installed'] === TRUE)
			{
				$name = $this->uninstallExtension($addon);
				if ($name && ! isset($uninstalled[$party][$addon]))
				{
					$uninstalled[$party][$addon] = $name;
				}
			}

			$plugin = $this->getPlugin($addon);
			if ( ! empty($plugin) && $plugin['installed'] === TRUE)
			{
				ee('Model')->get('Plugin')
					->filter('plugin_package', $addon)
					->delete();

				if ( ! isset($uninstalled[$party][$addon]))
				{
					$uninstalled[$party][$addon] = $plugin['name'];
				}
			}
		}

		foreach (array('first', 'third') as $party)
		{
			if ( ! empty($uninstalled[$party]))
			{
				$alert = ee('CP/Alert')->makeInline($party . '-party')
					->asSuccess()
					->withTitle(lang('addons_uninstalled'))
					->addToBody(lang('addons_uninstalled_desc'))
					->addToBody(array_values($uninstalled[$party]))
					->defer();
			}
		}

		$return = $this->base_url;

		if (ee()->input->get('return'))
		{
			$return = ee('CP/URL')->decodeUrl(ee()->input->get('return'));
		}

		ee()->functions->redirect($return);
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
		$this->assertUserHasAccess($addon);

		ee()->view->cp_page_title = lang('addon_manager');

		$vars = array();
		$breadcrumb = array(
			ee('CP/URL')->make('addons')->compile() => lang('addon_manager')
		);

		if (is_null($method))
		{
			$method = (ee()->input->get_post('method') !== FALSE) ? ee()->input->get_post('method') : 'index';
		}

		// Module
		$module = $this->getModule($addon);
		if ( ! empty($module) && $module['installed'] === TRUE)
		{
			$data = $this->getModuleSettings($addon, $method, array_slice(func_get_args(), 2));

			$addon_header = (isset(ee()->cp->header)) ? ee()->cp->header : ee()->view->header;
			$header = array('title' => $module['name']);

			if (isset($addon_header['toolbar_items']))
			{
				$header['toolbar_items'] = $addon_header['toolbar_items'];
			}

			ee()->view->header = $header;
			ee()->view->cp_heading = $module['name'] . ' ' . lang('configuration');

			if (is_array($data))
			{
				if (isset($data['ajax']) && $data['ajax'])
				{
					return $data['body'];
				}

				$vars['_module_cp_body'] = $data['body'];

				if (isset($data['heading']))
				{
					ee()->view->cp_heading = $data['heading'];
				}

				if (isset($data['breadcrumb']))
				{
					$breadcrumb = array_merge($breadcrumb, $data['breadcrumb']);
				}
			}
			else
			{
				$vars['_module_cp_body'] = $data;
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
					ee()->functions->redirect(ee('CP/URL')->make('addons/settings/' . $addon));
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
						ee()->functions->redirect(ee('CP/URL')->make('addons/settings/' . $addon));
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
		ee()->view->cp_page_title = ee()->view->cp_heading;

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
		$this->assertUserHasAccess($addon);

		try
		{
			$info = ee('Addon')->get($addon);
		}
		catch (\Exception $e)
		{
			show_error(lang('requested_module_not_installed').NBS.$addon);
		}

		$readme_file = $info->getPath() . '/README.md';

		if ( ! file_exists($readme_file))
		{
			show_404();
		}

		ee()->view->cp_page_title = $info->getName() . ' ' . lang('manual');

		$vars = array(
			'name'        => $info->getName(),
			'version'     => $this->formatVersionNumber($info->getVersion()),
			'author'      => $info->getAuthor(),
			'author_url'  => ee()->cp->masked_url($info->get('author_url')),
			'docs_url'    => ee()->cp->masked_url($info->get('docs_url')),
			'description' => $info->get('description')
		);

		// Some pre-processing:
		//   1. Remove any #'s at the start of the doc, since that will be redundant with the add-on info

		$readme = preg_replace('/^\s*#.*?\n/s', '', file_get_contents($readme_file));

		$parser = new MarkdownExtra;
		$parser->url_filter_func = function ($url) {
		    return ee()->cp->masked_url($url);
		};
		$readme = $parser->transform($readme);

		// Some post-processing
		//   1. Step headers back (h2 becomes h1, h3 becomes, h2, etc.)
		//   2. Change codeblocks to textareas
		//   3. Add <mark> around h4's (params and variables)
		//   4. Pull out header tree for sidebar nav (h1 and h2 only)

		for ($i = 2, $j = 1; $i <=6; $i++, $j++)
		{
			$readme = str_replace(array("<h{$i}>", "</h{$i}>"), array("<h{$j}>", "</h{$j}>"), $readme);
		}

		$pre_tags = array('<pre><code>', '</code></pre>', '<h4>', '</h4>');
		$post_tags = array('<textarea>', '</textarea>', '<h4><mark>', '</mark></h4>');

		$readme = str_replace($pre_tags, $post_tags, $readme);

		// [
		// 	[0] => <h1>full tag</h1>
		// 	[1] => 1
		// 	[2] => full tag
		// ]
		preg_match_all('|<h([12])>(.*?)</h\\1>|', $readme, $matches, PREG_SET_ORDER);

		$nav = array();
		$child = array();
		foreach($matches as $key => $match)
		{
			// give 'em id's so they are linkable
			$new_header = "<h{$match[1]} id=\"ref{$key}\">{$match[2]}</h{$match[1]}>";

			// just in case they use the same name in multiple headers, we need to id separately
			// hence preg_replace() with a limit instead of str_replace()
			$readme = preg_replace('/'.preg_quote($match[0], '/').'/', $new_header, $readme, 1);

			if ($match[1] == 1)
			{
				// append any children (h2's) if they exist
				if ( ! empty($child))
				{
					$nav[] = $child;
					$child = array();
				}

				$nav[strip_tags($match[2])] = "#ref{$key}";
			}
			else
			{
				// save the children for later. SAVE THE CHILDREN!
				$child[strip_tags($match[2])] = "#ref{$key}";
			}
		}

		// don't forget the youngest!
		if ( ! empty($child))
		{
			$nav[] = $child;
		}

		// Register our menu and header
		ee()->menu->register_left_nav($nav);
		ee()->view->header = array(
			'title' => lang('addon_manager'),
			'form_url' => ee('CP/URL')->make('addons'),
			'search_button_value' => lang('search_addons_button')
		);

		$vars['readme'] = $readme;

		ee()->view->cp_heading = $vars['name'] . ' ' . lang('manual');

		ee()->view->cp_breadcrumbs = array(
			ee('CP/URL')->make('addons')->compile() => lang('addon_manager')
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
			$info = ee('Addon')->get($name);
		}
		catch (\Exception $e)
		{
			show_404();
		}

		if ( ! $info->hasModule())
		{
			return array();
		}

		// Use lang file if present, otherwise fallback to addon.setup
		ee()->lang->loadfile($name, '', FALSE);
		$display_name = (lang(strtolower($name).'_module_name') != strtolower($name).'_module_name')
			? lang(strtolower($name).'_module_name') : $info->getName();

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
			$data['module_id'] = $module->module_id;
			$data['installed'] = TRUE;
			$data['version'] = $module->module_version;

			if ($info->get('settings_exist'))
			{
				$data['settings_url'] = ee('CP/URL')->make('addons/settings/' . $name);
			}

			if ($info->hasInstaller())
			{
				$class = $info->getInstallerClass();

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
			$info = ee('Addon')->get($name);
		}
		catch (\Exception $e)
		{
			show_404();
		}

		if ( ! $info->hasPlugin())
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
		);

		$model = ee('Model')->get('Plugin')
			->filter('plugin_package', $name)
			->first();

		if ( ! is_null($model))
		{
			$data['installed'] = TRUE;
			$data['version'] = $model->plugin_version;
			if (version_compare($info->getVersion(), $model->plugin_version, '>'))
			{
				$data['update'] = $info->getVersion();
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
			$info = ee('Addon')->get($name);
		}
		catch (\Exception $e)
		{
			show_404();
		}

		if ( ! $info->hasFieldtype())
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
				$data['settings_url'] = ee('CP/URL')->make('addons/settings/' . $name);
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
			$info = ee('Addon')->get($name);
		}
		catch (\Exception $e)
		{
			show_404();
		}

		if ( ! $info->hasExtension())
		{
			return array();
		}

		$class_name = ucfirst($name) . '_ext';

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
					return array();
				}
			}

			// Get some details on the extension
			$ext_obj = new $class_name($extension->settings);
			if (version_compare($info->getVersion(), $extension->version, '>')
				&& method_exists($ext_obj, 'update_extension') === TRUE)
			{
				$data['update'] = $info->getVersion();
			}

			if ($info->get('settings_exist'))
			{
				$data['settings_url'] = ee('CP/URL')->make('addons/settings/' . $name);
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
		ee()->lang->loadfile($module, '', FALSE);

		if (ee()->addons_installer->install($module, 'module', FALSE))
		{
			try
			{
				$info = ee('Addon')->get($module);
			}
			catch (\Exception $e)
			{
				show_404();
			}

			$name = (lang(strtolower($module).'_module_name') != strtolower($module).'_module_name')
				? lang(strtolower($module).'_module_name') : $info->getName();
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
		ee()->lang->loadfile($module, '', FALSE);

		if (ee()->addons_installer->uninstall($module, 'module', FALSE))
		{
			try
			{
				$info = ee('Addon')->get($module);
			}
			catch (\Exception $e)
			{
				show_404();
			}

			$name = (lang(strtolower($module).'_module_name') != strtolower($module).'_module_name')
				? lang(strtolower($module).'_module_name') : $info->getName();
		}

		return $name;
	}

	// --------------------------------------------------------------------

	/**
	 * Installs a fieldtype
	 *
	 * @param  str	$fieldtype	The add-on to install
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
	private function getModuleSettings($name, $method = "index", $parameters)
	{
		$addon = ee()->security->sanitize_filename(strtolower($name));

		$info = ee('Addon')->get($name);

		$module = ee('Model')->get('Module')
			->filter('module_name', $name)
			->first();

		if (ee()->session->userdata['group_id'] != 1)
		{
			// Do they have access to this module?
			if ( ! isset($module))
			{
				show_error(lang('unauthorized_access'), 403);
			}

			$this->assertUserHasAccess($addon);
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

		// instantiate the module cp class
		$class = $info->getControlPanelClass();
		$mod = new $class;
		$mod->_ee_path = APPPATH;

		// add validation callback support to the mcp class (see EE_form_validation for more info)
		ee()->set('_mcp_reference', $mod);

		// its possible that a module will try to call a method that does not exist
		// either by accident (ie: a missed function) or by deliberate user url hacking
		if ( ! method_exists($mod, $method))
		{
			// 3.0 introduced camel-cased method names that are translated from a URL
			// segment separated by dashes or underscores
			$method = str_replace('-', '_', $method);
			$words = explode('_', $method);
			$method = strtolower(array_shift($words));
			$words = array_map('ucfirst', $words);
			$method .= implode('', $words);

			if ( ! method_exists($mod, $method))
			{
				show_404();
			}
		}

		$_module_cp_body = call_user_func_array(array($mod, $method), $parameters);

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
			show_error(lang('unauthorized_access'), 403);
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
		$OBJ = new $class_name($current);

		if (method_exists($OBJ, 'settings_form') === TRUE)
		{
			return $OBJ->settings_form($current);
		}

		$vars = array(
			'base_url' => ee('CP/URL')->make('addons/settings/' . $name . '/save'),
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
						'type' => 'select',
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
						'kill_pipes' => isset($options['1']['kill_pipes']) ? $options['1']['kill_pipes'] : FALSE
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

		return ee('View')->make('_shared/form_with_box')->render($vars);
	}

	private function saveExtensionSettings($name)
	{
		if (ee()->config->item('allow_extensions') != 'y')
		{
			show_error(lang('unauthorized_access'), 403);
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
			->all();

		$extension_model->settings = $settings;
		$extension_model->save();

		ee('CP/Alert')->makeInline('shared-form')
			->asSuccess()
			->withTitle(lang('settings_saved'))
			->addToBody(sprintf(lang('settings_saved_desc'), $extension['name']))
			->defer();
	}

	private function getFieldtypeSettings($fieldtype)
	{
		if ( ! ee()->cp->allowed_group('can_access_addons'))
		{
			show_error(lang('unauthorized_access'), 403);
		}

		ee()->api_channel_fields->fetch_installed_fieldtypes();
		$FT = ee()->api_channel_fields->setup_handler($fieldtype['package'], TRUE);

		$FT->settings = $fieldtype['settings'];

		$fieldtype_settings = ee()->api_channel_fields->apply('display_global_settings');

		if (is_array($fieldtype_settings))
		{
			$vars = array(
				'base_url' => ee('CP/URL')->make('addons/settings/' . $fieldtype['package'] . '/save'),
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
			$html .= form_open(ee('CP/URL')->make('addons/settings/' . $fieldtype['package'] . '/save'), 'class="settings"');
			$html .= ee('CP/Alert')->get('shared-form');
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
		if ( ! ee()->cp->allowed_group('can_access_addons'))
		{
			show_error(lang('unauthorized_access'), 403);
		}

		ee()->api_channel_fields->fetch_installed_fieldtypes();
		$FT = ee()->api_channel_fields->setup_handler($fieldtype['package'], TRUE);

		$FT->settings = $fieldtype['settings'];

		$settings = ee()->api_channel_fields->apply('save_global_settings');

		$fieldtype_model = ee('Model')->get('Fieldtype')
			->filter('name', $fieldtype['package'])
			->first();

		$fieldtype_model->settings = $settings;
		$fieldtype_model->save();

		ee('CP/Alert')->makeInline('shared-form')
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

	private function assertUserHasAccess($addon)
	{
		if (ee()->session->userdata('group_id') == 1)
		{
			return;
		}

		$module = $this->getModule($addon);

 		if ( ! isset($module['module_id'])
			|| ! in_array($module['module_id'], $this->assigned_modules))
		{
			show_error(lang('unauthorized_access'), 403);
		}
	}
}

// EOF

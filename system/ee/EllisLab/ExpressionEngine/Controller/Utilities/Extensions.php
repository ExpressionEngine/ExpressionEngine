<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2019, EllisLab Corp. (https://ellislab.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace EllisLab\ExpressionEngine\Controller\Utilities;

use CP_Controller;
use EllisLab\ExpressionEngine\Library\CP\Table;

/**
 *Extensions Controller
 */
class Extensions extends Utilities {

	var $perpage		= 25;
	var $params			= array();
	var $base_url;

	/**
	 * Constructor
	 */
	public function __construct()
	{
		parent::__construct();

		if ( ! ee()->cp->allowed_group('can_access_addons')
			OR ! ee()->cp->allowed_group('can_admin_addons'))
		{
			show_error(lang('unauthorized_access'), 403);
		}

		ee()->lang->loadfile('addons');

		$this->params['perpage'] = $this->perpage; // Set a default

		$this->base_url = ee('CP/URL')->make('utilities/extensions');

		ee()->load->library('addons');
		ee()->load->helper(array('file', 'directory'));
	}

	/**
	 * Index function
	 *
	 * @return	void
	 */
	public function index()
	{
		if (ee()->config->item('allow_extensions') == 'n') {
			ee('CP/Alert')->makeInline('extensions')
				->asWarning()
				->withTitle(lang('extensions_disabled'))
				->addToBody(lang('extensions_disabled_message'))
				->now();
		}

		if (ee()->input->post('bulk_action') == 'enable')
		{
			$this->enable(ee()->input->post('selection'));
		}
		elseif (ee()->input->post('bulk_action') == 'disable')
		{
			$this->disable(ee()->input->post('selection'));
		}

		ee()->view->cp_page_title = lang('manage_extensions');
		ee()->view->cp_heading = lang('manage_addon_extensions');

		$vars = array();

		$data = array();

		foreach($this->getExtensions() as $addon => $info)
		{
			$toolbar = array();

			if ($info['installed'])
			{
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
						'rel' => 'external'
					);
				}

				$attrs = array();
			}

			switch ($info['enabled'])
			{
				case TRUE: $status = array('class' => 'enable', 'content' => lang('enabled')); break;
				case FALSE: $status = array('class' => 'disable', 'content' => lang('disabled')); break;
			}

			$data[] = array(
				'attrs' => $attrs,
				'columns' => array(
					'name' => $info['name'] . '(' . $info['version'] . ')',
					'status' => $status,
					array('toolbar_items' => $toolbar),
					array(
						'name' => 'selection[]',
						'value' => $info['package']
					)
				)
			);
		}

		$table = ee('CP/Table', array('autosort' => TRUE, 'autosearch' => TRUE, 'limit' => $this->params['perpage']));
		$table->setColumns(
			array(
				'name',
				'status' => array(
					'type'	=> Table::COL_STATUS
				),
				'manage' => array(
					'type'	=> Table::COL_TOOLBAR
				),
				array(
					'type'	=> Table::COL_CHECKBOX
				)
			)
		);
		$table->setNoResultsText('no_addon_extensions_search_results');
		$table->setData($data);

		$vars['table'] = $table->viewData($this->base_url);
		$vars['form_url'] = $vars['table']['base_url'];

		if ( ! empty($vars['table']['data']))
		{
			// Paginate!
			$vars['pagination'] = ee('CP/Pagination', $vars['table']['total_rows'])
				->perPage($vars['table']['limit'])
				->currentPage($vars['table']['page'])
				->render($vars['table']['base_url']);
		}

		// Set search results heading
		if ( ! empty($vars['table']['search']))
		{
			ee()->view->cp_heading = sprintf(
				lang('search_results_heading'),
				$vars['table']['total_rows'],
				htmlspecialchars($vars['table']['search'], ENT_QUOTES, 'UTF-8')
			);
		}

		ee()->cp->render('utilities/extensions', $vars);
	}

	/**
	 * Enable an add-on
	 *
	 * @param	str|array	$addons	The name(s) of add-ons to install
	 * @return	void
	 */
	private function enable($addons)
	{
		if ( ! is_array($addons))
		{
			$addons = array($addons);
		}

		$enabled = array();

		foreach ($addons as $addon)
		{
			$extension = $this->getExtensions($addon);

			// @TODO use this code once the models are ready
			// ee('Model')->get('Extension')
			// 	->filter('class', $extension['class'])
			// 	->set('enabled', TRUE)
			// 	->update();
			// Get the list of hooks and the existing state
			$hooks = ee()->db->select('extension_id, enabled')
				->where('class', $extension['class'])
				->get('extensions')
				->result_array();

			foreach ($hooks as $index => $data)
			{
				$hooks[$index]['enabled'] = 'y';
			}
			ee()->db->update_batch('extensions', $hooks, 'extension_id');

			$enabled[$addon] = $extension['name'];
		}

		if ( ! empty($installed))
		{
			ee()->view->set_message('success', lang('extensions_enabled'), lang('extensions_enabled_desc') . implode(', ', $enabled));
		}
	}

	/**
	 * Disable an add-on
	 *
	 * @param	str|array	$addons	The name(s) of add-ons to install
	 * @return	void
	 */
	private function disable($addons)
	{
		if ( ! is_array($addons))
		{
			$addons = array($addons);
		}

		$disabled = array();

		foreach ($addons as $addon)
		{
			$extension = $this->getExtensions($addon);

			// @TODO use this code once the models are ready
			// ee('Model')->get('Extension')
			// 	->filter('class', $extension['class'])
			// 	->set('enabled', FALSE)
			// 	->update();
			// Get the list of hooks and the existing state
			$hooks = ee()->db->select('extension_id, enabled')
				->where('class', $extension['class'])
				->get('extensions')
				->result_array();

			foreach ($hooks as $index => $data)
			{
				$hooks[$index]['enabled'] = 'n';
			}
			ee()->db->update_batch('extensions', $hooks, 'extension_id');

			$disabled[$addon] = $extension['name'];
		}

		if ( ! empty($installed))
		{
			ee()->view->set_message('success', lang('extensions_disabled'), lang('extensions_disabled_desc') . implode(', ', $disabled));
		}
	}

	/**
	 * Get a list of extensions
	 *
	 * @param	str	$name	(optional) Limit the return to this add-on
	 * @return	array		Add-on data in the following format:
	 *   e.g. 'version'		 => '--',
	 *        'installed'	 => TRUE|FALSE,
	 *        'name'		 => 'FooBar',
	 *        'package'		 => 'foobar',
	 *        'class'        => 'Foobar_ext',
	 *        'enabled'		 => NULL|TRUE|FALSE
	 *        'manual_url'	 => '' (optional),
	 *        'settings_url' => '' (optional)
	 *
	 * @todo This isn't DRY. See the addons controller. :( -scb
	 */
	private function getExtensions($extension_name = NULL)
	{
		$extensions = array();

		if (ee()->config->item('allow_extensions') != 'y')
		{
			return $extensions;
		}

		$providers = ee('App')->getProviders();
		// Remove non-add-on providers from the list
		unset($providers['ee'], $providers['channel'], $providers['comment']);

		foreach (array_keys($providers) as $name)
		{
			try
			{
				$info = ee('App')->get($name);
			}
			catch (\Exception $e)
			{
				continue;
			}

			if ( ! file_exists($info->getPath() . '/ext.' . $name . '.php'))
			{
				continue;
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

			if ( ! $extension)
			{
				continue;
			}

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

				// Get some details on the extension
				$Extension = new $class_name();
				if (version_compare($info->getVersion(), $extension->version, '>')
					&& method_exists($Extension, 'update_extension') === TRUE)
				{
					$data['update'] = $info->getVersion();
				}

				if ($info->get('settings_exist'))
				{
					$data['settings_url'] = ee('CP/URL')->make('addons/settings/' . $name);
				}

				if ($info->get('docs_url'))
				{
					$data['manual_url'] = ee()->cp->masked_url($info->get('docs_url'));
				}
			}
			if (is_null($extension_name))
			{
				$extensions[$name] = $data;
			}
			elseif ($extension_name == $name)
			{
				return $data;
			}
		}

		return $extensions;
	}

}

// EOF

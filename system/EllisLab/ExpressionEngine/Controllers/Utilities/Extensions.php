<?php

namespace EllisLab\ExpressionEngine\Controllers\Utilities;

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
 * ExpressionEngine Extensions CP Controller Class
 *
 * @package		ExpressionEngine
 * @subpackage	Control Panel
 * @category	Control Panel
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */
class Extensions extends Utilities {

	var $perpage		= 20;
	var $params			= array();
	var $base_url;

	/**
	 * Constructor
	 */
	public function __construct()
	{
		parent::__construct();

		if ( ! ee()->cp->allowed_group('can_access_addons', 'can_access_extensions'))
		{
			show_error(lang('unauthorized_access'));
		}

		ee()->lang->loadfile('addons');

		$this->params['perpage'] = $this->perpage; // Set a default

		// Add in any submitted search phrase
		ee()->view->search_value = ee()->input->get_post('search');

		$this->base_url = new URL('addons/extensions', ee()->session->session_id());

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
		if (ee()->input->post('bulk_action') == 'enable')
		{
			return $this->enable(ee()->input->post('selection'));
		}
		elseif (ee()->input->post('bulk_action') == 'disable')
		{
			return $this->disable(ee()->input->post('selection'));
		}

		ee()->view->cp_page_title = lang('addon_manager');
		ee()->view->cp_heading = lang('manage_addon_extensions');

		$vars = array();

		if ( ! empty(ee()->view->search_value))
		{
			$this->base_url->setQueryStringVariable('search', ee()->view->search_value);
		}

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
					);
				}

				$attrs = array();
			}

			switch ($info['enabled'])
			{
				case TRUE: $status = lang('enabled'); break;
				case FALSE: $status = lang('disabled'); break;
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

		$table = Table::create(array('autosort' => TRUE, 'autosearch' => TRUE, 'limit' => $this->params['perpage']));
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

		ee()->cp->render('utilities/extensions', $vars);
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
			// We only want installed extensions here; you cannot disable an
			// uninstalled extension
			if ( ! isset($installed[$ext['class']]))
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

			$data = array(
				'version'		=> $installed[$class_name]['version'],
				'installed'		=> TRUE,
				'enabled'		=> ($installed[$class_name]['enabled'] == 'y'),
				'name'			=> (isset($Extension->name)) ? $Extension->name : $ext['name'],
				'package'		=> $ext_name,
			);

			if ($Extension->settings_exist == 'y')
			{
				$data['settings_url'] = cp_url('addons/settings/' . $ext_name);
			}

			if ($Extension->docs_url)
			{
				$data['manual_url'] = ee()->config->item('base_url') . ee()->config->item('index_page') . '?URL=' . urlencode($Extension->docs_url);
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

}
// EOF
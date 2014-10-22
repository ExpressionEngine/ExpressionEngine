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
class Extensions extends CP_Controller {

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

		// Sidebar Menu
		$menu = array(
			'all_addons' 		=> cp_url('addons'),
			'manage_extensions'	=> cp_url('addons/extensions')
		);

		ee()->menu->register_left_nav($menu);

		$this->params['perpage'] = $this->perpage; // Set a default

		// Add in any submitted search phrase
		ee()->view->search_value = ee()->input->get_post('search');

		$this->base_url = new URL('addons/extensions', ee()->session->session_id());

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
		if (ee()->input->post('bulk_action') == 'enable')
		{
			return $this->enable(ee()->input->post('selection'));
		}
		elseif (ee()->input->post('bulk_action') == 'disable')
		{
			return $this->disable(ee()->input->post('selection'));
		}
		elseif (ee()->input->post('bulk_action') == 'remove')
		{
			return $this->remove(ee()->input->post('selection'));
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
			$toolbar = array(
				'install' => array(
					'href' => cp_url('addons/extensions/install/' . $info['package']),
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

				$attrs = array();
			}

			switch ($info['enabled'])
			{
				case NULL: $status = lang('uninstalled'); break;
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

		ee()->cp->render('addons/extensions', $vars);
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
			ee()->view->set_message('success', lang('addons_installed'), lang('addons_installed_desc') . implode(', ', $installed), TRUE);
		}
		ee()->functions->redirect(cp_url('addons'));
	}

	// --------------------------------------------------------------------

	/**
	 * Uninstalls an add-on
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
	 * Get a list of extensions
	 *
	 * @access	private
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
				'version'		=> $Extension->version,
				'installed'		=> FALSE,
				'enabled'		=> NULL,
				'name'			=> (isset($Extension->name)) ? $Extension->name : $ext['name'],
				'package'		=> $ext_name,
			);

			if (isset($installed[$ext_name]))
			{
				$data['version'] = $installed[$ext_name]['version'];
				$data['installed'] = TRUE;
				$data['enabled'] = ($installed[$ext_name]['enabled'] == 'y');

				if ($Extension->settings_exist == 'y')
				{
					$data['settings_url'] = cp_url('addons/extensions/settings/' . $ext_name);
				}

				if ($Extension->docs_url)
				{
					$data['manual_url'] = anchor(ee()->config->item('base_url').ee()->config->item('index_page').'?URL='.urlencode($Extension->docs_url), lang('documentation'));
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
	 * Installs a module
	 *
	 * @access private
	 * @param  str	$module	The add-on to install
	 * @return str			The name of the add-on just installed
	 */
	private function installExtension($module)
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
	private function uninstallExtension($module)
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
}
// END CLASS

/* End of file Extensions.php */
/* Location: ./system/expressionengine/controllers/cp/Addons/Extensions.php */
<?php

namespace EllisLab\ExpressionEngine\Controller\Design;

use CP_Controller;
use ZipArchive;
use EllisLab\ExpressionEngine\Library\CP\Table;
use EllisLab\ExpressionEngine\Library\Data\Collection;
use EllisLab\ExpressionEngine\Model\Template\TemplateRoute;

/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2015, EllisLab, Inc.
 * @license		https://ellislab.com/expressionengine/user-guide/license.html
 * @link		http://ellislab.com
 * @since		Version 3.0
 * @filesource
 */

// ------------------------------------------------------------------------

/**
 * ExpressionEngine CP Abstract Design Class
 *
 * @package		ExpressionEngine
 * @subpackage	Control Panel
 * @category	Control Panel
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */
abstract class AbstractDesign extends CP_Controller {

	/**
	 * Constructor
	 */
	public function __construct()
	{
		parent::__construct();

		if ( ! $this->cp->allowed_group('can_access_design'))
		{
			show_error(lang('unauthorized_access'));
		}

		ee()->lang->loadfile('design');
	}

	protected function generateSidebar($active = NULL)
	{
		$active_group_id = NULL;
		if (is_numeric($active))
		{
			$active_group_id = (int) $active;
		}

		$sidebar = ee('CP/Sidebar')->make();

		// Template Groups
		$template_group_list = $sidebar->addHeader(lang('template_groups'))
			->withButton(lang('new'), ee('CP/URL', 'design/group/create'))
			->addFolderList('template-group')
				->withRemoveUrl(ee('CP/URL', 'design/group/remove'))
				->withRemovalKey('group_name')
				->withNoResultsText(lang('zero_template_groups_found'));

		$template_groups = ee('Model')->get('TemplateGroup')
			->filter('site_id', ee()->config->item('site_id'))
			->order('group_name', 'asc');

		if (ee()->session->userdata['group_id'] != 1)
		{
			$template_groups->filter('group_id', 'IN', array_keys(ee()->session->userdata['assigned_template_groups']));
		}

		foreach ($template_groups->all() as $group)
		{
			$item = $template_group_list->addItem($group->group_name, ee('CP/URL', 'design/manager/' . $group->group_name))
				->withEditUrl(ee('CP/URL', 'design/group/edit/' . $group->group_name))
				->withRemoveConfirmation(lang('template_group') . ': <b>' . $group->group_name . '</b>')
				->identifiedBy($group->group_name);

			if ($active_group_id == $group->group_id)
			{
				$item->isActive();
			}

			if ($group->is_site_default)
			{
				$item->asDefaultItem();
			}
		}

		// System Templates
		$system_templates = $sidebar->addHeader(lang('system_templates'))
			->addFolderList('system-templates');

		$item = $system_templates->addItem(lang('messages'), ee('CP/URL', 'design/system'))
			->withEditUrl(ee('CP/URL', 'design/system'))
			->cannotRemove();

		if ($active == 'messages')
		{
			$item->isActive();
		}

		$item = $system_templates->addItem(lang('email'), ee('CP/URL', 'design/email'))
			->withEditUrl(ee('CP/URL', 'design/email'))
			->cannotRemove();

		if ($active == 'email')
		{
			$item->isActive();
		}

		if (ee('Model')->get('Module')->filter('module_name', 'Member')->first())
		{
			$item = $system_templates->addItem(lang('members'), ee('CP/URL', 'design/members'))
				->withEditUrl(ee('CP/URL', 'design/members'))
				->cannotRemove();

			if ($active == 'members')
			{
				$item->isActive();
			}
		}

		if (ee()->config->item('forum_is_installed') == "y")
		{
			$item = $system_templates->addItem(lang('forums'), ee('CP/URL', 'design/forums'))
				->withEditUrl(ee('CP/URL', 'design/forums'))
				->cannotRemove();

			if ($active == 'forums')
			{
				$item->isActive();
			}
		}

		// Template Partials
		$header = $sidebar->addHeader(lang('template_partials'), ee('CP/URL', 'design/snippets'))
			->withButton(lang('new'), ee('CP/URL', 'design/snippets/create'));

		if ($active == 'partials')
		{
			$header->isActive();
		}

		// Template Variables
		$header = $sidebar->addHeader(lang('template_variables'), ee('CP/URL', 'design/variables'))
			->withButton(lang('new'), ee('CP/URL', 'design/variables/create'));

		if ($active == 'variables')
		{
			$header->isActive();
		}

		// Template Routes
		if ( ! TemplateRoute::getConfig())
		{
			$header = $sidebar->addHeader(lang('template_routes'), ee('CP/URL', 'design/routes'));

			if ($active == 'routes')
			{
				$header->isActive();
			}
		}

		ee()->cp->add_js_script(array(
			'file' => array('cp/design/menu'),
		));
	}

	protected function stdHeader($return = NULL)
	{
		if ( ! $return)
		{
			$return = base64_encode(ee()->cp->get_safe_refresh());
		}

		$header = array(
			'title' => lang('template_manager'),
			'form_url' => ee('CP/URL', 'design/template/search', array('return' => $return)),
			'toolbar_items' => array(
				'settings' => array(
					'href' => ee('CP/URL', 'settings/template'),
					'title' => lang('settings')
				),
			),
			'search_button_value' => lang('search_templates')
		);

		if (ee('Model')->get('Template')
			->filter('site_id', ee()->config->item('site_id'))
			->count() > 0)
		{
			$header['toolbar_items']['download'] =array(
				'href' => ee('CP/URL', 'design/export'),
				'title' => lang('export_all')
			);
		}

		ee()->view->header = $header;
	}

	/**
	 * Determines if the logged in user has edit privileges for a given template
	 * group. We need either a group's unique id or a template's unique id to
	 * determine access.
	 *
	 * @param  int  $group_id    The id of the template group in question (optional)
	 * @param  int  $template_id The id of the template in question (optional)
	 * @return bool TRUE if the user has edit privileges, FALSE if not
	 */
	protected function hasEditTemplatePrivileges($group_id = NULL, $template_id = NULL)
	{
		// If the user is a Super Admin, return true
		if (ee()->session->userdata['group_id'] == 1)
		{
			return TRUE;
		}

		if ( ! $group_id)
		{
			if ( ! $template_id)
			{
				return FALSE;
			}
			else
			{
				$group_id = ee('Model')->get('Template', $template_id)
					->fields('group_id')
					->first()
					->group_id;
			}
		}

		return array_key_exists($group_id, ee()->session->userdata['assigned_template_groups']);
	}

	protected function loadCodeMirrorAssets($selector = 'template_data')
	{
		ee()->javascript->set_global(
			'editor.lint', $this->_get_installed_plugins_and_modules()
		);

		ee()->cp->add_to_head(ee()->view->head_link('css/codemirror.css'));
		ee()->cp->add_to_head(ee()->view->head_link('css/codemirror-additions.css'));
		ee()->cp->add_js_script(array(
				'plugin'	=> 'ee_codemirror',
				'file'		=> array(
					'codemirror/codemirror',
					'codemirror/closebrackets',
					'codemirror/lint',
					'codemirror/overlay',
					'codemirror/xml',
					'codemirror/css',
					'codemirror/javascript',
					'codemirror/htmlmixed',
					'codemirror/ee-mode',
					'codemirror/dialog',
					'codemirror/searchcursor',
					'codemirror/search',
				)
			)
		);
		ee()->javascript->output("$('textarea[name=\"" . $selector . "\"]').toggleCodeMirror();");
	}

	/**
	 *  Returns installed module information for CodeMirror linting
	 */
	private function _get_installed_plugins_and_modules()
	{
		$addons = array_keys(ee('Addon')->all());

		$modules = ee('Model')->get('Module')->all()->pluck('module_name');
		$plugins = ee('Model')->get('Plugin')->all()->pluck('plugin_name');

		$modules = array_map('strtolower', $modules);
		$plugins = array_map('strtolower', $plugins);
		$installed = array_merge($modules, $plugins);

		return array(
			'available' => $installed,
			'not_installed' => array_values(array_diff($addons, $installed))
		);
	}

	/**
	 * Export templates
	 *
	 * @param  int|array $template_ids The ids of templates to export
	 * @return void
	 */
	protected function exportTemplates($template_ids)
	{
		if ( ! is_array($template_ids))
		{
			$template_ids = array($template_ids);
		}

		$templates = ee('Model')->get('Template', $template_ids)
			->filter('site_id', ee()->config->item('site_id'))
			->all();

		if ( ! $templates)
		{
			ee('CP/Alert')->makeInline('shared-form')
				->asIssue()
				->withTitle(lang('error_export'))
				->addToBody(lang('error_export_no_templates'))
				->now();
			return;
		}

		// Create the Zip Archive
		$zipfilename = tempnam(sys_get_temp_dir(), '');
		$zip = new ZipArchive();
		if ($zip->open($zipfilename, ZipArchive::CREATE) !== TRUE)
		{
			ee('CP/Alert')->makeInline('shared-form')
				->asIssue()
				->withTitle(lang('error_export'))
				->addToBody(lang('error_cannot_create_zip'))
				->now();
			return;
		}

		// Loop through templates and add them to the zip
		$templates->each(function($template) use($zip) {
				$filename = $template->getTemplateGroup()->group_name . 'group/' . $template->template_name . $template->getFileExtension();
				$zip->addFromString($filename, $template->template_data);
			});

		$zip->close();

		$data = file_get_contents($zipfilename);
		unlink($zipfilename);

		ee()->load->helper('download');
		force_download('ExpressionEngine-templates.zip', $data);
	}

	protected function buildTableFromTemplateCollection(Collection $templates, $include_group_name = FALSE)
	{
		$table = ee('CP/Table', array('autosort' => TRUE));
		$table->setColumns(
			array(
				'template' => array(
					'encode' => FALSE
				),
				'type' => array(
					'encode' => FALSE
				),
				'hits',
				'manage' => array(
					'type'	=> Table::COL_TOOLBAR
				),
				array(
					'type'	=> Table::COL_CHECKBOX
				)
			)
		);
		$table->setNoResultsText('no_templates_found');

		$data = array();

		$template_id = ee()->session->flashdata('template_id');

		$hidden_indicator = ($this->config->item('hidden_template_indicator') != '') ? $this->config->item('hidden_template_indicator') : '_';
		$hidden_indicator_length = strlen($hidden_indicator);

		foreach ($templates as $template)
		{
			$group = $template->getTemplateGroup();
			$template_name = htmlentities($template->template_name, ENT_QUOTES);
			$edit_url = ee('CP/URL', 'design/template/edit/' . $template->template_id);

			if ($include_group_name)
			{
				$template_name = $group->group_name . '/' . $template_name;
			}

			$template_name = '<a href="' . $edit_url->compile() . '">' . $template_name . '</a>';

			if (strncmp($template->template_name, $hidden_indicator, $hidden_indicator_length) == 0)
			{
				$template_name = '<span class="hidden">' . $template_name . '</span>';
			}

			if ($template->template_name == 'index')
			{
				$template_name = '<span class="index">' . $template_name . '</span>';
			}

			$view_url = ee()->functions->fetch_site_index();
			$view_url = rtrim($view_url, '/').'/';

			if ($template->template_type == 'css')
			{
				$view_url .= QUERY_MARKER.'css='.$group->group_name.'/'.$template->template_name;
			}
			else
			{
				$view_url .= $group->group_name.(($template->template_name == 'index') ? '' : '/'.$template->template_name);
			}

			$type_col = $template->template_type;
			if (in_array($template->template_type, array('webpage', 'feed', 'css', 'js', 'static', 'xml')))
			{
				$type_col = lang($template->template_type.'_type_col');
			}

			$column = array(
				$template_name,
				'<span class="st-info">'.$type_col.'</span>',
				$template->hits,
				array('toolbar_items' => array(
					'view' => array(
						'href' => ee()->cp->masked_url($view_url),
						'title' => lang('view'),
						'rel' => 'external'
					),
					'edit' => array(
						'href' => $edit_url,
						'title' => lang('edit')
					),
					'settings' => array(
						'href' => '',
						'rel' => 'modal-template-settings',
						'class' => 'm-link',
						'title' => lang('settings'),
						'data-template-id' => $template->template_id
					),
				)),
				array(
					'name' => 'selection[]',
					'value' => $template->template_id,
					'data' => array(
						'confirm' => lang('template') . ': <b>' . htmlentities($template->template_name, ENT_QUOTES) . '</b>'
					)
				)
			);

			$attrs = array();

			if ($template_id && $template->template_id == $template_id)
			{
				$attrs = array('class' => 'selected');
			}

			$data[] = array(
				'attrs'		=> $attrs,
				'columns'	=> $column
			);
		}

		$table->setData($data);

		return $table;
	}

	/**
	 * Saves a new template revision and rotates revisions based on 'max_tmpl_revisions' config item
	 *
	 * @param	Template	$template	Saved template model object
	 */
	protected function saveNewTemplateRevision($template)
	{
		if ( ! bool_config_item('save_tmpl_revisions'))
		{
			return;
		}

		// Create the new version
		$version = ee('Model')->make('RevisionTracker');
		$version->Template = $template;
		$version->item_table = 'exp_templates';
		$version->item_field = 'template_data';
		$version->item_data = $template->template_data;
		$version->item_date = ee()->localize->now;
		$version->Author = $template->LastAuthor;
		$version->save();

		// Now, rotate template revisions based on 'max_tmpl_revisions' config item
		$versions = ee('Model')->get('RevisionTracker')
			->filter('item_id', $template->getId())
			->filter('item_field', 'template_data')
			->order('item_date', 'desc')
			->limit(ee()->config->item('max_tmpl_revisions'))
			->all();

		// Reassign versions and delete the leftovers
		$template->Versions = $versions;
		$template->save();
	}
}
// EOF

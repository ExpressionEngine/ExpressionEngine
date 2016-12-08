<?php

namespace EllisLab\ExpressionEngine\Controller\Design;

use EllisLab\ExpressionEngine\Controller\Design\AbstractDesign as AbstractDesignController;

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
 * ExpressionEngine CP Design\Group Class
 *
 * @package		ExpressionEngine
 * @subpackage	Control Panel
 * @category	Control Panel
 * @author		EllisLab Dev Team
 * @link		https://ellislab.com
 */
class Group extends AbstractDesignController {

	/**
	 * Constructor
	 */
	function __construct()
	{
		parent::__construct();

		if ( ! ee()->cp->allowed_group('can_access_design'))
		{
			show_error(lang('unauthorized_access'), 403);
		}

		$this->stdHeader();
	}

	public function create()
	{
		if ( ! ee()->cp->allowed_group('can_create_template_groups'))
		{
			show_error(lang('unauthorized_access'), 403);
		}

		$groups = array(
			'false' => '-- ' . strtolower(lang('none')) . ' --'
		);
		ee('Model')->get('TemplateGroup')
			->all()
			->filter('site_id', ee()->config->item('site_id'))
			->each(function($group) use (&$groups) {
				$groups[$group->group_id] = $group->group_name;
				if ($group->is_site_default)
				{
					$groups[$group->group_id] .= ' (' . lang('default') . ')';
				}
			});

		$vars = array(
			'ajax_validate' => TRUE,
			'base_url' => ee('CP/URL')->make('design/group/create'),
			'save_btn_text' => sprintf(lang('btn_save'), lang('template_group')),
			'save_btn_text_working' => 'btn_saving',
			'sections' => array(
				array(
					array(
						'title' => 'name',
						'desc' => 'name_desc',
						'fields' => array(
							'group_name' => array(
								'type' => 'text',
								'required' => TRUE
							)
						)
					),
					array(
						'title' => 'duplicate_group',
						'desc' => 'duplicate_group_desc',
						'fields' => array(
							'duplicate_group' => array(
								'type' => 'select',
								'choices' => $groups
							)
						)
					),
					array(
						'title' => 'make_default_group',
						'desc' => 'make_default_group_desc',
						'fields' => array(
							'make_default_group' => array(
								'type' => 'yes_no',
								'value' => ee('Model')->get('TemplateGroup')
									->filter('site_id', ee()->config->item('site_id'))
									->filter('is_site_default', 'y')
									->count() ? 'n' : 'y'
							)
						)
					),
				)
			)
		);

		ee()->load->library('form_validation');
		ee()->form_validation->set_rules(array(
			array(
				'field' => 'group_name',
				'label' => 'lang:name',
				'rules' => 'required|callback__group_name_checks'
			),
			array(
				'field' => 'make_default_group',
				'label' => 'lang:make_default_group',
				'rules' => 'required|enum[y,n]'
			)
		));

		if (AJAX_REQUEST)
		{
			ee()->form_validation->run_ajax();
			exit;
		}
		elseif (ee()->form_validation->run() !== FALSE)
		{
			$group = ee('Model')->make('TemplateGroup');
			$group->site_id = ee()->config->item('site_id');
			$group->group_name = ee()->input->post('group_name');
			$group->is_site_default = ee()->input->post('make_default_group');

			if ($this->session->userdata('group_id') != 1)
			{
				$group->MemberGroups = ee('Model')->get('MemberGroup', $this->session->userdata('group_id'))->first();
			}

			$group->save();

			$duplicate = FALSE;

			if (is_numeric(ee()->input->post('duplicate_group')))
			{
				$master_group = ee('Model')->get('TemplateGroup', ee()->input->post('duplicate_group'))->first();
				$master_group_templates = $master_group->getTemplates();
				if (count($master_group_templates) > 0)
				{
					$duplicate = TRUE;
				}
			}

			if ( ! $duplicate)
			{
				$template = ee('Model')->make('Template');
				$template->group_id = $group->group_id;
				$template->template_name = 'index';
				$template->template_data = '';
				$template->last_author_id = 0;
				$template->edit_date = ee()->localize->now;
				$template->site_id = ee()->config->item('site_id');
				$template->save();
			}
			else
			{
				foreach ($master_group_templates as $master_template)
				{
					$values = $master_template->getValues();
					unset($values['template_id']);
					$new_template = ee('Model')->make('Template', $values);
					$new_template->template_id = NULL;
					$new_template->group_id = $group->group_id;
					$new_template->edit_date = ee()->localize->now;
					$new_template->site_id = ee()->config->item('site_id');
					$new_template->hits = 0; // Reset hits
					$new_template->NoAccess = $master_template->NoAccess;
					if (ee()->session->userdata['group_id'] != 1)
					{
						$new_template->allow_php = FALSE;
					}
					$new_template->save();
				}
			}

			ee('CP/Alert')->makeInline('shared-form')
				->asSuccess()
				->withTitle(lang('create_template_group_success'))
				->addToBody(sprintf(lang('create_template_group_success_desc'), $group->group_name))
				->defer();

			ee()->functions->redirect(ee('CP/URL')->make('design/manager/' . $group->group_name));
		}
		elseif (ee()->form_validation->errors_exist())
		{
			ee('CP/Alert')->makeInline('shared-form')
				->asIssue()
				->withTitle(lang('create_template_group_error'))
				->addToBody(lang('create_template_group_error_desc'))
				->now();
		}

		$this->generateSidebar();
		ee()->view->cp_page_title = lang('create_new_template_group');

		ee()->cp->render('settings/form', $vars);
	}

	public function edit($group_name)
	{
		if ( ! ee()->cp->allowed_group('can_edit_template_groups'))
		{
			show_error(lang('unauthorized_access'), 403);
		}

		$group = ee('Model')->get('TemplateGroup')
			->filter('group_name', $group_name)
			->filter('site_id', ee()->config->item('site_id'))
			->first();

		if ( ! $group)
		{
			show_error(sprintf(lang('error_no_template_group'), $group_name));
		}

		if ($this->hasEditTemplatePrivileges($group->group_id) === FALSE)
		{
			show_error(lang('unauthorized_access'), 403);
		}

		$vars = array(
			'ajax_validate' => TRUE,
			'base_url' => ee('CP/URL')->make('design/group/edit/' . $group->group_name),
			'form_hidden' => array(
				'old_name' => $group->group_name
			),
			'save_btn_text' => sprintf(lang('btn_save'), lang('template_group')),
			'save_btn_text_working' => 'btn_saving',
			'sections' => array(
				array(
					array(
						'title' => 'name',
						'desc' => 'alphadash_desc',
						'fields' => array(
							'group_name' => array(
								'type' => 'text',
								'required' => TRUE,
								'value' => $group->group_name
							)
						)
					),
					array(
						'title' => 'make_default_group',
						'desc' => 'make_default_group_desc',
						'fields' => array(
							'make_default_group' => array(
								'type' => 'yes_no',
								'value' => $group->is_site_default
							)
						)
					),
				)
			)
		);

		ee()->load->library('form_validation');
		ee()->form_validation->set_rules(array(
			array(
				'field' => 'group_name',
				'label' => 'lang:group_name',
				'rules' => 'required|callback__group_name_checks'
			),
			array(
				'field' => 'make_default_group',
				'label' => 'lang:make_default_group',
				'rules' => 'required|enum[y,n]'
			)
		));

		if (AJAX_REQUEST)
		{
			ee()->form_validation->run_ajax();
			exit;
		}
		elseif (ee()->form_validation->run() !== FALSE)
		{
			$group->group_name = ee()->input->post('group_name');
			$group->is_site_default = ee()->input->post('make_default_group');

			$group->save();

			ee('CP/Alert')->makeInline('shared-form')
				->asSuccess()
				->withTitle(lang('edit_template_group_success'))
				->addToBody(sprintf(lang('edit_template_group_success_desc'), $group->group_name))
				->defer();

			ee()->functions->redirect(ee('CP/URL')->make('design/manager/' . $group->group_name));
		}
		elseif (ee()->form_validation->errors_exist())
		{
			ee('CP/Alert')->makeInline('shared-form')
				->asIssue()
				->withTitle(lang('edit_template_group_error'))
				->addToBody(lang('edit_template_group_error_desc'))
				->now();
		}

		$this->generateSidebar($group->group_id);
		ee()->view->cp_page_title = lang('edit_template_group');

		ee()->cp->render('settings/form', $vars);
	}

	public function remove()
	{
		if ( ! ee()->cp->allowed_group('can_delete_template_groups'))
		{
			show_error(lang('unauthorized_access'), 403);
		}

		$group = ee('Model')->get('TemplateGroup')
			->filter('group_name', ee()->input->post('group_name'))
			->filter('site_id', ee()->config->item('site_id'))
			->first();

		if ( ! $group)
		{
			show_error(lang('group_not_found'));
		}
		else
		{
			if ($this->hasEditTemplatePrivileges($group->group_id) === FALSE)
			{
				show_error(lang('unauthorized_access'), 403);
			}

			// Delete the group folder if it exists
			if (ee()->config->item('save_tmpl_files') == 'y')
			{
				$basepath = PATH_TMPL;
				$basepath .= ee()->config->item('site_short_name') . '/' . $group->group_name . '.group/';

				ee()->load->helper('file');
				delete_files($basepath, TRUE);
				@rmdir($basepath);
			}

			$group->delete();
			ee('CP/Alert')->makeInline('template-group')
				->asSuccess()
				->withTitle(lang('template_group_removed'))
				->addToBody(sprintf(lang('template_group_removed_desc'), ee()->input->post('group_name')))
				->defer();
		}

		ee()->functions->redirect(ee('CP/URL')->make('design'));
	}

	/**
	  *	 Check Template Group Name
	  */
	public function _group_name_checks($str)
	{
		if ( ! preg_match("#^[a-zA-Z0-9_\-/]+$#i", $str))
		{
			ee()->lang->loadfile('admin');
			ee()->form_validation->set_message('_group_name_checks', lang('illegal_characters'));
			return FALSE;
		}

		$reserved_names = array('act', 'css');

		if (in_array($str, $reserved_names))
		{
			ee()->form_validation->set_message('_group_name_checks', lang('reserved_name'));
			return FALSE;
		}

		$count = ee('Model')->get('TemplateGroup')
			->filter('site_id', ee()->config->item('site_id'))
			->filter('group_name', $str)
			->count();

		if ((strtolower($this->input->post('old_name')) != strtolower($str)) AND $count > 0)
		{
			$this->form_validation->set_message('_group_name_checks', lang('template_group_taken'));
			return FALSE;
		}
		elseif ($count > 1)
		{
			$this->form_validation->set_message('_group_name_checks', lang('template_group_taken'));
			return FALSE;
		}

		return TRUE;
	}
}

// EOF

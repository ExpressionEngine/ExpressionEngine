<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2019, EllisLab Corp. (https://ellislab.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace EllisLab\ExpressionEngine\Controller\Design;

use EllisLab\ExpressionEngine\Controller\Design\AbstractDesign as AbstractDesignController;
use EllisLab\ExpressionEngine\Model\Template\TemplateGroup as TemplateGroupModel;
use EllisLab\ExpressionEngine\Service\Validation\Result as ValidationResult;

/**
 * Design\Group Controller
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

		$errors = NULL;

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

		// Not a superadmin?  Preselect their member group as allowed to view templates
		$selected_member_groups = ($this->session->userdata('group_id') != 1) ? array($this->session->userdata('group_id')) : array();

		// only member groups with design manager access
		$member_groups = ee('Model')->get('MemberGroup')
			->filter('group_id', 'NOT IN', array(1, 2, 4))
			->filter('can_access_design', 'y')
			->filter('site_id', ee()->config->item('site_id'))
			->order('group_title', 'asc')
			->all()
			->getDictionary('group_id', 'group_title');

		if ( ! empty($_POST))
		{
			$group = ee('Model')->make('TemplateGroup');
			$group->site_id = ee()->config->item('site_id');

			$result = $this->validateTemplateGroup($group);
			if ($result instanceOf ValidationResult)
			{
				$errors = $result;

				if ($result->isValid())
				{
					// Only set member groups from post if they have permission to admin member groups and a value is set
					if (ee()->input->post('member_groups') && ($this->session->userdata('group_id') == 1 OR ee()->cp->allowed_group('can_admin_mbr_groups')))
					{
						$group->MemberGroups = ee('Model')->get('MemberGroup', ee('Request')->post('member_groups'))->all();
					}
					elseif ($this->session->userdata('group_id') != 1 AND ! ee()->cp->allowed_group('can_admin_mbr_groups'))
					{
						// No permission to admin, so their group is automatically added to the template group
						$group->MemberGroups = ee('Model')->get('MemberGroup', $this->session->userdata('group_id'))->first();
					}

					// Does the current member have permission to access the template group they just created?
					$member_groups = $group->MemberGroups->pluck('group_id');
					$redirect_name = ($this->session->userdata('group_id') == 1 OR in_array($this->session->userdata('group_id'), $member_groups)) ? TRUE : FALSE;

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

					// Only redirect to the template group if the member has permission to view it
					$name = ($redirect_name) ? $group->group_name : '';

					ee('CP/Alert')->makeInline('shared-form')
						->asSuccess()
						->withTitle(lang('create_template_group_success'))
						->addToBody(sprintf(lang('create_template_group_success_desc'), $group->group_name))
						->defer();

					ee()->functions->redirect(ee('CP/URL')->make('design/manager/' . $name));
				}
			}
		}

		$vars = array(
			'ajax_validate' => TRUE,
			'errors' => $errors,
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
								'type' => 'radio',
								'choices' => $groups,
								'no_results' => [
									'text' => sprintf(lang('no_found'), lang('template_groups'))
								]
							)
						)
					),
					array(
						'title' => 'make_default_group',
						'desc' => 'make_default_group_desc',
						'fields' => array(
							'is_site_default' => array(
								'type' => 'yes_no',
								'value' => ee('Model')->get('TemplateGroup')
									->filter('site_id', ee()->config->item('site_id'))
									->filter('is_site_default', 'y')
									->count() ? 'n' : 'y'
							)
						)
					),
					array(
					'title' => 'template_member_groups',
					'desc' => 'template_member_groups_desc',
					'fields' => array(
						'member_groups' => array(
							'type' => 'checkbox',
							'choices' => $member_groups,
							'value' => $selected_member_groups,
							'no_results' => [
								'text' => sprintf(lang('no_found'), lang('member_groups'))
								]
							)
						)
					),
				)
			)
		);

		// Permission check for assigning member groups to templates
		if ( ! ee()->cp->allowed_group('can_admin_mbr_groups'))
		{
			unset($vars['sections'][0][3]);
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

		$errors = NULL;

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

		$selected_member_groups = ($group->MemberGroups) ? $group->MemberGroups->pluck('group_id') : array();

		// only member groups with permission to access templates
		$member_groups = ee('Model')->get('MemberGroup')
			->filter('group_id', 'NOT IN', array(1, 2, 4))
			->filter('can_access_design', 'y')
			->filter('site_id', ee()->config->item('site_id'))
			->order('group_title', 'asc')
			->all()
			->getDictionary('group_id', 'group_title');

		if ( ! empty($_POST))
		{
			$result = $this->validateTemplateGroup($group);
			if ($result instanceOf ValidationResult)
			{
				$errors = $result;

				if ($result->isValid())
				{
					// On edit, if they don't have permission to edit member group permissions, they can't change
					// template member group settings
					if ($this->session->userdata('group_id') == 1 OR ee()->cp->allowed_group('can_admin_mbr_groups'))
					{
						// If post is null and field should be present, unassign members
						// If field isn't present, we don't change whatever it's currently set to
						if ( ! ee()->input->post('member_groups'))
						{
							$group->MemberGroups = array();
						}
						else
						{
							$group->MemberGroups = ee('Model')->get('MemberGroup', ee('Request')->post('member_groups'))->all();
						}
					}

					// Does the current member have permission to access the template group they just edited?
					$member_groups = $group->MemberGroups->pluck('group_id');
					$redirect_name = ($this->session->userdata('group_id') == 1 OR in_array($this->session->userdata('group_id'), $member_groups)) ? TRUE : FALSE;

					$group->save();

					// Only redirect to the template group if the member has permission to view it
					$name = ($redirect_name) ? $group->group_name : '';

					ee('CP/Alert')->makeInline('shared-form')
						->asSuccess()
						->withTitle(lang('edit_template_group_success'))
						->addToBody(sprintf(lang('edit_template_group_success_desc'), $group->group_name))
						->defer();

					ee()->functions->redirect(ee('CP/URL')->make('design/manager/' . $name));
				}
			}
		}

		$vars = array(
			'ajax_validate' => TRUE,
			'errors' => $errors,
			'base_url' => ee('CP/URL')->make('design/group/edit/' . $group_name),
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
							'is_site_default' => array(
								'type' => 'yes_no',
								'value' => $group->is_site_default
							)
						)
					),
					array(
					'title' => 'template_member_groups',
					'desc' => 'template_member_groups_desc',
					'fields' => array(
						'member_groups' => array(
							'type' => 'checkbox',
							'required' => TRUE,
							'choices' => $member_groups,
							'value' => $selected_member_groups,
							'no_results' => [
								'text' => sprintf(lang('no_found'), lang('member_groups'))
								]
							)
						)
					),
				)
			)
		);

		// Permission check for assigning member groups to templates
		if ( ! ee()->cp->allowed_group('can_admin_mbr_groups'))
		{
			unset($vars['sections'][0][2]);
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
	 * Sets a template group entity with the POSTed data and validates it, setting
	 * an alert if there are any errors.
	 *
	 * @param TemplateGroupModel $$group A TemplateGroup entity
	 * @return mixed FALSE if nothing was posted, void if it was an AJAX call,
	 *  or a ValidationResult object.
	 */
	private function validateTemplateGroup(TemplateGroupModel $group)
	{
		if (empty($_POST))
		{
			return FALSE;
		}

		$group->group_name = ee()->input->post('group_name');
		$group->is_site_default = ee()->input->post('is_site_default');

		$result = $group->validate();

		$field = ee()->input->post('ee_fv_field');

		if ($response = $this->ajaxValidation($result))
		{
			ee()->output->send_ajax_response($response);
		}

		if ($result->failed())
		{
			ee('CP/Alert')->makeInline('shared-form')
				->asIssue()
				->withTitle(lang('edit_template_group_error'))
				->addToBody(lang('edit_template_group_error_desc'))
				->now();
		}

		return $result;
	}
}

// EOF

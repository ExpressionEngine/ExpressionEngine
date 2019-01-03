<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2019, EllisLab Corp. (https://ellislab.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace EllisLab\ExpressionEngine\Controller\Categories;

use EllisLab\ExpressionEngine\Controller\Categories\AbstractCategories as AbstractCategoriesController;

/**
 * Category Groups Controller
 */
class Groups extends AbstractCategoriesController {

	/**
	 * Remove category groups handler
	 */
	public function remove()
	{
		if ( ! $this->cp->allowed_group('can_delete_categories'))
		{
			show_error(lang('unauthorized_access'), 403);
		}

		$group_id = ee()->input->post('content_id');
		$group = ee('Model')->get('CategoryGroup', $group_id)->first();

		if ( ! empty($group_id) && $group)
		{
			$group->delete();

			ee()->logger->log_action(lang('category_groups_removed').':'.NBS.NBS.$group->group_name);

			ee()->functions->clear_caching('all', '');

			ee('CP/Alert')->makeInline('channels')
				->asSuccess()
				->withTitle(lang('category_groups_removed'))
				->addToBody(sprintf(
					lang('category_groups_removed_desc'),
					htmlentities($group->group_name, ENT_QUOTES, 'UTF-8')
				))
				->defer();
		}
		else
		{
			show_error(lang('unauthorized_access'), 403);
		}

		ee()->functions->redirect(ee('CP/URL')->make('categories', ee()->cp->get_url_state()));
	}

	/**
	 * New category group form
	 */
	public function create()
	{
		if ( ! $this->cp->allowed_group('can_create_categories'))
		{
			show_error(lang('unauthorized_access'), 403);
		}

		return $this->form();
	}

	/**
	 * Edit category group form
	 */
	public function edit($group_id)
	{
		if ( ! $this->cp->allowed_group('can_edit_categories'))
		{
			show_error(lang('unauthorized_access'), 403);
		}

		return $this->form($group_id);
	}

	/**
	 * Category group creation/edit form
	 *
	 * @param	int	$group_id	ID of category group to edit
	 */
	private function form($group_id = NULL)
	{
		$this->generateSidebar();

		if (is_null($group_id))
		{
			$alert_key = 'created';
			ee()->view->cp_page_title = lang('create_category_group');
			ee()->view->base_url = ee('CP/URL')->make('categories/groups/create');
			$cat_group = ee('Model')->make('CategoryGroup');
		}
		else
		{
			$cat_group = ee('Model')->get('CategoryGroup')
				->filter('group_id', $group_id)
				->first();

			if ( ! $cat_group)
			{
				show_error(lang('unauthorized_access'), 403);
			}

			$alert_key = 'updated';
			ee()->view->cp_page_title = lang('edit_category_group');
			ee()->view->base_url = ee('CP/URL')->make('categories/groups/edit/'.$group_id);
		}

		ee()->form_validation->set_rules(array(
			array(
				'field' => 'group_name',
				'label' => 'lang:name',
				'rules' => 'required|strip_tags|trim|valid_xss_check|alpha_dash_space|callback_validCategoryGroupName['.$group_id.']'
			)
		));

		//ee()->form_validation->validateNonTextInputs($vars['sections']);

		$errors = NULL;

		if ( ! empty($_POST))
		{
			$cat_group = $this->setWithPost($cat_group);
			$result = $cat_group->validate();

			if (isset($_POST['ee_fv_field']) && $response = $this->ajaxValidation($result))
			{
				return $response;
			}

			if ($result->isValid())
			{
				$cat_group->save();

				ee('CP/Alert')->makeInline('shared-form')
					->asSuccess()
					->withTitle(lang('category_group_'.$alert_key))
					->addToBody(sprintf(lang('category_group_'.$alert_key.'_desc'), $cat_group->group_name))
					->defer();

				if (AJAX_REQUEST)
				{
					return ['saveId' => $cat_group->getId()];
				}

				if (ee('Request')->post('submit') == 'save_and_new')
				{
					ee()->functions->redirect(ee('CP/URL')->make('categories/groups/create'));
				}
				elseif (ee()->input->post('submit') == 'save_and_close')
				{
					ee()->functions->redirect(ee('CP/URL')->make('categories/group/'.$cat_group->getId()));
				}
				else
				{
					if (is_null($group_id))
					{
						ee()->session->set_flashdata('highlight_id', $cat_group->getId());
					}

					ee()->functions->redirect(ee('CP/URL')->make('categories/groups/edit/'.$cat_group->getId()));
				}
			}
			else
			{
				$errors = $result;

				ee('CP/Alert')->makeInline('shared-form')
					->asIssue()
					->withTitle(lang('category_group_not_'.$alert_key))
					->addToBody(lang('category_group_not_'.$alert_key.'_desc'))
					->now();
			}
		}

		ee()->view->ajax_validate = TRUE;

		$vars = [
			'sections' => [],
			'tabs' => [
				'details' => $this->renderDetailsTab($cat_group, $errors),
				'permissions' => $this->renderPermissionsTab($cat_group, $errors)
			],
			'buttons' => [
				[
					'name' => 'submit',
					'type' => 'submit',
					'value' => 'save',
					'text' => 'save',
					'working' => 'btn_saving'
				],
				[
					'name' => 'submit',
					'type' => 'submit',
					'value' => 'save_and_new',
					'text' => 'save_and_new',
					'working' => 'btn_saving'
				],
				[
					'name' => 'submit',
					'type' => 'submit',
					'value' => 'save_and_close',
					'text' => 'save_and_close',
					'working' => 'btn_saving'
				]
			]
		];

		if (AJAX_REQUEST)
		{
			unset($vars['buttons'][2]);
		}

		if ( ! $cat_group->isNew())
		{
			$vars['tabs']['fields'] = $this->renderFieldsTab($cat_group, $errors);

			ee()->javascript->set_global([
				'categoryField.createUrl' =>
				ee('CP/URL')->make('categories/fields/create/'.$cat_group->getId())->compile(),
				'categoryField.editUrl' =>
				ee('CP/URL')->make('categories/fields/edit/'.$cat_group->getId().'/###/')->compile(),
				'categoryField.removeUrl' =>
				ee('CP/URL')->make('categories/fields/remove')->compile(),
				'categoryField.fieldUrl' =>
				ee('CP/URL')->make('categories/groups/render-fields-field/'.$cat_group->getId())->compile()
			]);

			// Call fieldtypes' display_settings methods to load any needed JS
			foreach (array('text', 'textarea', 'select') as $fieldtype)
			{
				$dummy_field = ee('Model')->make('CategoryField');
				$dummy_field->field_type = $fieldtype;
				$dummy_field->getSettingsForm();
			}

			ee()->cp->add_js_script('file', 'cp/channel/cat_group_form');
		}

		if (AJAX_REQUEST)
		{
			return ee()->cp->render('_shared/form', $vars);
		}

		ee()->cp->add_js_script('plugin', 'ee_url_title');

		ee()->cp->set_breadcrumb(ee('CP/URL')->make('categories'), lang('category_manager'));

		ee()->cp->render('settings/form', $vars);
	}

	/**
	 * Renders the Details tab for the Category Group create/edit form
	 *
	 * @param CategoryGroup $cat_group A CategoryGroup entity
	 * @param null|ValidationResult $errors NULL (if nothing was submitted) or
	 *   a ValidationResult object. This is needed to render any inline erorrs
	 *   on the form.
	 * @return string HTML
	 */
	private function renderDetailsTab($cat_group, $errors)
	{
		$section = array(
			array(
				'title' => 'name',
				'fields' => array(
					'group_name' => array(
						'type' => 'text',
						'value' => $cat_group->group_name,
						'required' => TRUE
					)
				)
			),
			array(
				'title' => 'html_formatting',
				'fields' => array(
					'field_html_formatting' => array(
						'type' => 'radio',
						'choices' => array(
							'all'	=> lang('allow_all_html'),
							'safe'	=> lang('allow_safe_html'),
							'none'	=> lang('convert_to_entities')
						),
						'value' => $cat_group->field_html_formatting
					)
				)
			),
			array(
				'title' => 'exclude_group_form',
				'desc' => 'exclude_group_form_desc',
				'fields' => array(
					'exclude_group' => array(
						'type' => 'radio',
						'choices' => array(
							0 => lang('none'),
							1 => lang('channels'),
							2 => lang('files')
						),
						'value' => ($cat_group->exclude_group) ?: 0
					)
				)
			)
		);

		return ee('View')->make('_shared/form/section')
				->render(array('name' => NULL, 'settings' => $section, 'errors' => $errors));
	}

	/**
	 * Renders the Permissions tab for the Category Group create/edit form
	 *
	 * @param CategoryGroup $cat_group A CategoryGroup entity
	 * @param null|ValidationResult $errors NULL (if nothing was submitted) or
	 *   a ValidationResult object. This is needed to render any inline erorrs
	 *   on the form.
	 * @return string HTML
	 */
	private function renderPermissionsTab($cat_group, $errors)
	{
		$member_groups = ee('Model')->get('MemberGroup')
			->filter('group_id', 'NOT IN', array(1,2,3,4))
			->filter('site_id', ee()->config->item('site_id'));

		$can_edit_categories = array();
		foreach ($member_groups->filter('can_edit_categories', 'y')->all() as $member_group)
		{
			$can_edit_categories[$member_group->group_id] = $member_group->group_title;
		}

		$can_delete_categories = array();
		foreach ($member_groups->filter('can_delete_categories', 'y')->all() as $member_group)
		{
			$can_delete_categories[$member_group->group_id] = $member_group->group_title;
		}

		$section = array(
			ee('CP/Alert')->makeInline('permissions-warn')
				->asWarning()
				->addToBody(lang('category_permissions_warning'))
				->addToBody(
					sprintf(lang('category_permissions_warning2'), '<span class="icon--caution" title="exercise caution"></span>'),
					'caution'
				)
				->cannotClose()
				->render(),
			array(
				'title' => 'edit_categories',
				'desc' => 'edit_categories_desc',
				'caution' => TRUE,
				'fields' => array(
					'can_edit_categories' => array(
						'type' => 'checkbox',
						'choices' => $can_edit_categories,
						'value' => explode('|', rtrim($cat_group->can_edit_categories, '|')),
						'no_results' => array(
							'text' => 'cat_group_no_member_groups_found'
						)
					)
				)
			),
			array(
				'title' => 'delete_categories',
				'desc' => 'delete_categories_desc',
				'caution' => TRUE,
				'fields' => array(
					'can_delete_categories' => array(
						'type' => 'checkbox',
						'choices' => $can_delete_categories,
						'value' => explode('|', rtrim($cat_group->can_edit_categories, '|')),
						'no_results' => array(
							'text' => 'cat_group_no_member_groups_found'
						)
					)
				)
			)
		);

		return ee('View')->make('_shared/form/section')
				->render(array('name' => NULL, 'settings' => $section, 'errors' => $errors));
	}

	/**
	 * Renders the Fields tab for the Category Group create/edit form
	 *
	 * @param CategoryGroup $cat_group A CategoryGroup entity
	 * @param null|ValidationResult $errors NULL (if nothing was submitted) or
	 *   a ValidationResult object. This is needed to render any inline erorrs
	 *   on the form.
	 * @return string HTML
	 */
	private function renderFieldsTab($cat_group, $errors)
	{
		$section = array(
			array(
				'title' => 'Fields',
				'desc' => 'category_fields_desc',
				'button' => [
					'text' => 'add_field',
					'rel' => 'add_new'
				],
				'fields' => array(
					'category_fields' => array(
						'type' => 'html',
						'content' => $this->renderFieldsField($cat_group)
					)
				)
			)
		);

		return ee('View')->make('_shared/form/section')
				->render(array('name' => NULL, 'settings' => $section, 'errors' => $errors));
	}

	/**
	 * Create the nested list of menu items for a given set
	 *
	 * @return Rendered HTML of selection form
	 */
	public function renderFieldsField($cat_group)
	{
		$choices = [];

		if (is_numeric($cat_group))
		{
			$cat_group = ee('Model')->get('CategoryGroup', $cat_group)->first();
		}

		if ($cat_group->CategoryFields)
		{
			$choices = $cat_group->CategoryFields->sortBy('field_order')->getDictionary('field_id', 'field_label');
		}

		return ee('View')->make('ee:_shared/form/fields/select')->render([
			'field_name'  => 'category_fields',
			'choices'     => $choices,
			'value'       => NULL,
			'force_react' => TRUE,
			'multi'       => FALSE,
			'nested'      => FALSE,
			'selectable'  => FALSE,
			'reorderable' => TRUE,
			'removable'   => TRUE,
			'editable'    => TRUE,
			'reorder_ajax_url'    => ee('CP/URL', 'categories/fields/reorder/'.$cat_group->getId())->compile(),
			'no_results' => [
				'text' => sprintf(lang('no_found'), lang('fields')),
				'link_text' => 'add_new',
				'link_href' => '#'
			]
		]);
	}

	/**
	 * Saves a category group
	 *
	 * @param	int $group_id ID of category group to save
	 * @return	int ID of category group saved
	 */
	private function setWithPost($cat_group)
	{
		$cat_group->site_id = ee()->config->item('site_id');
		$cat_group->set($_POST);
		$cat_group->can_edit_categories = (ee()->input->post('can_edit_categories'))
			? implode('|', $_POST['can_edit_categories']) : '';
		$cat_group->can_delete_categories = (ee()->input->post('can_delete_categories'))
			? implode('|', $_POST['can_delete_categories']) : '';

		return $cat_group;
	}
}

// EOF

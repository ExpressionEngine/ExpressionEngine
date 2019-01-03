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

use EllisLab\ExpressionEngine\Library\CP;
use EllisLab\ExpressionEngine\Controller\Categories\AbstractCategories as AbstractCategoriesController;

/**
 * Category Fields Controller
 */
class Fields extends AbstractCategoriesController {

	/**
	 * AJAX end point for reordering category fields
	 */
	public function reorder($group_id)
	{
		if ( ! ee()->cp->allowed_group('can_edit_categories'))
		{
			show_error(lang('unauthorized_access'), 403);
		}

		$cat_fields = ee('Model')->get('CategoryGroup')
			->filter('group_id', $group_id)
			->first()
			->CategoryFields
			->indexBy('field_id');

		foreach (ee('Request')->post('order') as $order => $field)
		{
			$field_model = $cat_fields[$field['id']];
			$field_model->field_order = $order + 1;
			$field_model->save();
		}

		return ['success'];
	}

	/**
	 * Remove channels handler
	 */
	public function remove()
	{
		if ( ! ee()->cp->allowed_group('can_delete_categories'))
		{
			show_error(lang('unauthorized_access'), 403);
		}

		$field_id = ee('Request')->post('content_id');

		ee('Model')->get('CategoryField', $field_id)->delete();

		return ['success'];
	}

	/**
	 * Category field create
	 *
	 * @param	int	$group_id		ID of category group field is to be in
	 */
	public function create($group_id)
	{
		if ( ! ee()->cp->allowed_group('can_create_categories'))
		{
			show_error(lang('unauthorized_access'), 403);
		}

		return $this->categoryFieldForm($group_id);
	}

	/**
	 * Category field edit
	 *
	 * @param	int	$group_id	ID of category group field is in
	 * @param	int	$field_id	ID of Field to edit
	 */
	public function edit($group_id, $field_id)
	{
		if ( ! ee()->cp->allowed_group('can_edit_categories'))
		{
			show_error(lang('unauthorized_access'), 403);
		}

		return $this->categoryFieldForm($group_id, $field_id);
	}

	/**
	 * Category field creation/edit form
	 *
	 * @param	int	$group_id	ID of category group field is (to be) in
	 * @param	int	$field_id	ID of field to edit
	 */
	private function categoryFieldForm($group_id, $field_id = NULL)
	{
		if (empty($group_id) OR ! is_numeric($group_id))
		{
			show_error(lang('unauthorized_access'), 403);
		}

		$fieldtype_choices = [
			'text'     => lang('text_input'),
			'textarea' => lang('textarea'),
			'select'   => lang('select_dropdown')
		];

		$cat_group = ee('Model')->get('CategoryGroup', $group_id)->first();

		if ($field_id)
		{
			$cat_field = ee('Model')->get('CategoryField')
				->filter('field_id', (int) $field_id)
				->first();

			$fieldtype_choices = array_intersect_key($fieldtype_choices, $cat_field->getCompatibleFieldtypes());

			$alert_key = 'updated';
			ee()->view->cp_page_title = lang('edit_category_field');
			ee()->view->base_url = ee('CP/URL')->make('categories/fields/edit/'.$group_id.'/'.$field_id);
		}
		else
		{
			// Only auto-complete field short name for new fields
			ee()->cp->add_js_script('plugin', 'ee_url_title');
			ee()->javascript->output('
				$("input[name=field_label]").bind("keyup keydown", function() {
					$(this).ee_url_title("input[name=field_name]");
				});
			');

			$cat_field = ee('Model')->make('CategoryField');
			$cat_field->setCategoryGroup($cat_group);
			$cat_field->site_id = ee()->config->item('site_id');
			$cat_field->field_type = 'text';

			$alert_key = 'created';
			ee()->view->cp_page_title = lang('create_category_field');
			ee()->view->base_url = ee('CP/URL')->make('categories/fields/create/'.$group_id);
		}

		if ( ! $cat_field)
		{
			show_error(lang('unauthorized_access'), 403);
		}

		ee()->lang->loadfile('admin_content');

		$vars['sections'] = array(
			array(
				array(
					'title' => 'type',
					'desc' => '',
					'fields' => array(
						'field_type' => array(
							'type' => 'dropdown',
							'choices' => $fieldtype_choices,
							'group_toggle' => array(
								'text' => 'text',
								'textarea' => 'textarea',
								'select' => 'select'
							),
							'value' => $cat_field->field_type
						)
					)
				),
				array(
					'title' => 'name',
					'fields' => array(
						'field_label' => array(
							'type' => 'text',
							'value' => $cat_field->field_label,
							'required' => TRUE
						)
					)
				),
				array(
					'title' => 'short_name',
					'desc' => 'alphadash_desc',
					'fields' => array(
						'field_name' => array(
							'type' => 'text',
							'value' => $cat_field->field_name,
							'required' => TRUE
						)
					)
				),
				array(
					'title' => 'require_field',
					'desc' => 'cat_require_field_desc',
					'fields' => array(
						'field_required' => array(
							'type' => 'yes_no',
							'value' => $cat_field->field_required
						)
					)
				)
			)
		);

		$vars['sections'] += $cat_field->getSettingsForm();

		// These are currently the only fieldtypes we allow; get their settings forms
		foreach (array_keys($fieldtype_choices) as $fieldtype)
		{
			if ($cat_field->field_type != $fieldtype)
			{
				$dummy_field = ee('Model')->make('CategoryField');
				$dummy_field->setCategoryGroup($cat_group);
				$dummy_field->field_type = $fieldtype;
				$vars['sections'] += $dummy_field->getSettingsForm();
			}
		}

		if ( ! empty($_POST))
		{
			$cat_field->set($_POST);
			$cat_field->field_default_fmt = isset($_POST['field_fmt']) ? $_POST['field_fmt'] : NULL;
			$result = $cat_field->validate();

			if (isset($_POST['ee_fv_field']) && $response = $this->ajaxValidation($result))
			{
				return $response;
			}

			if ($result->isValid())
			{
				$cat_field->save();

				if (isset($_POST['update_formatting']) && $_POST['update_formatting'] == 'y')
				{
					$cat_field->updateFormattingOnExisting();
				}

				ee('CP/Alert')->makeInline('shared-form')
					->asSuccess()
					->withTitle(lang('category_field_'.$alert_key))
					->addToBody(sprintf(lang('category_field_'.$alert_key.'_desc'), $cat_field->field_label))
					->defer();

				if (AJAX_REQUEST)
				{
					return ['saveId' => $cat_field->getId()];
				}

				if (ee('Request')->post('submit') == 'save_and_new')
				{
					ee()->functions->redirect(ee('CP/URL')->make('categories/fields/create'));
				}
				else
				{
					ee()->functions->redirect(ee('CP/URL')->make('categories/fields/'.$group_id));
				}
			}
			else
			{
				$vars['errors'] = $result;
				ee('CP/Alert')->makeInline('shared-form')
					->asIssue()
					->withTitle(lang('category_field_not_'.$alert_key))
					->addToBody(lang('category_field_not_'.$alert_key.'_desc'))
					->now();
			}
		}

		ee()->view->ajax_validate = TRUE;
		$vars['buttons'] = [
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
			]
		];

		if (AJAX_REQUEST)
		{
			return ee()->cp->render('_shared/form', $vars);
		}

		ee()->cp->set_breadcrumb(ee('CP/URL')->make('categories'), lang('category_manager'));
		ee()->cp->set_breadcrumb(ee('CP/URL')->make('categories/fields/'.$group_id), lang('category_fields'));

		$this->generateSidebar();

		ee()->cp->render('settings/form', $vars);
	}
}

// EOF

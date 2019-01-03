<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2019, EllisLab Corp. (https://ellislab.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace EllisLab\ExpressionEngine\Controller\Fields;

use EllisLab\ExpressionEngine\Controller\Fields\AbstractFields as AbstractFieldsController;
use EllisLab\ExpressionEngine\Model\Channel\ChannelField;

/**
 * Fields Controller
 */
class Fields extends AbstractFieldsController {

	public function index()
	{
		$group_id = ee('Request')->get('group_id');

		if ($group_id)
		{
			$base_url = ee('CP/URL')->make('fields', ['group_id' => $group_id]);
		}
		else
		{
			$base_url = ee('CP/URL')->make('fields');
		}

		if (ee()->input->post('bulk_action') == 'remove')
		{
			$this->remove(ee()->input->post('selection'));
			ee()->functions->redirect($base_url);
		}

		$this->generateSidebar($group_id);

		$vars['create_url'] = $group_id
			? ee('CP/URL')->make('fields/create/'.$group_id)
			: ee('CP/URL')->make('fields/create');
		$vars['base_url'] = $base_url;

		$data = array();

		$field_id = ee()->session->flashdata('field_id');

		// Set up filters
		$group_ids = ee('Model')->get('ChannelFieldGroup')
			->filter('site_id', 'IN', [ee()->config->item('site_id'), 0])
			->order('group_name')
			->all()
			->getDictionary('group_id', 'group_name');

		$filters = ee('CP/Filter');
		$group_filter = $filters->make('group_id', 'group_filter', $group_ids);
		$group_filter->setPlaceholder(lang('all'));
		$group_filter->disableCustomValue();

		$fieldtypes = ee('Model')->make('ChannelField')->getCompatibleFieldtypes();

		$fieldtype_filter = $filters->make('fieldtype', 'type_filter', $fieldtypes);
		$fieldtype_filter->setPlaceholder(lang('all'));
		$fieldtype_filter->disableCustomValue();

		$page = ee('Request')->get('page') ?: 1;
		$per_page = 10;

		$filters->add($group_filter)
			->add($fieldtype_filter);

		$filter_values = $filters->values();

		$total_fields = 0;

		$group = $group_id && $group_id != 'all'
			? ee('Model')->get('ChannelFieldGroup', $group_id)->first()
			: NULL;

		// Are we showing a specific group? If so, we need to apply filtering differently
		// because we are acting on a collection instead of a query builder
		if ($group)
		{
			$fields = $group->ChannelFields->sortBy('field_label')->asArray();

			if ($search = ee()->input->get_post('filter_by_keyword'))
			{
				$fields = array_filter($fields, function($field) use ($search) {
					return strpos(
						strtolower($field->field_label).strtolower($field->field_name),
						strtolower($search)
					) !== FALSE;
				});
			}
			if ($fieldtype = $filter_values['fieldtype'])
			{
				$fields = array_filter($fields, function($field) use ($fieldtype) {
					return $field->field_type == $fieldtype;
				});
			}

			$total_fields = count($fields);
		}
		else
		{
			$fields = ee('Model')->get('ChannelField')
				->filter('site_id', 'IN', [ee()->config->item('site_id'), 0]);

			if ($search = ee()->input->get_post('filter_by_keyword'))
			{
				$fields->search(['field_label', 'field_name'], $search);
			}

			if ($fieldtype = $filter_values['fieldtype'])
			{
				$fields->filter('field_type', $fieldtype);
			}

			$total_fields = $fields->count();
		}

		$filters->add('Keyword')
			->add('Perpage', $total_fields, 'all_fields', TRUE);

		$filter_values = $filters->values();
		$vars['base_url']->addQueryStringVariables($filter_values);
		$per_page = $filter_values['perpage'];

		if ($group)
		{
			$fields = array_slice($fields, (($page - 1) * $per_page), $per_page);
		}
		else
		{
			$fields = $fields->limit($per_page)
				->offset(($page - 1) * $per_page)
				->order('field_label')
				->all();
		}

		// Only show filters if there is data to filter or we are currently filtered
		if ($group_id OR ! empty($fields))
		{
			$vars['filters'] = $filters->render(ee('CP/URL')->make('fields'));
		}

		foreach ($fields as $field)
		{
			$edit_url = ee('CP/URL')->make('fields/edit/' . $field->getId());
			$fieldtype = isset($fieldtypes[$field->field_type]) ? '('.$fieldtypes[$field->field_type].')' : '';

			$data[] = [
				'id' => $field->getId(),
				'label' => $field->field_label,
				'faded' => strtolower($fieldtype),
				'href' => $edit_url,
				'extra' => LD.$field->field_name.RD,
				'selected' => ($field_id && $field->getId() == $field_id),
				'toolbar_items' => ee()->cp->allowed_group('can_edit_channel_fields') ? [
					'edit' => [
						'href' => $edit_url,
						'title' => lang('edit')
					]
				] : NULL,
				'selection' => ee()->cp->allowed_group('can_delete_channel_fields') ? [
					'name' => 'selection[]',
					'value' => $field->getId(),
					'data' => [
						'confirm' => lang('field') . ': <b>' . ee('Format')->make('Text', $field->field_label)->convertToEntities() . '</b>'
					]
				] : NULL
			];
		}

		if (ee()->cp->allowed_group('can_delete_channel_fields'))
		{
			ee()->javascript->set_global('lang.remove_confirm', lang('field') . ': <b>### ' . lang('fields') . '</b>');
			ee()->cp->add_js_script(array(
				'file' => array(
					'cp/confirm_remove',
				),
			));
		}

		$vars['pagination'] = ee('CP/Pagination', $total_fields)
			->perPage($per_page)
			->currentPage($page)
			->render($vars['base_url']);

		$vars['cp_page_title'] = $group
			? $group->group_name . '&mdash;' . lang('fields' )
			: lang('all_fields');
		$vars['fields'] = $data;
		$vars['no_results'] = ['text' => sprintf(lang('no_found'), lang('fields')), 'href' => $vars['create_url']];

		ee()->cp->render('fields/index', $vars);
	}

	public function create($group_id = NULL)
	{
		if ( ! ee()->cp->allowed_group('can_create_channel_fields'))
		{
			show_error(lang('unauthorized_access'), 403);
		}

		if (ee('Request')->post('group_id'))
		{
			$group_id = ee('Request')->post('group_id');
		}

		ee()->view->cp_breadcrumbs = array(
			ee('CP/URL')->make('fields')->compile() => lang('field_manager')
		);

		$this->generateSidebar($group_id);

		$errors = NULL;
		$field = ee('Model')->make('ChannelField');

		if ( ! empty($_POST))
		{
			$field = $this->setWithPost($field);
			$result = $field->validate();

			if (isset($_POST['ee_fv_field']) && $response = $this->ajaxValidation($result))
			{
			    return $response;
			}

			if ($result->isValid())
			{
				$field->save();

				if ($group_id)
				{
					$field_group = ee('Model')->get('ChannelFieldGroup', $group_id)->first();
					if ($field_group)
					{
						$field_group->ChannelFields->getAssociation()->add($field);
						$field_group->save();
					}
				}

				ee('CP/Alert')->makeInline('shared-form')
					->asSuccess()
					->withTitle(lang('create_field_success'))
					->addToBody(sprintf(lang('create_field_success_desc'), $field->field_label))
					->defer();

				if (AJAX_REQUEST)
				{
					return ['saveId' => $field->getId()];
				}

				if (ee('Request')->post('submit') == 'save_and_new')
				{
					$return = (empty($group_id)) ? '' : '/'.$group_id;
					ee()->functions->redirect(ee('CP/URL')->make('fields/create'.$return));
				}
				elseif (ee()->input->post('submit') == 'save_and_close')
				{
					ee()->functions->redirect(ee('CP/URL')->make('fields'));
				}
				else
				{
					ee()->functions->redirect(ee('CP/URL')->make('fields/edit/'.$field->getId()));
				}
			}
			else
			{
				$errors = $result;

				ee('CP/Alert')->makeInline('shared-form')
					->asIssue()
					->withTitle(lang('create_field_error'))
					->addToBody(lang('create_field_error_desc'))
					->now();
			}
		}

		$vars = array(
			'errors' => $errors,
			'ajax_validate' => TRUE,
			'base_url' => $group_id
				? ee('CP/URL')->make('fields/create/'.$group_id)
				: ee('CP/URL')->make('fields/create'),
			'sections' => $this->form($field),
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
			],
			'form_hidden' => array(
				'field_id' => NULL
			),
		);

		if (AJAX_REQUEST)
		{
			unset($vars['buttons'][2]);
		}

		ee()->view->cp_page_title = lang('create_new_field');

		if (AJAX_REQUEST)
		{
			return ee()->cp->render('_shared/form', $vars);
		}

		ee()->cp->add_js_script('plugin', 'ee_url_title');

		ee()->javascript->set_global([
			'publish.foreignChars' => ee()->config->loadFile('foreign_chars')
		]);

		ee()->javascript->output('
			$("input[name=field_label]").bind("keyup keydown", function() {
				$(this).ee_url_title("input[name=field_name]", true);
			});
		');

		ee()->cp->render('settings/form', $vars);
	}

	public function edit($id)
	{
		if ( ! ee()->cp->allowed_group('can_edit_channel_fields'))
		{
			show_error(lang('unauthorized_access'), 403);
		}

		$field = ee('Model')->get('ChannelField', $id)
			->first();

		if ( ! $field)
		{
			show_404();
		}

		$field_groups = $field->ChannelFieldGroups;
		$active_groups = $field_groups->pluck('group_id');
		$this->generateSidebar($active_groups);

		ee()->view->cp_breadcrumbs = array(
			ee('CP/URL')->make('fields')->compile() => lang('field_manager'),
		);

		$errors = NULL;

		if ( ! empty($_POST))
		{
			$field = $this->setWithPost($field);
			$result = $field->validate();

			if ($response = $this->ajaxValidation($result))
			{
			    return $response;
			}

			if ($result->isValid())
			{
				$field->save();

				if (ee()->input->post('update_formatting') == 'y')
				{
					ee()->db->where('field_ft_' . $field->field_id . ' IS NOT NULL', NULL, FALSE);
					ee()->db->update(
						$field->getDataStorageTable(),
						array('field_ft_'.$field->field_id => $field->field_fmt)
					);
				}

				ee('CP/Alert')->makeInline('shared-form')
					->asSuccess()
					->withTitle(lang('edit_field_success'))
					->addToBody(sprintf(lang('edit_field_success_desc'), $field->field_label))
					->defer();

				if (ee('Request')->post('submit') == 'save_and_new')
				{
					ee()->functions->redirect(ee('CP/URL')->make('fields/create'));
				}
				elseif (ee()->input->post('submit') == 'save_and_close')
				{
					ee()->functions->redirect(ee('CP/URL')->make('fields'));
				}
				else
				{
					ee()->functions->redirect(ee('CP/URL')->make('fields/edit/'.$field->getId()));
				}
			}
			else
			{
				$errors = $result;

				ee('CP/Alert')->makeInline('shared-form')
					->asIssue()
					->withTitle(lang('edit_field_error'))
					->addToBody(lang('edit_field_error_desc'))
					->now();
			}
		}

		$vars = array(
			'errors' => $errors,
			'ajax_validate' => TRUE,
			'base_url' => ee('CP/URL')->make('fields/edit/' . $id),
			'sections' => $this->form($field),
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
			],
			'form_hidden' => array(
				'field_id' => $id,
			),
		);

		ee()->view->cp_page_title = lang('edit_field');

		ee()->cp->render('settings/form', $vars);
	}

	private function setWithPost(ChannelField $field)
	{
		$field->field_list_items = ($field->field_list_items) ?: '';
		$field->field_order = ($field->field_order) ?: 0;
		$field->site_id = (int) $field->site_id ?: 0;

		$field->set($_POST);

		if ($field->field_pre_populate)
		{
			list($channel_id, $field_id) = explode('_', $_POST['field_pre_populate_id']);

			$field->field_pre_channel_id = $channel_id;
			$field->field_pre_field_id = $field_id;
		}

		return $field;
	}

	private function form(ChannelField $field = NULL)
	{
		if ( ! $field)
		{
			$field = ee('Model')->make('ChannelField');
		}

		$fieldtype_choices = $field->getCompatibleFieldtypes();

		$fieldtypes = ee('Model')->get('Fieldtype')
			->fields('name')
			->filter('name', 'IN', array_keys($fieldtype_choices))
			->order('name')
			->all();

		$field->field_type = ($field->field_type) ?: 'text';

		$sections = array(
			array(
				array(
					'title' => 'type',
					'desc' => '',
					'fields' => array(
						'field_type' => array(
							'type' => 'dropdown',
							'choices' => $fieldtype_choices,
							'group_toggle' => $fieldtypes->getDictionary('name', 'name'),
							'value' => $field->field_type,
							'no_results' => ['text' => sprintf(lang('no_found'), lang('fieldtypes'))]
						)
					)
				),
				array(
					'title' => 'name',
					'fields' => array(
						'field_label' => array(
							'type' => 'text',
							'value' => $field->field_label,
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
							'value' => $field->field_name,
							'required' => TRUE
						)
					)
				),
				array(
					'title' => 'instructions',
					'desc' => 'instructions_desc',
					'fields' => array(
						'field_instructions' => array(
							'type' => 'textarea',
							'value' => $field->field_instructions,
						)
					)
				),
				array(
					'title' => 'require_field',
					'desc' => 'require_field_desc',
					'fields' => array(
						'field_required' => array(
							'type' => 'yes_no',
							'value' => $field->field_required,
						)
					)
				),
				array(
					'title' => 'include_in_search',
					'desc' => 'include_in_search_desc',
					'fields' => array(
						'field_search' => array(
							'type' => 'yes_no',
							'value' => $field->field_search,
						)
					)
				),
				array(
					'title' => 'hide_field',
					'desc' => 'hide_field_desc',
					'fields' => array(
						'field_is_hidden' => array(
							'type' => 'yes_no',
							'value' => $field->field_is_hidden,
						)
					)
				),
			),
		);

		$field_options = $field->getSettingsForm();
		if (is_array($field_options) && ! empty($field_options))
		{
			$sections = array_merge($sections, $field_options);
		}

		foreach ($fieldtypes as $fieldtype)
		{
			if ($fieldtype->name == $field->field_type)
			{
				continue;
			}

			// If editing an option field, populate the dummy fieldtype with the
			// same settings to make switching between the different types easy
			if ( ! $field->isNew() &&
				in_array(
					$fieldtype->name,
					array('checkboxes', 'multi_select', 'radio', 'select')
				))
			{
				$dummy_field = clone $field;
			}
			else
			{
				$dummy_field = ee('Model')->make('ChannelField');
			}
			$dummy_field->field_type = $fieldtype->name;
			$field_options = $dummy_field->getSettingsForm();

			if (is_array($field_options) && ! empty($field_options))
			{
				$sections = array_merge($sections, $field_options);
			}
		}

		ee()->javascript->output('$(document).ready(function () {
			EE.cp.fieldToggleDisable();
		});');

		return $sections;
	}

	private function remove($field_ids)
	{
		if ( ! ee()->cp->allowed_group('can_delete_channel_fields'))
		{
			show_error(lang('unauthorized_access'), 403);
		}

		if ( ! is_array($field_ids))
		{
			$field_ids = array($field_ids);
		}

		$fields = ee('Model')->get('ChannelField', $field_ids)->all();

		$field_names = $fields->pluck('field_label');

		$fields->delete();
		ee('CP/Alert')->makeInline('fields')
			->asSuccess()
			->withTitle(lang('success'))
			->addToBody(lang('fields_removed_desc'))
			->addToBody($field_names)
			->defer();

		foreach ($field_names as $field_name)
		{
			ee()->logger->log_action(sprintf(lang('removed_field'), '<b>' . $field_name . '</b>'));
		}
	}
}

// EOF

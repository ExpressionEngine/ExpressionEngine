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
use EllisLab\ExpressionEngine\Model\Channel\ChannelFieldGroup;

/**
 * Channel\Fields\Groups Controller
*/
class Groups extends AbstractFieldsController {

	public function __construct()
	{
		parent::__construct();

		if ( ! ee()->cp->allowed_group_any(
			'can_create_channel_fields',
			'can_edit_channel_fields',
			'can_delete_channel_fields'
		))
		{
			show_error(lang('unauthorized_access'), 403);
		}

		ee()->lang->loadfile('admin');
		ee()->lang->loadfile('admin_content');
	}

	public function create()
	{
		if ( ! ee()->cp->allowed_group('can_create_channel_fields'))
		{
			show_error(lang('unauthorized_access'), 403);
		}

		ee()->view->cp_breadcrumbs = array(
			ee('CP/URL')->make('fields')->compile() => lang('field_manager'),
		);

		$this->generateSidebar();

		$vars = array(
			'ajax_validate' => TRUE,
			'base_url' => ee('CP/URL')->make('fields/groups/create'),
			'sections' => $this->form(),
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
		);

		if (AJAX_REQUEST)
		{
			unset($vars['buttons'][2]);
		}

		if ( ! empty($_POST))
		{
			$field_group = $this->setWithPost(ee('Model')->make('ChannelFieldGroup'));
			$result = $field_group->validate();

			if (isset($_POST['ee_fv_field']) && $response = $this->ajaxValidation($result))
			{
			    return $response;
			}

			if ($result->isValid())
			{
				$field_group->save();

				ee('CP/Alert')->makeInline('shared-form')
					->asSuccess()
					->withTitle(lang('create_field_group_success'))
					->addToBody(sprintf(lang('create_field_group_success_desc'), $field_group->group_name))
					->defer();

				if (AJAX_REQUEST)
				{
					return ['saveId' => $field_group->getId()];
				}

				if (ee('Request')->post('submit') == 'save_and_new')
				{
					ee()->functions->redirect(ee('CP/URL')->make('fields/groups/create'));
				}
				elseif (ee()->input->post('submit') == 'save_and_close')
				{
					ee()->functions->redirect(ee('CP/URL')->make('fields'));
				}
				else
				{
					ee()->functions->redirect(ee('CP/URL')->make('fields/groups/edit/'.$field_group->getId()));
				}
			}
			else
			{
				$vars['errors'] = $result;
				ee('CP/Alert')->makeInline('shared-form')
					->asIssue()
					->withTitle(lang('create_field_group_error'))
					->addToBody(lang('create_field_group_error_desc'))
					->now();
			}
		}

		ee()->view->cp_page_title = lang('create_field_group');

		if (AJAX_REQUEST)
		{
			return ee()->cp->render('_shared/form', $vars);
		}

		ee()->cp->render('settings/form', $vars);
	}

	public function edit($id)
	{
		if ( ! ee()->cp->allowed_group('can_edit_channel_fields'))
		{
			show_error(lang('unauthorized_access'), 403);
		}

		$field_group = ee('Model')->get('ChannelFieldGroup', $id)->first();

		if ( ! $field_group)
		{
			show_404();
		}

		ee()->view->cp_breadcrumbs = array(
			ee('CP/URL')->make('fields')->compile() => lang('field_manager'),
		);

		$this->generateSidebar($id);

		$vars = array(
			'ajax_validate' => TRUE,
			'base_url' => ee('CP/URL')->make('fields/groups/edit/' . $id),
			'sections' => $this->form($field_group),
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
		);

		if ( ! empty($_POST))
		{
			$field_group = $this->setWithPost($field_group);
			$result = $field_group->validate();

			if ($response = $this->ajaxValidation($result))
			{
			    return $response;
			}

			if ($result->isValid())
			{
				$field_group->save();
				$field_group->onAfterUpdate([]);

				ee('CP/Alert')->makeInline('shared-form')
					->asSuccess()
					->withTitle(lang('edit_field_group_success'))
					->addToBody(sprintf(lang('edit_field_group_success_desc'), $field_group->group_name))
					->defer();

				if (ee('Request')->post('submit') == 'save_and_new')
				{
					ee()->functions->redirect(ee('CP/URL')->make('fields/groups/create'));
				}
				elseif (ee()->input->post('submit') == 'save_and_close')
				{
					ee()->functions->redirect(ee('CP/URL')->make('fields'));
				}
				else
				{
					ee()->functions->redirect(ee('CP/URL')->make('fields/groups/edit/'.$field_group->getId()));
				}
			}
			else
			{
				$vars['errors'] = $result;
				ee('CP/Alert')->makeInline('shared-form')
					->asIssue()
					->withTitle(lang('edit_field_group_error'))
					->addToBody(lang('edit_field_group_error_desc'))
					->now();
			}
		}

		ee()->view->cp_page_title = lang('edit_field_group');

		ee()->cp->render('settings/form', $vars);
	}

	private function setWithPost(ChannelFieldGroup $field_group)
	{
		$field_group->site_id = ($field_group->site_id) ?: 0;
		$field_group->set($_POST);
		$field_group->ChannelFields = ee('Model')->get('ChannelField', ee()->input->post('channel_fields'))->all();
		return $field_group;
	}

	private function form(ChannelFieldGroup $field_group = NULL)
	{
		if ( ! $field_group)
		{
			$field_group = ee('Model')->make('ChannelFieldGroup');
            $field_group->ChannelFields = NULL;
		}

		// If it's an AJAX request, we're probably in a modal; we currently
		// can't open a modal from a modal, lest inception
		$should_allow_field_creation = ! AJAX_REQUEST;

		$add_fields_button = NULL;
		if ($should_allow_field_creation)
		{
			$add_fields_button = [
				'text' => 'add_field',
				'rel' => 'add_new'
			];
		}

		$sections = array(
			array(
				array(
					'title' => 'name',
					'desc' => '',
					'fields' => array(
						'group_name' => array(
							'type' => 'text',
							'value' => $field_group->group_name,
							'required' => TRUE
						)
					)
				),
				array(
					'title' => 'fields',
					'desc' => 'fields_assign_to_group',
					'button' => $add_fields_button,
					'fields' => array(
						'channel_fields' => array(
							'type' => 'html',
							'content' => $this->renderFieldsField($field_group, $should_allow_field_creation)
						)
					)
				),
			)
		);

		$fieldtypes = ee('Model')->get('Fieldtype')
			->fields('name')
			->all();

		// Call fieldtypes' display_settings methods to load any needed JS
		foreach ($fieldtypes as $fieldtype)
		{
			$dummy_field = ee('Model')->make('ChannelField');
			$dummy_field->field_type = $fieldtype->name;
			$dummy_field->getSettingsForm();
		}

		ee()->javascript->set_global([
			'fieldManager.fields.createUrl' => ee('CP/URL')->make('fields/create')->compile(),
			'fieldManager.fields.fieldUrl' => ee('CP/URL')->make('fields/groups/render-fields-field')->compile()
		]);

		ee()->cp->add_js_script('plugin', 'ee_url_title');
		ee()->cp->add_js_script('file', 'cp/fields/field_manager');

		return $sections;
	}

	/**
	 * Renders the Field Groups selection form for the channel create/edit form
	 *
	 * @param Channel $channel A Channel entity, optional
	 * @return string HTML
	 */
	public function renderFieldsField($field_group = NULL, $allow_add = TRUE)
	{
		$fields = ee('Model')->get('ChannelField')
			->fields('field_label', 'field_name')
			->filter('site_id', 'IN', [ee()->config->item('site_id'), 0])
			->order('field_label')
			->all();

		$custom_field_options = $fields->map(function($field) {
			return [
				'label' => $field->field_label,
				'value' => $field->getId(),
				'instructions' => LD.$field->field_name.RD
			];
		});

		$selected = ee('Request')->post('channel_fields') ?: [];

		if ($field_group)
		{
			$selected = $field_group->ChannelFields->pluck('field_id');
		}

		$no_results = ['text' => sprintf(lang('no_found'), lang('fields'))];

		if ($allow_add)
		{
			$no_results['link_text'] = 'add_new';
			$no_results['link_href'] = ee('CP/URL')->make('fields/groups/create');
		}

		return ee('View')->make('ee:_shared/form/fields/select')->render([
			'field_name' => 'channel_fields',
			'choices'    => $custom_field_options,
			'value'      => $selected,
			'multi'      => TRUE,
			'no_results' => $no_results
		]);
	}

	public function remove()
	{
		if ( ! ee()->cp->allowed_group('can_delete_channel_fields'))
		{
			show_error(lang('unauthorized_access'), 403);
		}

		$group_id = ee()->input->post('content_id');

		$field_groups = ee('Model')->get('ChannelFieldGroup', $group_id)->all();

		$group_names = $field_groups->pluck('group_name');

		$field_groups->delete();
		ee('CP/Alert')->makeInline('field-groups')
			->asSuccess()
			->withTitle(lang('success'))
			->addToBody(lang('field_groups_removed_desc'))
			->addToBody($group_names)
			->defer();

		ee()->functions->redirect(ee('CP/URL')->make('fields', ee()->cp->get_url_state()));
	}
}

// EOF

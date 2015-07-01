<?php

namespace EllisLab\ExpressionEngine\Controllers\Channels\Fields;

use EllisLab\ExpressionEngine\Library\CP\Table;
use EllisLab\ExpressionEngine\Controllers\Channels\AbstractChannels as AbstractChannelsController;
use EllisLab\ExpressionEngine\Module\Channel\Model\ChannelField;

/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2015, EllisLab, Inc.
 * @license		http://ellislab.com/expressionengine/user-guide/license.html
 * @link		http://ellislab.com
 * @since		Version 3.0
 * @filesource
 */

// ------------------------------------------------------------------------

/**
 * ExpressionEngine CP Channel\Fields\Fields Class
 *
 * @package		ExpressionEngine
 * @subpackage	Control Panel
 * @category	Control Panel
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */
class Fields extends AbstractChannelsController {

	public function __construct()
	{
		parent::__construct();

		if ( ! ee()->cp->allowed_group(
			'can_access_admin',
			'can_admin_channels',
			'can_access_content_prefs'
		))
		{
			show_error(lang('unauthorized_access'));
		}

		ee()->lang->loadfile('admin');
		ee()->lang->loadfile('admin_content');
	}

	public function fields()
	{
		if (ee()->input->post('bulk_action') == 'remove')
		{
			$this->remove(ee()->input->post('selection'));
			ee()->functions->redirect(ee('CP/URL', 'channels/fields'));
		}

		$base_url = ee('CP/URL', 'channels/fields');

		$vars = array(
			'create_url' => ee('CP/URL', 'channels/fields/create')
		);

		$groups = ee('Model')->get('ChannelFieldGroup')
			->filter('site_id', ee()->config->item('site_id'))
			->all();

		$group_filter = ee('Filter')->make('filter_by_group', 'filter_by_group', $groups->getDictionary('group_id', 'group_name'))
			->disableCustomValue();

		$filters = ee('Filter')->add($group_filter);

		$fields = ee('Model')->get('ChannelField')
			->filter('site_id', ee()->config->item('site_id'));

		if ( ! is_null($group_filter->value()))
		{
			$fields->filter('group_id', $group_filter->value());
		}

		$vars['filters'] = $filters->render($base_url);

		$base_url->addQueryStringVariables($filters->values());

		$table = ee('CP/Table');
		$table->setColumns(
			array(
				'id',
				'name',
				'short_name' => array(
					'encode' => FALSE
				),
				'type',
				'manage' => array(
					'type'	=> Table::COL_TOOLBAR
				),
				array(
					'type'	=>
						Table::COL_CHECKBOX
				)
			)
		);
		$table->setNoResultsText('no_fields', 'create_new', $vars['create_url']);

		$data = array();

		$field_id = ee()->session->flashdata('field_id');

		foreach ($fields->all() as $field)
		{
			$column = array(
				$field->field_id,
				$field->field_label,
				'<var>{' . htmlentities($field->field_name, ENT_QUOTES) . '}</var>',
				$field->field_type,
				array('toolbar_items' => array(
					'edit' => array(
						'href' => ee('CP/URL', 'channels/fields/edit/' . $field->field_id),
						'title' => lang('edit')
					)
				)),
				array(
					'name' => 'selection[]',
					'value' => $field->field_id,
					'data' => array(
						'confirm' => lang('field') . ': <b>' . htmlentities($field->field_label, ENT_QUOTES) . '</b>'
					)
				)
			);

			$attrs = array();

			if ($field_id && $field->field_id == $field_id)
			{
				$attrs = array('class' => 'selected');
			}

			$data[] = array(
				'attrs'		=> $attrs,
				'columns'	=> $column
			);
		}

		$table->setData($data);

		$vars['table'] = $table->viewData($base_url);

		$vars['pagination'] = ee('CP/Pagination', $vars['table']['total_rows'])
			->perPage($vars['table']['limit'])
			->currentPage($vars['table']['page'])
			->render($vars['table']['base_url']);

		ee()->javascript->set_global('lang.remove_confirm', lang('field') . ': <b>### ' . lang('fields') . '</b>');
		ee()->cp->add_js_script(array(
			'file' => array(
				'cp/v3/confirm_remove',
			),
		));

		ee()->view->cp_page_title = lang('custom_fields');

		ee()->cp->render('channels/fields/index', $vars);
	}

	public function create()
	{
		ee()->view->cp_breadcrumbs = array(
			ee('CP/URL', 'channels/fields')->compile() => lang('custom_fields'),
		);

		$vars = array(
			'ajax_validate' => TRUE,
			'base_url' => ee('CP/URL', 'channels/fields/create'),
			'sections' => $this->form(),
			'save_btn_text' => 'btn_create_field',
			'save_btn_text_working' => 'btn_saving',
			'form_hidden' => array(
				'field_id' => NULL,
				'site_id' => ee()->config->item('site_id')
			),
		);

		if (AJAX_REQUEST)
		{
			ee()->form_validation->run_ajax();
			exit;
		}
		elseif (ee()->form_validation->run() !== FALSE)
		{
			$field = $this->saveWithPost(ee('Model')->make('ChannelField'));

			ee()->session->set_flashdata('field_id', $field->field_id);

			ee('Alert')->makeInline('shared-form')
				->asSuccess()
				->withTitle(lang('create_field_success'))
				->addToBody(sprintf(lang('create_field_success_desc'), $field->field_label))
				->defer();

			ee()->functions->redirect(ee('CP/URL', 'channels/fields'));
		}
		elseif (ee()->form_validation->errors_exist())
		{
			ee('Alert')->makeInline('shared-form')
				->asIssue()
				->withTitle(lang('create_field_error'))
				->addToBody(lang('create_field_error_desc'))
				->now();
		}

		ee()->view->cp_page_title = lang('create_field');

		ee()->cp->render('settings/form', $vars);
	}

	public function edit($id)
	{
		$field = ee('Model')->get('ChannelField', $id)
			->filter('site_id', ee()->config->item('site_id'))
			->first();

		if ( ! $field)
		{
			show_404();
		}

		ee()->view->cp_breadcrumbs = array(
			ee('CP/URL', 'channels/fields')->compile() => lang('custom_fields'),
		);

		$vars = array(
			'ajax_validate' => TRUE,
			'base_url' => ee('CP/URL', 'channels/fields/edit/' . $id),
			'sections' => $this->form($field),
			'save_btn_text' => 'btn_edit_field',
			'save_btn_text_working' => 'btn_saving',
			'form_hidden' => array(
				'field_id' => $id,
				'site_id' => ee()->config->item('site_id')
			),
		);

		if (AJAX_REQUEST)
		{
			ee()->form_validation->run_ajax();
			exit;
		}
		elseif (ee()->form_validation->run() !== FALSE)
		{
			$field = $this->saveWithPost($field);

			ee('Alert')->makeInline('shared-form')
				->asSuccess()
				->withTitle(lang('edit_field_success'))
				->addToBody(sprintf(lang('edit_field_success_desc'), $field->field_label))
				->defer();

			ee()->functions->redirect(ee('CP/URL', 'channels/fields/edit/' . $id));
		}
		elseif (ee()->form_validation->errors_exist())
		{
			ee('Alert')->makeInline('shared-form')
				->asIssue()
				->withTitle(lang('edit_field_error'))
				->addToBody(lang('edit_field_error_desc'))
				->now();
		}

		ee()->view->cp_page_title = lang('edit_field');

		ee()->cp->render('settings/form', $vars);
	}

	private function saveWithPost(ChannelField $field)
	{
		$field->site_id = ee()->config->item('site_id');
		$field->field_type = $_POST['field_type'];
		$field->group_id = ($field->group_id) ?: 0;
		$field->field_list_items = ($field->field_list_items) ?: '';
		$field->field_order = ($field->field_order) ?: 0;

		$field->set($_POST);
		$field->save();

		$field_data = $_POST;
		$field_data['field_id'] = $field->field_id;
		$field_data['group_id'] = $field->group_id;

		ee()->load->library('api');
		ee()->legacy_api->instantiate('channel_fields');
		ee()->api_channel_fields->update_field($field_data);

		return $field;
	}

	private function form(ChannelField $field = NULL)
	{
		if ( ! $field)
		{
			$field = ee('Model')->make('ChannelField');
		}

		$fieldtypes = ee('Model')->get('Fieldtype')
			->order('name')
			->all();

		$fieldtype_choices = array();

		foreach ($fieldtypes as $fieldtype)
		{
			$info = ee('App')->get($fieldtype->name);
			$fieldtype_choices[$fieldtype->name] = $info->getName();
		}

		$field->field_type = ($field->field_type) ?: 'text';

		$sections = array(
			array(
				array(
					'title' => 'type',
					'desc' => '',
					'fields' => array(
						'field_type' => array(
							'type' => 'select',
							'choices' => $fieldtype_choices,
							'value' => $field->field_type
						)
					)
				),
				array(
					'title' => 'label',
					'desc' => 'label_desc',
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
					'desc' => 'short_name_desc',
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
		if ( ! empty($field_options))
		{
			$sections = array_merge($sections, $field_options);
		}


		ee()->form_validation->set_rules(array(
			array(
				'field' => 'field_label',
				'label' => 'lang:label',
				'rules' => 'required'
			),
			array(
				'field' => 'field_name',
				'label' => 'lang:name',
				'rules' => 'required'
			),
		));

		return $sections;
	}

	private function remove($field_ids)
	{
		if ( ! is_array($field_ids))
		{
			$field_ids = array($field_ids);
		}

		$fields = ee('Model')->get('ChannelField', $field_ids)
			->filter('site_id', ee()->config->item('site_id'))
			->all();

		$field_names = $fields->pluck('field_label');

		$fields->delete();
		ee('Alert')->makeInline('fields')
			->asSuccess()
			->withTitle(lang('success'))
			->addToBody(lang('fields_removed_desc'))
			->addToBody($field_names)
			->defer();
	}

}

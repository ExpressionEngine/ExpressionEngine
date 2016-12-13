<?php

namespace EllisLab\ExpressionEngine\Controller\Channels\Fields;

use EllisLab\ExpressionEngine\Library\CP\Table;
use EllisLab\ExpressionEngine\Controller\Channels\AbstractChannels as AbstractChannelsController;
use EllisLab\ExpressionEngine\Model\Channel\ChannelField;

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
 * ExpressionEngine CP Channel\Fields\Fields Class
 *
 * @package		ExpressionEngine
 * @subpackage	Control Panel
 * @category	Control Panel
 * @author		EllisLab Dev Team
 * @link		https://ellislab.com
 */
class Fields extends AbstractChannelsController {

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

		$this->generateSidebar('field');

		ee()->lang->loadfile('admin');
		ee()->lang->loadfile('admin_content');
	}

	public function fields($group_id)
	{
		if (ee()->input->post('bulk_action') == 'remove')
		{
			$this->remove(ee()->input->post('selection'));
			ee()->functions->redirect(ee('CP/URL', 'channels/fields/'.$group_id));
		}

		$group = ee('Model')->get('ChannelFieldGroup')
			->filter('group_id', $group_id)
			->first();

		$base_url = ee('CP/URL', 'channels/fields/'.$group_id);

		$vars = array(
			'create_url' => ee('CP/URL', 'channels/fields/create/' . $group->group_id),
			'group_id'   => $group->group_id
		);

		$fields = ee('Model')->get('ChannelField')
			->filter('site_id', ee()->config->item('site_id'))
			->filter('group_id', $group_id);

		$table = $this->buildTableFromChannelFieldsQuery($fields, array(), ee()->cp->allowed_group('can_delete_channel_fields'));
		$table->setNoResultsText('no_fields', 'create_new', ee('CP/URL')->make('channels/fields/create/' . $group_id));

		$vars['table'] = $table->viewData($base_url);
		$vars['show_create_button'] = ee()->cp->allowed_group('can_create_channel_fields');

		$vars['pagination'] = ee('CP/Pagination', $vars['table']['total_rows'])
			->perPage($vars['table']['limit'])
			->currentPage($vars['table']['page'])
			->render($vars['table']['base_url']);

		ee()->javascript->set_global('lang.remove_confirm', lang('field') . ': <b>### ' . lang('fields') . '</b>');
		ee()->cp->add_js_script(array(
			'file' => array(
				'cp/confirm_remove',
			),
		));

		ee()->cp->set_breadcrumb(ee('CP/URL')->make('channels/fields/groups'), lang('field_groups'));
		ee()->view->cp_page_title = sprintf(lang('custom_fields_for'), $group->group_name);

		ee()->cp->render('channels/fields/index', $vars);
	}

	public function create($group_id)
	{
		if ( ! ee()->cp->allowed_group('can_create_channel_fields'))
		{
			show_error(lang('unauthorized_access'), 403);
		}

		ee()->view->cp_breadcrumbs = array(
			ee('CP/URL')->make('channels/fields/groups')->compile() => lang('field_groups'),
			ee('CP/URL')->make('channels/fields/' . $group_id)->compile() => lang('fields'),
		);

		$errors = NULL;

		if ( ! empty($_POST))
		{
			$field = $this->setWithPost(
				ee('Model')->make('ChannelField', compact($group_id))
			);
			$result = $field->validate();

			if ($response = $this->ajaxValidation($result))
			{
			    return $response;
			}

			if ($result->isValid())
			{
				$field->save();

				ee()->session->set_flashdata('field_id', $field->field_id);

				ee('CP/Alert')->makeInline('shared-form')
					->asSuccess()
					->withTitle(lang('create_field_success'))
					->addToBody(sprintf(lang('create_field_success_desc'), $field->field_label))
					->defer();

				ee()->functions->redirect(ee('CP/URL')->make('channels/fields/'.$group_id));
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
			'base_url' => ee('CP/URL')->make('channels/fields/create/' . $group_id),
			'sections' => $this->form(),
			'save_btn_text' => sprintf(lang('btn_save'), lang('field')),
			'save_btn_text_working' => 'btn_saving',
			'form_hidden' => array(
				'field_id' => NULL,
			'group_id' => $group_id,
				'site_id' => ee()->config->item('site_id')
			),
		);

		ee()->view->cp_page_title = lang('create_field');

		ee()->cp->add_js_script('plugin', 'ee_url_title');

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
			->filter('site_id', ee()->config->item('site_id'))
			->first();

		if ( ! $field)
		{
			show_404();
		}

		ee()->view->cp_breadcrumbs = array(
			ee('CP/URL')->make('channels/fields/groups')->compile() => lang('field_groups'),
			ee('CP/URL')->make('channels/fields/' . $field->group_id)->compile() => lang('fields'),
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
					ee()->db->where('field_ft_'.$field->field_id. ' IS NOT NULL', NULL, FALSE);
					ee()->db->update(
						'channel_data',
						array('field_ft_'.$field->field_id => $field->field_fmt)
					);
				}

				ee('CP/Alert')->makeInline('shared-form')
					->asSuccess()
					->withTitle(lang('edit_field_success'))
					->addToBody(sprintf(lang('edit_field_success_desc'), $field->field_label))
					->defer();

				ee()->functions->redirect(ee('CP/URL')->make('channels/fields/' . $field->group_id));
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
			'base_url' => ee('CP/URL')->make('channels/fields/edit/' . $id),
			'sections' => $this->form($field),
			'save_btn_text' => sprintf(lang('btn_save'), lang('field')),
			'save_btn_text_working' => 'btn_saving',
			'form_hidden' => array(
				'field_id' => $id,
				'group_id' => $field->group_id,
				'site_id' => ee()->config->item('site_id')
			),
		);

		ee()->view->cp_page_title = lang('edit_field');

		ee()->cp->render('settings/form', $vars);
	}

	private function setWithPost(ChannelField $field)
	{
		$field->site_id = ee()->config->item('site_id');
		$field->group_id = ($field->group_id) ?: 0;
		$field->field_list_items = ($field->field_list_items) ?: '';
		$field->field_order = ($field->field_order) ?: 0;

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
							'type' => 'select',
							'choices' => $fieldtype_choices,
							'group_toggle' => $fieldtypes->getDictionary('name', 'name'),
							'value' => $field->field_type
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

			$dummy_field = ee('Model')->make('ChannelField');
			$dummy_field->field_type = $fieldtype->name;
			$field_options = $dummy_field->getSettingsForm();

			if (is_array($field_options) && ! empty($field_options))
			{
				$sections = array_merge($sections, $field_options);
			}
		}

		ee()->cp->add_js_script(array(
			'file' => array(
				'cp/form_group',
				'cp/channel/fields'
			),
		));

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

		$fields = ee('Model')->get('ChannelField', $field_ids)
			->filter('site_id', ee()->config->item('site_id'))
			->all();

		$field_names = $fields->pluck('field_label');

		$fields->delete();
		ee('CP/Alert')->makeInline('fields')
			->asSuccess()
			->withTitle(lang('success'))
			->addToBody(lang('fields_removed_desc'))
			->addToBody($field_names)
			->defer();
	}

}

// EOF

<?php

namespace EllisLab\ExpressionEngine\Controllers\Channels\Fields;

use EllisLab\ExpressionEngine\Library\CP\Pagination;
use EllisLab\ExpressionEngine\Library\CP\Table;
use EllisLab\ExpressionEngine\Controllers\Channels\AbstractChannels as AbstractChannelsController;
use EllisLab\ExpressionEngine\Module\Channel\Model\ChannelFieldGroup;

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
 * ExpressionEngine CP Channel\Fields\Groups Class
 *
 * @package		ExpressionEngine
 * @subpackage	Control Panel
 * @category	Control Panel
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */
class Groups extends AbstractChannelsController {

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

	public function groups()
	{
		if (ee()->input->post('bulk_action') == 'remove')
		{
			$this->remove(ee()->input->post('selection'));
			ee()->functions->redirect(ee('CP/URL', 'channels/fields/groups/groups'));
		}

		$groups = ee('Model')->get('ChannelFieldGroup')
			->filter('site_id', ee()->config->item('site_id'))
			->all();

		$vars = array(
			'create_url' => ee('CP/URL', 'channels/fields/groups/create')
		);

		$table = ee('CP/Table');
		$table->setColumns(
			array(
				'group_name',
				'manage' => array(
					'type'	=> Table::COL_TOOLBAR
				),
				array(
					'type'	=>
						Table::COL_CHECKBOX
				)
			)
		);
		$table->setNoResultsText('no_group_groups', 'create_new', $vars['create_url']);

		$data = array();

		$group_id = ee()->session->flashdata('group_id');

		foreach ($groups as $group)
		{
			$column = array(
				$group->group_name,
				array('toolbar_items' => array(
					'edit' => array(
						'href' => cp_url('channels/fields/groups/edit/' . $group->group_id),
						'title' => lang('edit')
					)
				)),
				array(
					'name' => 'selection[]',
					'value' => $group->group_id,
					'data' => array(
						'confirm' => lang('group') . ': <b>' . htmlentities($group->group_name, ENT_QUOTES) . '</b>'
					)
				)
			);

			$attrs = array();

			if ($group_id && $group->group_id == $group_id)
			{
				$attrs = array('class' => 'selected');
			}

			$data[] = array(
				'attrs'		=> $attrs,
				'columns'	=> $column
			);
		}

		$table->setData($data);

		$vars['table'] = $table->viewData(ee('CP/URL', 'channels/fields/groups'));

		$pagination = new Pagination(
			$vars['table']['limit'],
			$vars['table']['total_rows'],
			$vars['table']['page']
		);
		$vars['pagination'] = $pagination->cp_links($vars['table']['base_url']);

		ee()->javascript->set_global('lang.remove_confirm', lang('group') . ': <b>### ' . lang('groups') . '</b>');
		ee()->cp->add_js_script(array(
			'file' => array(
				'cp/v3/confirm_remove',
			),
		));

		ee()->view->cp_page_title = lang('field_groups');
		ee()->view->cp_page_title_desc = lang('field_groups_desc');

		ee()->cp->render('channels/fields/groups/index', $vars);
	}

	public function create()
	{
		ee()->view->cp_breadcrumbs = array(
			ee('CP/URL', 'channels/fields/groups')->compile() => lang('field_groups'),
		);

		$vars = array(
			'ajax_validate' => TRUE,
			'base_url' => ee('CP/URL', 'channels/fields/groups/create'),
			'sections' => $this->form(),
			'save_btn_text' => 'btn_create_field_group',
			'save_btn_text_working' => 'btn_saving'
		);

		if (AJAX_REQUEST)
		{
			ee()->form_validation->run_ajax();
			exit;
		}
		elseif (ee()->form_validation->run() !== FALSE)
		{
			$field_group = $this->saveWithPost(ee('Model')->make('ChannelFieldGroup'));

			ee()->session->set_flashdata('group_id', $field_group->group_id);

			ee('Alert')->makeInline('shared-form')
				->asSuccess()
				->withTitle(lang('create_field_group_success'))
				->addToBody(sprintf(lang('create_field_group_success_desc'), $field_group->group_name))
				->defer();

			ee()->functions->redirect(ee('CP/URL', 'channels/fields/groups'));
		}
		elseif (ee()->form_validation->errors_exist())
		{
			ee('Alert')->makeInline('shared-form')
				->asIssue()
				->withTitle(lang('create_field_group_error'))
				->addToBody(lang('create_field_group_error_desc'))
				->now();
		}

		ee()->view->cp_page_title = lang('create_field_group');

		ee()->cp->render('settings/form', $vars);
	}

	public function edit($id)
	{
		$field_group = ee('Model')->get('ChannelFieldGroup', $id)->first();

		if ( ! $field_group)
		{
			show_error(lang('unauthorized_access'));
		}

		ee()->view->cp_breadcrumbs = array(
			ee('CP/URL', 'channels/fields/groups')->compile() => lang('field_groups'),
		);

		$vars = array(
			'ajax_validate' => TRUE,
			'base_url' => ee('CP/URL', 'channels/fields/groups/edit/' . $id),
			'sections' => $this->form($field_group),
			'save_btn_text' => 'btn_edit_field_group',
			'save_btn_text_working' => 'btn_saving'
		);

		if (AJAX_REQUEST)
		{
			ee()->form_validation->run_ajax();
			exit;
		}
		elseif (ee()->form_validation->run() !== FALSE)
		{
			$this->saveWithPost($field_group);

			ee('Alert')->makeInline('shared-form')
				->asSuccess()
				->withTitle(lang('edit_field_group_success'))
				->addToBody(sprintf(lang('edit_field_group_success_desc'), $field_group->group_name))
				->defer();

			ee()->functions->redirect(ee('CP/URL', 'channels/fields/groups/edit/' . $id));
		}
		elseif (ee()->form_validation->errors_exist())
		{
			ee('Alert')->makeInline('shared-form')
				->asIssue()
				->withTitle(lang('edit_field_group_error'))
				->addToBody(lang('edit_field_group_error_desc'))
				->now();
		}

		ee()->view->cp_page_title = lang('edit_field_group');

		ee()->cp->render('settings/form', $vars);
	}

	private function saveWithPost(ChannelFieldGroup $field_group)
	{
		$custom_fields = ee('Model')->get('ChannelField', ee()->input->post('custom_fields'))
			->filter('site_id', ee()->config->item('site_id'))
			->all();

		$field_group->group_name = ee()->input->post('group_name');
		$field_group->ChannelFields = $custom_fields;
		$field_group->save();
	}

	private function form(ChannelFieldGroup $field_group = NULL)
	{
		if ( ! $field_group)
		{
			$field_group = ee('Model')->make('ChannelFieldGroup');
		}

		$custom_fields_options = array();
		$disabled_custom_fields_options = array();

		$fields = ee('Model')->get('ChannelField')
			->filter('site_id', ee()->config->item('site_id'))
			->all();

		foreach ($fields as $field)
		{
			$display = $field->field_label;

			$assigned_to = $field->ChannelFieldGroup->first();

			if ($assigned_to
				&& $assigned_to->group_id != $field_group->group_id)
			{
				$disabled_custom_fields_options[] = $field->field_id;

				$display =  '<s>' . $display . '</s>';
				$display .= ' <i>&mdash; ' . lang('assigned_to');
				$display .= ' <a href="' . ee('CP/URL', 'channels/fields/groups/edit/' . $assigned_to->group_id) . '">' . $assigned_to->group_name . '</a></i>';
			}

			$custom_fields_options[$field->field_id] = $display;
		}

		$custom_fields_value = array();

		$selected_fields = $field_group->ChannelFields->all();
		$custom_fields_value = ($selected_fields) ? $selected_fields->pluck('field_id') : array();

		// Alert to show only for new channels
		$alert = ee('Alert')->makeInline('permissions-warn')
			->asWarning()
			->addToBody(lang('create_field_group_warning'))
			->addToBody(sprintf(lang('create_field_group_warning2'), ee('CP/URL', 'channels/fields/create')))
			->cannotClose()
			->render();

		$sections = array(
			array(
				$alert,
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
					'title' => 'custom_fields',
					'desc' => 'custom_fields_desc',
					'fields' => array(
						'custom_fields' => array(
							'type' => 'checkbox',
							'choices' => $custom_fields_options,
							'disabled_choices' => $disabled_custom_fields_options,
							'value' => $custom_fields_value,
							'no_results' => array(
								'text' => 'custom_fields_not_found',
								'link_text' => 'create_new_field',
								'link_href' => ee('CP/URL', 'channels/fields/create')->compile()
							)
						)
					)
				)
			)
		);

		ee()->form_validation->set_rules(array(
			array(
				'field' => 'group_name',
				'label' => 'lang:name',
				'rules' => 'required|callback__field_group_name_checks[' . $field_group->group_id . ']'
			)
		));

		return $sections;
	}

	/**
	  *	 Check Field Group Name
	  */
	public function _field_group_name_checks($str, $group_id)
	{
		if ( ! preg_match("#^[a-zA-Z0-9_\-/\s]+$#i", $str))
		{
			ee()->lang->loadfile('admin');
			ee()->form_validation->set_message('_field_group_name_checks', lang('illegal_characters'));
			return FALSE;
		}

		$group = ee('Model')->get('ChannelFieldGroup')
			->filter('site_id', ee()->config->item('site_id'))
			->filter('group_name', $str);

		if ($group_id)
		{
			$group->filter('group_id', '!=', $group_id);
		}

		if ($group->count())
		{
			ee()->form_validation->set_message('_field_group_name_checks', lang('taken_field_group_name'));
			return FALSE;
		}

		return TRUE;
	}


	private function remove($group_ids)
	{

	}

}
<?php

namespace EllisLab\ExpressionEngine\Controller\Channels\Fields;

use EllisLab\ExpressionEngine\Library\CP\Table;
use EllisLab\ExpressionEngine\Controller\Channels\AbstractChannels as AbstractChannelsController;
use EllisLab\ExpressionEngine\Model\Channel\ChannelFieldGroup;

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
 * ExpressionEngine CP Channel\Fields\Groups Class
 *
 * @package		ExpressionEngine
 * @subpackage	Control Panel
 * @category	Control Panel
 * @author		EllisLab Dev Team
 * @link		https://ellislab.com
 */
class Groups extends AbstractChannelsController {

	public function __construct()
	{
		parent::__construct();

		if ( ! ee()->cp->allowed_group_any(
			'can_create_channel_fields',
			'can_edit_channel_fields',
			'can_delete_channel_fields'
		))
		{
			show_error(lang('unauthorized_access'));
		}

		$this->generateSidebar('field');

		ee()->lang->loadfile('admin');
		ee()->lang->loadfile('admin_content');
	}

	public function groups()
	{
		if (ee()->input->post('bulk_action') == 'remove')
		{
			$this->remove(ee()->input->post('selection'));
			ee()->functions->redirect(ee('CP/URL')->make('channels/fields/groups/groups'));
		}

		$groups = ee('Model')->get('ChannelFieldGroup')
			->filter('site_id', ee()->config->item('site_id'));

		$vars = array(
			'create_url' => ee('CP/URL')->make('channels/fields/groups/create')
		);

		$table = $this->buildTableFromChannelGroupsQuery($groups, array(), ee()->cp->allowed_group('can_delete_channel_fields'));

		$vars['table'] = $table->viewData(ee('CP/URL')->make('channels/fields/groups'));
		$vars['show_create_button'] = ee()->cp->allowed_group('can_create_channel_fields');

		$vars['pagination'] = ee('CP/Pagination', $vars['table']['total_rows'])
			->perPage($vars['table']['limit'])
			->currentPage($vars['table']['page'])
			->render($vars['table']['base_url']);

		ee()->javascript->set_global('lang.remove_confirm', lang('group') . ': <b>### ' . lang('groups') . '</b>');
		ee()->cp->add_js_script(array(
			'file' => array(
				'cp/confirm_remove',
			),
		));

		ee()->view->cp_page_title = lang('field_groups');
		ee()->view->cp_page_title_desc = lang('field_groups_desc');

		ee()->cp->render('channels/fields/groups/index', $vars);
	}

	public function create()
	{
		if ( ! ee()->cp->allowed_group('can_create_channel_fields'))
		{
			show_error(lang('unauthorized_access'));
		}

		ee()->view->cp_breadcrumbs = array(
			ee('CP/URL')->make('channels/fields/groups')->compile() => lang('field_groups'),
		);

		$vars = array(
			'ajax_validate' => TRUE,
			'base_url' => ee('CP/URL')->make('channels/fields/groups/create'),
			'sections' => $this->form(),
			'save_btn_text' => sprintf(lang('btn_save'), lang('field_group')),
			'save_btn_text_working' => 'btn_saving'
		);

		if ( ! empty($_POST))
		{
			$field_group = $this->setWithPost(ee('Model')->make('ChannelFieldGroup'));
			$field_group->site_id = ee()->config->item('site_id');
			$result = $field_group->validate();

			if ($response = $this->ajaxValidation($result))
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

				ee()->session->set_flashdata('group_id', $field_group->group_id);

				ee()->functions->redirect(ee('CP/URL')->make('channels/fields/groups'));
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

		ee()->cp->render('settings/form', $vars);
	}

	public function edit($id)
	{
		if ( ! ee()->cp->allowed_group('can_edit_channel_fields'))
		{
			show_error(lang('unauthorized_access'));
		}

		$field_group = ee('Model')->get('ChannelFieldGroup', $id)->first();

		if ( ! $field_group)
		{
			show_404();
		}

		ee()->view->cp_breadcrumbs = array(
			ee('CP/URL')->make('channels/fields/groups')->compile() => lang('field_groups'),
		);

		$vars = array(
			'ajax_validate' => TRUE,
			'base_url' => ee('CP/URL')->make('channels/fields/groups/edit/' . $id),
			'sections' => $this->form($field_group),
			'save_btn_text' => sprintf(lang('btn_save'), lang('field_group')),
			'save_btn_text_working' => 'btn_saving'
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

				ee('CP/Alert')->makeInline('shared-form')
					->asSuccess()
					->withTitle(lang('edit_field_group_success'))
					->addToBody(sprintf(lang('edit_field_group_success_desc'), $field_group->group_name))
					->defer();

				ee()->functions->redirect(ee('CP/URL', 'channels/fields/groups'));
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
		$field_group->group_name = ee()->input->post('group_name');
		return $field_group;
	}

	private function form(ChannelFieldGroup $field_group = NULL)
	{
		if ( ! $field_group)
		{
			$field_group = ee('Model')->make('ChannelFieldGroup');
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
				)
			)
		);

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
		if ( ! ee()->cp->allowed_group('can_delete_channel_fields'))
		{
			show_error(lang('unauthorized_access'));
		}

		if ( ! is_array($group_ids))
		{
			$group_ids = array($group_ids);
		}

		$field_groups = ee('Model')->get('ChannelFieldGroup', $group_ids)
			->filter('site_id', ee()->config->item('site_id'))
			->all();

		$group_names = $field_groups->pluck('group_name');

		$field_groups->delete();
		ee('CP/Alert')->makeInline('field-groups')
			->asSuccess()
			->withTitle(lang('success'))
			->addToBody(lang('field_groups_removed_desc'))
			->addToBody($group_names)
			->defer();
	}

}

// EOF

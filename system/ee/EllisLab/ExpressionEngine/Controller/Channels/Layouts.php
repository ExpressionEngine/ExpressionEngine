<?php

namespace EllisLab\ExpressionEngine\Controller\Channels;

use EllisLab\ExpressionEngine\Library\CP\Table;

use EllisLab\ExpressionEngine\Model\Channel\Display\DefaultChannelLayout;
use EllisLab\ExpressionEngine\Controller\Channels\AbstractChannels as AbstractChannelsController;
use EllisLab\ExpressionEngine\Model\Channel\Channel;
use EllisLab\ExpressionEngine\Library\Data\Collection;

/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2015, EllisLab, Inc.
 * @license		https://ellislab.com/expressionengine/user-guide/license.html
 * @link		http://ellislab.com
 * @since		Version 3.0
 * @filesource
 */

// ------------------------------------------------------------------------

/**
 * ExpressionEngine CP Channel\Layout Class
 *
 * @package		ExpressionEngine
 * @subpackage	Control Panel
 * @category	Control Panel
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */
class Layouts extends AbstractChannelsController {

	public function __construct()
	{
		parent::__construct();

		if ( ! ee()->cp->allowed_group('can_admin_channels'))
		{
			show_error(lang('unauthorized_access'));
		}

		ee()->lang->loadfile('content');

		$this->generateSidebar('channel');
	}

	public function layouts($channel_id)
	{
		if (ee()->input->post('bulk_action') == 'remove')
		{
			$this->remove(ee()->input->post('selection'));
			ee()->functions->redirect(ee('CP/URL', 'channels/layouts/' . $channel_id));
		}

		$channel = ee('Model')->get('Channel', $channel_id)
			->filter('site_id', ee()->config->item('site_id'))
			->first();

		if ( ! $channel)
		{
			show_error(lang('unauthorized_access'));
		}

		$vars['channel_id'] = $channel_id;
		$vars['create_url'] = ee('CP/URL', 'channels/layouts/create/' . $channel_id);

		$table = ee('CP/Table');
		$table->setColumns(
			array(
				'name',
				'member_group',
				'manage' => array(
					'type'	=> Table::COL_TOOLBAR
				),
				array(
					'type'	=>
						Table::COL_CHECKBOX
				)
			)
		);
		$table->setNoResultsText('no_layouts', 'create_new', $vars['create_url']);

		$data = array();

		$layout_id = ee()->session->flashdata('layout_id');

		foreach ($channel->ChannelLayouts as $layout)
		{
			$column = array(
				$layout->layout_name,
				implode(',', $layout->MemberGroups->pluck('group_title')),
				array('toolbar_items' => array(
					'edit' => array(
						'href' => ee('CP/URL', 'channels/layouts/edit/' . $layout->layout_id),
						'title' => lang('edit')
					)
				)),
				array(
					'name' => 'selection[]',
					'value' => $layout->layout_id,
					'data' => array(
						'confirm' => lang('layout') . ': <b>' . htmlentities($layout->layout_name, ENT_QUOTES) . '</b>'
					)
				)
			);

			$attrs = array();

			if ($layout_id && $layout->layout_id == $layout_id)
			{
				$attrs = array('class' => 'selected');
			}

			$data[] = array(
				'attrs'		=> $attrs,
				'columns'	=> $column
			);
		}

		$table->setData($data);

		$vars['table'] = $table->viewData(ee('CP/URL', 'channels/layout/' . $channel_id));

		$vars['pagination'] = ee('CP/Pagination', $vars['table']['total_rows'])
			->perPage($vars['table']['limit'])
			->currentPage($vars['table']['page'])
			->render($vars['table']['base_url']);

		ee()->javascript->set_global('lang.remove_confirm', lang('layout') . ': <b>### ' . lang('layouts') . '</b>');
		ee()->cp->add_js_script(array(
			'file' => array(
				'cp/v3/confirm_remove',
			),
		));

		ee()->view->cp_page_title = sprintf(lang('channel_form_layouts'), $channel->channel_title);
		ee()->cp->set_breadcrumb(ee('CP/URL', 'channels'), lang('channels'));

		ee()->cp->render('channels/layout/index', $vars);
	}

	public function create($channel_id)
	{
		ee()->view->header = NULL;
		ee()->view->left_nav = NULL;

		$channel = ee('Model')->get('Channel', $channel_id)
			->filter('site_id', ee()->config->item('site_id'))
			->first();

		if ( ! $channel)
		{
			show_error(lang('unauthorized_access'));
		}

		$entry = ee('Model')->make('ChannelEntry');
		$entry->Channel = $channel;

		$default_layout = new DefaultChannelLayout($channel_id, NULL);
		$channel_layout = ee('Model')->make('ChannelLayout');
		$field_layout = $default_layout->getLayout();

		foreach ($channel->CustomFields as $custom_field)
		{
			$field_layout[0]['fields'][] = array(
				'field' => $entry->getCustomFieldPrefix() . $custom_field->field_id,
				'visible' => TRUE,
				'collapsed' => FALSE
			);
		}

		$channel_layout->field_layout = $field_layout;

		$assigned_member_groups = array();

		ee('Model')->get('ChannelLayout')
			->filter('site_id', ee()->config->item('site_id'))
			->filter('channel_id', $channel_id)
			->all()
			->each(function($layout) use (&$assigned_member_groups) {
				foreach ($layout->MemberGroups->pluck('group_id') as $group_id)
				{
					$assigned_member_groups[$group_id] = $layout;
				}
			});

		$vars = array(
			'channel' => $channel,
			'form_url' => ee('CP/URL', 'channels/layouts/create/' . $channel_id),
			'layout' => $entry->getDisplay(),
			'channel_layout' => $channel_layout,
			'member_groups' => $this->getEligibleMemberGroups($channel),
			'selected_member_groups' => array(),
			'assigned_member_groups' => $assigned_member_groups,
			'submit_button_text' => lang('btn_create_layout')
		);

		ee()->load->library('form_validation');
		ee()->form_validation->set_rules(array(
			array(
				'field' => 'layout_name',
				'label' => 'lang:layout_name',
				'rules' => 'required'
			),
			array(
				'field' => 'member_groups',
				'label' => 'lang:member_groups',
				'rules' => 'required'
			),
		));

		if (AJAX_REQUEST)
		{
			ee()->form_validation->run_ajax();
			exit;
		}
		elseif (ee()->form_validation->run() !== FALSE)
		{
			$layout = ee('Model')->make('ChannelLayout');
			$layout->Channel = $channel;
			$layout->site_id = ee()->config->item('site_id');
			$layout->layout_name = ee()->input->post('layout_name');
			$layout->field_layout = json_decode(ee()->input->post('field_layout'), TRUE);

			$member_groups = ee('Model')->get('MemberGroup', ee()->input->post('member_groups'))
				->filter('site_id', ee()->config->item('site_id'))
				->all();

			$layout->MemberGroups = $member_groups;

			$layout->save();

			ee('Alert')->makeInline('layout-form')
				->asSuccess()
				->withTitle(lang('create_layout_success'))
				->addToBody(sprintf(lang('create_layout_success_desc'), ee()->input->post('layout_name')))
				->defer();

			ee()->functions->redirect(ee('CP/URL', 'channels/layouts/edit/' . $layout->layout_id));
		}
		elseif (ee()->form_validation->errors_exist())
		{
			ee('Alert')->makeInline('layout-form')
				->asIssue()
				->withTitle(lang('create_layout_error'))
				->addToBody(lang('create_layout_error_desc'))
				->now();
		}

		ee()->view->cp_breadcrumbs = array(
			ee('CP/URL', 'channels')->compile() => lang('channels'),
			ee('CP/URL', 'channels/layouts/' . $channel_id)->compile() => lang('form_layouts')
		);

		ee()->view->cp_page_title = lang('create_form_layout');

		ee()->javascript->set_global('publish_layout', $channel_layout->field_layout);
		ee()->cp->add_js_script('ui', array('droppable', 'sortable'));
		ee()->cp->add_js_script('file', 'cp/channel/layout');

		ee()->cp->render('channels/layout/form', $vars);
	}

	public function edit($layout_id)
	{
		ee()->view->header = NULL;
		ee()->view->left_nav = NULL;

		$channel_layout = ee('Model')->get('ChannelLayout', $layout_id)
			->filter('site_id', ee()->config->item('site_id'))
			->first();

		if ( ! $channel_layout)
		{
			show_error(lang('unauthorized_access'));
		}

		$channel = $channel_layout->Channel;

		$entry = ee('Model')->make('ChannelEntry');
		$entry->Channel = $channel;

		$assigned_member_groups = array();

		ee('Model')->get('ChannelLayout')
			->filter('site_id', ee()->config->item('site_id'))
			->filter('channel_id', $channel->channel_id)
			->filter('layout_id', '!=', $layout_id) // Exclude this layout
			->all()
			->each(function($layout) use (&$assigned_member_groups) {
				foreach ($layout->MemberGroups->pluck('group_id') as $group_id)
				{
					$assigned_member_groups[$group_id] = $layout;
				}
			});

		$vars = array(
			'channel' => $channel,
			'form_url' => ee('CP/URL', 'channels/layouts/edit/' . $layout_id),
			'layout' => $entry->getDisplay($channel_layout),
			'channel_layout' => $channel_layout,
			'member_groups' => $this->getEligibleMemberGroups($channel),
			'selected_member_groups' => $channel_layout->MemberGroups->pluck('group_id'),
			'assigned_member_groups' => $assigned_member_groups,
			'submit_button_text' => lang('btn_edit_layout')
		);

		ee()->load->library('form_validation');
		ee()->form_validation->set_rules(array(
			array(
				'field' => 'layout_name',
				'label' => 'lang:layout_name',
				'rules' => 'required'
			),
			array(
				'field' => 'member_groups',
				'label' => 'lang:member_groups',
				'rules' => 'required'
			),
		));

		if (AJAX_REQUEST)
		{
			ee()->form_validation->run_ajax();
			exit;
		}
		elseif (ee()->form_validation->run() !== FALSE)
		{
			$channel_layout->layout_name = ee()->input->post('layout_name');
			$channel_layout->field_layout = json_decode(ee()->input->post('field_layout'), TRUE);

			$member_groups = ee('Model')->get('MemberGroup', ee()->input->post('member_groups'))
				->filter('site_id', ee()->config->item('site_id'))
				->all();

			$channel_layout->MemberGroups = $member_groups;

			$channel_layout->save();

			ee('Alert')->makeInline('layout-form')
				->asSuccess()
				->withTitle(lang('edit_layout_success'))
				->addToBody(sprintf(lang('edit_layout_success_desc'), ee()->input->post('layout_name')))
				->defer();

			ee()->functions->redirect(ee('CP/URL', 'channels/layouts/edit/' . $layout_id));
		}
		elseif (ee()->form_validation->errors_exist())
		{
			ee('Alert')->makeInline('layout-form')
				->asIssue()
				->withTitle(lang('edit_layout_error'))
				->addToBody(lang('edit_layout_error_desc'))
				->now();
		}

		ee()->view->cp_breadcrumbs = array(
			ee('CP/URL', 'channels')->compile() => lang('channels'),
			ee('CP/URL', 'channels/layouts/' . $channel_layout->channel_id)->compile() => lang('form_layouts')
		);

		$alert_required = ee('Alert')->makeBanner('tab-has-required-fields')
			->asIssue()
			->canClose()
			->withTitle(lang('error_cannot_hide_tab'))
			->addToBody(lang('error_tab_has_required_fields'));

		$alert_not_empty = ee('Alert')->makeBanner('tab-has-fields')
			->asIssue()
			->canClose()
			->withTitle(lang('error_cannot_remove_tab'))
			->addToBody(lang('error_tab_has_fields'));

		ee()->view->cp_page_title = sprintf(lang('edit_form_layout'), $channel_layout->layout_name);

		ee()->javascript->set_global('publish_layout', $channel_layout->field_layout);
		ee()->javascript->set_global('alert.required', $alert_required->render());
		ee()->javascript->set_global('alert.not_empty', $alert_not_empty->render());

		ee()->cp->add_js_script('ui', array('droppable', 'sortable'));
		ee()->cp->add_js_script('file', 'cp/channel/layout');

		ee()->cp->render('channels/layout/form', $vars);
	}

	private function getEligibleMemberGroups(Channel $channel)
	{
		$super_admins = ee('Model')->get('MemberGroup', 1)
			->filter('site_id', ee()->config->item('site_id'))
			->all();

		$member_groups = array_merge($super_admins->asArray(), $channel->AssignedMemberGroups->asArray());

		return new Collection($member_groups);
	}

	private function remove($layout_ids)
	{
		if ( ! is_array($layout_ids))
		{
			$layout_ids = array($layout_ids);
		}

		$channel_layouts = ee('Model')->get('ChannelLayout', $layout_ids)
			->filter('site_id', ee()->config->item('site_id'))
			->all();

		$layout_names = array();

		foreach ($channel_layouts as $layout)
		{
			$layout_names[] = $layout->layout_name;
		}

		$channel_layouts->delete();
		ee('Alert')->makeInline('layouts')
			->asSuccess()
			->withTitle(lang('success'))
			->addToBody(lang('layouts_removed_desc'))
			->addToBody($layout_names)
			->defer();
	}

}
// EOF

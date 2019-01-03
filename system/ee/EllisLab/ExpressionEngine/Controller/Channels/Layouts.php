<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2019, EllisLab Corp. (https://ellislab.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace EllisLab\ExpressionEngine\Controller\Channels;

use EllisLab\ExpressionEngine\Library\CP\Table;

use EllisLab\ExpressionEngine\Model\Channel\Display\DefaultChannelLayout;
use EllisLab\ExpressionEngine\Controller\Channels\AbstractChannels as AbstractChannelsController;
use EllisLab\ExpressionEngine\Model\Channel\Channel;
use EllisLab\ExpressionEngine\Library\Data\Collection;

/**
 * Channels\Layouts Controller
 */
class Layouts extends AbstractChannelsController {

	public function __construct()
	{
		parent::__construct();

		if ( ! ee()->cp->allowed_group('can_edit_channels'))
		{
			show_error(lang('unauthorized_access'), 403);
		}

		ee()->lang->loadfile('content');
	}

	public function layouts($channel_id = NULL)
	{
		if (ee()->input->post('bulk_action') == 'remove')
		{
			$this->remove(ee()->input->post('selection'));
			ee()->functions->redirect(ee('CP/URL')->make('channels/layouts/' . $channel_id));
		}

		$channel = is_numeric($channel_id)
			? ee('Model')->get('Channel', $channel_id) : ee('Model')->get('Channel');
		$channel = $channel->filter('site_id', ee()->config->item('site_id'))->first();

		if ( ! $channel)
		{
			$vars = [
				'no_results' => [
					'text' => sprintf(lang('no_found'), lang('channels'))
						.' <a href="'.ee('CP/URL', 'channels/create').'">'.lang('add_new').'</a> '
						.lang('or').' <a href="#" rel="import-channel">'.lang('import').'</a>'
				],
				'channel_id' => ''
			];
			return ee()->cp->render('channels/layout/index', $vars);
		}

		$vars['channel_id'] = $channel_id;
		$vars['create_url'] = ee('CP/URL')->make('channels/layouts/create/' . $channel->getId());
		$vars['base_url'] = ee('CP/URL', 'channels/layouts/' . $channel->getId());

		$data = array();

		$layout_id = ee()->session->flashdata('layout_id');

		// Set up filters
		$group_ids = ee('Model')->get('MemberGroup')
			// Banned & Pending have their own views
			->filter('group_id', 'NOT IN', array(2, 4))
			->filter('site_id', ee()->config->item('site_id'))
			->order('group_title', 'asc')
			->all()
			->getDictionary('group_id', 'group_title');

		$options = $group_ids;
		$options['all'] = lang('all');

		$filters = ee('CP/Filter');
		$group_filter = $filters->make('group_id', 'member_group', $options);
		$group_filter->setPlaceholder(lang('all'));
		$group_filter->disableCustomValue();

		$filters->add($group_filter)
			->add('Keyword');

		$filter_values = $filters->values();

		$page = ee('Request')->get('page') ?: 1;
		$per_page = 10;

		$layouts = $channel->ChannelLayouts->asArray();

		// Perform filtering
		if ($group_id = $filter_values['group_id'])
		{
			$layouts = array_filter($layouts, function($layout) use ($group_id) {
				return in_array($group_id, $layout->MemberGroups->pluck('group_id'));
			});
		}
		if ($search = $filter_values['filter_by_keyword'])
		{
			$layouts = array_filter($layouts, function($layout) use ($search) {
				return strpos(
					strtolower($layout->layout_name),
					strtolower($search)
				) !== FALSE;
			});
		}

		$layouts = array_slice($layouts, (($page - 1) * $per_page), $per_page);

		// Only show filters if there is data to filter or we are currently filtered
		if ($group_id OR ! empty($layouts))
		{
			$vars['filters'] = $filters->render($vars['base_url']);
		}

		foreach ($layouts as $layout)
		{
			$edit_url = ee('CP/URL')->make('channels/layouts/edit/' . $layout->layout_id);

			$data[] = [
				'id' => $layout->getId(),
				'label' => $layout->layout_name,
				'href' => $edit_url,
				'extra' => implode(', ', $layout->MemberGroups->pluck('group_title')),
				'selected' => ($layout_id && $layout->layout_id == $layout_id),
				'toolbar_items' => [
					'edit' => [
						'href' => $edit_url,
						'title' => lang('edit')
					]
				],
				'selection' => [
					'name' => 'selection[]',
					'value' => $layout->layout_id,
					'data' => [
						'confirm' => lang('layout') . ': <b>' . ee('Format')->make('Text', $layout->layout_name)->convertToEntities() . '</b>'
					]
				]
			];
		}

		ee()->javascript->set_global('lang.remove_confirm', lang('layout') . ': <b>### ' . lang('layouts') . '</b>');
		ee()->cp->add_js_script(array(
			'file' => array(
				'cp/confirm_remove',
			),
		));

		$vars['pagination'] = ee('CP/Pagination', $channel->ChannelLayouts->count())
			->perPage($per_page)
			->currentPage($page)
			->render($vars['base_url']);

		$vars['breadcrumb_title'] = '';
		$vars['cp_page_title'] = sprintf(lang('channel_form_layouts'), $channel->channel_title);
		$vars['channel_title'] = ee('Format')->make('Text', $channel->channel_title)->convertToEntities();
		$vars['layouts'] = $data;
		$vars['no_results'] = ['text' => lang('no_layouts'), 'href' => $vars['create_url']];

		ee()->cp->set_breadcrumb(ee('CP/URL')->make('channels'), lang('channel_manager'));

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
			show_error(lang('unauthorized_access'), 403);
		}

		$entry = ee('Model')->make('ChannelEntry');
		$entry->Channel = $channel;

		$channel_layout = ee('Model')->make('ChannelLayout');
		$channel_layout->Channel = $channel;
		$channel_layout->site_id = ee()->config->item('site_id');

		if ( ! ee()->input->post('field_layout'))
		{
			$default_layout = new DefaultChannelLayout($channel_id, NULL);
			$field_layout = $default_layout->getLayout();

			foreach ($channel->getAllCustomFields() as $custom_field)
			{
				$field_layout[0]['fields'][] = array(
					'field' => $entry->getCustomFieldPrefix() . $custom_field->field_id,
					'visible' => TRUE,
					'collapsed' => FALSE
				);
			}

			$channel_layout->field_layout = $field_layout;
		}
		else
		{
			$channel_layout->field_layout = json_decode(ee()->input->post('field_layout'), TRUE);
		}

		ee()->load->library('form_validation');
		ee()->form_validation->set_rules(array(
			array(
				'field' => 'layout_name',
				'label' => 'lang:layout_name',
				'rules' => 'required'
			),
			array(
				'field' => 'member_groups',
				'label' => 'lang:layout_member_groups',
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

			$member_groups = ee('Model')->get('MemberGroup', ee()->input->post('member_groups'))
				->filter('site_id', ee()->config->item('site_id'))
				->all();

			$channel_layout->MemberGroups = $member_groups;

			$channel_layout->save();

			ee()->session->set_flashdata('layout_id', $channel_layout->layout_id);

			ee('CP/Alert')->makeInline('layout-form')
				->asSuccess()
				->withTitle(lang('create_layout_success'))
				->addToBody(sprintf(lang('create_layout_success_desc'), $channel_layout->layout_name))
				->defer();

			if (ee('Request')->post('submit') == 'save_and_new')
			{
				ee()->functions->redirect(ee('CP/URL')->make('channels/layouts/create/'.$channel_id));
			}
			elseif (ee()->input->post('submit') == 'save_and_close')
			{
				ee()->functions->redirect(ee('CP/URL')->make('channels/layouts/' . $channel_id));
			}
			else
			{
				ee()->functions->redirect(ee('CP/URL')->make('channels/layouts/edit/'.$channel_layout->getId()));
			}
		}
		elseif (ee()->form_validation->errors_exist())
		{
			ee('CP/Alert')->makeInline('layout-form')
				->asIssue()
				->withTitle(lang('create_layout_error'))
				->addToBody(lang('create_layout_error_desc'))
				->now();
		}

		$vars = array(
			'channel' => $channel,
			'form_url' => ee('CP/URL', 'channels/layouts/create/' . $channel_id),
			'layout' => $entry->getDisplay($channel_layout),
			'channel_layout' => $channel_layout,
			'form' => $this->getForm($channel_layout),
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

		ee()->view->cp_breadcrumbs = array(
			ee('CP/URL')->make('channels')->compile() => lang('channels'),
			ee('CP/URL')->make('channels/layouts/' . $channel_id)->compile() => ee('Format')->make('Text', $channel->channel_title)->convertToEntities()
		);

		ee()->view->cp_page_title = lang('create_form_layout');

		$this->addJSAlerts();
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
			->with('Channel')
			->filter('site_id', ee()->config->item('site_id'))
			->first();

		if ( ! $channel_layout)
		{
			show_error(lang('unauthorized_access'), 403);
		}

		$channel_layout->synchronize();

		$channel = $channel_layout->Channel;

		$entry = ee('Model')->make('ChannelEntry');
		$entry->Channel = $channel;

		if (ee()->input->post('field_layout'))
		{
			$channel_layout->field_layout = json_decode(ee()->input->post('field_layout'), TRUE);
		}

		ee()->load->library('form_validation');
		ee()->form_validation->set_rules(array(
			array(
				'field' => 'layout_name',
				'label' => 'lang:layout_name',
				'rules' => 'required'
			),
			array(
				'field' => 'member_groups',
				'label' => 'lang:layout_member_groups',
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

			$member_groups = ee('Model')->get('MemberGroup', ee()->input->post('member_groups'))
				->filter('site_id', ee()->config->item('site_id'))
				->all();

			$channel_layout->MemberGroups = $member_groups;

			$channel_layout->save();

			ee('CP/Alert')->makeInline('layout-form')
				->asSuccess()
				->withTitle(lang('edit_layout_success'))
				->addToBody(sprintf(lang('edit_layout_success_desc'), ee()->input->post('layout_name')))
				->defer();

			if (ee('Request')->post('submit') == 'save_and_new')
			{
				ee()->functions->redirect(ee('CP/URL')->make('channels/layouts/create/' . $channel->getId()));
			}
			elseif (ee()->input->post('submit') == 'save_and_close')
			{
				ee()->functions->redirect(ee('CP/URL')->make('channels/layouts/' . $channel->getId()));
			}
			else
			{
				ee()->functions->redirect(ee('CP/URL')->make('channels/layouts/edit/' . $channel_layout->getId()));
			}
		}
		elseif (ee()->form_validation->errors_exist())
		{
			ee('CP/Alert')->makeInline('layout-form')
				->asIssue()
				->withTitle(lang('edit_layout_error'))
				->addToBody(lang('edit_layout_error_desc'))
				->now();
		}

		$vars = array(
			'channel' => $channel,
			'form_url' => ee('CP/URL', 'channels/layouts/edit/' . $layout_id),
			'layout' => $entry->getDisplay($channel_layout),
			'channel_layout' => $channel_layout,
			'form' => $this->getForm($channel_layout),
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

		ee()->view->cp_breadcrumbs = array(
			ee('CP/URL')->make('channels')->compile() => lang('channels'),
			ee('CP/URL')->make('channels/layouts/' . $channel_layout->channel_id)->compile() => ee('Format')->make('Text', $channel->channel_title)->convertToEntities()
		);

		ee()->view->cp_page_title = lang('edit_form_layout');

		$this->addJSAlerts();
		ee()->javascript->set_global('publish_layout', $this->prepareLayoutForJS($channel_layout));

		ee()->cp->add_js_script('ui', array('droppable', 'sortable'));
		ee()->cp->add_js_script('file', 'cp/channel/layout');

		ee()->cp->render('channels/layout/form', $vars);
	}

	private function addJSAlerts()
	{
		$alert_required = ee('CP/Alert')->makeBanner('tab-has-required-fields')
			->asIssue()
			->canClose()
			->withTitle(lang('error_cannot_hide_tab'))
			->addToBody(lang('error_tab_has_required_fields'));

		$alert_not_empty = ee('CP/Alert')->makeBanner('tab-has-fields')
			->asIssue()
			->canClose()
			->withTitle(lang('error_cannot_remove_tab'))
			->addToBody(lang('error_tab_has_fields'));

		ee()->javascript->set_global('alert.required', $alert_required->render());
		ee()->javascript->set_global('alert.not_empty', $alert_not_empty->render());
	}

	private function getForm($layout)
	{
		$disabled_choices = array();
		$member_groups = $this->getEligibleMemberGroups($layout->Channel)
			->getDictionary('group_id', 'group_title');

		$other_layouts = ee('Model')->get('ChannelLayout')
			->with('MemberGroups')
			->filter('site_id', ee()->config->item('site_id'))
			->filter('channel_id', $layout->Channel->channel_id);

		if ( ! $layout->isNew())
		{
			// Exclude this layout
			$other_layouts->filter('layout_id', '!=', $layout->layout_id);
		}

		foreach ($other_layouts->all() as $other_layout)
		{
			foreach ($other_layout->MemberGroups as $group)
			{
				$member_groups[$group->group_id] = [
					'label' => $group->group_title,
					'value' => $group->group_id,
					'instructions' => lang('assigned_to') . ' ' . $other_layout->layout_name
				];
				$disabled_choices[] = $group->group_id;
			}
		}

		$selected_member_groups = ($layout->MemberGroups) ? $layout->MemberGroups->pluck('group_id') : array();

		$section = array(
			array(
				'title' => 'name',
				'fields' => array(
					'layout_name' => array(
						'type' => 'text',
						'required' => TRUE,
						'value' => $layout->layout_name,
					)
				)
			),
			array(
				'title' => 'layout_member_groups',
				'desc' => 'member_groups_desc',
				'fields' => array(
					'member_groups' => array(
						'type' => 'checkbox',
						'required' => TRUE,
						'choices' => $member_groups,
						'disabled_choices' => $disabled_choices,
						'value' => $selected_member_groups,
						'no_results' => [
							'text' => sprintf(lang('no_found'), lang('member_groups'))
						]
					)
				)
			),
		);

		return ee('View')->make('ee:_shared/form/section')
				->render(array('name' => 'layout_options', 'settings' => $section));
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
		ee('CP/Alert')->makeInline('layouts')
			->asSuccess()
			->withTitle(lang('success'))
			->addToBody(lang('layouts_removed_desc'))
			->addToBody($layout_names)
			->defer();
	}

	private function prepareLayoutForJS($channel_layout)
	{
		$field_layout = $channel_layout->field_layout;

		if (bool_config_item('enable_comments') && $channel_layout->Channel->comment_system_enabled)
		{
			$comment_expiration_date = [
				'field'     => 'comment_expiration_date',
				'visible'   => TRUE,
				'collapsed' => FALSE
			];

			$allow_comments = [
				'field'     => 'allow_comments',
				'visible'   => TRUE,
				'collapsed' => FALSE
			];

			$has_comment_expiration_date = FALSE;
			$has_allow_comments = FALSE;

			foreach ($field_layout as $i => $section)
			{
				foreach ($section['fields'] as $j => $field_info)
				{
					if ($field_info['field'] == 'comment_expiration_date')
					{
						$has_comment_expiration_date = TRUE;
						continue;
					}

					if ($field_info['field'] == 'allow_comments')
					{
						$has_allow_comments = TRUE;
						continue;
					}
				}
			}

			// Order matters...

			if ( ! $has_allow_comments)
			{
				$field_layout[0]['fields'][] = $allow_comments;
			}

			if ( ! $has_comment_expiration_date)
			{
				$field_layout[0]['fields'][] = $comment_expiration_date;
			}

		}

		return $field_layout;
	}
}

// EOF

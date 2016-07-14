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
 * @copyright	Copyright (c) 2003 - 2016, EllisLab, Inc.
 * @license		https://expressionengine.com/license
 * @link		https://ellislab.com
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
 * @link		https://ellislab.com
 */
class Layouts extends AbstractChannelsController {

	public function __construct()
	{
		parent::__construct();

		if ( ! ee()->cp->allowed_group('can_edit_channels'))
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
			ee()->functions->redirect(ee('CP/URL')->make('channels/layouts/' . $channel_id));
		}

		$channel = ee('Model')->get('Channel', $channel_id)
			->filter('site_id', ee()->config->item('site_id'))
			->first();

		if ( ! $channel)
		{
			show_error(lang('unauthorized_access'));
		}

		$vars['channel_id'] = $channel_id;
		$vars['create_url'] = ee('CP/URL')->make('channels/layouts/create/' . $channel_id);

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
			$edit_url = ee('CP/URL')->make('channels/layouts/edit/' . $layout->layout_id);
			$column = array(
				array(
					'content' => $layout->layout_name,
					'href' => $edit_url
				),
				implode(', ', $layout->MemberGroups->pluck('group_title')),
				array('toolbar_items' => array(
					'edit' => array(
						'href' => $edit_url,
						'title' => lang('edit')
					)
				)),
				array(
					'name' => 'selection[]',
					'value' => $layout->layout_id,
					'data' => array(
						'confirm' => lang('layout') . ': <b>' . htmlentities($layout->layout_name, ENT_QUOTES, 'UTF-8') . '</b>'
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

		$vars['table'] = $table->viewData(ee('CP/URL')->make('channels/layout/' . $channel_id));

		$vars['pagination'] = ee('CP/Pagination', $vars['table']['total_rows'])
			->perPage($vars['table']['limit'])
			->currentPage($vars['table']['page'])
			->render($vars['table']['base_url']);

		ee()->javascript->set_global('lang.remove_confirm', lang('layout') . ': <b>### ' . lang('layouts') . '</b>');
		ee()->cp->add_js_script(array(
			'file' => array(
				'cp/confirm_remove',
			),
		));

		ee()->view->cp_page_title = sprintf(lang('channel_form_layouts'), $channel->channel_title);
		ee()->cp->set_breadcrumb(ee('CP/URL')->make('channels'), lang('channels'));

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

		$channel_layout = ee('Model')->make('ChannelLayout');
		$channel_layout->Channel = $channel;
		$channel_layout->site_id = ee()->config->item('site_id');

		if ( ! ee()->input->post('field_layout'))
		{
			$default_layout = new DefaultChannelLayout($channel_id, NULL);
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

			ee()->functions->redirect(ee('CP/URL', 'channels/layouts/' . $channel_id));
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
			'submit_button_text' => sprintf(lang('btn_save'), lang('layout'))
		);

		ee()->view->cp_breadcrumbs = array(
			ee('CP/URL')->make('channels')->compile() => lang('channels'),
			ee('CP/URL')->make('channels/layouts/' . $channel_id)->compile() => lang('form_layouts')
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
			->filter('site_id', ee()->config->item('site_id'))
			->first();

		if ( ! $channel_layout)
		{
			show_error(lang('unauthorized_access'));
		}

		$this->removeStaleFields($channel_layout);

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

			ee()->functions->redirect(ee('CP/URL', 'channels/layouts/' . $channel_layout->Channel->channel_id));
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
			'submit_button_text' => sprintf(lang('btn_save'), lang('layout'))
		);

		ee()->view->cp_breadcrumbs = array(
			ee('CP/URL')->make('channels')->compile() => lang('channels'),
			ee('CP/URL')->make('channels/layouts/' . $channel_layout->channel_id)->compile() => lang('form_layouts')
		);

		ee()->view->cp_page_title = sprintf(lang('edit_form_layout'), $channel_layout->layout_name);

		$this->addJSAlerts();
		ee()->javascript->set_global('publish_layout', $channel_layout->field_layout);

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
				$member_groups[$group->group_id] = '<s>' . $group->group_title . '</s> <i>&mdash; ' . lang('assigned_to') . ' <a href="' . ee('CP/URL', 'channels/layouts/edit/' . $other_layout->layout_id) . '">' . $other_layout->layout_name . '</a></i>';
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

	/**
	 * Loops through the layout field data and removes any fields that are no
	 * longer part of the channel.
	 *
	 * @param obj $channel_layout A ChannelLayout object
	 * @return void
	 */
	private function removeStaleFields($channel_layout)
	{
		$field_layout = $channel_layout->field_layout;

		$fields = $channel_layout->Channel->CustomFields->map(function($field) {
			return "field_id_" . $field->field_id;
		});

		foreach ($field_layout as $i => $section)
		{
			foreach ($section['fields'] as $j => $field_info)
			{
				// Remove any fields that have since been deleted.
				if (strpos($field_info['field'], 'field_id_') === 0
					&& ! in_array($field_info['field'], $fields))
				{
					array_splice($field_layout[$i]['fields'], $j, 1);
				}
			}
		}

		$channel_layout->field_layout = $field_layout;
	}
}

// EOF

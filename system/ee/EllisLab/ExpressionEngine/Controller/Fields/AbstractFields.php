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

use CP_Controller;

/**
 * Abstract Categories
 */
abstract class AbstractFields extends CP_Controller {

	/**
	 * Constructor
	 */
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
		ee()->lang->loadfile('channel');

		$header = [
			'title' => lang('field_manager')
		];

		if (ee()->cp->allowed_group('can_create_channel_fields'))
		{
			$header['action_button'] = [
				'text' => lang('new_field'),
				'href' => ee('CP/URL')->make('fields/create/'.ee('Request')->get('group_id') ?: '')
			];
		}

		ee()->view->header = $header;

		ee()->javascript->set_global(
			'sets.importUrl',
			ee('CP/URL', 'channels/sets')->compile()
		);

		ee()->cp->add_js_script(array(
			'file' => array('cp/channel/menu'),
		));
	}

	protected function generateSidebar($active = NULL)
	{
		// More than one group can be active, so we use an array
		$active_groups = (is_array($active)) ? $active : array($active);

		$all_fields = ee('CP/Sidebar')->makeNew()->addMarginBottom();
		$all_fields->addHeader(lang('all_fields'), ee('CP/URL')->make('fields'))->isInactive();

		$sidebar = ee('CP/Sidebar')->makeNew();

		$list = $sidebar->addHeader(lang('field_groups_uc'));

		$list = $list->addFolderList('field_groups')
			->withNoResultsText(sprintf(lang('no_found'), lang('field_groups')));

		if (ee()->cp->allowed_group('can_delete_channel_fields'))
		{
			$list->withRemoveUrl(ee('CP/URL')->make('fields/groups/remove', ee()->cp->get_url_state()))
				->withRemovalKey('content_id');
		}

		$imported_groups = ee()->session->flashdata('imported_field_groups') ?: [];

		$field_groups = ee('Model')->get('ChannelFieldGroup')
			->filter('site_id', 'IN', [ee()->config->item('site_id'), 0])
			->order('group_name')
			->all();

		foreach ($field_groups as $group)
		{
			$group_name = ee('Format')->make('Text', $group->group_name)->convertToEntities();

			$item = $list->addItem(
				$group_name,
				ee('CP/URL')->make('fields', ['group_id' => $group->getId()])
			);

			if (ee()->cp->allowed_group('can_edit_channel_fields'))
			{
				$item->withEditUrl(
					ee('CP/URL')->make('fields/groups/edit/' . $group->getId())
				);
			}

			if (ee()->cp->allowed_group('can_delete_channel_fields'))
			{
				$item->withRemoveConfirmation(
					lang('field_group') . ': <b>' . $group_name . '</b>'
				)->identifiedBy($group->getId());
			}

			if (in_array($group->getId(), $active_groups))
			{
				$item->isActive();
			}
			else
			{
				$item->isInactive();
			}

			if (in_array($group->getId(), $imported_groups))
			{
				$item->isSelected();
			}
		}

		$sidebar->addActionBar()
			->withLeftButton(
				lang('new'),
				ee('CP/URL')->make('fields/groups/create')
			);

		ee()->view->left_nav = $all_fields->render().$sidebar->render();
	}

	/**
	 * AJAX endpoint for Relationship field settings author list
	 *
	 * @return	array
	 */
	public function relationshipMemberFilter()
	{
		ee()->load->add_package_path(PATH_ADDONS.'relationship');

		ee()->load->library('Relationships_ft_cp');
		$util = ee()->relationships_ft_cp;

		$author_list = $util->all_authors(ee('Request')->get('search'));

		ee()->load->remove_package_path(PATH_ADDONS.'relationship');

		return ee('View/Helpers')->normalizedChoices($author_list, TRUE);
	}
}

// EOF

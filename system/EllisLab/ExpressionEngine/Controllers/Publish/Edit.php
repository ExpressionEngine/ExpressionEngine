<?php

namespace EllisLab\ExpressionEngine\Controllers\Publish;

use EllisLab\ExpressionEngine\Library\CP\Pagination;
use EllisLab\ExpressionEngine\Library\CP\Table;
use EllisLab\ExpressionEngine\Library\CP\URL;
use EllisLab\ExpressionEngine\Controllers\Publish\Publish;

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
 * ExpressionEngine CP Publish/Edit Class
 *
 * @package		ExpressionEngine
 * @subpackage	Control Panel
 * @category	Control Panel
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */
class Edit extends Publish {

	protected $isAdmin = FALSE;
	protected $assignedChannelIds = array();

	public function __construct()
	{
		parent::__construct();

		$this->isAdmin = (ee()->session->userdata['group_id'] == 1);
		$this->assignedChannelIds = array_keys(ee()->session->userdata['assigned_channels']);
	}

	/**
	 * Displays all available entries
	 *
	 * @return void
	 */
	public function index()
	{
		if (ee()->input->post('bulk_action') == 'edit')
		{
			$this->edit(ee()->input->post('selection'));
		}
		elseif (ee()->input->post('bulk_action') == 'remove')
		{
			$this->remove(ee()->input->post('selection'));
		}
		elseif (ee()->input->post('bulk_action') == 'categories')
		{
			$this->manageCategories(ee()->input->post('selection'));
		}

		$vars = array();
		$base_url = new URL('publish/edit', ee()->session->session_id());
		$channel = NULL;
		$channel_name = '';

		$entries = ee('Model')->get('ChannelEntry')
			->filter('site_id', ee()->config->item('site_id'));

		// We need to filter by Channel first (if necissary) as that will
		// impact the entry count for the perpage filter
		$channel_filter = $this->createChannelFilter();
		$channel_id = $channel_filter->value();

		// If we have a selected channel filter, and we are not an admin, we
		// first need to ensure it is in the list of assigned channels. If it
		// is we will filter by that id. If not we throw an error.
		if ($channel_id)
		{
			if ($this->isAdmin || in_array($channel_id, $this->assignedChannelIds))
			{
				$entries->filter('channel_id', $channel_id);
				$channel = ee('Model')->get('Channel', $channel_id)
					->first();
				$channel_name = $channel->channel_title;
			}
			else
			{
				show_error(lang('unauthorized_access'));
			}
		}
		// If we have no selected channel filter, and we are not an admin, we
		// need to filter via WHERE IN
		else
		{
			if ( ! $this->isAdmin)
			{
				if (empty($this->assignedChannelIds))
				{
					show_error(lang('no_channels'));
				}

				$entries->filter('channel_id', 'IN', $this->assignedChannelIds);
			}
		}

		$category_filter = $this->createCategoryFilter($channel);
		if ($category_filter->value())
		{
			// $entries->with('Categories')
			// 	->filter('Categories.cat_id', $category_filter->value());
		}

		$status_filter = $this->createStatusFilter($channel);
		if ($status_filter->value())
		{
			$entries->filter('status', $status_filter->value());
		}

		ee()->view->search_value = ee()->input->get_post('search');
		if ( ! empty(ee()->view->search_value))
		{
			$base_url->setQueryStringVariable('search', ee()->view->search_value);
		}

		$filters = ee('Filter')
			->add($channel_filter)
			->add($category_filter)
			->add($status_filter)
			->add('Date');

		$filter_values = $filters->values();

		if ( ! empty($filter_values['filter_by_date']))
		{
			if (is_array($filter_values['filter_by_date']))
			{
				$entries->filter('entry_date', '>=', $filter_values['filter_by_date'][0]);
				$entries->filter('entry_date', '<', $filter_values['filter_by_date'][1]);
			}
			else
			{
				$entries->filter('entry_date', '>=', ee()->localize->now - $filter_values['filter_by_date']);
			}
		}

		$count = $entries->count();

		// Add this last to get the right $count
		$filters->add('Perpage', $count, 'all_entries');

		ee()->view->filters = $filters->render($base_url);

		$filter_values = $filters->values();
		$base_url->addQueryStringVariables($filter_values);

		$table = Table::create();

		$table->setColumns(
			array(
				'entry_id',
				'title',
				'comment_total',
				'entry_date',
				'status' => array(
					'type'	=> Table::COL_STATUS
				),
				'manage' => array(
					'type'	=> Table::COL_TOOLBAR
				),
				array(
					'type'	=> Table::COL_CHECKBOX
				)
			)
		);
		$table->setNoResultsText(lang('no_entries_exist'));

		if ($channel_id)
		{
			$table->addActionButton(cp_url('publish/create/' . $channel_id), sprintf(lang('btn_create_new_entry_in_channel'), $channel_name));
		}
		else
		{
			$table->addActionContent(ee('View')->make('publish/partials/create_new_menu')->render(array('button_text' => lang('btn_create_new'))));
		}

		$page = ((int) ee()->input->get('page')) ?: 1;
		$offset = ($page - 1) * $filter_values['perpage']; // Offset is 0 indexed

		$entries->order($table->sort_col, $table->sort_dir)
			->limit($filter_values['perpage'])
			->offset($offset);

		$data = array();

		$entry_id = ee()->session->flashdata('entry_id');

		foreach ($entries->all() as $entry)
		{
			$title = $entry->title . '<br><span class="meta-info">&mdash; ' . lang('by') . ': ' . $entry->getAuthor()->screen_name . ', ' . lang('in') . ': ' . $entry->getChannel()->channel_title . '</span>';

			if ($entry->comment_total > 1)
			{
				$comments = '(<a href="' . cp_url('publish/comments/entry/' . $entry->entry_id) . '">' . $entry->comment_total . ')';
			}
			else
			{
				$comments = '(0)';
			}

			$column = array(
				$entry->entry_id,
				$title,
				$comments,
				ee()->localize->human_time($entry->entry_date),
				$entry->status,
				array('toolbar_items' => array(
					'view' => array(
						'href' => '', //ee()->cp->masked_url($view_url),
						'title' => lang('view')
					),
					'edit' => array(
						'href' => cp_url('publish/edit/entry/' . $entry->entry_id),
						'title' => lang('edit')
					),
				)),
				array(
					'name' => 'selection[]',
					'value' => $entry->entry_id,
					'data' => array(
						'confirm' => lang('entry') . ': <b>' . htmlentities($entry->title, ENT_QUOTES) . '</b>'
					)
				)
			);

			$attrs = array();

			if ($entry_id && $entry->entry_id == $entry_id)
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
		$vars['form_url'] = $vars['table']['base_url'];

		$pagination = new Pagination($filter_values['perpage'], $count, $page);
		$vars['pagination'] = $pagination->cp_links($base_url);

		ee()->javascript->set_global('lang.remove_confirm', lang('entry') . ': <b>### ' . lang('entries') . '</b>');
		ee()->cp->add_js_script(array(
			'file' => array(
				'cp/v3/confirm_remove',
			),
		));

		ee()->view->cp_page_title = lang('edit_channel_entries');
		ee()->view->cp_heading = sprintf(lang('all_channel_entries'), $channel_name);

		ee()->cp->render('publish/edit/index', $vars);
	}

	private function createChannelFilter()
	{
		$allowed_channel_ids = ($this->isAdmin) ? NULL : $this->assignedChannelIds;
		$channels = ee('Model')->get('Channel', $allowed_channel_ids)
			->filter('site_id', ee()->config->item('site_id'))
			->all();

		$channel_filter_options = array();
		foreach ($channels as $channel)
		{
			$channel_filter_options[$channel->channel_id] = $channel->channel_title;
		}
		$channel_filter = ee('Filter')->make('filter_by_channel', 'filter_by_channel', $channel_filter_options);
		$channel_filter->disableCustomValue(); // This may have to go
		return $channel_filter;
	}

	private function createCategoryFilter($channel = NULL)
	{
		$cat_id = ($channel) ? explode('|', $channel->cat_group) : NULL;

		$category_groups = ee('Model')->get('CategoryGroup', $cat_id)
			->filter('site_id', ee()->config->item('site_id'))
			->filter('exclude_group', '!=', 1)
			->all();

		$category_options = array();
		foreach ($category_groups as $group)
		{
			foreach ($group->getCategories() as $category)
			{
				$category_options[$category->cat_id] = $category->cat_name;
			}
		}

		$categories = ee('Filter')->make('filter_by_category', 'filter_by_category', $category_options);
		$categories->disableCustomValue();
		return $categories;
	}

	private function createStatusFilter($channel = NULL)
	{
		$statuses = ee('Model')->get('Status')
			->filter('site_id', ee()->config->item('site_id'));

		if ($channel)
		{
			$statuses->filter('group_id', $channel->status_group);
		}

		$status_options = array();

		foreach ($statuses->all() as $status)
		{
			$status_name = ($status->status == 'closed' OR $status->status == 'open') ?  lang($status->status) : $status->status;
			$status_options[$status->status] = $status_name;
		}

		$status = ee('Filter')->make('filter_by_status', 'filter_by_status', $status_options);
		$status->disableCustomValue();
		return $status;
	}

	private function edit($entry_ids)
	{
		if ( ! is_array($entry_ids))
		{
			$entry_ids = array($entry_ids);
		}

		$entries = ee('Model')->get('ChannelEntry', $entry_ids)
			->filter('site_id', ee()->config->item('site_id'));

		if ( ! $this->isAdmin)
		{
			if (empty($this->assignedChannelIds))
			{
				show_error(lang('no_channels'));
			}

			$entries->filter('channel_id', 'IN', $this->assignedChannelIds);
		}

		ee()->functions->redirect(cp_url('publish/edit', ee()->cp->get_url_state()));
	}

	private function remove($entry_ids)
	{
		if ( ! is_array($entry_ids))
		{
			$entry_ids = array($entry_ids);
		}

		$entries = ee('Model')->get('ChannelEntry', $entry_ids)
			->filter('site_id', ee()->config->item('site_id'));

		if ( ! $this->isAdmin)
		{
			if (empty($this->assignedChannelIds))
			{
				show_error(lang('no_channels'));
			}

			$entries->filter('channel_id', 'IN', $this->assignedChannelIds);
		}

		$entry_names = $entries->all()->pluck('title');

		$entries->delete();

		ee('Alert')->makeInline('entries-form')
			->asSuccess()
			->withTitle(lang('success'))
			->addToBody(lang('entries_removed_desc'))
			->addToBody($entry_names)
			->defer();

		ee()->functions->redirect(cp_url('publish/edit', ee()->cp->get_url_state()));
	}

	private function manageCategories($entry_ids)
	{
		if ( ! is_array($entry_ids))
		{
			$entry_ids = array($entry_ids);
		}

		$entries = ee('Model')->get('ChannelEntry', $entry_ids)
			->filter('site_id', ee()->config->item('site_id'));

		if ( ! $this->isAdmin)
		{
			if (empty($this->assignedChannelIds))
			{
				show_error(lang('no_channels'));
			}

			$entries->filter('channel_id', 'IN', $this->assignedChannelIds);
		}

		ee()->functions->redirect(cp_url('publish/edit', ee()->cp->get_url_state()));
	}

}
// EOF
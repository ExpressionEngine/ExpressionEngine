<?php

namespace EllisLab\ExpressionEngine\Controllers\Publish;

use EllisLab\ExpressionEngine\Library\CP\Pagination;
use EllisLab\ExpressionEngine\Library\CP\Table;
use EllisLab\ExpressionEngine\Library\CP\URL;
use EllisLab\ExpressionEngine\Controllers\Publish\AbstractPublish as AbstractPublishController;

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
class Edit extends AbstractPublishController {

	/**
	 * Displays all available entries
	 *
	 * @return void
	 */
	public function index()
	{
		if (ee()->input->post('bulk_action') == 'remove')
		{
			$this->remove(ee()->input->post('selection'));
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
			if ($this->is_admin || in_array($channel_id, $this->assigned_channel_ids))
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
			if ( ! $this->is_admin)
			{
				if (empty($this->assigned_channel_ids))
				{
					show_error(lang('no_channels'));
				}

				$entries->filter('channel_id', 'IN', $this->assigned_channel_ids);
			}
		}

		$category_filter = $this->createCategoryFilter($channel);
		if ($category_filter->value())
		{
			$entries->with('Categories')
				->filter('Categories.cat_id', $category_filter->value());
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
			$entries->filter('title', 'LIKE', '%' . ee()->view->search_value . '%');
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
				'column_entry_id',
				'column_title',
				'column_comment_total',
				'column_entry_date',
				'column_status' => array(
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

		$entries->order(str_replace('column_', '', $table->sort_col), $table->sort_dir)
			->limit($filter_values['perpage'])
			->offset($offset);

		$data = array();

		$entry_id = ee()->session->flashdata('entry_id');

		foreach ($entries->all() as $entry)
		{
			$autosaves = $entry->getAutosaves()->count();

			$title = $entry->title;

			if ($autosaves)
			{
				$title .= ' <span class="auto-save" title="' . lang('auto_saved') . '">&#10033;</span>';
			}

			$title .= '<br><span class="meta-info">&mdash; ' . lang('by') . ': ' . $entry->getAuthor()->getMemberName() . ', ' . lang('in') . ': ' . $entry->getChannel()->channel_title . '</span>';

			if ($entry->comment_total > 1)
			{
				$comments = '(<a href="' . cp_url('publish/comments/entry/' . $entry->entry_id) . '">' . $entry->comment_total . '</a>)';
			}
			else
			{
				$comments = '(0)';
			}

			$toolbar = array();

			$live_look_template = $entry->getChannel()->getLiveLookTemplate();

			if ($live_look_template)
			{
				$view_url = ee()->functions->create_url($live_look_template->getPath() . '/' . $entry->entry_id);
				$toolbar['view'] = array(
					'href' => ee()->cp->masked_url($view_url),
					'title' => lang('view')
				);
			}

			$toolbar['edit'] = array(
				'href' => cp_url('publish/edit/entry/' . $entry->entry_id),
				'title' => lang('edit')
			);

			$column = array(
				$entry->entry_id,
				$title,
				$comments,
				ee()->localize->human_time($entry->entry_date),
				$entry->status,
				array('toolbar_items' => $toolbar),
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

			if ($autosaves)
			{
				$attrs = array('class' => 'auto-saved');
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
		if ( ! empty(ee()->view->search_value))
		{
			ee()->view->cp_heading = sprintf(lang('search_results_heading'), $count, ee()->view->search_value);
		}
		else
		{
			ee()->view->cp_heading = sprintf(lang('all_channel_entries'), $channel_name);
		}

		ee()->cp->render('publish/edit/index', $vars);
	}

	public function entry($id, $autosave_id = NULL)
	{
		$entry = ee('Model')->get('ChannelEntry', $id)
			->filter('site_id', ee()->config->item('site_id'))
			->first();

		if ( ! $entry)
		{
			show_error(lang('no_entries_matching_that_criteria'));
		}

		// -------------------------------------------
		// 'publish_form_entry_data' hook.
		//  - Modify entry's data
		//  - Added: 1.4.1
			if (ee()->extensions->active_hook('publish_form_entry_data') === TRUE)
			{
				$result = ee()->extensions->call('publish_form_entry_data', $entry->getValues());
				$entry->set($result);
			}
		// -------------------------------------------

		ee()->view->cp_page_title = sprintf(lang('edit_entry_with_title'), $entry->title);

		$form_attributes = array(
			'class' => 'settings ajax-validate',
		);

		$vars = array(
			'form_url' => cp_url('publish/edit/entry/' . $id),
			'form_attributes' => $form_attributes,
			'errors' => new \EllisLab\ExpressionEngine\Service\Validation\Result,
			'button_text' => lang('btn_edit_entry')
		);

		if ($autosave_id)
		{
			$autosaved = ee('Model')->get('ChannelEntryAutosave', $autosave_id)
				->filter('site_id', ee()->config->item('site_id'))
				->first();

			if ($autosaved)
			{
				$entry->set($autosaved->entry_data);
			}
		}

		if (count($_POST))
		{
			$entry->set($_POST);
			$result = $entry->validate();

			if (AJAX_REQUEST)
			{
				$field = ee()->input->post('ee_fv_field');
				// Remove any namespacing to run validation for the parent field
				$field = preg_replace('/\[.+?\]/', '', $field);

				if ($result->hasErrors($field))
				{
					ee()->output->send_ajax_response(array('error' => $result->renderError($field)));
				}
				else
				{
					ee()->output->send_ajax_response('success');
				}
				exit;
			}

			if ($result->isValid())
			{
				$entry->edit_date = ee()->localize->now;
				$entry->save();

				ee('Alert')->makeInline('entry-form')
					->asSuccess()
					->withTitle(lang('edit_entry_success'))
					->addToBody(sprintf(lang('edit_entry_success_desc'), $entry->title))
					->defer();

				ee()->functions->redirect(cp_url('publish/edit/entry/' . $id, ee()->cp->get_url_state()));
			}
			else
			{
				$vars['errors'] = $result;
				// Hacking
				ee()->load->library('form_validation');
				ee()->form_validation->_error_array = $result->renderErrors();
				ee('Alert')->makeInline('entry-form')
					->asIssue()
					->withTitle(lang('edit_entry_error'))
					->addToBody(lang('edit_entry_error_desc'))
					->now();
			}
		}

		$channel_layout = ee('Model')->get('ChannelLayout')
			->filter('site_id', ee()->config->item('site_id'))
			->filter('channel_id', $entry->channel_id)
			->with('MemberGroups')
			->filter('MemberGroups.group_id', ee()->session->userdata['group_id'])
			->first();

		$vars = array_merge($vars, array(
			'entry' => $entry,
			'layout' => $entry->getDisplay($channel_layout),
		));

		$this->setGlobalJs($entry, TRUE);

		ee()->cp->add_js_script(array(
			'plugin' => array(
				'ee_url_title',
				'ee_filebrowser',
				'ee_fileuploader',
			),
			'file' => array('cp/v3/publish')
		));

		ee()->view->cp_breadcrumbs = array(
			cp_url('publish/edit', array('filter_by_channel' => $entry->channel_id)) => $entry->getChannel()->channel_title,
		);

		ee()->cp->render('publish/edit/entry', $vars);
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

	private function remove($entry_ids)
	{
		if ( ! is_array($entry_ids))
		{
			$entry_ids = array($entry_ids);
		}

		$entries = ee('Model')->get('ChannelEntry', $entry_ids)
			->filter('site_id', ee()->config->item('site_id'));

		if ( ! $this->is_admin)
		{
			if (empty($this->assigned_channel_ids))
			{
				show_error(lang('no_channels'));
			}

			$entries->filter('channel_id', 'IN', $this->assigned_channel_ids);
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

}
// EOF
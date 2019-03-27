<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2019, EllisLab Corp. (https://ellislab.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace EllisLab\ExpressionEngine\Controller\Publish;

use EllisLab\ExpressionEngine\Controller\Publish\AbstractPublish as AbstractPublishController;
use EllisLab\ExpressionEngine\Library\CP\Table;
use EllisLab\ExpressionEngine\Model\Channel\ChannelEntry as ChannelEntry;
use EllisLab\ExpressionEngine\Service\Validation\Result as ValidationResult;
use Mexitek\PHPColors\Color;
use EllisLab\ExpressionEngine\Library\CP\EntryManager;

/**
 * Publish/Edit Controller
 */
class Edit extends AbstractPublishController {

	public function __construct()
	{
		parent::__construct();

		if ( ! ee()->cp->allowed_group_any(
			'can_edit_other_entries',
			'can_edit_self_entries'
			))
		{
			show_error(lang('unauthorized_access'), 403);
		}
	}

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
		$base_url = ee('CP/URL')->make('publish/edit');

		$entry_listing = ee('CP/EntryListing',
			ee()->input->get_post('filter_by_keyword'),
			ee()->input->get_post('search_in') ?: 'titles',
			ee()->cp->allowed_group('can_edit_other_entries')
		);

		$entries = $entry_listing->getEntries();
		$filters = $entry_listing->getFilters();
		$channel_id = $entry_listing->channel_filter->value();

		if ( ! ee()->cp->allowed_group('can_edit_other_entries'))
		{
			$entries->filter('author_id', ee()->session->userdata('member_id'));
		}

		$count = $entry_listing->getEntryCount();

		$vars['filters'] = $filters->render($base_url);
		$vars['search_value'] = htmlentities(ee()->input->get_post('filter_by_keyword'), ENT_QUOTES, 'UTF-8');

		$filter_values = $filters->values();
		$base_url->addQueryStringVariables($filter_values);

		$table = ee('CP/Table', array(
			'sort_dir' => 'desc',
			'sort_col' => 'column_entry_date',
		));

		$default_columns = ee()->input->get_post('columns') ?: [
			'entry_id',
			'title',
			'entry_date',
			'author',
			'status'
		];

		$default_columns[] = 'checkbox';

		$columns = array_map(function($identifier) {
			return EntryManager\ColumnFactory::getColumn($identifier);
		}, $default_columns);

		$columns = array_filter($columns);

		$column_renderer = new EntryManager\ColumnRenderer($columns);

		$table->setColumns($column_renderer->getTableColumnsConfig());
		$table->setNoResultsText(lang('no_entries_exist'));

		$show_new_button = TRUE;
		if ($channel_id)
		{
			$channel = $entry_listing->getChannelModelFromFilter();

			// Have we reached the max entries limit for this channel?
			if ($channel->maxEntriesLimitReached())
			{
				// Don't show New button
				$show_new_button = FALSE;

				$desc_key = ($channel->max_entries == 1)
					? 'entry_limit_reached_one_desc' : 'entry_limit_reached_desc';
				ee('CP/Alert')->makeInline()
					->asWarning()
					->withTitle(lang('entry_limit_reached'))
					->addToBody(sprintf(lang($desc_key), $channel->max_entries))
					->now();
			}
		}

		$page = ((int) ee()->input->get('page')) ?: 1;
		$offset = ($page - 1) * $filter_values['perpage']; // Offset is 0 indexed

		$entries->order(str_replace('column_', '', $table->sort_col), $table->sort_dir)
			->limit($filter_values['perpage'])
			->offset($offset);

		$data = array();

		$entry_id = ee()->session->flashdata('entry_id');

		$statuses = ee('Model')->get('Status')->all()->indexBy('status');

		foreach ($entries->all() as $entry)
		{
			$attrs = array();

			if ($entry_id && $entry->entry_id == $entry_id)
			{
				$attrs = array('class' => 'selected');
			}

			if ($entry->Autosaves->count())
			{
				$attrs = array('class' => 'auto-saved');
			}

			$data[] = array(
				'attrs'		=> $attrs,
				'columns'	=> $column_renderer->getRenderedTableRowForEntry($entry)
			);

		}
		$table->setData($data);

		$vars['table'] = $table->viewData($base_url);
		$vars['form_url'] = $vars['table']['base_url'];

		$menu = ee()->menu->generate_menu();
		$choices = [];
		foreach ($menu['channels']['create'] as $text => $link) {
			$choices[$link->compile()] = $text;
		}

		ee()->view->header = array(
			'title' => lang('entry_manager'),
			'action_button' => ee()->cp->allowed_group('can_create_entries') && $show_new_button ? [
				'text' => $channel_id ? sprintf(lang('btn_create_new_entry_in_channel'), $channel->channel_title) : lang('new'),
				'href' => ee('CP/URL', 'publish/create/' . $channel_id)->compile(),
				'filter_placeholder' => lang('filter_channels'),
				'choices' => $channel_id ? NULL : $choices
			] : NULL
		);

		$vars['pagination'] = ee('CP/Pagination', $count)
			->perPage($filter_values['perpage'])
			->currentPage($page)
			->render($base_url);

		ee()->javascript->set_global([
			'lang.remove_confirm' => lang('entry') . ': <b>### ' . lang('entries') . '</b>',

			'publishEdit.sequenceEditFormUrl' => ee('CP/URL')->make('publish/edit/entry/###')->compile(),
			'publishEdit.bulkEditFormUrl' => ee('CP/URL')->make('publish/bulk-edit')->compile(),
			'publishEdit.addCategoriesFormUrl' => ee('CP/URL')->make('publish/bulk-edit/categories/add')->compile(),
			'publishEdit.removeCategoriesFormUrl' => ee('CP/URL')->make('publish/bulk-edit/categories/remove')->compile(),
			'bulkEdit.lang' => [
				'selectedEntries'       => lang('selected_entries'),
				'filterSelectedEntries' => lang('filter_selected_entries'),
				'noEntriesFound'        => sprintf(lang('no_found'), lang('entries')),
				'showing'               => lang('showing'),
				'of'                    => lang('of'),
				'clearAll'              => lang('clear_all'),
				'removeFromSelection'   => lang('remove_from_selection'),
			]
		]);

		ee()->cp->add_js_script(array(
			'file' => array(
				'cp/confirm_remove',
				'cp/publish/entry-list',
				'components/bulk_edit_entries',
				'cp/publish/bulk-edit'
			),
		));

		ee()->view->cp_page_title = lang('edit_channel_entries');
		if ( ! empty($filter_values['filter_by_keyword']))
		{
			$vars['cp_heading'] = sprintf(lang('search_results_heading'), $count, $filter_values['filter_by_keyword']);
		}
		else
		{
			$vars['cp_heading'] = sprintf(
				lang('all_channel_entries'),
				(isset($channel->channel_title)) ? $channel->channel_title : ''
			);
		}

		$vars['can_edit'] = ee('Permission')->hasAny('can_edit_self_entries', 'can_edit_other_entries');
		$vars['can_delete'] = ee('Permission')->hasAny('can_delete_all_entries', 'can_delete_self_entries');

		// Gather data for column selection tool
		$available_columns = EntryManager\ColumnFactory::getAvailableColumns();

		$vars['available_columns'] = $this->getColumnChoices($available_columns);
		$vars['selected_columns'] = $this->getColumnChoices($columns);

		$vars += $this->getColumnChoices($columns);

		ee()->cp->add_js_script([
			'plugin' => ['ui.touch.punch', 'ee_interact.event'],
			'file' => ['fields/relationship/mutable_relationship', 'fields/relationship/relationship'],
			'ui' => 'sortable'
		]);

		if (AJAX_REQUEST)
		{
			return array(
				'html' => ee('View')->make('publish/partials/edit_list_table')->render($vars),
				'url' => $vars['form_url']->compile()
			);
		}

		ee()->cp->render('publish/edit/index', $vars);
	}

	/**
	 * Formats column data for use in selection UI
	 *
	 * @param array[Column]
	 * @return array Identifier => Label
	 */
	private function getColumnChoices($columns)
	{
		$column_choices = [];

		foreach ($columns as $column)
		{
			$identifier = $column->getTableColumnIdentifier();

			// This column is mandatory, not optional
			if ($identifier == 'checkbox')
			{
				continue;
			}

			$column_choices[$identifier] = strip_tags(lang($column->getTableColumnLabel()));
		}

		return $column_choices;
	}

	public function entry($id = NULL, $autosave_id = NULL)
	{
		if ( ! $id)
		{
			show_404();
		}

		$base_url = ee('CP/URL')->getCurrentUrl();

		// Sequence editing?
		$sequence_editing = FALSE;
		if ($entry_ids = ee('Request')->get('entry_ids'))
		{
			$sequence_editing = TRUE;

			$index = array_search($id, $entry_ids) + 1;
			$next_entry_id = isset($entry_ids[$index]) ? $entry_ids[$index] : NULL;
			$base_url->setQueryStringVariable('next_entry_id', $next_entry_id);
		}

		// If an entry or channel on a different site is requested, try
		// to switch sites and reload the publish form
		$site_id = (int) ee()->input->get_post('site_id');
		if ($site_id != 0 && $site_id != ee()->config->item('site_id') && empty($_POST))
		{
			ee()->cp->switch_site($site_id, $base_url);
		}

		$entry = ee('Model')->get('ChannelEntry', $id)
			->with('Channel')
			->filter('site_id', ee()->config->item('site_id'))
			->first();

		if ( ! $entry)
		{
			show_error(lang('no_entries_matching_that_criteria'));
		}

		if ( ! ee()->cp->allowed_group('can_edit_other_entries')
			&& $entry->author_id != ee()->session->userdata('member_id'))
		{
			show_error(lang('unauthorized_access'), 403);
		}

		if ( ! in_array($entry->channel_id, $this->assigned_channel_ids))
		{
			show_error(lang('unauthorized_access'), 403);
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

		$entry_title = htmlentities($entry->title, ENT_QUOTES, 'UTF-8');
		ee()->view->cp_page_title = sprintf(lang('edit_entry_with_title'), $entry_title);

		$form_attributes = array(
			'class' => 'ajax-validate',
		);

		$vars = array(
			'form_url' => $base_url,
			'form_attributes' => $form_attributes,
			'form_title' => lang('edit_entry'),
			'errors' => new \EllisLab\ExpressionEngine\Service\Validation\Result,
			'autosaves' => $this->getAutosavesTable($entry, $autosave_id),
			'extra_publish_controls' => $entry->Channel->extra_publish_controls,
			'buttons' => $this->getPublishFormButtons($entry),
			'in_modal_context' => $sequence_editing
		);

		if ($sequence_editing)
		{
			$vars['modal_title'] = sprintf('(%d of %d) %s', $index, count($entry_ids), $entry_title);
			$vars['buttons'] = [[
				'name' => 'submit',
				'type' => 'submit',
				'value' => 'save_and_next',
				'text' => $index == count($entry_ids) ? 'save_and_close' : 'save_and_next',
				'working' => 'btn_saving'
			]];
		}

		if ($entry->isLivePreviewable())
		{
			$modal = ee('View')->make('publish/live-preview-modal')->render([
				'preview_url' => ee('CP/URL')->make('publish/preview/' . $entry->channel_id . '/' . $entry->entry_id)
			]);
			ee('CP/Modal')->addModal('live-preview', $modal);
		}

		$version_id = ee()->input->get('version');

		if ($entry->Channel->enable_versioning)
		{
			$vars['revisions'] = $this->getRevisionsTable($entry, $version_id);
		}

		if ($version_id)
		{
			$version = $entry->Versions->filter('version_id', $version_id)->first();
			$version_data = $version->version_data;
			$entry->set($version_data);
		}

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

		$channel_layout = ee('Model')->get('ChannelLayout')
			->filter('site_id', ee()->config->item('site_id'))
			->filter('channel_id', $entry->channel_id)
			->with('MemberGroups')
			->filter('MemberGroups.group_id', ee()->session->userdata['group_id'])
			->first();

		$vars['layout'] = $entry->getDisplay($channel_layout);

		$result = $this->validateEntry($entry, $vars['layout']);

		if ($result instanceOf ValidationResult)
		{
			$vars['errors'] = $result;

			if ($result->isValid())
			{
				return $this->saveEntryAndRedirect($entry);
			}
		}

		$vars['entry'] = $entry;

		$this->setGlobalJs($entry, TRUE);

		ee()->cp->add_js_script(array(
			'plugin' => array(
				'ee_url_title',
				'ee_filebrowser',
				'ee_fileuploader',
			),
			'file' => array('cp/publish/publish')
		));

		ee()->view->cp_breadcrumbs = array(
			ee('CP/URL')->make('publish/edit', array('filter_by_channel' => $entry->channel_id))->compile() => $entry->Channel->channel_title,
		);

		if (ee('Request')->get('modal_form') == 'y')
		{
			$vars['layout']->setIsInModalContext(TRUE);
			ee()->output->enable_profiler(FALSE);
			return ee()->view->render('publish/modal-entry', $vars);
		}

		ee()->cp->render('publish/entry', $vars);
	}

	private function remove($entry_ids)
	{
		if ( ! ee()->cp->allowed_group('can_delete_all_entries')
			&& ! ee()->cp->allowed_group('can_delete_self_entries'))
		{
			show_error(lang('unauthorized_access'), 403);
		}

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

		if ( ! ee()->cp->allowed_group('can_delete_all_entries'))
		{
			$entries->filter('author_id', ee()->session->userdata('member_id'));
		}

		$entry_names = $entries->all()->pluck('title');

		$entries->delete();

		ee('CP/Alert')->makeInline('entries-form')
			->asSuccess()
			->withTitle(lang('success'))
			->addToBody(lang('entries_removed_desc'))
			->addToBody($entry_names)
			->defer();

		ee()->functions->redirect(ee('CP/URL')->make('publish/edit', ee()->cp->get_url_state()));
	}

}

// EOF

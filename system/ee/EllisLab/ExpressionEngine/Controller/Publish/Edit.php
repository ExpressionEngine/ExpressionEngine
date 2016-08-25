<?php

namespace EllisLab\ExpressionEngine\Controller\Publish;

use EllisLab\ExpressionEngine\Library\CP\Table;
use Mexitek\PHPColors\Color;

use EllisLab\ExpressionEngine\Controller\Publish\AbstractPublish as AbstractPublishController;
use EllisLab\ExpressionEngine\Service\Validation\Result as ValidationResult;

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
 * ExpressionEngine CP Publish/Edit Class
 *
 * @package		ExpressionEngine
 * @subpackage	Control Panel
 * @category	Control Panel
 * @author		EllisLab Dev Team
 * @link		https://ellislab.com
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
			show_error(lang('unauthorized_access'));
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

		$entry_listing = ee('CP/EntryListing', ee()->input->get_post('search'));
		$entries = $entry_listing->getEntries();
		$filters = $entry_listing->getFilters();
		$channel_id = $entry_listing->channel_filter->value();

		if ( ! ee()->cp->allowed_group('can_edit_other_entries'))
		{
			$entries->filter('author_id', ee()->session->userdata('member_id'));
		}

		$count = $entries->count();

		if ( ! empty(ee()->view->search_value))
		{
			$base_url->setQueryStringVariable('search', ee()->view->search_value);
		}

		$vars['filters'] = $filters->render($base_url);
		$vars['search_value'] = htmlentities(ee()->input->get_post('search'), ENT_QUOTES, 'UTF-8');

		$filter_values = $filters->values();
		$base_url->addQueryStringVariables($filter_values);

		$table = ee('CP/Table', array(
			'sort_dir' => 'desc',
			'sort_col' => 'column_entry_date',
		));

		$columns = array(
			'column_entry_id',
			'column_title' => array(
				'encode' => FALSE
			)
		);

		$show_comments_column = (
			ee()->config->item('enable_comments') == 'y' OR
			ee('Model')->get('Comment')
				->filter('site_id', ee()->config->item('site_id'))
				->count() > 0);

		if ($show_comments_column)
		{
			$columns = array_merge($columns, array(
				'column_comment_total' => array(
					'encode' => FALSE
				)
			));
		}

		$columns = array_merge($columns, array(
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
		));

		$table->setColumns($columns);
		$table->setNoResultsText(lang('no_entries_exist'));

		if ($channel_id)
		{
			$channel = ee('Model')->get('Channel', $channel_id)->first();
			$vars['create_button'] = '<a class="btn tn action" href="'.ee('CP/URL', 'publish/create/' . $channel_id).'">'.sprintf(lang('btn_create_new_entry_in_channel'), $channel->channel_title).'</a>';

			// Have we reached the max entries limit for this channel?
			if ($channel->max_entries !== '0' && $count >= $channel->max_entries)
			{
				// Don't show create button
				$vars['create_button'] = '';

				$desc_key = ($channel->max_entries === '1')
					? 'entry_limit_reached_one_desc' : 'entry_limit_reached_desc';
				ee('CP/Alert')->makeInline()
					->asWarning()
					->withTitle(lang('entry_limit_reached'))
					->addToBody(sprintf(lang($desc_key), $channel->max_entries))
					->now();
			}
		}
		else
		{
			$vars['create_button'] = ee('View')->make('publish/partials/create_new_menu')->render(array('button_text' => lang('btn_create_new')));
		}

		$page = ((int) ee()->input->get('page')) ?: 1;
		$offset = ($page - 1) * $filter_values['perpage']; // Offset is 0 indexed

		$entries->order(str_replace('column_', '', $table->sort_col), $table->sort_dir)
			->limit($filter_values['perpage'])
			->offset($offset);

		$data = array();

		$entry_id = ee()->session->flashdata('entry_id');

		$statuses = ee('Model')->get('Status')
			->filter('site_id', ee()->config->item('site_id'))
			->all();

		foreach ($entries->all() as $entry)
		{
			if (ee()->cp->allowed_group('can_edit_other_entries')
				|| (ee()->cp->allowed_group('can_edit_self_entries') &&
					$entry->author_id == ee()->session->userdata('member_id')
					)
				)
			{
				$can_edit = TRUE;
			}
			else
			{
				$can_edit = FALSE;
			}

			// wW had a delete cascade issue that could leave entries orphaned and
			// resulted in errors, so we'll sneakily use this controller to clean up
			// for now.
			if (is_null($entry->Channel))
			{
				$entry->delete();
				continue;
			}

			$autosaves = $entry->Autosaves->count();

			// Escape markup in title
			$title = htmlentities($entry->title, ENT_QUOTES, 'UTF-8');

			if ($can_edit)
			{
				$edit_link = ee('CP/URL')->make('publish/edit/entry/' . $entry->entry_id);
				$title = '<a href="' . $edit_link . '">' . $title . '</a>';
			}

			if ($autosaves)
			{
				$title .= ' <span class="auto-save" title="' . lang('auto_saved') . '">&#10033;</span>';
			}

			$title .= '<br><span class="meta-info">&mdash; ' . lang('by') . ': ' . htmlentities($entry->getAuthorName(), ENT_QUOTES, 'UTF-8') . ', ' . lang('in') . ': ' . htmlentities($entry->Channel->channel_title, ENT_QUOTES, 'UTF-8') . '</span>';

			if ($entry->comment_total > 0)
			{
				if (ee()->cp->allowed_group('can_moderate_comments'))
				{
					$comments = '(<a href="' . ee('CP/URL')->make('publish/comments/entry/' . $entry->entry_id) . '">' . $entry->comment_total . '</a>)';
				}
				else
				{
					$comments = '(' . $entry->comment_total . ')';
				}
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
					'title' => lang('view'),
					'rel' => 'external'
				);
			}

			if ($can_edit)
			{
				$toolbar['edit'] = array(
					'href' => $edit_link,
					'title' => lang('edit')
				);
			}

			if (ee()->cp->allowed_group('can_delete_all_entries')
				|| (ee()->cp->allowed_group('can_delete_self_entries') &&
					$entry->author_id == ee()->session->userdata('member_id')
					)
				)
			{
				$can_delete = TRUE;
			}
			else
			{
				$can_delete = FALSE;
			}

			$disabled_checkbox = ! $can_delete;

			// Display status highlight if one exists
			$status = $statuses->filter('group_id', $entry->Channel->status_group)
				->filter('status', $entry->status)
				->first();

			if ($status)
			{
				$highlight = new Color($status->highlight);
				$color = ($highlight->isLight())
					? $highlight->darken(100)
					: $highlight->lighten(100);

				$status = array(
					'content'          => $status->status,
					'color'            => $color,
					'background-color' => $status->highlight
				);
			}
			else
			{
				$status = $entry->status;
			}

			$column = array(
				$entry->entry_id,
				$title,
				ee()->localize->human_time($entry->entry_date),
				$status,
				array('toolbar_items' => $toolbar),
				array(
					'name' => 'selection[]',
					'value' => $entry->entry_id,
					'disabled' => $disabled_checkbox,
					'data' => array(
						'confirm' => lang('entry') . ': <b>' . htmlentities($entry->title, ENT_QUOTES, 'UTF-8') . '</b>'
					)
				)
			);

			if ($show_comments_column)
			{
				array_splice($column, 2, 0, array($comments));
			}

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

		ee()->view->header = array(
			'title' => lang('entry_manager'),
			'form_url' => $vars['form_url'],
			'search_button_value' => lang('btn_search_entries')
		);

		$vars['pagination'] = ee('CP/Pagination', $count)
			->perPage($filter_values['perpage'])
			->currentPage($page)
			->render($base_url);

		ee()->javascript->set_global('lang.remove_confirm', lang('entry') . ': <b>### ' . lang('entries') . '</b>');
		ee()->cp->add_js_script(array(
			'file' => array(
				'cp/confirm_remove',
				'cp/publish/entry-list'
			),
		));

		ee()->view->cp_page_title = lang('edit_channel_entries');
		if ( ! empty(ee()->view->search_value))
		{
			$vars['cp_heading'] = sprintf(lang('search_results_heading'), $count, ee()->view->search_value);
		}
		else
		{
			$vars['cp_heading'] = sprintf(
				lang('all_channel_entries'),
				(isset($channel->channel_title)) ? $channel->channel_title : ''
			);
		}

		if (AJAX_REQUEST)
		{
			return array(
				'html' => ee('View')->make('publish/partials/edit_list_table')->render($vars),
				'url' => $vars['form_url']->compile()
			);
		}

		ee()->cp->render('publish/edit/index', $vars);
	}

	public function entry($id = NULL, $autosave_id = NULL)
	{
		if ( ! $id)
		{
			show_404();
		}

		// If an entry or channel on a different site is requested, try
		// to switch sites and reload the publish form
		$site_id = (int) ee()->input->get_post('site_id');
		if ($site_id != 0 && $site_id != ee()->config->item('site_id') && empty($_POST))
		{
			ee()->cp->switch_site(
				$site_id,
				ee('CP/URL')->make('publish/edit/entry/'.$id)
			);
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
			show_error(lang('unauthorized_access'));
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

		ee()->view->cp_page_title = sprintf(lang('edit_entry_with_title'), htmlentities($entry->title, ENT_QUOTES, 'UTF-8'));

		$form_attributes = array(
			'class' => 'settings ajax-validate',
		);

		$vars = array(
			'form_url' => ee('CP/URL')->make('publish/edit/entry/' . $id),
			'form_attributes' => $form_attributes,
			'errors' => new \EllisLab\ExpressionEngine\Service\Validation\Result,
			'button_text' => lang('btn_publish'),
			'extra_publish_controls' => $entry->Channel->extra_publish_controls
		);

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
				$this->saveEntryAndRedirect($entry);
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

		if ($entry->Channel->CategoryGroups)
		{
			ee('Category')->addCategoryModals();
		}

		ee()->cp->render('publish/entry', $vars);
	}

	private function remove($entry_ids)
	{
		if ( ! ee()->cp->allowed_group('can_delete_all_entries')
			&& ! ee()->cp->allowed_group('can_delete_self_entries'))
		{
			show_error(lang('unauthorized_access'));
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

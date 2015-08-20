<?php

namespace EllisLab\ExpressionEngine\Controller\Publish;

use EllisLab\ExpressionEngine\Library\CP\Table;

use EllisLab\ExpressionEngine\Controller\Publish\AbstractPublish as AbstractPublishController;

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
		$base_url = ee('CP/URL', 'publish/edit');
		$channel_name = '';

		$entry_listing = ee('CP/EntryListing', ee()->input->get_post('search'));
		$entries = $entry_listing->getEntries();
		$filters = $entry_listing->getFilters();
		$channel_id = $entry_listing->channel_filter->value();
		$count = $entries->count();

		if ( ! empty(ee()->view->search_value))
		{
			$base_url->setQueryStringVariable('search', ee()->view->search_value);
		}

		ee()->view->filters = $filters->render($base_url);
		ee()->view->search_value = ee()->input->get_post('search');

		$filter_values = $filters->values();
		$base_url->addQueryStringVariables($filter_values);

		$table = ee('CP/Table');

		$table->setColumns(
			array(
				'column_entry_id',
				'column_title' => array(
					'encode' => FALSE
				),
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

		$channels = ee('Model')->get('Channel')
			->fields('channel_id', 'channel_name')
			->filter('site_id', ee()->config->item('site_id'))
			->all();

		if (count($channels) == 1)
		{
			$channel_id = $channels[0]->channel_id;
			$channel_name = $channels[0]->channel_name;
		}

		if ($channel_id)
		{
			$vars['create_button'] = '<a class="btn tn action" href="'.ee('CP/URL', 'publish/create/' . $channel_id).'">'.sprintf(lang('btn_create_new_entry_in_channel'), $channel_name).'</a>';
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

		foreach ($entries->all() as $entry)
		{
			$autosaves = $entry->Autosaves->count();

			$edit_link = ee('CP/URL', 'publish/edit/entry/' . $entry->entry_id);
			$title = '<a href="' . $edit_link . '">' . htmlentities($entry->title, ENT_QUOTES). '</a>';

			if ($autosaves)
			{
				$title .= ' <span class="auto-save" title="' . lang('auto_saved') . '">&#10033;</span>';
			}

			$title .= '<br><span class="meta-info">&mdash; ' . lang('by') . ': ' . htmlentities($entry->Author->getMemberName(), ENT_QUOTES) . ', ' . lang('in') . ': ' . htmlentities($entry->Channel->channel_title, ENT_QUOTES) . '</span>';

			if ($entry->comment_total > 1)
			{
				$comments = '(<a href="' . ee('CP/URL', 'publish/comments/entry/' . $entry->entry_id) . '">' . $entry->comment_total . '</a>)';
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
				'href' => $edit_link,
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
			'form_url' => ee('CP/URL', 'publish/edit/entry/' . $id),
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

		if (count($_POST))
		{
			$entry->set($_POST);

			// if categories are not in POST, then they've unchecked everything
			// and we need to clear them out
			if ( ! isset($_POST['categories']))
			{
				$entry->Categories = NULL;
			}

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
				if ($entry->versioning_enabled && ee()->input->post('save_revision'))
				{
					$entry->saveVersion();

					ee('CP/Alert')->makeInline('entry-form')
						->asSuccess()
						->withTitle(lang('revision_saved'))
						->addToBody(sprintf(lang('revision_saved_desc'), $entry->Versions->count() + 1, $entry->title))
						->defer();

					ee()->functions->redirect(ee('CP/URL', 'publish/edit/entry/' . $id, ee()->cp->get_url_state()));
				}
				else
				{
					$entry->edit_date = ee()->localize->now;
					$entry->save();

					ee('CP/Alert')->makeInline('entry-form')
						->asSuccess()
						->withTitle(lang('edit_entry_success'))
						->addToBody(sprintf(lang('edit_entry_success_desc'), $entry->title))
						->defer();

					ee()->functions->redirect(ee('CP/URL', 'publish/edit/', array('filter_by_channel' => $entry->channel_id)));
				}
			}
			else
			{
				$vars['errors'] = $result;
				// Hacking
				ee()->load->library('form_validation');
				ee()->form_validation->_error_array = $result->renderErrors();
				ee('CP/Alert')->makeInline('entry-form')
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
			'file' => array('cp/channel/publish')
		));

		ee()->view->cp_breadcrumbs = array(
			ee('CP/URL', 'publish/edit', array('filter_by_channel' => $entry->channel_id))->compile() => $entry->getChannel()->channel_title,
		);

		ee()->cp->render('publish/entry', $vars);
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

		ee('CP/Alert')->makeInline('entries-form')
			->asSuccess()
			->withTitle(lang('success'))
			->addToBody(lang('entries_removed_desc'))
			->addToBody($entry_names)
			->defer();

		ee()->functions->redirect(ee('CP/URL', 'publish/edit', ee()->cp->get_url_state()));
	}

}
// EOF

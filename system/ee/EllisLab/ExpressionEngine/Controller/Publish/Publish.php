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
use EllisLab\ExpressionEngine\Service\Validation\Result as ValidationResult;
use EllisLab\ExpressionEngine\Model\Channel\ChannelEntry;

/**
 * Publish Controller
 */
class Publish extends AbstractPublishController {

	public function __construct()
	{
		parent::__construct();

		if ( ! ee('Permission')->hasAny(
			'can_create_entries',
			'can_edit_self_entries',
			'can_edit_other_entries'
		))
		{
			show_error(lang('unauthorized_access'), 403);
		}
	}

	/**
	 * Renders a single field for a given channel or channel entry
	 *
	 * @param int $channel_id The Channel ID
	 * @param int $entry_id The Entry ID
	 * @return array An associative array (for JSON) containing the rendered HTML
	 */
	public function field($channel_id, $entry_id)
	{
		if (is_numeric($entry_id) && $entry_id != 0)
		{
			$entry = ee('Model')->get('ChannelEntry', $entry_id)
				->filter('site_id', ee()->config->item('site_id'))
				->first();
		}
		else
		{
			$entry = ee('Model')->make('ChannelEntry');
			$entry->Channel = ee('Model')->get('Channel', $channel_id)->first();
		}

		$entry->set($_POST);

		return array('html' => $entry->getCustomField(ee()->input->get('field_name'))->getForm());
	}

	/**
	 * Populates the default author list in Channel Settings, also serves as
	 * AJAX endpoint for that filtering
	 *
	 * @return array ID => Screen name array of authors
	 */
	public function authorList()
	{
		$authors = ee('Member')->getAuthors(ee('Request')->get('search'));

		if (AJAX_REQUEST)
		{
			return ee('View/Helpers')->normalizedChoices($authors);
		}

		return $authors;
	}

	/**
	 * AJAX end-point for relationship field filtering
	 */
	public function relationshipFilter()
	{
		ee()->load->add_package_path(PATH_ADDONS.'relationship');
		ee()->load->library('EntryList');
		ee()->output->send_ajax_response(ee()->entrylist->ajaxFilter());
	}

	/**
	 * Autosaves a channel entry
	 *
	 * @param int $channel_id The Channel ID
	 * @param int $entry_id The Entry ID
	 * @return void
	 */
	public function autosave($channel_id, $entry_id)
	{
		$site_id = ee()->config->item('site_id');

		$autosave = ee('Model')->get('ChannelEntryAutosave')
			->filter('original_entry_id', $entry_id)
			->filter('site_id', $site_id)
			->filter('channel_id', $channel_id)
			->first();

		if ( ! $autosave)
		{
			$autosave = ee('Model')->make('ChannelEntryAutosave');
			$autosave->original_entry_id = $entry_id;
			$autosave->site_id = $site_id;
			$autosave->channel_id = $channel_id;
		}

		$autosave->edit_date = ee()->localize->now;
		$autosave->entry_data = $_POST;

		// This is currently unused, but might be useful for display purposes
		$autosave->author_id = ee()->input->post('author_id', ee()->session->userdata('member_id'));

		// This group of columns is unused
		$autosave->title = (ee()->input->post('title')) ?: 'autosave_' . ee()->localize->now;
		$autosave->url_title = (ee()->input->post('url_title')) ?: 'autosave_' . ee()->localize->now;
		$autosave->status = ee()->input->post('status');

		// This group of columns is also unused
		$autosave->entry_date = 0;
		$autosave->year = 0;
		$autosave->month = 0;
		$autosave->day = 0;

		$autosave->save();

		$time = ee()->localize->human_time(ee()->localize->now);
		$time = trim(strstr($time, ' '));

		ee()->output->send_ajax_response(array(
			'success' => ee('View')->make('ee:publish/partials/autosave_badge')->render(['time' => $time]),
			'autosave_entry_id' => $autosave->entry_id,
			'original_entry_id'	=> $entry_id
		));
	}

	/**
	 * Creates a new channel entry
	 *
	 * @param int $channel_id The Channel ID
	 * @param int|NULL $autosave_id An optional autosave ID, for pre-populating
	 *   the form
	 * @return string Rendered HTML
	 */
	public function create($channel_id = NULL, $autosave_id = NULL)
	{
		if ( ! $channel_id)
		{
			show_404();
		}

		if ( ! ee()->cp->allowed_group('can_create_entries') OR
			 ! in_array($channel_id, $this->assigned_channel_ids))
		{
			show_error(lang('unauthorized_access'), 403);
		}

		$channel = ee('Model')->get('Channel', $channel_id)
			->filter('site_id', ee()->config->item('site_id'))
			->first();

		if ( ! $channel)
		{
			show_error(lang('no_channel_exists'));
		}

		// Redirect to edit listing if we've reached max entries for this channel
		if ($channel->maxEntriesLimitReached())
		{
			ee()->functions->redirect(
				ee('CP/URL')->make('publish/edit/', array('filter_by_channel' => $channel_id))
			);
		}

		$entry = ee('Model')->make('ChannelEntry');
		$entry->Channel = $channel;
		$entry->site_id =  ee()->config->item('site_id');
		$entry->author_id = ee()->session->userdata('member_id');
		$entry->ip_address = ee()->session->userdata['ip_address'];
		$entry->versioning_enabled = $channel->enable_versioning;
		$entry->sticky = FALSE;

		// Set some defaults based on Channel Settings
		$entry->allow_comments = (isset($channel->deft_comments)) ? $channel->deft_comments : TRUE;

		if (isset($channel->deft_status))
		{
			$entry->status = $channel->deft_status;
		}

		if ( ! empty($channel->deft_category))
		{
			$cat = ee('Model')->get('Category', $channel->deft_category)->first();
			if ($cat)
			{
				// set directly so other categories don't get lazy loaded
				// along with our default
				$entry->Categories = $cat;
			}
		}

		$entry->title = $channel->default_entry_title;
		$entry->url_title = $channel->url_title_prefix;

		if (isset($_GET['BK']))
		{
			$this->populateFromBookmarklet($entry);
		}

		ee()->view->cp_page_title = sprintf(lang('create_entry_with_channel_name'), $channel->channel_title);

		$form_attributes = array(
			'class' => 'ajax-validate',
		);

		$vars = array(
			'form_url' => ee('CP/URL')->getCurrentUrl(),
			'form_attributes' => $form_attributes,
			'form_title' => lang('new_entry'),
			'errors' => new \EllisLab\ExpressionEngine\Service\Validation\Result,
			'revisions' => $this->getRevisionsTable($entry),
			'extra_publish_controls' => $channel->extra_publish_controls,
			'buttons' => $this->getPublishFormButtons($entry)
		);

		if (ee('Request')->get('modal_form') == 'y')
		{
			$vars['buttons'] = [[
				'name' => 'submit',
				'type' => 'submit',
				'value' => 'save_and_close',
				'text' => 'save_and_close',
				'working' => 'btn_saving'
			]];
		}

		if ($entry->isLivePreviewable())
		{
			$modal = ee('View')->make('publish/live-preview-modal')->render([
				'preview_url' => ee('CP/URL')->make('publish/preview/' . $entry->channel_id)
			]);
			ee('CP/Modal')->addModal('live-preview', $modal);
		}

		if ($autosave_id)
		{
			$autosaved = ee('Model')->get('ChannelEntryAutosave', $autosave_id)
				->filter('channel_id', $channel_id)
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

		// Auto-saving needs an entry_id...
		$entry->entry_id = 0;

		$vars['autosaves'] = $this->getAutosavesTable($entry, $autosave_id);
		$vars['entry'] = $entry;

		$this->setGlobalJs($entry, TRUE);

		ee()->cp->add_js_script(array(
			'plugin' => array(
				'ee_url_title',
				'ee_filebrowser',
				'ee_fileuploader',
			),
			'file' => array('cp/publish/publish', 'cp/channel/category_edit')
		));

		ee()->view->cp_breadcrumbs = array(
			ee('CP/URL')->make('publish/edit', array('filter_by_channel' => $entry->channel_id))->compile() => $entry->Channel->channel_title,
		);

		$vars['breadcrumb_title'] = lang('new_entry');

		if (ee('Request')->get('modal_form') == 'y')
		{
			$vars['layout']->setIsInModalContext(TRUE);
			return ee('View')->make('publish/modal-entry')->render($vars);
		}

		ee()->cp->render('publish/entry', $vars);
	}

	/**
	 * Populates a channel entry entity from a bookmarklet action
	 *
	 * @param ChannelEntry $entry A Channel Entry entity to populate
	 * @return void
	 */
	private function populateFromBookmarklet(ChannelEntry $entry)
	{
		$data = array();

		if (($title = ee()->input->get('title')) !== FALSE)
		{
			$data['title'] = $title;
		}

		foreach ($_GET as $key => $value)
		{
			if (strpos($key, 'field_id_') === 0)
			{
				$data[$key] = $value;
			}
		}

		if (empty($data))
		{
			return;
		}

		$entry->set($data);
	}

	public function preview($channel_id, $entry_id = NULL)
	{
		if (empty($_POST))
		{
			return;
		}

		$channel = ee('Model')->get('Channel', $channel_id)
			->filter('site_id', ee()->config->item('site_id'))
			->first();

		if ($entry_id)
		{
			$entry = ee('Model')->get('ChannelEntry', $entry_id)
				->with('Channel', 'Author')
				->first();
		}
		else
		{
			$entry = ee('Model')->make('ChannelEntry');
			$entry->entry_id = PHP_INT_MAX;
			$entry->Channel = $channel;
			$entry->site_id =  ee()->config->item('site_id');
			$entry->author_id = ee()->session->userdata('member_id');
			$entry->ip_address = ee()->session->userdata['ip_address'];
			$entry->versioning_enabled = $channel->enable_versioning;
			$entry->sticky = FALSE;
		}

		$entry->set($_POST);
		$data = $entry->getModChannelResultsArray();
		$data['entry_site_id'] = $entry->site_id;
		if (isset($_POST['categories']))
		{
			$data['categories'] = $_POST['categories'];
		}

		ee('LivePreview')->setEntryData($data);

		ee()->load->library('template', NULL, 'TMPL');

		$template_id = NULL;

		if ( ! empty($_POST['pages__pages_uri'])
			&& ! empty($_POST['pages__pages_template_id']))
		{
			$values = [
				'pages_uri'         => $_POST['pages__pages_uri'],
				'pages_template_id' => $_POST['pages__pages_template_id'],
			];

			$page_tab = new \Pages_tab;
			$site_pages = $page_tab->prepareSitePagesData($entry, $values);

			ee()->config->set_item('site_pages', $site_pages);
			$entry->Site->site_pages = $site_pages;

			$template_id = $_POST['pages__pages_template_id'];
		}

		if ($entry->hasPageURI())
		{
			$uri = $entry->getPageURI();
			ee()->uri->page_query_string = $entry->entry_id;
			if ( ! $template_id)
			{
				$template_id = $entry->getPageTemplateID();
			}
		}
		else
		{
			// We want to avoid replacing `{url_title}` with an empty string since that
			// can cause the wrong thing to render (like 404s).
			if (empty($entry->url_title))
			{
				$entry->url_title = $entry->entry_id;
			}

			$uri = str_replace(['{url_title}', '{entry_id}'], [$entry->url_title, $entry->entry_id], $channel->preview_url);
		}

		// -------------------------------------------
		// 'publish_live_preview_route' hook.
		//  - Set alternate URI and/or template to use for preview
		//  - Added 4.2.0
		//
			if (ee()->extensions->active_hook('publish_live_preview_route') === TRUE)
			{
				$route = ee()->extensions->call('publish_live_preview_route', array_merge($_POST, $data), $uri, $template_id);
				$uri = $route['uri'];
				$template_id = $route['template_id'];
			}
		//
		// -------------------------------------------

		ee()->uri->_set_uri_string($uri);

		// Compile the segments into an array
		ee()->uri->segments = [];
		ee()->uri->_explode_segments();

		// Re-index the segment array so that it starts with 1 rather than 0
		ee()->uri->_reindex_segments();

		ee()->core->loadSnippets();

		$template_group = '';
		$template_name = '';

		if ($template_id)
		{
			$template = ee('Model')->get('Template', $template_id)
				->with('TemplateGroup')
				->first();

			$template_group = $template->TemplateGroup->group_name;
			$template_name = $template->template_name;
		}

		ee()->TMPL->run_template_engine($template_group, $template_name);
	}
}

// EOF

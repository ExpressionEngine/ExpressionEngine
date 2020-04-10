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

use CP_Controller;
use EllisLab\ExpressionEngine\Library\CP\Table;

use EllisLab\ExpressionEngine\Model\Channel\ChannelEntry;
/**
 * Abstract Publish Controller
 */
abstract class AbstractPublish extends CP_Controller {

	protected $is_admin = FALSE;
	protected $assigned_channel_ids = array();

	/**
	 * Constructor
	 */
	public function __construct()
	{
		parent::__construct();

		ee()->lang->loadfile('content');

		ee()->cp->get_installed_modules();

		$this->is_admin = (ee()->session->userdata['group_id'] == 1);
		$this->assigned_channel_ids = array_keys(ee()->session->userdata['assigned_channels']);

		$this->pruneAutosaves();
	}

	protected function createChannelFilter()
	{
		$allowed_channel_ids = ($this->is_admin) ? NULL : $this->assigned_channel_ids;
		$channels = ee('Model')->get('Channel', $allowed_channel_ids)
			->fields('channel_id', 'channel_title')
			->filter('site_id', ee()->config->item('site_id'))
			->order('channel_title', 'asc')
			->all();

		$channel_filter_options = array();
		foreach ($channels as $channel)
		{
			$channel_filter_options[$channel->channel_id] = $channel->channel_title;
		}
		$channel_filter = ee('CP/Filter')->make('filter_by_channel', 'filter_by_channel', $channel_filter_options);
		$channel_filter->disableCustomValue(); // This may have to go
		return $channel_filter;
	}

	protected function setGlobalJs($entry, $valid)
	{
		$entry_id = $entry->entry_id;
		$channel_id = $entry->channel_id;

		$autosave_interval_seconds = (ee()->config->item('autosave_interval_seconds') === FALSE) ?
										60 : ee()->config->item('autosave_interval_seconds');

		//	Create Foreign Character Conversion JS
		$foreign_characters = ee()->config->loadFile('foreign_chars');

		/* -------------------------------------
		/*  'foreign_character_conversion_array' hook.
		/*  - Allows you to use your own foreign character conversion array
		/*  - Added 1.6.0
		* 	- Note: in 2.0, you can edit the foreign_chars.php config file as well
		*/
			if (isset(ee()->extensions->extensions['foreign_character_conversion_array']))
			{
				$foreign_characters = ee()->extensions->call('foreign_character_conversion_array');
			}
		/*
		/* -------------------------------------*/

		$smileys_enabled = (isset(ee()->cp->installed_modules['emoticon']) ? TRUE : FALSE);

		if ($smileys_enabled)
		{
			ee()->load->helper('smiley');
			ee()->cp->add_to_foot(smiley_js());
		}

		ee()->javascript->set_global(array(
			'lang.add_new_html_button'       => lang('add_new_html_button'),
			'lang.close'                     => lang('close'),
			'lang.confirm_exit'              => lang('confirm_exit'),
			'lang.loading'                   => lang('loading'),
			'publish.autosave.interval'      => (int) $autosave_interval_seconds,
			'publish.autosave.URL'           => ee('CP/URL')->make('publish/autosave/' . $channel_id . '/' . $entry_id)->compile(),
			'publish.channel_title'          => ee('Format')->make('Text', $entry->Channel->channel_title)
				->convertToEntities()
				->compile(),
			'publish.default_entry_title'    => $entry->Channel->default_entry_title,
			'publish.foreignChars'           => $foreign_characters,
			'publish.urlLength'              => URL_TITLE_MAX_LENGTH,
			'publish.lang.no_member_groups'  => lang('no_member_groups'),
			'publish.lang.refresh_layout'    => lang('refresh_layout'),
			'publish.lang.tab_count_zero'    => lang('tab_count_zero'),
			'publish.lang.tab_has_req_field' => lang('tab_has_req_field'),
			'publish.markitup.foo'           => FALSE,
			'publish.smileys'                => $smileys_enabled,
			'publish.field.URL'              => ee('CP/URL', 'publish/field/' . $channel_id . '/' . $entry_id)->compile(),
			'publish.url_title_prefix'       => $entry->Channel->url_title_prefix,
			'publish.which'                  => ($entry_id) ? 'edit' : 'new',
			'publish.word_separator'         => ee()->config->item('word_separator') != "dash" ? '_' : '-',
			'user.can_edit_html_buttons'     => ee()->cp->allowed_group('can_edit_html_buttons'),
			'user.foo'                       => FALSE,
			'user_id'                        => ee()->session->userdata('member_id'),
		));

		ee('Category')->addCategoryJS();

		// -------------------------------------------
		//	Publish Page Title Focus - makes the title field gain focus when the page is loaded
		//
		//	Hidden Configuration Variable - publish_page_title_focus => Set focus to the tile? (y/n)

		ee()->javascript->set_global('publish.title_focus', FALSE);

		if ( ! $entry_id && $valid && bool_config_item('publish_page_title_focus'))
		{
			ee()->javascript->set_global('publish.title_focus', TRUE);
		}
	}

	protected function getRevisionsTable($entry, $version_id = FALSE)
	{
		$table = ee('CP/Table');

		$table->setColumns(
			array(
				'rev_id',
				'rev_date',
				'rev_author',
				'manage' => array(
					'encode' => FALSE
				)
			)
		);
		$table->setNoResultsText(lang('no_revisions'));

		$data = array();
		$authors = array();
		$i = $entry->Versions->count();
		$current_author_id = FALSE;
		$current_id = $i+1;

		foreach ($entry->Versions->sortBy('version_date')->reverse() as $version)
		{
			if ( ! isset($authors[$version->author_id]))
			{
				$authors[$version->author_id] = $version->getAuthorName();
			}

			if ( ! $current_author_id)
			{
				$current_author_id = $authors[$version->author_id];
			}

			$toolbar = ee('View')->make('_shared/toolbar')->render(array(
				'toolbar_items' => array(
						'txt-only' => array(
							'href' => ee('CP/URL')->make('publish/edit/entry/' . $entry->entry_id, array('version' => $version->version_id)),
							'title' => lang('view'),
							'content' => lang('view')
						),
					)
				)
			);

			$attrs = ($version->version_id == $version_id) ? array('class' => 'selected') : array();

			$data[] = array(
				'attrs'   => $attrs,
				'columns' => array(
					$i,
					ee()->localize->human_time($version->version_date->format('U')),
					$authors[$version->author_id],
					$toolbar
				)
			);
			$i--;
		}


		if ( ! $entry->isNew())
		{
			$attrs = (!$version_id) ? array('class' => 'selected') : array();

			$current_author_id = (!$current_author_id) ? $entry->getAuthorName() : $current_author_id;


			// Current
			$edit_date = ($entry->edit_date)
				? ee()->localize->human_time($entry->edit_date->format('U'))
				: NULL;

			array_unshift($data, array(
				'attrs'   => $attrs,
				'columns' => array(
					$current_id,
					$edit_date,
					$current_author_id,
					'<span class="st-open">' . lang('current') . '</span>'
				))
			);
		}


		$table->setData($data);

		return ee('View')->make('_shared/table')->render($table->viewData(''));
	}

	protected function getAutosavesTable($entry, $autosave_id = FALSE)
	{
		$table = ee('CP/Table');

		$table->setColumns(
			array(
				'rev_id',
				'rev_date',
				'rev_author',
				'manage' => array(
					'encode' => FALSE
				)
			)
		);

		$data = array();
		$authors = array();
		$i = $entry->getAutosaves()->count();

		if ( ! $entry->isNew())
		{
			$i++;
			$attrs = ( ! $autosave_id) ? ['class' => 'selected'] : [];

			if ( ! isset($authors[$entry->author_id]))
			{
				$authors[$entry->author_id] = $entry->getAuthorName();
			}

			// Current
			$edit_date = ($entry->edit_date)
				? ee()->localize->human_time($entry->edit_date->format('U'))
				: NULL;

			$data[] = array(
				'attrs'   => $attrs,
				'columns' => array(
					$i,
					$edit_date,
					$authors[$entry->author_id],
					'<span class="st-open">' . lang('current') . '</span>'
				)
			);
			$i--;
		}

		foreach ($entry->getAutosaves()->sortBy('edit_date')->reverse() as $autosave)
		{
			if ( ! isset($authors[$autosave->author_id]) && $autosave->Author)
			{
				$authors[$autosave->author_id] = $autosave->Author->getMemberName();
			}

			$toolbar = ee('View')->make('_shared/toolbar')->render(array(
				'toolbar_items' => array(
						'txt-only' => array(
							'href' => $entry->entry_id
								? ee('CP/URL')->make('publish/edit/entry/' . $entry->entry_id . '/' . $autosave->entry_id)
								: ee('CP/URL')->make('publish/create/' . $entry->Channel->channel_id . '/' . $autosave->entry_id),
							'title' => lang('view'),
							'content' => lang('view')
						),
					)
				)
			);

			$attrs = ($autosave->getId() == $autosave_id) ? array('class' => 'selected') : array();

			$data[] = array(
				'attrs'   => $attrs,
				'columns' => array(
					$i,
					ee()->localize->human_time($autosave->edit_date),
					isset($authors[$autosave->author_id]) ? $authors[$autosave->author_id] : '-',
					$toolbar
				)
			);
			$i--;
		}

		$table->setData($data);

		return ee('View')->make('_shared/table')->render($table->viewData(''));
	}

	protected function validateEntry(ChannelEntry $entry, $layout)
	{
		if (empty($_POST))
		{
			return FALSE;
		}

		$action = ($entry->isNew()) ? 'create' : 'edit';

		// Get all the fields that should be in the DOM. Any that were not
		// POSTed will be set to NULL. This addresses a bug where browsers
		// do not POST unchecked checkboxes.
		foreach ($layout->getTabs() as $tab)
		{
			// Invisible tabs were not rendered
			if ($tab->isVisible())
			{
				foreach ($tab->getFields() as $field)
				{
					// Fields that were not required and not visible were not rendered
					if ( ! $field->isRequired() && ! $field->isVisible())
					{
						continue;
					}

					$field_name = strstr($field->getName(), '[', TRUE) ?: $field->getName();

					if ( ! array_key_exists($field_name, $_POST))
					{
						$_POST[$field_name] = NULL;
					}

				}
			}
		}

		if ( ! ee()->cp->allowed_group('can_assign_post_authors'))
		{
			unset($_POST['author_id']);
		}

		$entry->set($_POST);

		$result = $entry->validate();

		if ($response = $this->ajaxValidation($result))
		{
			ee()->output->send_ajax_response($response);
		}

		if ($result->failed())
		{
			ee('CP/Alert')->makeInline('shared-form')
				->asIssue()
				->withTitle(lang($action . '_entry_error'))
				->addToBody(lang($action . '_entry_error_desc'))
				->now();
		}

		return $result;
	}

	protected function saveEntryAndRedirect($entry)
	{
		$action = ($entry->isNew()) ? 'create' : 'edit';
		$entry->edit_date = ee()->localize->now;
		$entry->save();

		ee()->session->set_flashdata('entry_id', $entry->entry_id);

		$alert = (ee('Request')->get('modal_form') == 'y' && ee('Request')->get('next_entry_id'))
			? ee('CP/Alert')->makeStandard()
			: ee('CP/Alert')->makeInline('entry-form');

		$alert->asSuccess()
			->withTitle(lang($action . '_entry_success'))
			->addToBody(sprintf(lang($action . '_entry_success_desc'), htmlentities($entry->title, ENT_QUOTES, 'UTF-8')))
			->defer();


		if (ee('Request')->get('modal_form') == 'y')
		{
			$next_entry_id = ee('Request')->get('next_entry_id');

			$result = [
				'saveId' => $entry->getId(),
				'item' => [
					'value' => $entry->getId(),
					'label' => $entry->title,
					'instructions' => $entry->Channel->channel_title
				]
			];

			if (is_numeric($next_entry_id))
			{
				$next_entry = ee('CP/URL')->getCurrentUrl();
				$next_entry->path = 'publish/edit/entry/' . $next_entry_id;
				$result += ['redirect' => $next_entry->compile()];
			}

			return $result;
		}
		elseif (ee()->input->post('submit') == 'save')
		{
			ee()->functions->redirect(ee('CP/URL')->make('publish/edit/entry/' . $entry->getId()));
		}
		elseif (ee()->input->post('submit') == 'save_and_close')
		{
			$redirect_url = ee('CP/URL')->make('publish/edit/', array('filter_by_channel' => $entry->channel_id));

			/* -------------------------------------
			/*  'entry_save_and_close_redirect' hook.
			/*  - Redirect to a different URL when "Save & Close" is clicked
			/*  - Added 4.0.0
			*/
				if (ee()->extensions->active_hook('entry_save_and_close_redirect'))
				{
					$redirect_url = ee()->extensions->call('entry_save_and_close_redirect', $entry);
				}
			/*
			/* -------------------------------------*/

			ee()->functions->redirect($redirect_url);
		}
		else
		{
			ee()->functions->redirect(ee('CP/URL')->make('publish/create/' . $entry->channel_id));
		}
	}

	/**
	 * Delete stale autosaved data based on the `autosave_prune_hours` config
	 * value
	 *
	 * @return void
	 */
	protected function pruneAutosaves()
	{
		$prune = ee()->config->item('autosave_prune_hours') ?: 6;
		$prune = $prune * 120; // From hours to seconds

		$cutoff = ee()->localize->now - $prune;

		$autosave = ee('Model')->get('ChannelEntryAutosave')
			->filter('edit_date', '<', $cutoff)
			->delete();
	}

	/**
	 * Get Submit Buttons for Publish Edit Form
	 * @param  ChannelEntry $entry ChannelEntry model entity
	 * @return array Submit button array
	 */
	protected function getPublishFormButtons(ChannelEntry $entry)
	{
		$buttons = [
			[
				'name' => 'submit',
				'type' => 'submit',
				'value' => 'save',
				'text' => 'save',
				'working' => 'btn_saving',
				// Disable these while JS is still loading key components, re-enabled in publish.js
				'attrs' => 'disabled="disabled"'
			]
		];

		if (ee('Permission')->has('can_create_entries'))
		{
			$buttons[] = [
				'name' => 'submit',
				'type' => 'submit',
				'value' => 'save_and_new',
				'text' => 'save_and_new',
				'working' => 'btn_saving',
				'attrs' => 'disabled="disabled"'
			];
		}

		$buttons[] = [
			'name' => 'submit',
			'type' => 'submit',
			'value' => 'save_and_close',
			'text' => 'save_and_close',
			'working' => 'btn_saving',
			'attrs' => 'disabled="disabled"'
		];

		// get rid of Save & New button if we've reached the max entries for this channel
		if ($entry->Channel->maxEntriesLimitReached())
		{
			unset($buttons[1]);
		}

		$has_preview_button  = FALSE;
		$show_preview_button = FALSE;

		if ($entry->hasLivePreview())
		{
			$has_preview_button  = TRUE;
			$show_preview_button = TRUE;
		}

		$pages_module = ee('Addon')->get('pages');
		if ($pages_module && $pages_module->isInstalled())
		{
			$has_preview_button = TRUE;
			if ($entry->hasPageURI())
			{
				$show_preview_button = TRUE;
			}
		}

		if ($has_preview_button)
		{
			$extra_class = ($show_preview_button) ? '' : ' hidden';

			$buttons[] = [
				'name'    => 'submit',
				'type'    => 'submit',
				'value'   => 'preview',
				'text'    => 'preview',
				'class'   => 'action js-modal-link--side' . $extra_class,
				'attrs'   => 'rel="live-preview" disabled="disabled"',
				'working' => 'btn_previewing'
			];
		}

		return $buttons;
	}
}

// EOF

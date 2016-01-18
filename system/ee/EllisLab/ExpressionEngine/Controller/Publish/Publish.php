<?php

namespace EllisLab\ExpressionEngine\Controller\Publish;

use EllisLab\ExpressionEngine\Controller\Publish\AbstractPublish as AbstractPublishController;
use EllisLab\ExpressionEngine\Model\Channel\ChannelEntry;

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
 * ExpressionEngine CP Publish Class
 *
 * @package		ExpressionEngine
 * @subpackage	Control Panel
 * @category	Control Panel
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */
class Publish extends AbstractPublishController {

	public function __construct()
	{
		parent::__construct();

		if ( ! ee()->cp->allowed_group('can_create_entries'))
		{
			show_error(lang('unauthorized_access'));
		}
	}

	/**
	 * Renders a single field for a given channel or channel entry
	 *
	 * @param int $channel_id The Channel ID
	 * @param int $entry_id The Entry ID
	 * @param string $field_name The name of the field to render
	 * @return array An associative array (for JSON) containing the rendered HTML
	 */
	public function field($channel_id, $entry_id, $field_name)
	{
		if ($entry_id)
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

		return array('html' => $entry->getCustomField($field_name)->getForm());
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
		$autosave->author_id = ee()->input->post('author_id');

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

		$alert = ee('CP/Alert')->makeInline()
			->asWarning()
			->cannotClose()
			->addToBody(lang('autosave_success') . $time);

		ee()->output->send_ajax_response(array(
			'success' => $alert->render(),
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

		if ( ! ee()->cp->allowed_group('can_create_entries'))
		{
			show_error(lang('unauthorized_access'));
		}

		$channel = ee('Model')->get('Channel', $channel_id)
			->filter('site_id', ee()->config->item('site_id'))
			->first();

		if ( ! $channel)
		{
			show_error(lang('no_channel_exists'));
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

		if (isset($channel->deft_category))
		{
			$cat = ee('Model')->get('Category', $channel->deft_category)->first();
			if ($cat)
			{
				$entry->Categories[] = $cat;
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
			'class' => 'settings ajax-validate',
		);

		$vars = array(
			'form_url' => ee('CP/URL')->make('publish/create/' . $channel_id),
			'form_attributes' => $form_attributes,
			'errors' => new \EllisLab\ExpressionEngine\Service\Validation\Result,
			'button_text' => lang('btn_publish'),
			'revisions' => $this->getRevisionsTable($entry),
			'extra_publish_controls' => $channel->extra_publish_controls
		);

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

		if (count($_POST))
		{
			if ( ! ee()->cp->allowed_group('can_assign_post_authors'))
			{
				unset($_POST['author_id']);
			}

			$entry->set($_POST);
			$entry->edit_date = ee()->localize->now;
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
				$entry->save();

				if ($entry->versioning_enabled && ee()->input->post('save_revision'))
				{
					$entry->saveVersion();

					ee('CP/Alert')->makeInline('entry-form')
						->asSuccess()
						->withTitle(lang('revision_saved'))
						->addToBody(sprintf(lang('revision_saved_desc'), $entry->Versions->count() + 1, $entry->title))
						->defer();

					ee()->functions->redirect(ee('CP/URL')->make('publish/edit/entry/' . $id, ee()->cp->get_url_state()));
				}
				else
				{
					ee()->session->set_flashdata('entry_id', $entry->entry_id);

					ee('CP/Alert')->makeInline('entry-form')
						->asSuccess()
						->withTitle(lang('create_entry_success'))
						->addToBody(sprintf(lang('create_entry_success_desc'), $entry->title))
						->defer();

					ee()->functions->redirect(ee('CP/URL')->make('publish/edit/', array('filter_by_channel' => $entry->channel_id)));
				}
			}
			else
			{
				$vars['errors'] = $result;

				ee('CP/Alert')->makeInline('entry-form')
					->asIssue()
					->withTitle(lang('create_entry_error'))
					->addToBody(lang('create_entry_error_desc'))
					->now();
			}
		}

		$channel_layout = ee('Model')->get('ChannelLayout')
			->filter('site_id', ee()->config->item('site_id'))
			->filter('channel_id', $channel_id)
			->with('MemberGroups')
			->filter('MemberGroups.group_id', ee()->session->userdata['group_id'])
			->first();

		// Auto-saving needs an entry_id...
		$entry->entry_id = 0;

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
			'file' => array('cp/publish/publish', 'cp/channel/category_edit')
		));

		if ($entry->Channel->CategoryGroups)
		{
			$this->addCategoryModals();
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
		$field = '';

		foreach ($_GET as $key => $value)
		{
			if (strpos($key, 'field_id_') === 0)
			{
				$field = $key;
				break;
			}
		}

		if ( ! $field)
		{
			return;
		}

		$data = array(
			'title' => ee()->input->get('title'),
			$field => ee()->input->get($field)
		);

		$entry->set($data);
	}
}
// EOF

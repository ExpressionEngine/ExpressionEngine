<?php

namespace EllisLab\ExpressionEngine\Controller\Publish;

use EllisLab\ExpressionEngine\Controller\Publish\AbstractPublish as AbstractPublishController;
use EllisLab\ExpressionEngine\Service\Validation\Result as ValidationResult;
use EllisLab\ExpressionEngine\Model\Channel\ChannelEntry;

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
 * ExpressionEngine CP Publish Class
 *
 * @package		ExpressionEngine
 * @subpackage	Control Panel
 * @category	Control Panel
 * @author		EllisLab Dev Team
 * @link		https://ellislab.com
 */
class Publish extends AbstractPublishController {

	public function __construct()
	{
		parent::__construct();

		if ( ! ee()->cp->allowed_group('can_create_entries'))
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
		if ($channel->max_entries != 0 && $channel->total_records >= $channel->max_entries)
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

		// Auto-saving needs an entry_id...
		$entry->entry_id = 0;

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

		if ($entry->Channel->CategoryGroups)
		{
			ee('Category')->addCategoryModals();
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
}

// EOF

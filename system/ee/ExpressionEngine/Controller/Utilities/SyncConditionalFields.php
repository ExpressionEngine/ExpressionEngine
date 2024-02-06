<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Controller\Utilities;

use ExpressionEngine\Library\CP;

/**
 * SyncConditionalFields Manager Controller
 */
class SyncConditionalFields extends Utilities
{
    public function __construct()
    {
        parent::__construct();

        // Member needs to have permission to edit channel fields to run sync
        if (! ee('Permission')->can('edit_channel_fields')) {
            show_error(lang('unauthorized_access'), 403);
        }
    }

    public function index()
    {
        // If this is a bulk sync request, lets redirect to the sync page
        if (ee('Request')->method() === 'POST' && ee('Request')->post('bulk_action') === 'SYNC') {
            $channel_ids = ee('Request')->post('channel_id');

            ee()->functions->redirect(
                ee('CP/URL')->make('utilities/sync-conditional-fields/sync')
                    ->setQueryStringVariable('channel_id', $channel_ids)
                    ->compile()
            );
        }

        // Loop through channels and build up array of channel data for the sync
        $data = [];
        $channels = ee('Model')->get('Channel')->all();
        foreach ($channels as $channel) {
            $data[] = array(
                $channel->channel_title,
                $channel->conditional_sync_required ? '<i class="app-notice__icon"></i>' : '-',
                array('toolbar_items' => array(
                    'sync' => array(
                        'href' => ee('CP/URL')->make(
                            'utilities/sync-conditional-fields/sync/',
                            array(
                                'channel_id[]' => $channel->channel_id
                            )
                        ),
                        'title' => lang('sync')
                    )
                )),
                array(
                    'name' => 'channel_id[]',
                    'value' => $channel->channel_id,
                )
            );
        }

        // Create table of channels we can sync
        $table = ee('CP/Table', array('autosort' => true, 'limit' => 0));
        $table->setColumns(
            array(
                lang('channel'),
                lang('sync_required') => [
                    'encode' => false
                ],
                'sync' => array(
                    'type' => CP\Table::COL_TOOLBAR
                ),
                array(
                    'type' => CP\Table::COL_CHECKBOX
                )
            )
        );
        $table->setNoResultsText('no_channels_available');
        $table->setData($data);

        $vars['table'] = $table->viewData(ee('CP/URL')->make('utilities/sync-conditional-fields'));

        ee()->view->cp_page_title = lang('sync_conditional_fields');
        ee()->view->table_heading = lang('sync_conditional_fields');

        ee()->view->cp_breadcrumbs = array(
            '' => lang('sync_conditional_fields')
        );

        return ee()->cp->render('utilities/sync-conditional-fields/sync-channels', $vars);
    }

    public function sync()
    {
        // If this is a post, lets put the vars in the URL as a GET
        if (ee('Request')->method() === 'POST') {
            $channel_ids = ee('Request')->post('channel_id');

            if (empty($channel_ids)) {
                ee()->functions->redirect(ee('CP/URL')->make('utilities/sync-conditional-fields/sync'));
            }

            ee()->functions->redirect(
                ee('CP/URL')->make('utilities/sync-conditional-fields/sync')
                    ->setQueryStringVariable('channel_id', $channel_ids)
                    ->compile()
            );
        }

        // Get an array of channel ID's
        $channel_ids = ee('Request')->get('channel_id');

        if (!is_null($channel_ids) && !is_array($channel_ids)) {
            $channel_ids = [$channel_ids];
        }

        $channels = ee('Model')->get('Channel');

        // If user passes in channel IDs, add that filter.
        // Otherwise we get all channel IDs
        if (!is_null($channel_ids)) {
            $channels = $channels->filter('channel_id', 'IN', $channel_ids);
        }

        $channels = $channels->all();

        // Get a count of channel entries per channel
        $channelEntryCount = 0;
        $groupedChannelEntryCounts = [];
        $channelTitlesList = '';

        foreach ($channels as $channel) {
            $count = $channel->Entries->count();
            $channelEntryCount += $count;
            $groupedChannelEntryCounts[] = [
                'channel_id' => $channel->getId(),
                'entry_count' => $count
            ];
            $channelTitlesList .= "<br> - " . $channel->channel_title;
        }

        ksort($groupedChannelEntryCounts);

        // Build the view
        $vars['sections'] = array(
            array(
                array(
                    'title' => 'field_conditions_sync_existing_entries',
                    'desc' => sprintf(lang('field_conditions_sync_desc'), $channelEntryCount, $channelTitlesList),
                    'fields' => array(
                        'progress' => array(
                            'type' => 'html',
                            'content' => ee()->load->view('_shared/progress_bar', array('percent' => 0), true)
                        ),
                        'message' => array(
                            'type' => 'html',
                            'content' => ee()->load->view('_shared/message', array(
                                'cp_messages' => [
                                    'field-instruct' => '<em>'.lang('field_conditions_sync_in_progress_message').'</em>'
                                ]), true)
                        )
                    )
                )
            )
        );

        $base_url = ee('CP/URL')->make('utilities/sync-conditional-fields/sync')->compile();
        $sync_url = ee('CP/URL')->make('utilities/sync-conditional-fields')->compile();

        $return = ee()->input->get('return') ? base64_decode(ee()->input->get('return')) : $sync_url;

        if ($channelEntryCount === 0) {
            ee()->functions->redirect($return);
        }

        ee()->cp->add_js_script('file', 'cp/fields/synchronize');

        // Globals needed for JS script
        ee()->javascript->set_global(array(
            'fieldManager' => array(
                'channel_entry_count' => $channelEntryCount,
                'groupedChannelEntryCounts' => $groupedChannelEntryCounts,

                'sync_baseurl' => $base_url,
                'sync_returnurl' => $return,
                'sync_endpoint' => ee('CP/URL')->make('utilities/sync-conditional-fields/evaluate')->compile(),
                'status_endpoint' => ee('CP/URL')->make('utilities/sync-conditional-fields/status')->compile(),
            )
        ));

        ee()->view->base_url = $base_url;
        ee()->view->cp_page_title = lang('field_conditions_syncing_conditional_logic');
        ee()->view->cp_page_title_alt = lang('field_conditions_syncing_conditional_logic');
        ee()->view->save_btn_text = 'btn_sync_conditional_logic';
        ee()->view->save_btn_text_working = 'btn_sync_conditional_logic_working';

        ee()->view->cp_breadcrumbs = array(
            ee('CP/URL')->make('utilities/sync-conditional-fields')->compile() => lang('sync_conditional_fields'),
            '' => lang('field_conditions_sync_conditional_logic')
        );

        ee()->cp->render('settings/form', $vars);
    }

    // Get certain number of entries and update their conditional logic
    public function evaluate()
    {
        $channel_id = (int) ee()->input->post('channel_id');
        $limit = (int) ee()->input->post('limit');
        $offset = (int) ee()->input->post('offset');
        $status = ee()->input->post('status');

        // Get all channel entries with post data
        $entries = ee('Model')->get('ChannelEntry')
            ->with(['Channel' => ['CustomFields' => ['FieldConditionSets' => 'FieldConditions']]]) // 'HiddenFields'
            ->filter('channel_id', $channel_id)
            ->limit($limit)
            ->offset($offset)
            ->all();

        foreach ($entries as $entry) {
            // Check to see if the conditional fields are outdated before saving
            if ($entry->conditionalFieldsOutdated()) {
                // Conditional fields are outdated, so we evaluate the conditions and save
                $entry->evaluateConditionalFields();
                $entry->HiddenFields->save();
            }
        }

        // clear caches
        if (ee()->config->item('new_posts_clear_caches') == 'y') {
            ee()->functions->clear_caching('all');
        } else {
            ee()->functions->clear_caching('sql');
        }

        return json_encode([
            'message_type' => 'success',
            'channel_id' => $channel_id,
            'entries' => $entries->pluck('entry_id'),
            'entries_proccessed' => $entries->count()
        ]);
    }

    // Update status of sync
    // This add ability to add success message after redirect
    // Also lets EE know when a channel sync is complete so we can set the flag
    public function status()
    {
        $status = ee()->input->post('status');

        // If the sync was successful, show success banner
        if ($status && $status === 'complete') {
            ee('CP/Alert')->makeInline('shared-form')
                ->asSuccess()
                ->withTitle(lang('field_conditions_sync_success'))
                ->addToBody(lang('field_conditions_sync_success_desc'))
                ->defer();

            return json_encode([
                'message_type' => lang('success'),
                'message' => lang('field_conditions_sync_success'),
            ]);
        }

        // If the channel is done syncing set the needs sync flag
        if ($status && $status === 'channel_complete') {
            $channel_id = (int) ee()->input->post('channel_id');
            $channel = ee('Model')->get('Channel', $channel_id)->first();
            $channel->conditional_sync_required = 'n';
            $channel->save();

            return json_encode([
                'message_type' => lang('success'),
                'message' => lang('channel_sync_success'),
            ]);
        }

        return json_encode([
            'message_type' => lang('error'),
            'message' => lang('field_conditions_sync_error'),
        ]);
    }
}

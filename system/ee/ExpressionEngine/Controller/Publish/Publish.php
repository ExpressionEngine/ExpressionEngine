<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Controller\Publish;

use ExpressionEngine\Controller\Publish\AbstractPublish as AbstractPublishController;
use ExpressionEngine\Service\Validation\Result as ValidationResult;
use ExpressionEngine\Model\Channel\ChannelEntry;

/**
 * Publish Controller
 */
class Publish extends AbstractPublishController
{
    public function __construct()
    {
        parent::__construct();

        $perms = [];

        foreach ($this->assigned_channel_ids as $channel_id) {
            $perms[] = 'can_create_entries_channel_id_' . $channel_id;
            $perms[] = 'can_edit_self_entries_channel_id_' . $channel_id;
            $perms[] = 'can_edit_other_entries_channel_id_' . $channel_id;
        }

        if (! ee('Permission')->hasAny($perms)) {
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
        $channel_id = (int) $channel_id;
        $entry_id = (int) $entry_id;

        if (is_numeric($entry_id) && $entry_id != 0) {
            $entry = ee('Model')->get('ChannelEntry', $entry_id)
                ->filter('site_id', ee()->config->item('site_id'))
                ->first();
        } else {
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

        if (AJAX_REQUEST) {
            return ee('View/Helpers')->normalizedChoices($authors);
        }

        return $authors;
    }

    /**
     * AJAX end-point for relationship field filtering
     */
    public function relationshipFilter()
    {
        ee()->load->add_package_path(PATH_ADDONS . 'relationship');
        ee()->load->library('EntryList');
        ee()->output->send_ajax_response(ee()->entrylist->ajaxFilter());
    }

    /**
     * AJAX endpoint for member relationships filter
     *
     * @return void
     */
    public function memberRelationshipFilter()
    {
        $settings = ee('Encrypt')->decode(
            ee('Request')->get('settings'),
            ee()->config->item('session_crypt_key')
        );
        $settings = json_decode($settings, true);

        if (empty($settings)) {
            show_error(lang('unauthorized_access'), 403);
        }

        $settings['search'] = ee('Request')->isPost() ? ee('Request')->post('search') : ee('Request')->get('search');
        $settings['channel_id'] = ee('Request')->isPost() ? ee('Request')->post('channel_id') : ee('Request')->get('channel_id');
        $settings['selected'] = ee('Request')->isPost() ? ee('Request')->post('selected') : ee('Request')->get('selected');

        if (! AJAX_REQUEST or ! ee()->session->userdata('member_id')) {
            show_error(lang('unauthorized_access'), 403);
        }

        $response = array();
        $members = ee('Model')->get('Member')->with('PrimaryRole');
        if (!empty($settings['limit'])) {
            $members->limit((int) $settings['limit']);
        }
        if (!empty($settings['selected'])) {
            $members->filter('member_id', 'NOT IN', explode('|', $settings['selected']));
        }
        if (!empty($settings['channel_id'])) {
            $members->filter('PrimaryRole.role_id', $settings['channel_id']);
        } elseif (!empty($settings['roles'])) {
            $members->filter('PrimaryRole.role_id', 'IN', $settings['roles']);
        }
        if (!empty($settings['search'])) {
            $members->search(['screen_name', 'username', 'email', 'member_id'], $settings['search']);
        }
        if (!empty($settings['order_field'])) {
            $members->order($settings['order_field'], $settings['order_dir'] == 'asc' ? 'asc' : 'desc');
        }
        foreach ($members->all() as $member) {
            $response[] = [
                'value' => $member->getId(),
                'label' => !empty($member->screen_name) ? $member->screen_name : $member->username,
                'instructions' => $member->PrimaryRole->name,
                'channel_id' => $member->role_id
            ];
        }

        ee()->output->send_ajax_response($response);
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
        $channel_id = (int) $channel_id;
        $entry_id = (int) $entry_id;

        $site_id = ee()->config->item('site_id');

        $autosave = ee('Model')->get('ChannelEntryAutosave')
            ->filter('original_entry_id', $entry_id)
            ->filter('site_id', $site_id)
            ->filter('channel_id', $channel_id)
            ->first();

        if (! $autosave) {
            $autosave = ee('Model')->make('ChannelEntryAutosave');
            $autosave->original_entry_id = $entry_id;
            $autosave->site_id = $site_id;
            $autosave->channel_id = $channel_id;
            $autosave->entry_data = $_POST;
        } else {
            $entry_data = $autosave->entry_data;
            foreach ($_POST as $key => $val) {
                $entry_data[$key] = $val;
            }
            $autosave->entry_data = $entry_data;
        }

        $autosave->edit_date = ee()->localize->now;

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
            'original_entry_id' => $entry_id
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
    public function create($channel_id = null, $autosave_id = null)
    {
        if (! $channel_id) {
            show_404();
        }

        $channel_id = (int) $channel_id;

        if (!is_null($autosave_id)) {
            $autosave_id = (int) $autosave_id;
        }

        if (! ee('Permission')->can('create_entries_channel_id_' . $channel_id) or
             ! in_array($channel_id, $this->assigned_channel_ids)) {
            show_error(lang('unauthorized_access'), 403);
        }

        $channel = ee('Model')->get('Channel', $channel_id)
            ->filter('site_id', ee()->config->item('site_id'))
            ->first();

        if (! $channel) {
            show_error(lang('no_channel_exists'));
        }

        // Redirect to edit listing if we've reached max entries for this channel
        if ($channel->maxEntriesLimitReached()) {
            ee()->functions->redirect(
                ee('CP/URL')->make('publish/edit/', array('filter_by_channel' => $channel_id))
            );
        }

        $entry = ee('Model')->make('ChannelEntry');
        $entry->Channel = $channel;
        $entry->site_id = ee()->config->item('site_id');
        $entry->author_id = ee()->session->userdata('member_id');
        $entry->ip_address = ee()->session->userdata['ip_address'];
        $entry->versioning_enabled = $channel->enable_versioning;
        $entry->sticky = false;

        // Set some defaults based on Channel Settings
        $entry->allow_comments = (isset($channel->deft_comments)) ? $channel->deft_comments : true;

        if (isset($channel->deft_status)) {
            $entry->status = $channel->deft_status;
        }

        if (! empty($channel->deft_category)) {
            $cat = ee('Model')->get('Category', $channel->deft_category)->first();
            if ($cat) {
                // set directly so other categories don't get lazy loaded
                // along with our default
                $entry->Categories = $cat;
            }
        }

        $entry->title = $channel->default_entry_title;
        $entry->url_title = $channel->url_title_prefix;

        if (isset($_GET['BK'])) {
            $this->populateFromBookmarklet($entry);
        }

        ee()->view->cp_page_title = sprintf(lang('create_entry_with_channel_name'), $channel->channel_title);

        $form_attributes = array(
            'class' => 'ajax-validate',
        );

        $livePreviewReady = $this->createLivePreviewModal($entry);

        $vars = array(
            'form_url' => ee('CP/URL')->getCurrentUrl(),
            'form_attributes' => $form_attributes,
            'form_title' => lang('new_entry'),
            'errors' => new \ExpressionEngine\Service\Validation\Result(),
            'revisions' => $this->getRevisionsTable($entry),
            'buttons' => $this->getPublishFormButtons($entry, $livePreviewReady),
            'head' => [
                'title' => lang('new_entry'),
                'class' => 'entries'
            ],
        );

        if (ee('Request')->get('modal_form') == 'y' || ! ee('Permission')->can('edit_self_entries_channel_id_' . $entry->channel_id)) {
            $vars['buttons'] = [[
                'name' => 'submit',
                'type' => 'submit',
                'value' => 'save_and_close',
                'text' => 'save_and_close',
                'working' => 'btn_saving'
            ]];
        }

        if (ee('Request')->get('load_autosave') == 'y') {
            $autosaveExists = ee('Model')->get('ChannelEntryAutosave')
                ->fields('entry_id')
                ->filter('original_entry_id', 0)
                ->filter('channel_id', $channel_id)
                ->filter('site_id', ee()->config->item('site_id'))
                ->first();
            if ($autosaveExists) {
                $autosave_id = $autosaveExists->entry_id;
            }
        }

        if ($autosave_id) {
            $autosaved = ee('Model')->get('ChannelEntryAutosave', $autosave_id)
                ->filter('channel_id', $channel_id)
                ->filter('site_id', ee()->config->item('site_id'))
                ->first();

            if ($autosaved) {
                $entry->set($autosaved->entry_data);
            }
        }

        $channel_layout = ee('Model')->get('ChannelLayout')
            ->filter('site_id', ee()->config->item('site_id'))
            ->filter('channel_id', $entry->channel_id)
            ->with('PrimaryRoles')
            ->filter('PrimaryRoles.role_id', ee()->session->userdata('role_id'))
            ->first();

        if (empty($channel_layout)) {
            $channel_layout = ee('Model')->get('ChannelLayout')
                ->filter('site_id', ee()->config->item('site_id'))
                ->filter('channel_id', $entry->channel_id)
                ->with('PrimaryRoles')
                ->filter('PrimaryRoles.role_id', 'IN', ee()->session->getMember()->getAllRoles()->pluck('role_id'))
                ->all()
                ->first();
        }

        $vars['layout'] = $entry->getDisplay($channel_layout);

        $result = $this->validateEntry($entry, $vars['layout']);

        if ($result instanceof ValidationResult) {
            $vars['errors'] = $result;

            if ($result->isValid()) {
                return $this->saveEntryAndRedirect($entry);
            }
        }

        // Auto-saving needs an entry_id...
        $entry->entry_id = 0;

        $vars['autosaves'] = $this->getAutosavesTable($entry, $autosave_id);
        $vars['entry'] = $entry;

        $this->setGlobalJs($entry, true);

        ee()->cp->add_js_script(array(
            'plugin' => array(
                'ee_url_title',
                'ee_filebrowser',
                'ee_fileuploader',
            ),
            'ui' => ['draggable'],
            'file' => array(
                'cp/publish/publish', 
                'cp/publish/entry-list',
                'cp/channel/category_edit',
            )
        ));

        ee()->view->cp_breadcrumbs = array(
            ee('CP/URL')->make('publish/edit')->compile() => lang('entries'),
            ee('CP/URL')->make('publish/edit', ['filter_by_channel' => $channel_id])->compile() => $channel->channel_title,
            '' => lang('new_entry')
        );

        $vars['breadcrumb_title'] = lang('new_entry');

        if (ee('Request')->get('modal_form') == 'y') {
            $vars['layout']->setIsInModalContext(true);

            return ee('View')->make('publish/modal-entry')->render($vars);
        }

        return ee()->cp->render('publish/entry', $vars);
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

        if (($title = ee()->input->get('title')) !== false) {
            $data['title'] = $title;
        }

        foreach ($_GET as $key => $value) {
            if (strpos($key, 'field_id_') === 0) {
                $data[$key] = $value;
            }
        }

        if (empty($data)) {
            return;
        }

        $entry->set($data);
    }

    public function preview($channel_id, $entry_id = null)
    {
        $channel_id = (int) $channel_id;

        if (!is_null($entry_id)) {
            $entry_id = (int) $entry_id;
        }

        return ee('LivePreview')->preview($channel_id, $entry_id);
    }
}

// EOF

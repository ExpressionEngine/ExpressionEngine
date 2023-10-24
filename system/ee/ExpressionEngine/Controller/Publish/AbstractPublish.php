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

use CP_Controller;
use ExpressionEngine\Model\Channel\ChannelEntry;

/**
 * Abstract Publish Controller
 */
abstract class AbstractPublish extends CP_Controller
{
    protected $is_admin = false;
    protected $assigned_channel_ids = array();

    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__construct();

        ee()->lang->loadfile('content');

        ee()->cp->get_installed_modules();

        $this->is_admin = (ee('Permission')->isSuperAdmin());
        $this->assigned_channel_ids = array_keys(ee()->session->userdata['assigned_channels']);

        $this->pruneAutosaves();
    }

    protected function createChannelFilter()
    {
        $allowed_channel_ids = ($this->is_admin) ? null : $this->assigned_channel_ids;
        $channels = ee('Model')->get('Channel', $allowed_channel_ids)
            ->fields('channel_id', 'channel_title')
            ->filter('site_id', ee()->config->item('site_id'))
            ->order('channel_title', 'asc')
            ->all();

        $channel_filter_options = array();
        foreach ($channels as $channel) {
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

        $autosave_interval_seconds = (ee()->config->item('autosave_interval_seconds') === false) ?
                                        60 : ee()->config->item('autosave_interval_seconds');

        //  Create Foreign Character Conversion JS
        $foreign_characters = ee()->config->loadFile('foreign_chars');

        /* -------------------------------------
        /*  'foreign_character_conversion_array' hook.
        /*  - Allows you to use your own foreign character conversion array
        /*  - Added 1.6.0
        *   - Note: in 2.0, you can edit the foreign_chars.php config file as well
        */
        if (isset(ee()->extensions->extensions['foreign_character_conversion_array'])) {
            $foreign_characters = ee()->extensions->call('foreign_character_conversion_array');
        }
        /*
        /* -------------------------------------*/

        $smileys_enabled = (isset(ee()->cp->installed_modules['emoticon']) ? true : false);

        if ($smileys_enabled) {
            ee()->load->helper('smiley');
            ee()->cp->add_to_foot(smiley_js());
        }

        $usesConditionalFields = false;
        foreach ($entry->getCustomFields() as $field) {
            if ($field->getItem('field_is_conditional') === true) {
                $usesConditionalFields = true;

                break;
            }
        }

        ee()->javascript->set_global(array(
            'lang.add_new_html_button' => lang('add_new_html_button'),
            'lang.close' => lang('close'),
            'lang.confirm_exit' => lang('confirm_exit'),
            'lang.loading' => lang('loading'),
            'lang.extra_title' => lang('extra_title'),
            'lang.edit_element' => lang('edit_element'),
            'lang.remove_btn' => lang('remove_btn'),
            'publish.autosave.interval' => (int) $autosave_interval_seconds,
            'publish.autosave.URL' => ee('CP/URL')->make('publish/autosave/' . $channel_id . '/' . $entry_id)->compile(),
            'publish.channel_title' => ee('Format')->make('Text', $entry->Channel->channel_title)
                ->convertToEntities()
                ->compile(),
            'publish.default_entry_title' => $entry->Channel->default_entry_title,
            'publish.foreignChars' => $foreign_characters,
            'publish.urlLength' => URL_TITLE_MAX_LENGTH,
            'publish.lang.no_member_groups' => lang('no_member_roles'),
            'publish.lang.refresh_layout' => lang('refresh_layout'),
            'publish.lang.tab_count_zero' => lang('tab_count_zero'),
            'publish.lang.tab_has_req_field' => lang('tab_has_req_field'),
            'publish.markitup.foo' => false,
            'publish.smileys' => $smileys_enabled,
            'publish.field.URL' => ee('CP/URL', 'publish/field/' . $channel_id . '/' . $entry_id)->compile(),
            'publish.url_title_prefix' => $entry->Channel->url_title_prefix,
            'publish.which' => ($entry_id) ? 'edit' : 'new',
            'publish.word_separator' => ee()->config->item('word_separator') != "dash" ? '_' : '-',
            'publish.has_conditional_fields' => $usesConditionalFields,
            'user.can_edit_html_buttons' => ee('Permission')->can('edit_html_buttons'),
            'user_id' => ee()->session->userdata('member_id'),
            'fileManager.fileDirectory.createUrl' => ee('CP/URL')->make('files/uploads/create')->compile(),
        ));

        ee('Category')->addCategoryJS($channel_id);

        // -------------------------------------------
        //  Publish Page Title Focus - makes the title field gain focus when the page is loaded
        //
        //  Hidden Configuration Variable - publish_page_title_focus => Set focus to the tile? (y/n)

        ee()->javascript->set_global('publish.title_focus', false);

        if (! $entry_id && $valid && bool_config_item('publish_page_title_focus')) {
            ee()->javascript->set_global('publish.title_focus', true);
        }
    }

    protected function getRevisionsTable($entry, $version_id = false)
    {
        $table = ee('CP/Table');

        $table->setColumns(
            array(
                'rev_id',
                'rev_date',
                'rev_author',
                'manage' => array(
                    'encode' => false
                )
            )
        );
        $table->setNoResultsText(lang('no_revisions'));

        $data = array();
        $authors = array();
        $i = $entry->Versions->count();
        $current_author_id = false;
        $current_id = $i + 1;

        foreach ($entry->Versions->sortBy('version_date')->reverse() as $version) {
            if (! isset($authors[$version->author_id])) {
                $authors[$version->author_id] = $version->getAuthorName();
            }

            if (! $current_author_id) {
                $current_author_id = $authors[$version->author_id];
            }

            $toolbar = ee('View')->make('_shared/toolbar')->render(
                array(
                    'toolbar_items' => array(
                        'txt-only' => array(
                            'href' => ee('CP/URL')->make('publish/edit/entry/' . $entry->entry_id, array('version' => $version->version_id)),
                            'title' => lang('load_revision'),
                            'content' => lang('view')
                        ),
                    )
                )
            );

            $attrs = ($version->version_id == $version_id) ? array('class' => 'selected') : array();

            $data[] = array(
                'attrs' => $attrs,
                'columns' => array(
                    $i,
                    ee()->localize->human_time($version->version_date->format('U'), true, true),
                    $authors[$version->author_id],
                    $toolbar
                )
            );
            $i--;
        }

        if (! $entry->isNew()) {
            $attrs = (!$version_id) ? array('class' => 'selected') : array();

            $current_author_id = (!$current_author_id) ? $entry->getAuthorName() : $current_author_id;

            // Current
            $edit_date = ($entry->edit_date)
                ? ee()->localize->human_time($entry->edit_date->format('U'), true, true)
                : null;

            array_unshift(
                $data,
                array(
                    'attrs' => $attrs,
                    'columns' => array(
                        $current_id,
                        $edit_date,
                        $current_author_id,
                        '<a href="' . ee('CP/URL')->make('publish/edit/entry/' . $entry->entry_id) . '"><span class="st-open">' . lang('current') . '</span></a>'
                    ))
            );
        }

        $table->setData($data);

        return ee('View')->make('_shared/table')->render($table->viewData(''));
    }

    protected function getAutosavesTable($entry, $autosave_id = false)
    {
        $table = ee('CP/Table');

        $table->setColumns(
            array(
                'rev_id',
                'rev_date',
                'rev_author',
                'manage' => array(
                    'encode' => false
                )
            )
        );

        $urlParams = [];
        if (ee('Request')->get('hide_closer') == 'y') {
            $urlParams = [
                'entry_ids' => ee('Request')->get('entry_ids'),
                'field_id' => ee('Request')->get('field_id'),
                'site_id' => ee('Request')->get('site_id'),
                'modal_form' => ee('Request')->get('modal_form'),
                'preview' => ee('Request')->get('preview'),
                'hide_closer' => ee('Request')->get('hide_closer'),
                'return' => ee('Request')->get('return')
            ];
        }

        $data = array();
        $authors = array();
        $i = $entry->getAutosaves()->filter('channel_id', $entry->channel_id)->count();

        if (! $entry->isNew()) {
            $i++;
            $attrs = (! $autosave_id) ? ['class' => 'selected'] : [];

            if (! isset($authors[$entry->author_id])) {
                $authors[$entry->author_id] = $entry->getAuthorName();
            }

            // Current
            $edit_date = ($entry->edit_date)
                ? ee()->localize->human_time($entry->edit_date->format('U'), true, true)
                : null;

            $data[] = array(
                'attrs' => $attrs,
                'columns' => array(
                    $i,
                    $edit_date,
                    $authors[$entry->author_id],
                    '<a href="' . ee('CP/URL')->make('publish/edit/entry/' . $entry->entry_id, $urlParams) . '"><span class="st-open">' . lang('current') . '</span></a>'
                )
            );
            $i--;
        }

        $currentAutosaveId = null;
        foreach ($entry->getAutosaves()->filter('channel_id', $entry->channel_id)->sortBy('edit_date')->reverse() as $autosave) {
            if (! isset($authors[$autosave->author_id]) && $autosave->Author) {
                $authors[$autosave->author_id] = $autosave->Author->getMemberName();
            }

            $toolbar = ee('View')->make('_shared/toolbar')->render(
                array(
                    'toolbar_items' => array(
                        'txt-only' => array(
                            'href' => $entry->entry_id
                                ? ee('CP/URL')->make('publish/edit/entry/' . $entry->entry_id . '/' . $autosave->entry_id, $urlParams)
                                : ee('CP/URL')->make('publish/create/' . $entry->Channel->channel_id . '/' . $autosave->entry_id),
                            'title' => lang('view'),
                            'content' => lang('view')
                        ),
                    )
                )
            );

            $attrs = ($autosave->getId() == $autosave_id) ? array('class' => 'selected') : array();
            if ($autosave->getId() == $autosave_id) {
                $currentAutosaveId = $autosave->getId();
            }

            $data[] = array(
                'attrs' => $attrs,
                'columns' => array(
                    $i,
                    ee()->localize->human_time($autosave->edit_date, true, true),
                    isset($authors[$autosave->author_id]) ? $authors[$autosave->author_id] : '-',
                    $toolbar
                )
            );
            $i--;
        }

        if ($autosave_id && ! empty($data) && empty($currentAutosaveId)) {
            $data[0]['attrs'] = ['class' => 'selected'];
        }

        $table->setData($data);

        return ee('View')->make('_shared/table')->render($table->viewData(''));
    }

    protected function validateEntry(ChannelEntry $entry, $layout)
    {
        if (empty($_POST)) {
            return false;
        }

        $action = ($entry->isNew()) ? 'create' : 'edit';

        // Get all the fields that should be in the DOM. Any that were not
        // POSTed will be set to NULL. This addresses a bug where browsers
        // do not POST unchecked checkboxes.
        $category_fields = [];
        $category_fields_hidden = [];

        foreach ($layout->getTabs() as $tab) {
            // Invisible tabs were not rendered
            if ($tab->isVisible()) {
                foreach ($tab->getFields() as $field) {
                    // Fields that were not required and not visible were not rendered
                    $field_name = strstr($field->getName(), '[', true) ?: $field->getName();

                    //categories need special treatment
                    if ($field_name == 'categories') {
                        $category_fields[] = $field->getName();
                        if (!$field->isVisible()) {
                            $category_fields_hidden[] = $field->getName();
                        }
                    }

                    if (! $field->isRequired() && ! $field->isVisible()) {
                        continue;
                    }

                    if (! array_key_exists($field_name, $_POST)) {
                        $_POST[$field_name] = null;
                    }
                }
            }
        }

        if (! ee('Permission')->can('assign_post_authors')) {
            unset($_POST['author_id']);
        }

        //workaround if some category groups are hidden and some are displayed
        if (count($category_fields_hidden) != 0 && count($category_fields_hidden) != count($category_fields)) {
            foreach ($category_fields_hidden as $fieldname) {
                $cat_group_name = trim(strstr($fieldname, '['), '[]');
                $cat_group_id = str_replace('cat_group_id_', '', $cat_group_name);
                $_POST['categories'][$cat_group_name] = $entry->Categories->filter('group_id', $cat_group_id)->pluck('cat_id');
            }
        }

        if (defined('CLONING_MODE') && CLONING_MODE === true && $this->entryCloningEnabled($entry)) {
            $entry->setId(null);
            $word_separator = ee()->config->item('word_separator') != "dash" ? '_' : '-';
            while (true !== $entry->validateUniqueUrlTitle('url_title', $_POST['url_title'], ['channel_id'], null)) {
                $_POST['url_title'] = 'copy' . $word_separator . $_POST['url_title'];
            }
            if ($_POST['title'] == $entry->title) {
                $_POST['title'] = lang('copy_of') . ' ' . $_POST['title'];
            }
            $action = 'create';
            $entry->set($_POST);
            $entry->markAsDirty();
        } else {
            if ($entry->isNew() && $entry->Channel->enforce_auto_url_title) {
                $_POST['url_title'] = ee('Format')->make('Text',  $entry->Channel->url_title_prefix . ee()->input->post('title', true))->urlSlug()->compile();
                $word_separator = ee()->config->item('word_separator') != "dash" ? '_' : '-';
                while (true !== $entry->validateUniqueUrlTitle('url_title', $_POST['url_title'], ['channel_id'], null)) {
                    $_POST['url_title'] = $_POST['url_title'] . $word_separator . uniqid();
                }
            }
            $entry->set($_POST);
        }

        $hidden_fields = $entry->evaluateConditionalFields();

        $result = $entry->validate();

        if ($response = $this->ajaxValidation($result)) {
            if (isset($response[0]) && $response[0] == 'success') {
                $response = [
                    'success' => 'success',
                    'hidden_fields' => $hidden_fields
                ];
            }

            ee()->output->send_ajax_response($response);
        }

        if ($result->failed()) {
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
        if (defined('CLONING_MODE') && CLONING_MODE === true && $this->entryCloningEnabled($entry)) {
            $action = 'clone';
            $entry->markAsDirty();
            $entry->set([
                'status' => 'closed',
                'recent_comment_date' => null,
                'comment_total' => 0,
                'forum_topic_id' => null,
                'view_count_one' => 0,
                'view_count_two' => 0,
                'view_count_three' => 0,
                'view_count_four' => 0,
                'entry_date' => ee()->localize->now,
            ]);
        }
        $entry->save();

        ee()->session->set_flashdata('entry_id', $entry->entry_id);

        $edit_entry_url = ee('CP/URL', 'publish/edit/entry/' . $entry->entry_id);

        $alert = (ee('Request')->get('modal_form') == 'y' && ee('Request')->get('next_entry_id'))
            ? ee('CP/Alert')->makeStandard()
            : ee('CP/Alert')->makeInline('entry-form');

        $lang_string = sprintf(lang($action . '_entry_success_desc'), htmlentities($edit_entry_url, ENT_QUOTES, 'UTF-8'), htmlentities($entry->title, ENT_QUOTES, 'UTF-8'), ee()->localize->human_time($entry->edit_date, true, true));

        $alert->asSuccess()
            ->withTitle(lang($action . '_entry_success'))
            ->addToBody($lang_string)
            ->defer();

        $qs = $_GET;
        unset($qs['S'], $qs['D'], $qs['C'], $qs['M'], $qs['version']);

        // Loop through and clean GET values
        foreach ($qs as $key => $value) {
            $qs[$key] = ee('Security/XSS')->clean($value);
        }

        if (ee('Request')->get('modal_form') == 'y') {
            $next_entry_id = ee('Request')->get('next_entry_id');

            $result = [
                'saveId' => $entry->getId(),
                'item' => [
                    'value' => $entry->getId(),
                    'label' => $entry->title,
                    'instructions' => $entry->Channel->channel_title
                ]
            ];

            if (is_numeric($next_entry_id)) {
                $next_entry = ee('CP/URL')->getCurrentUrl();
                $next_entry->path = 'publish/edit/entry/' . $next_entry_id;
                $result += ['redirect' => $next_entry->compile()];
            }

            return $result;
        } elseif (ee()->input->post('submit') == 'save' || (defined('CLONING_MODE') && CLONING_MODE === true)) {
            // If we just cloned an entry, we set the "status changed" warning banner
            if ((defined('CLONING_MODE') && CLONING_MODE === true)) {
                $cloneAlert = (ee('Request')->get('modal_form') == 'y' && ee('Request')->get('next_entry_id'))
                    ? ee('CP/Alert')->makeStandard('entry-form-clone')
                    : ee('CP/Alert')->makeInline('entry-form-clone');

                $cloneAlert->asWarning()
                    ->canClose()
                    ->addToBody(sprintf(lang('status_changed_desc'), lang('closed')))
                    ->defer();
            }

            if (ee()->input->get('return') != '') {
                $redirect_url = urldecode(ee()->input->get('return'));
            } elseif (ee()->input->post('return') != '') {
                $redirect_url = ee()->input->post('return');
            } else {
                $redirect_url = ee('CP/URL')->make('publish/edit/entry/' . $entry->getId(), $qs);
            }
            ee()->functions->redirect($redirect_url);
        } elseif (ee()->input->post('submit') == 'save_and_close') {
            if (! empty($qs)) {
                $redirect_url = ee('CP/URL')->make('publish/edit/', $qs);
            } else {
                $redirect_url = ee('CP/URL')->make('publish/edit/', array('filter_by_channel' => $entry->channel_id));
            }

            /* -------------------------------------
            /*  'entry_save_and_close_redirect' hook.
            /*  - Redirect to a different URL when "Save & Close" is clicked
            /*  - Added 4.0.0
            */
            if (ee()->extensions->active_hook('entry_save_and_close_redirect')) {
                $redirect_url = ee()->extensions->call('entry_save_and_close_redirect', $entry, $redirect_url);
            }
            /*
            /* -------------------------------------*/

            ee()->functions->redirect($redirect_url);
        } else {
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
        $prune = $prune * 3600; // From hours to seconds

        $cutoff = ee()->localize->now - $prune;

        $autosave = ee('Model')->get('ChannelEntryAutosave')
            ->filter('edit_date', '<', $cutoff)
            ->delete();
    }

    /**
     * Get Submit Buttons for Publish Edit Form
     * @param  ChannelEntry $entry ChannelEntry model entity
     * @param  bool $livePreviewSetup indicates whether Live Preview has been set up correctly
     * @return array Submit button array
     */
    protected function getPublishFormButtons(ChannelEntry $entry, $livePreviewSetup = true)
    {
        $buttons = [
            [
                'name' => 'submit',
                'type' => 'submit',
                'value' => 'save',
                'text' => 'save',
                'working' => 'btn_saving',
                'shortcut' => 's',
                // Disable these while JS is still loading key components, re-enabled in publish.js
                'attrs' => 'disabled="disabled"'
            ]
        ];

        if (ee('Permission')->has('can_create_entries') && !$entry->Channel->maxEntriesLimitReached()) {
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

        if (!$entry->isNew() && $this->entryCloningEnabled($entry) && !$entry->Channel->maxEntriesLimitReached()) {
            $buttons[] = [
                'name' => 'submit',
                'type' => 'submit',
                'value' => 'save_as_new_entry',
                'text' => 'save_as_new_entry',
                'working' => 'btn_saving',
                'attrs' => 'disabled="disabled"'
            ];
        }

        if ($livePreviewSetup === true) {
            $buttons[] = [
                'name' => 'submit',
                'type' => 'submit',
                'value' => 'preview',
                'text' => 'preview',
                'class' => 'action',
                'attrs' => 'rel="live-preview" disabled="disabled"',
            ];
        } elseif ($livePreviewSetup === false && ee('Permission')->hasAll('can_admin_channels', 'can_edit_channels')) {
            $buttons[] = [
                'name' => 'submit',
                'type' => 'button',
                'value' => 'preview',
                'text' => 'preview',
                'html' => '<i class="app-notice__icon"></i> ',
                'class' => 'action',
                'attrs' => 'rel="live-preview-setup" disabled="disabled"',
            ];
        }

        return $buttons;
    }

    protected function entryCloningEnabled(ChannelEntry $entry)
    {
        if (ee('pro:Access')->hasRequiredLicense()) {
            if (ee()->config->item('enable_entry_cloning') === false || ee()->config->item('enable_entry_cloning') === 'y') {
                if ($entry->Channel->enable_entry_cloning) {
                    return true;
                }
            }
        }

        return false;
    }

    protected function createLivePreviewModal(ChannelEntry $entry)
    {
        if (($entry->livePreviewAllowed() && $entry->isLivePreviewable()) || ee()->input->get('return') != '') {
            $lp_domain_mismatch = false;
            if (isset($_SERVER['HTTP_HOST']) && !empty($_SERVER['HTTP_HOST'])) {
                $lp_domain_mismatch = true;
                $configuredUrls = ee('Model')->get('Config')
                    ->filter('key', 'IN', ['base_url', 'site_url', 'cp_url'])
                    ->all()
                    ->pluck('parsed_value');
                $extraDomains = ee('Config')->getFile()->get('allowed_preview_domains');
                if (!empty($extraDomains)) {
                    if (!is_array($extraDomains)) {
                        $extraDomains = explode(',', $extraDomains);
                    }
                    $configuredUrls = array_merge($configuredUrls, $extraDomains);
                }
                foreach ($configuredUrls as $configuredUrl) {
                    if (strpos($configuredUrl, $_SERVER['HTTP_HOST']) !== false) {
                        $lp_domain_mismatch = false;

                        break;
                    }
                }
            }

            if ($lp_domain_mismatch) {
                $lp_setup_alert = ee('CP/Alert')->makeBanner('live-preview-setup')
                    ->asIssue()
                    ->canClose()
                    ->withTitle(lang('preview_cannot_display'))
                    ->addToBody(lang('preview_domain_error_instructions'));
                ee()->javascript->set_global('alert.lp_setup', $lp_setup_alert->render());

                return false;
            } else {
                $action_id = ee()->db->select('action_id')
                    ->where('class', 'Channel')
                    ->where('method', 'live_preview')
                    ->get('actions');
                $preview_url = ee()->functions->fetch_site_index() . QUERY_MARKER . 'ACT=' . $action_id->row('action_id') . AMP . 'channel_id=' . $entry->channel_id;
                if (!empty($entry->entry_id)) {
                    $preview_url .= AMP . 'entry_id=' . $entry->entry_id;
                }
                if (ee()->input->get('return') != '') {
                    $preview_url .= AMP . 'return=' . rawurlencode(base64_encode(urldecode(ee()->input->get('return', true))));
                }
                if (ee()->input->get('prefer_system_preview') == 'y') {
                    $preview_url .= AMP . 'prefer_system_preview=y';
                }
                //cross-domain live previews are only possible if $_SERVER['HTTP_HOST'] is set
                if (isset($_SERVER['HTTP_HOST']) && !empty($_SERVER['HTTP_HOST'])) {
                    $preview_url .= AMP . 'from=' . rawurlencode(base64_encode((ee('Request')->isEncrypted() ? 'https://' : 'http://') . strtolower($_SERVER['HTTP_HOST'])));
                }
                $modal_vars = [
                    'preview_url' => $preview_url,
                    'hide_closer' => ee()->input->get('hide_closer') === 'y' ? true : false
                ];
                $modal = ee('View')->make('publish/live-preview-modal')->render($modal_vars);
                ee('CP/Modal')->addModal('live-preview', $modal);

                return true;
            }
        } elseif (!$entry->livePreviewAllowed()) {
            // if preview is disabled on channel, we do not show banner
            return null;
        } elseif (ee('Permission')->hasAll('can_admin_channels', 'can_edit_channels')) {
            $lp_setup_alert = ee('CP/Alert')->makeBanner('live-preview-setup')
                ->asIssue()
                ->canClose()
                ->withTitle(lang('preview_url_not_set'))
                ->addToBody(sprintf(lang('preview_url_not_set_desc'), ee('CP/URL')->make('channels/edit/' . $entry->channel_id)->compile() . '#tab=t-4&id=fieldset-preview_url'));
            ee()->javascript->set_global('alert.lp_setup', $lp_setup_alert->render());
            return false;
        }

        return null;
    }
}

// EOF

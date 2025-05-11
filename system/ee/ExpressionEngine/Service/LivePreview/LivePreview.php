<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Service\LivePreview;

use ExpressionEngine\Model\Channel\ChannelEntry;

/**
 * LivePreview Service
 */
class LivePreview
{
    /**
     * @var obj $session_delegate A Session object
     */
    private $session_delegate;

    /**
     * @var string $cache_class The class name to hand off to the Session cache
     */
    private $cache_class = 'channel_entry';

    /**
     * @var string $key The key to hand off to the Session cache
     */
    private $key = 'live-preview';

    /**
     * Constructor
     *
     * @param obj $session_delegate A Session object
     */
    public function __construct($session_delegate)
    {
        $this->session_delegate = $session_delegate;
    }

    /**
     * Do we have entry data?
     *
     * @return bool TRUE if it is, FALSE if it is not
     */
    public function hasEntryData()
    {
        return ($this->getEntryData() !== false);
    }

    /**
     * Gets the entry data for the live preview.
     *
     * @return array|bool Array of entry data or FALSE if there is no preview data
     */
    public function getEntryData()
    {
        return $this->session_delegate->cache($this->cache_class, $this->key, false);
    }

    /**
     * Sets the live preview data
     *
     * @param array $data The entry data
     * @return void
     */
    public function setEntryData($data)
    {
        $this->session_delegate->set_cache($this->cache_class, $this->key, $data);
    }

    /**
     * Determine whether the LivePreview is for a specific Entry
     *
     * @param int $id
     * @return boolean
     */
    public function forEntryId($id)
    {
        if (!$this->hasEntryData()) {
            return false;
        }

        $entryId = $this->getEntryData()['entry_id'] ?? null;

        return $entryId === (int) $id;
    }

    /**
     * Create Live Preview Modal, or prepare the URL
     *
     * @param ChannelEntry $entry
     * @param string $return modal|url
     * @return void
     */
    public function createLivePreviewModal(ChannelEntry $entry, $return = 'modal')
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
                if ($return != 'modal') {
                    return false;
                }
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
                if ($return == 'url') {
                    return $preview_url;
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
            if ($return != 'modal') {
                return false;
            }
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

    /**
     * generate and display the live preview
     */
    public function preview($channel_id, $entry_id = null, $preview_url = null, $prefer_system_preview = false, $useSavedData = false)
    {
        if (empty($_POST) && !$useSavedData) {
            return;
        }

        $channel = ee('Model')->get('Channel', $channel_id)
            ->filter('site_id', ee()->config->item('site_id'))
            ->first();

        if ($entry_id) {
            $entry = ee('Model')->get('ChannelEntry', $entry_id)
                ->with('Channel', 'Author')
                ->first();
        } else {
            $entry = ee('Model')->make('ChannelEntry');
            $entry->entry_id = PHP_INT_MAX;
            $entry->Channel = $channel;
            $entry->site_id = ee()->config->item('site_id');
            $entry->author_id = ee()->session->userdata('member_id');
            $entry->ip_address = ee()->session->userdata['ip_address'];
            $entry->versioning_enabled = $channel->enable_versioning;
            $entry->sticky = false;
        }

        $data = [];
        if (!$useSavedData) {
            $entry->set($_POST);
            $data = $entry->getModChannelResultsArray();
            // because the template parser operates with saved data, and we have only raw data
            // we need to normalize those first
            // the data passed with POST can be different (array, or formatting applied)
            // so we pass it through save() function of the fieldtypes
            // which normally returns the field's to-be-saved content
            ee()->legacy_api->instantiate('channel_fields');
            foreach ($entry->getStructure()->getAllCustomFields() as $field) {
                $key = 'field_id_' . $field->getId();
                if (array_key_exists($key, $_POST) && !empty($data[$key])) {
                    $ftClass = ucfirst($field->field_type) . '_ft';
                    ee()->api_channel_fields->include_handler($field->field_type);
                    $justTheFt = new $ftClass();
                    try {
                        $saved = $justTheFt->save($_POST[$key]);
                        if (!empty($saved)) {
                            $data[$key] = $saved;
                        }
                    } catch (\Throwable $e) {
                        // `save` code might be too complex, so if it errors, silently continue
                    }
                }
            }
            $data['entry_site_id'] = $entry->site_id;
            if (isset($_POST['categories'])) {
                $data['categories'] = $_POST['categories'];
            }

            //perform conditional fields calculations
            $hiddenFields = $entry->evaluateConditionalFields();
            if (!empty($hiddenFields)) {
                foreach ($hiddenFields as $hiddenFieldId) {
                    $data['field_hide_' . $hiddenFieldId] = 'y';
                    $data['field_id_' . $hiddenFieldId] = null;
                }
            }

            ee('LivePreview')->setEntryData($data);
        }

        ee()->load->library('template', null, 'TMPL');

        $template_id = null;

        if (! $useSavedData &&
            ! empty($_POST['pages__pages_uri']) &&
            ! empty($_POST['pages__pages_template_id'])
           ) {
            //pages data passed with POST
            $values = [
                'pages_uri' => $_POST['pages__pages_uri'],
                'pages_template_id' => $_POST['pages__pages_template_id'],
            ];

            $page_tab = new \Pages_tab();
            $site_pages = $page_tab->prepareSitePagesData($entry, $values);

            ee()->config->set_item('site_pages', $site_pages);
            $entry->Site->site_pages = $site_pages;

            $template_id = $_POST['pages__pages_template_id'];
        }

        if (!empty($preview_url)) {
            //preview/return url directly specified
            $site_index = str_ireplace(['http:', 'https:'], '', ee()->functions->fetch_site_index());
            $preview_url = str_ireplace(['http:', 'https:'], '', $preview_url);
            $uri = str_replace($site_index, '', $preview_url);
            $parsed_url = parse_url($uri);
            if ($parsed_url && isset($parsed_url['host'])) {
                $uri = str_ireplace($parsed_url['host'], '', $uri);
            }
            $uri = trim($uri, '/');
        }

        if (empty($preview_url) || $prefer_system_preview === true) {
            if ($entry->hasPageURI()) {
                //pre-existing page URI
                $uri = $entry->getPageURI();
                ee()->uri->page_query_string = $entry->entry_id;
                if (! $template_id) {
                    $template_id = $entry->getPageTemplateID();
                }
            } elseif (!empty($channel->preview_url)) {
                //channel settings
                // We want to avoid replacing `{url_title}` with an empty string since that
                // can cause the wrong thing to render (like 404s).
                if (empty($entry->url_title)) {
                    $entry->url_title = $entry->entry_id;
                }

                $uri = str_replace(['{url_title}', '{entry_id}'], [$entry->url_title, $entry->entry_id], $channel->preview_url);
            }
        }

        // -------------------------------------------
        // 'publish_live_preview_route' hook.
        //  - Set alternate URI and/or template to use for preview
        //  - Added 4.2.0
        if (ee()->extensions->active_hook('publish_live_preview_route') === true) {
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

        if ($template_id) {
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

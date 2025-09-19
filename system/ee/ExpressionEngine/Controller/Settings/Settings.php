<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Controller\Settings;

use CP_Controller;
use ExpressionEngine\Library\CP;

/**
 * Settings Controller
 */
class Settings extends CP_Controller
{
    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__construct();

        ee('CP/Alert')->makeDeprecationNotice()->now();

        if (! ee('Permission')->can('access_sys_prefs')) {
            show_error(lang('unauthorized_access'), 403);
        }

        ee()->lang->loadfile('settings');
        ee()->load->library('form_validation');
        ee()->load->model('addons_model');

        $this->generateSidebar();

        ee()->view->header = array(
            'title' => lang('system_settings'),
        );
    }

    protected function generateSidebar($active = null)
    {
        $sidebar = ee('CP/Sidebar')->make();
        ee()->lang->load('pro');

        $list = $sidebar->addHeader(lang('general'))
            ->addBasicList();

        $sidebar->addItem(lang('general_settings'), ee('CP/URL')->make('settings/general'));
        $sidebar->addItem(lang('url_path_settings'), ee('CP/URL')->make('settings/urls'));

        if (ee('Permission')->can('access_comm')) {
            $sidebar->addItem(lang('outgoing_email'), ee('CP/URL')->make('settings/email'));
        }

        $sidebar->addItem(lang('debugging_output'), ee('CP/URL')->make('settings/debug-output'));

        $sidebar->addItem(lang('logging'), ee('CP/URL')->make('settings/logging'));

        $content_and_design_link = null;

        if (ee('Permission')->can('admin_channels')) {
            $content_and_design_link = ee('CP/URL')->make('settings/content-design');
        }

        $list = $sidebar->addHeader(lang('content_and_design'))
            ->addBasicList();

        $list->addItem(lang('settings'), $content_and_design_link);

        if (ee('Permission')->hasAll('can_access_addons', 'can_admin_addons')) {
            $list->addItem(lang('comment_settings'), ee('CP/URL')->make('settings/comments'));
        }

        $link = ee('CP/URL')->make('settings/buttons');
        $item = $list->addItem(lang('html_buttons'), $link);
        if ($link->matchesTheRequestedURI()) {
            $item->isActive();
        }

        if (ee('Permission')->hasAll('can_access_design', 'can_admin_design')) {
            $list->addItem(lang('template_settings'), ee('CP/URL')->make('settings/template'));
        }

        $sidebar->addItem(lang('frontedit'), ee('CP/URL')->make('settings/pro/frontedit'));
        $sidebar->addItem(lang('branding_settings'), ee('CP/URL')->make('settings/pro/branding'));

        $list->addItem(lang('tracking'), ee('CP/URL')->make('settings/tracking'));

        $list->addItem(lang('word_censoring'), ee('CP/URL')->make('settings/word-censor'));

        $link = ee('CP/URL')->make('settings/menu-manager');
        $item = $list->addItem(lang('menu_manager'), $link);
        if ($link->matchesTheRequestedURI()) {
            $item->isActive();
        }

        if (ee('Permission')->hasAll('can_access_members', 'can_admin_roles')) {
            $list = $sidebar->addHeader(lang('members'))
                ->addBasicList();

            $list->addItem(lang('member_settings'), ee('CP/URL')->make('settings/members'));
            $link = ee('CP/URL')->make('settings/member-fields');
            $item = $list->addItem(lang('custom_member_fields'), $link);
            if ($link->matchesTheRequestedURI()) {
                $item->isActive();
            }
            $list->addItem(lang('manage_bans'), ee('CP/URL')->make('settings/ban'));
            $list->addItem(lang('messages'), ee('CP/URL')->make('settings/messages'));
            $list->addItem(lang('avatars'), ee('CP/URL')->make('settings/avatars'));
        }

        if (ee('Permission')->can('access_security_settings')) {
            $list = $sidebar->addHeader(lang('security_privacy'))
                ->addBasicList();

            $list->addItem(lang('settings'), ee('CP/URL')->make('settings/security-privacy'));
            $list->addItem(lang('access_throttling'), ee('CP/URL')->make('settings/throttling'));
            $list->addItem(lang('captcha'), ee('CP/URL')->make('settings/captcha'));
        } elseif (ee('Permission')->can('manage_consents')) {
            $list = $sidebar->addHeader(lang('security_privacy'))->addBasicList();
        }

        if (ee('Permission')->can('manage_consents')) {
            $link = ee('CP/URL')->make('settings/consents');
            $item = $list->addItem(lang('consent_requests'), $link);
            if ($link->matchesTheRequestedURI()) {
                $item->isActive();
            }
        }

        $sidebar->addItem(lang('cookie_settings'), ee('CP/URL')->make('settings/pro/cookies'));
    }

    /**
     * Index
     */
    public function index()
    {
        $landing = ee('CP/URL')->make('settings');

        // Redirect to the first section they have permission
        $settings_options = array(
            'can_access_sys_prefs' => ee('CP/URL')->make('settings/general'),
            'can_admin_design' => ee('CP/URL')->make('settings/content-design'),
            'can_access_members' => ee('CP/URL')->make('settings/members'),
            'can_access_security_settings' => ee('CP/URL')->make('settings/security-privacy')
        );

        foreach ($settings_options as $allow => $link) {
            if (ee('Permission')->hasAll($allow)) {
                $landing = $link;

                break;
            }
        }

        ee()->functions->redirect($landing);
    }

    /**
     * Generic method to take an array of fields structured for the form
     * view, check POST for their values, and then save the values in site
     * preferences
     *
     * @param   array   $sections   Array of sections passed to form view
     * @return  bool    Success or failure of saving the settings
     */
    protected function saveSettings($sections)
    {
        // Clear the add-on cache in case they've changed their site license key.
        ee()->cache->file->delete('/addons-status');

        $fields = array();

        // Make sure we're getting only the fields we asked for
        foreach ($sections as $settings) {
            if (isset($settings['settings'])) {
                $fields = array_merge($fields, $this->getFieldsForSettings($settings['settings']));
            } else {
                $fields = array_merge($fields, $this->getFieldsForSettings($settings));
            }
        }

        // Any values that are strictly a false boolean should be ignored as this
        // is only possible as output from ee()->input->post() when a value wasn't sent
        $fields = array_filter($fields, function ($value) {
            return $value !== false;
        });

        $config_update = ee()->config->update_site_prefs($fields);

        if (! empty($config_update)) {
            ee()->load->helper('html_helper');
            ee()->view->set_message('issue', lang('cp_message_issue'), ul($config_update), true);

            return false;
        }

        return true;
    }

    /**
     * Get the fields from settings' arrays
     * @param  array $settings Array of settings
     * @return array Array of [field_name] => [value]
     */
    private function getFieldsForSettings($settings)
    {
        $fields = array();
        foreach ($settings as $setting) {
            if (!empty($setting)) {
                foreach ($setting['fields'] as $field_name => $field) {
                    if (isset($field['save_in_config']) && $field['save_in_config'] === false) {
                        continue;
                    }

                    $fields[$field_name] = ee()->input->post($field_name);
                }
            }
        }

        return $fields;
    }
}
// END CLASS

// EOF

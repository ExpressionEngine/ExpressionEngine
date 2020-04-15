<?php

/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2020, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace EllisLab\ExpressionEngine\Service\JumpMenu;

/**
 * Custom Menu
 */
class JumpMenu extends AbstractJumpMenu
{
    protected static $items = array(
        '1' => array(
            'home' => array(
                'icon' => 'fa-home',
                'command' => 'home dashboard',
                'dynamic' => false,
                'addon' => false,
                'target' => 'homepage'
            ),
            'viewEntriesIn' => array(
                'icon' => 'fa-eye',
                'command' => 'view entries in',
                'dynamic' => true,
                'addon' => false,
                'target' => 'publish/view',
                'permission' => null
            ),
            'createEntryIn' => array(
                'icon' => 'fa-plus',
                'command' => 'create new publish entry in',
                'dynamic' => true,
                'addon' => false,
                'target' => 'publish/create',
                'permission' => ['can_create_entries']
            ),
            'editEntry' => array(
                'icon' => 'fa-pencil-alt',
                'command' => 'edit entry titled',
                'dynamic' => true,
                'addon' => false,
                'target' => 'publish/edit',
                'permission' => ['can_edit_other_entries', 'can_edit_self_entries']
            ),
            'createMember' => array(
                'icon' => 'fa-plus',
                'command' => 'create new publish member',
                'dynamic' => false,
                'addon' => false,
                'target' => 'members/create',
                'permission' => 'can_create_members'
            ),
            'createMemberRole' => array(
                'icon' => 'fa-plus',
                'command' => 'create new publish member role group',
                'dynamic' => false,
                'addon' => false,
                'target' => 'members/groups/create',
                'permission' => 'can_create_roles'
            ),
            'createCategoryIn' => array(
                'icon' => 'fa-plus',
                'command' => 'create new publish category in',
                'dynamic' => true,
                'addon' => false,
                'target' => 'categories/create',
                'permission' => 'can_create_categories'
            ),
            'editCategory' => array(
                'icon' => 'fa-pencil-alt',
                'command' => 'edit category titled',
                'dynamic' => true,
                'addon' => false,
                'target' => 'categories/edit',
                'permission' => 'can_edit_categories'
            ),
            'viewFiles' => array(
                'icon' => 'fa-eye',
                'command' => 'view all files',
                'dynamic' => false,
                'addon' => false,
                'target' => 'files',
                'permission' => 'can_access_files'
            ),
            'viewFilesIn' => array(
                'icon' => 'fa-eye',
                'command' => 'view files in',
                'dynamic' => true,
                'addon' => false,
                'target' => 'files/view',
                'permission' => 'can_edit_files'
            ),
            'editUploadDirectory' => array(
                'icon' => 'fa-pencil-alt',
                'command' => 'edit upload directory',
                'dynamic' => true,
                'addon' => false,
                'target' => 'files/directories',
                'permission' => 'can_edit_upload_directories'
            ),
            'viewMembers' => array(
                'icon' => 'fa-eye',
                'command' => 'view members',
                'dynamic' => false,
                'addon' => false,
                'target' => 'members',
                'permission' => 'can_access_members'
            ),
            'viewMembersIn' => array(
                'icon' => 'fa-eye',
                'command' => 'view members in role group',
                'dynamic' => true,
                'addon' => false,
                'target' => 'members/view',
                'permission' => 'can_access_members'
            ),
            'editMember' => array(
                'icon' => 'fa-pencil-alt',
                'command' => 'edit member titled',
                'dynamic' => true,
                'addon' => false,
                'target' => 'members/edit',
                'permission' => 'can_edit_members'
            ),
            'editMemberRole' => array(
                'icon' => 'fa-pencil-alt',
                'command' => 'edit member role group titled',
                'dynamic' => true,
                'addon' => false,
                'target' => 'members/group',
                'permission' => 'can_edit_roles'
            ),
            'viewAddons' => array(
                'icon' => 'fa-eye',
                'command' => 'view addons add-ons modules plugins extensions',
                'dynamic' => false,
                'addon' => false,
                'target' => 'addons',
                'permission' => 'can_access_addons'
            ),
            'viewChannels' => array(
                'icon' => 'fa-eye',
                'command' => 'view channels',
                'dynamic' => false,
                'addon' => false,
                'target' => 'channels',
                'permission' => 'can_admin_channels'
            ),
            'viewChannelFields' => array(
                'icon' => 'fa-eye',
                'command' => 'view channel fields',
                'dynamic' => false,
                'addon' => false,
                'target' => 'fields',
                'permission' => ['can_create_channel_fields', 'can_edit_channel_fields']
            ),
            'createChannel' => array(
                'icon' => 'fa-plus',
                'command' => 'create new publish channel',
                'dynamic' => false,
                'addon' => false,
                'target' => 'channels/create',
                'permission' => 'can_create_channels'
            ),
            'editChannel' => array(
                'icon' => 'fa-pencil-alt',
                'command' => 'edit channel settings titled',
                'dynamic' => true,
                'addon' => false,
                'target' => 'channels/edit',
                'permission' => 'can_edit_channels'
            ),
            'createChannelField' => array(
                'icon' => 'fa-plus',
                'command' => 'create new publish channel field',
                'dynamic' => false,
                'addon' => false,
                'target' => 'fields/create',
                'permission' => 'can_create_channel_fields'
            ),
            'viewTemplates' => array(
                'icon' => 'fa-eye',
                'command' => 'view templates',
                'dynamic' => false,
                'addon' => false,
                'target' => 'design',
                'permission' => 'can_access_design'
            ),
            'viewTemplatesIn' => array(
                'icon' => 'fa-eye',
                'command' => 'view templates in',
                'dynamic' => true,
                'addon' => false,
                'target' => 'templates/view',
                'permission' => 'can_access_design'
            ),
            'createTemplateGroup' => array(
                'icon' => 'fa-plus',
                'command' => 'create new publish template group',
                'dynamic' => false,
                'addon' => false,
                'target' => 'design/group/create',
                'permission' => 'can_create_template_groups'
            ),
            'createTemplateIn' => array(
                'icon' => 'fa-plus',
                'command' => 'create new publish template in',
                'dynamic' => true,
                'addon' => false,
                'target' => 'templates/create',
                'permission' => 'can_access_design'
            ),
            'editTemplate' => array(
                'icon' => 'fa-pencil-alt',
                'command' => 'edit template titled',
                'dynamic' => true,
                'addon' => false,
                'target' => 'templates/edit',
                'permission' => 'can_access_design'
            ),
            'editTemplateGroup' => array(
                'icon' => 'fa-pencil-alt',
                'command' => 'edit template group titled',
                'dynamic' => true,
                'addon' => false,
                'target' => 'templates/group',
                'permission' => 'can_edit_template_groups'
            ),
            'systemSettingsGeneral' => array(
                'icon' => 'fa-wrench',
                'command' => 'edit general settings',
                'dynamic' => false,
                'addon' => false,
                'target' => 'settings/general',
                'permission' => 'can_access_sys_prefs'
            ),
            'systemSettingsUrls' => array(
                'icon' => 'fa-wrench',
                'command' => 'edit general settings url and path settings',
                'dynamic' => false,
                'addon' => false,
                'target' => 'settings/urls',
                'permission' => 'can_access_sys_prefs'
            ),
            'systemSettingsEmail' => array(
                'icon' => 'fa-wrench',
                'command' => 'edit general settings email settings',
                'dynamic' => false,
                'addon' => false,
                'target' => 'settings/email',
                'permission' => 'can_access_sys_prefs'
            ),
            'systemSettingsDebugging' => array(
                'icon' => 'fa-wrench',
                'command' => 'edit general settings debugging settings',
                'dynamic' => false,
                'addon' => false,
                'target' => 'settings/debug-output',
                'permission' => 'can_access_sys_prefs'
            ),
            'systemSettingsContentDesign' => array(
                'icon' => 'fa-wrench',
                'command' => 'edit content and design settings',
                'dynamic' => false,
                'addon' => false,
                'target' => 'settings/content-design',
                'permission' => 'can_access_sys_prefs'
            ),
            'systemSettingsComments' => array(
                'icon' => 'fa-wrench',
                'command' => 'edit content and design comment settings',
                'dynamic' => false,
                'addon' => false,
                'target' => 'settings/comments',
                'permission' => 'can_access_sys_prefs'
            ),
            'systemSettingsButtons' => array(
                'icon' => 'fa-wrench',
                'command' => 'edit content and design html buttons settings',
                'dynamic' => false,
                'addon' => false,
                'target' => 'settings/buttons',
                'permission' => 'can_access_sys_prefs'
            ),
            'systemSettingsTemplate' => array(
                'icon' => 'fa-wrench',
                'command' => 'edit content and design template settings',
                'dynamic' => false,
                'addon' => false,
                'target' => 'settings/template',
                'permission' => 'can_access_sys_prefs'
            ),
            'systemSettingsHitTracking' => array(
                'icon' => 'fa-wrench',
                'command' => 'edit content and design hit tracking settings',
                'dynamic' => false,
                'addon' => false,
                'target' => 'settings/hit-tracking',
                'permission' => 'can_access_sys_prefs'
            ),
            'systemSettingsWordCensoring' => array(
                'icon' => 'fa-wrench',
                'command' => 'edit content and design word censoring settings',
                'dynamic' => false,
                'addon' => false,
                'target' => 'settings/word-censor',
                'permission' => 'can_access_sys_prefs'
            ),
            'systemSettingsMenuManager' => array(
                'icon' => 'fa-wrench',
                'command' => 'edit content and design menu manager settings',
                'dynamic' => false,
                'addon' => false,
                'target' => 'settings/menu-manager',
                'permission' => 'can_access_sys_prefs'
            ),
            'systemSettingsMembers' => array(
                'icon' => 'fa-wrench',
                'command' => 'edit members settings',
                'dynamic' => false,
                'addon' => false,
                'target' => 'settings/members',
                'permission' => 'access_sys_prefs'
            ),
            'systemSettingsMessages' => array(
                'icon' => 'fa-wrench',
                'command' => 'edit members messages settings',
                'dynamic' => false,
                'addon' => false,
                'target' => 'settings/messages',
                'permission' => 'can_access_sys_prefs'
            ),
            'systemSettingsAvatars' => array(
                'icon' => 'fa-wrench',
                'command' => 'edit members avatars settings',
                'dynamic' => false,
                'addon' => false,
                'target' => 'settings/avatars',
                'permission' => 'can_access_sys_prefs'
            ),
            'systemSettingsSecurityPrivacy' => array(
                'icon' => 'fa-wrench',
                'command' => 'edit security and privacy settings',
                'dynamic' => false,
                'addon' => false,
                'target' => 'settings/security-privacy',
                'permission' => 'can_access_security_settings'
            ),
            'systemSettingsAccessThrottling' => array(
                'icon' => 'fa-wrench',
                'command' => 'edit security and privacy access throttling settings',
                'dynamic' => false,
                'addon' => false,
                'target' => 'settings/throttling',
                'permission' => 'can_access_security_settings'
            ),
            'systemSettingsCaptcha' => array(
                'icon' => 'fa-wrench',
                'command' => 'edit security and privacy captcha settings',
                'dynamic' => false,
                'addon' => false,
                'target' => 'settings/captcha',
                'permission' => 'can_access_security_settings'
            ),
            'systemSettingsConsentRequests' => array(
                'icon' => 'fa-wrench',
                'command' => 'edit security and privacy consent requests settings',
                'dynamic' => false,
                'addon' => false,
                'target' => 'settings/consents',
                'permission' => 'can_manage_consents'
            ),
            'systemUtilitiesCommunicate' => array(
                'icon' => 'fa-hammer',
                'command' => 'system utilities communicate',
                'dynamic' => false,
                'addon' => false,
                'target' => 'utilities/communicate',
                'permission' => 'can_access_comm'
            ),
            'systemUtilitiesCommunicateSent' => array(
                'icon' => 'fa-hammer',
                'command' => 'system utilities communicate sent',
                'dynamic' => false,
                'addon' => false,
                'target' => 'utilities/communicate/sent',
                'permission' => 'can_access_comm'
            ),
            'systemUtilitiesTranslation' => array(
                'icon' => 'fa-hammer',
                'command' => 'system utilities translation',
                'dynamic' => false,
                'addon' => false,
                'target' => 'utilities/translate',
                'permission' => 'can_access_translate'
            ),
            'systemUtilitiesPHPInfo' => array(
                'icon' => 'fa-hammer',
                'command' => 'system utilities phpinfo',
                'dynamic' => false,
                'addon' => false,
                'target' => 'utilities/php',
                'permission' => 'can_access_utilities'
            ),
            'systemUtilitiesExtensions' => array(
                'icon' => 'fa-hammer',
                'command' => 'system utilities extensions',
                'dynamic' => false,
                'addon' => false,
                'target' => 'utilities/extensions',
                'permission' => 'can_access_utilities'
            ),
            'systemUtilitiesFileConverter' => array(
                'icon' => 'fa-hammer',
                'command' => 'system utilities member tools import file converter',
                'dynamic' => false,
                'addon' => false,
                'target' => 'utilities/import-converter',
                'permission' => 'can_access_import'
            ),
            'systemUtilitiesMemberImport' => array(
                'icon' => 'fa-hammer',
                'command' => 'system utilities member tools member import',
                'dynamic' => false,
                'addon' => false,
                'target' => 'utilities/member-import',
                'permission' => 'can_access_import'
            ),
            'systemUtilitiesMassNotificationExport' => array(
                'icon' => 'fa-hammer',
                'command' => 'system utilities member tools mass notification export email addresses',
                'dynamic' => false,
                'addon' => false,
                'target' => 'utilities/export-email-addresses',
                'permission' => 'can_access_import'
            ),
            'systemUtilitiesBackupUtility' => array(
                'icon' => 'fa-hammer',
                'command' => 'system utilities database backup utility',
                'dynamic' => false,
                'addon' => false,
                'target' => 'utilities/db-backup',
                'permission' => 'can_access_sql_manager'
            ),
            'systemUtilitiesSQLManager' => array(
                'icon' => 'fa-hammer',
                'command' => 'system utilities database sql manager',
                'dynamic' => false,
                'addon' => false,
                'target' => 'utilities/sql',
                'permission' => 'can_access_sql_manager'
            ),
            'systemUtilitiesQueryForm' => array(
                'icon' => 'fa-hammer',
                'command' => 'system utilities database sql query form',
                'dynamic' => false,
                'addon' => false,
                'target' => 'utilities/query',
                'permission' => 'can_access_sql_manager'
            ),
            'systemUtilitiesCacheManager' => array(
                'icon' => 'fa-hammer',
                'command' => 'system utilities data operations clear cache manager',
                'dynamic' => false,
                'addon' => false,
                'target' => 'utilities/cache',
                'permission' => 'can_access_data'
            ),
            'systemUtilitiesSearchReindex' => array(
                'icon' => 'fa-hammer',
                'command' => 'system utilities data operations search reindex',
                'dynamic' => false,
                'addon' => false,
                'target' => 'utilities/reindex',
                'permission' => 'can_access_data'
            ),
            'systemUtilitiesStatistics' => array(
                'icon' => 'fa-hammer',
                'command' => 'system utilities data operations statistics',
                'dynamic' => false,
                'addon' => false,
                'target' => 'utilities/stats',
                'permission' => 'can_access_data'
            ),
            'systemUtilitiesSearchAndReplace' => array(
                'icon' => 'fa-hammer',
                'command' => 'system utilities data operations search and replace',
                'dynamic' => false,
                'addon' => false,
                'target' => 'utilities/sandr',
                'permission' => 'can_access_data'
            ),
            'switchTheme' => array(
                'icon' => 'fa-random',
                'command' => 'switch theme light dark',
                'dynamic' => true,
                'addon' => false,
                'target' => 'themes/switch'
            )
        )
    );

    /**
     * Is the menu empty?
     *
     * @return bool Is empty?
     */
    public function hasItems()
    {
        return ! empty(self::$items);
    }


    /**
     * Get all items in the menu
     *
     * @return array of Link|Submenu Objects
     */
    public function getItems()
    {
        if (empty(ee()->session) || ee()->session->getMember() === null) {
            return [];
        }

        $items = ee()->cache->file->get('jumpmenu/' . ee()->session->getMember()->getId());
        if (!empty($items)) {
            return $items;
        }
        $this->primeCache();
        return self::$items;
    }

    /**
     * clear all caches
     * for now we're just forcing file driver, but that might change later
     */

    public function clearAllCaches()
    {
        ee()->cache->file->clean('jumpmenu');
    }

    /**
     * Returns items and rebuilds item list and caches it
     */
    public function primeCache()
    {
        ee()->cache->file->delete('jumpmenu/' . ee()->session->getMember()->getId());

        $items = self::$items;

        foreach ($items[1] as $name => $item) {
            if (!ee('Permission')->isSuperAdmin() && !empty($item['permission']) && !ee('Permission')->hasAny($item['permission'])) {
                unset($items[1][$name]);
            }
            if (!isset($item['command_title'])) {
                $items[1][$name]['command_title'] = lang('jump_' . $name);
            }
        }

        if (ee('Permission')->can('access_addons')) {
            $addon_infos = ee('Addon')->all();
            $assigned_modules = ee()->session->getMember()->getAssignedModules()->pluck('module_name');
            foreach ($addon_infos as $name => $info) {
                if ($info->hasModule() && !in_array($info->getModuleClass(), $assigned_modules)) {
                    continue;
                }

                if ($info->get('built_in') || ! $info->isInstalled() || ! $info->get('settings_exist')) {
                    continue;
                }

                if ($info->hasExtension() || $info->hasControlPanel()) {
                    // Create a jump to the add-on itself.
                    $items[1]['addon_' . $name] = array(
                        'icon' => 'fa-puzzle-piece',
                        'command' => 'addon add-on ' . $name,
                        'command_title' => lang('addon') . ': ' . $info->getName(),
                        'dynamic' => false,
                        'addon' => true,
                        'target' => 'addons/settings/' . $name
                    );
                }

                if (!$info->hasJumpMenu()) {
                    continue;
                }

                $items[1] = array_merge($items[1], $info->getJumps());
            }
        }

        // Cache our items. We're bypassing the checks for the default
        // cache driver because we want this to be cached and working
        // even if the dev has set caching to disabled.
        ee()->cache->file->save('jumpmenu/' . ee()->session->getMember()->getId(), $items, 3600);

        // Assign our combined item list back to our static variable.
        self::$items = $items;
    }
}

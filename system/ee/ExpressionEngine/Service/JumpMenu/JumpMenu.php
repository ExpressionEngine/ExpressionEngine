<?php

/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Service\JumpMenu;

/**
 * Custom Menu
 */
class JumpMenu extends AbstractJumpMenu
{
    protected static $items = array(
        '1' => array(
            //theme
            'switchTheme' => array(
                'icon' => 'fa-random',
                'command' => 'switch theme light dark slate',
                'dynamic' => true,
                'addon' => false,
                'target' => 'themes/switch'
            ),
            //entries
            'viewEntriesIn' => array(
                'icon' => 'fa-newspaper',
                'command' => 'view entries',
                'dynamic' => true,
                'addon' => false,
                'target' => 'publish/view',
                'permission' => null
            ),
            'createEntryIn' => array(
                'icon' => 'fa-plus',
                'command' => 'create publish new entry',
                'dynamic' => true,
                'addon' => false,
                'target' => 'publish/create',
                'permission' => ['can_create_entries']
            ),
            'editEntry' => array(
                'icon' => 'fa-pencil-alt',
                'command' => 'edit entry',
                'dynamic' => true,
                'addon' => false,
                'target' => 'publish/edit',
                'permission' => ['can_edit_other_entries', 'can_edit_self_entries']
            ),
            //files
            'viewFiles' => array(
                'icon' => 'fa-archive',
                'command' => 'view all_files',
                'dynamic' => false,
                'addon' => false,
                'target' => 'files',
                'permission' => 'can_access_files'
            ),
            //members
            'viewMembers' => array(
                'icon' => 'fa-users',
                'command' => 'view_members',
                'dynamic' => false,
                'addon' => false,
                'target' => 'members',
                'permission' => 'can_access_members'
            ),
            //design
            'viewTemplates' => array(
                'icon' => 'fa-file',
                'command' => 'view templates template_manager',
                'dynamic' => false,
                'addon' => false,
                'target' => 'design',
                'permission' => 'can_access_design'
            ),
            //cache
            'systemUtilitiesCacheManager' => array(
                'icon' => 'fa-database',
                'command' => 'system_utilities cache_manager caches_to_clear',
                'dynamic' => false,
                'addon' => false,
                'target' => 'utilities/cache',
                'permission' => 'can_access_data'
            ),
            //addons
            'viewAddons' => array(
                'icon' => 'fa-puzzle-piece',
                'command' => 'view addons add-ons modules plugins extensions',
                'dynamic' => false,
                'addon' => false,
                'target' => 'addons',
                'permission' => 'can_access_addons'
            ),
            //channels & fields
            'viewChannelFields' => array(
                'icon' => 'fa-pen-field',
                'command' => 'view edit_channel_fields custom_fields',
                'dynamic' => false,
                'addon' => false,
                'target' => 'fields',
                'permission' => ['can_create_channel_fields', 'can_edit_channel_fields']
            ),
            'viewChannels' => array(
                'icon' => 'fa-sitemap',
                'command' => 'view manage_channels',
                'dynamic' => false,
                'addon' => false,
                'target' => 'channels',
                'permission' => 'can_admin_channels'
            ),
            //cp
            'home' => array(
                'icon' => 'fa-home',
                'command' => 'nav_homepage dashboard overview',
                'dynamic' => false,
                'addon' => false,
                'target' => 'homepage'
            ),
            //members
            'createMember' => array(
                'icon' => 'fa-plus',
                'command' => 'new create_member',
                'dynamic' => false,
                'addon' => false,
                'target' => 'members/create',
                'permission' => 'can_create_members'
            ),
            'createMemberField' => array(
                'icon' => 'fa-plus',
                'command' => 'new create_member_field',
                'dynamic' => false,
                'addon' => false,
                'target' => 'settings/member-fields/create',
                'permission' => 'can_admin_roles'
            ),
            'createMemberRole' => array(
                'icon' => 'fa-plus',
                'command' => 'create_new_role',
                'dynamic' => false,
                'addon' => false,
                'target' => 'members/roles/create',
                'permission' => 'can_create_roles'
            ),
            'viewMembersIn' => array(
                'icon' => 'fa-eye',
                'command' => 'view_members filter_role',
                'dynamic' => true,
                'addon' => false,
                'target' => 'members/view',
                'permission' => 'can_access_members'
            ),
            'editMember' => array(
                'icon' => 'fa-pencil-alt',
                'command' => 'edit_member',
                'dynamic' => true,
                'addon' => false,
                'target' => 'members/edit',
                'permission' => 'can_edit_members'
            ),
            'viewMemberRoles' => array(
                'icon' => 'fa-user-tag fa-fw',
                'command' => 'view member roles_manager',
                'dynamic' => false,
                'addon' => false,
                'target' => 'members/roles',
                'permission' => 'can_edit_roles'
            ),
            'editMemberRole' => array(
                'icon' => 'fa-user-tag fa-fw',
                'command' => 'member edit_role',
                'dynamic' => true,
                'addon' => false,
                'target' => 'members/role',
                'permission' => 'can_edit_roles'
            ),
            'viewMemberFields' => array(
                'icon' => 'fa-bars fa-fw',
                'command' => 'custom_profile_fields',
                'dynamic' => false,
                'addon' => false,
                'target' => 'settings/member-fields',
                'permission' => 'can_admin_roles'
            ),
            'editMemberField' => array(
                'icon' => 'fa-bars fa-fw',
                'command' => 'edit_member_field',
                'dynamic' => true,
                'addon' => false,
                'target' => 'members/field',
                'permission' => 'can_admin_roles'
            ),
            'membersBanSettings' => array(
                'icon' => 'fa-ban fa-fw',
                'command' => 'manage_bans ip_address_banning email_address_banning username_banning screen_name_banning ban_options',
                'dynamic' => false,
                'addon' => false,
                'target' => 'settings/ban',
                'permission' => 'ban_users'
            ),
            //categories
            'viewCategories' => array(
                'icon' => 'fa-eye',
                'command' => 'view edit_categories',
                'dynamic' => false,
                'addon' => false,
                'target' => 'categories',
                'permission' => 'can_edit_categories'
            ),
            'createCategoryIn' => array(
                'icon' => 'fa-plus',
                'command' => 'add_category',
                'dynamic' => true,
                'addon' => false,
                'target' => 'categories/create',
                'permission' => 'can_create_categories'
            ),
            'editCategory' => array(
                'icon' => 'fa-pencil-alt',
                'command' => 'edit_category',
                'dynamic' => true,
                'addon' => false,
                'target' => 'categories/edit',
                'permission' => 'can_edit_categories'
            ),
            //comments
            'viewComments' => array(
                'icon' => 'fa-comments',
                'command' => 'view comments',
                'dynamic' => false,
                'addon' => false,
                'target' => 'publish/comments'
            ),
            'viewCommentsFor' => array(
                'icon' => 'fa-comments',
                'command' => 'view comments',
                'dynamic' => true,
                'addon' => false,
                'target' => 'comments/list'
            ),
            //files
            'viewFilesIn' => array(
                'icon' => 'fa-eye',
                'command' => 'view files content_files',
                'dynamic' => true,
                'addon' => false,
                'target' => 'files/view',
                'permission' => 'can_edit_files'
            ),
            'syncUploadDirectory' => array(
                'icon' => 'fa-sync-alt',
                'command' => 'synchronize_directory upload_directory',
                'dynamic' => true,
                'addon' => false,
                'target' => 'files/sync',
                'permission' => 'upload_new_files'
            ),
            'editUploadDirectory' => array(
                'icon' => 'fa-pencil-alt',
                'command' => 'edit_upload_directory',
                'dynamic' => true,
                'addon' => false,
                'target' => 'files/directories',
                'permission' => 'can_edit_upload_directories'
            ),
            'createUploadDirectory' => array(
                'icon' => 'fa-plus',
                'command' => 'new create_upload_directory',
                'dynamic' => false,
                'addon' => false,
                'target' => 'files/uploads/create',
                'permission' => 'can_create_upload_directories'
            ),
            //profile
            'logout' => array(
                'icon' => 'fa-sign-out-alt',
                'command' => 'logout log_out',
                'dynamic' => false,
                'addon' => false,
                'target' => 'login/logout'
            ),
            'myProfileSettings' => array(
                'icon' => 'fa-user',
                'command' => 'my_profile my_account',
                'dynamic' => false,
                'addon' => false,
                'target' => 'members/profile/settings'
            ),
            'myProfileEmail' => array(
                'icon' => 'fa-user',
                'command' => 'my_profile my_account email_settings',
                'dynamic' => false,
                'addon' => false,
                'target' => 'members/profile/email'
            ),
            'myProfileAuth' => array(
                'icon' => 'fa-user',
                'command' => 'my_profile my_account auth_settings',
                'dynamic' => false,
                'addon' => false,
                'target' => 'members/profile/auth'
            ),
            'myProfileDate' => array(
                'icon' => 'fa-user',
                'command' => 'my_profile my_account date_settings',
                'dynamic' => false,
                'addon' => false,
                'target' => 'members/profile/date'
            ),
            'myProfileConsents' => array(
                'icon' => 'fa-user',
                'command' => 'my_profile my_account consents',
                'dynamic' => false,
                'addon' => false,
                'target' => 'members/profile/consent'
            ),
            'myProfilePublishing' => array(
                'icon' => 'fa-user',
                'command' => 'my_profile my_account publishing_settings',
                'dynamic' => false,
                'addon' => false,
                'target' => 'members/profile/publishing'
            ),
            'myProfileButtons' => array(
                'icon' => 'fa-user',
                'command' => 'my_profile my_account html_buttons',
                'dynamic' => false,
                'addon' => false,
                'target' => 'members/profile/buttons'
            ),
            'myProfileQuicklinks' => array(
                'icon' => 'fa-user',
                'command' => 'my_profile my_account quick_links',
                'dynamic' => false,
                'addon' => false,
                'target' => 'members/profile/quicklinks'
            ),
            'myProfileBookmarklets' => array(
                'icon' => 'fa-user',
                'command' => 'my_profile my_account bookmarklets',
                'dynamic' => false,
                'addon' => false,
                'target' => 'members/profile/bookmarks'
            ),
            'myProfileSubscriptions' => array(
                'icon' => 'fa-user',
                'command' => 'my_profile my_account subscriptions',
                'dynamic' => false,
                'addon' => false,
                'target' => 'members/profile/subscriptions'
            ),
            'myProfileActivity' => array(
                'icon' => 'fa-user',
                'command' => 'my_profile my_account info_and_activity',
                'dynamic' => false,
                'addon' => false,
                'target' => 'members/profile/activity'
            ),
            'myProfileIgnore' => array(
                'icon' => 'fa-user',
                'command' => 'my_profile my_account blocked_members',
                'dynamic' => false,
                'addon' => false,
                'target' => 'members/profile/ignore'
            ),
            'myProfileAccess' => array(
                'icon' => 'fa-user',
                'command' => 'my_profile my_account access_overview',
                'dynamic' => false,
                'addon' => false,
                'target' => 'members/profile/access'
            ),
            'myProfileCpSettings' => array(
                'icon' => 'fa-user',
                'command' => 'my_profile my_account cp_settings',
                'dynamic' => false,
                'addon' => false,
                'target' => 'members/profile/cp-settings'
            ),
            //channels
            'createChannel' => array(
                'icon' => 'fa-plus',
                'command' => 'create create_channel',
                'dynamic' => false,
                'addon' => false,
                'target' => 'channels/create',
                'permission' => 'can_create_channels'
            ),
            'editChannel' => array(
                'icon' => 'fa-pencil-alt',
                'command' => 'edit_channel edit_channel_prefs',
                'dynamic' => true,
                'addon' => false,
                'target' => 'channels/edit',
                'permission' => 'can_edit_channels'
            ),
            'editChannelField' => array(
                'icon' => 'fa-pencil-alt',
                'command' => 'edit_field custom_fields',
                'dynamic' => true,
                'addon' => false,
                'target' => 'channels/field',
                'permission' => 'can_edit_channel_fields'
            ),
            'createChannelField' => array(
                'icon' => 'fa-plus',
                'command' => 'create_new_custom_field',
                'dynamic' => false,
                'addon' => false,
                'target' => 'fields/create',
                'permission' => 'can_create_channel_fields'
            ),
            'viewLayouts' => array(
                'icon' => 'fa-eye',
                'command' => 'view manage_channels form_layouts',
                'dynamic' => true,
                'addon' => false,
                'target' => 'channels/layouts',
                'permission' => 'can_edit_channels'
            ),
            //design
            'viewTemplatesIn' => array(
                'icon' => 'fa-eye',
                'command' => 'view templates',
                'dynamic' => true,
                'addon' => false,
                'target' => 'templates/view',
                'permission' => 'can_access_design'
            ),
            'viewTemplatePartials' => array(
                'icon' => 'fa-shapes fa-fw',
                'command' => 'view template_partials',
                'dynamic' => false,
                'addon' => false,
                'target' => 'design/snippets',
                'permission' => 'can_access_design'
            ),
            'viewTemplateVariables' => array(
                'icon' => 'fa-cube fa-fw',
                'command' => 'view template_variables',
                'dynamic' => false,
                'addon' => false,
                'target' => 'design/variables',
                'permission' => 'can_access_design'
            ),
            'templateRoutes' => array(
                'icon' => 'fa-truck fa-fw',
                'command' => 'template_routes',
                'dynamic' => false,
                'addon' => false,
                'target' => 'design/routes',
                'permission' => 'can_admin_design'
            ),
            'createTemplateGroup' => array(
                'icon' => 'fa-plus',
                'command' => 'create_new_template_group',
                'dynamic' => false,
                'addon' => false,
                'target' => 'design/group/create',
                'permission' => 'can_create_template_groups'
            ),
            'createTemplateIn' => array(
                'icon' => 'fa-plus',
                'command' => 'create_new_template',
                'dynamic' => true,
                'addon' => false,
                'target' => 'templates/create',
                'permission' => 'can_access_design'
            ),
            'editTemplate' => array(
                'icon' => 'fa-pencil-alt',
                'command' => 'edit_template_title',
                'dynamic' => true,
                'addon' => false,
                'target' => 'templates/edit',
                'permission' => 'can_access_design'
            ),
            'editTemplateGroup' => array(
                'icon' => 'fa-pencil-alt',
                'command' => 'edit_template_group',
                'dynamic' => true,
                'addon' => false,
                'target' => 'templates/group',
                'permission' => 'can_edit_template_groups'
            ),
            'systemTemplatesMessages' => array(
                'icon' => 'fa-eye',
                'command' => 'system_message_templates',
                'dynamic' => false,
                'addon' => false,
                'target' => 'design/system',
                'permission' => 'can_access_design'
            ),
            'systemTemplatesEmail' => array(
                'icon' => 'fa-eye',
                'command' => 'email_message_templates',
                'dynamic' => false,
                'addon' => false,
                'target' => 'design/email',
                'permission' => 'can_access_design'
            ),

            //settings
            'systemSettingsGeneral' => array(
                'icon' => 'fa-wrench',
                'command' => 'system_settings',
                'dynamic' => false,
                'addon' => false,
                'target' => 'settings/general',
                'permission' => 'can_access_sys_prefs',
                'anchors' => array(
                    // Site name
                    'fieldset-site_name' => array(
                        'trail' => [
                            'settings',
                            // 'general_settings'
                        ],
                        'command' => 'site site_name',
                        'command_title' => 'site_name'
                    ),
                    // Short name
                    'fieldset-site_short_name' => array(
                        'trail' => [
                            'settings',
                            // 'general_settings'
                        ],
                        'command' => 'site_short_name',
                        'command_title' => 'site_short_name'
                    ),
                    // Short name
                    'fieldset-site_license_key' => array(
                        'trail' => [
                            'settings',
                            // 'general_settings'
                        ],
                        'command' => 'site_license_key',
                        'command_title' => 'site_license_key'
                    ),
                    // System on off
                    'fieldset-is_system_on' => array(
                        'trail' => [
                            'settings',
                            // 'general_settings'
                        ],
                        'command' => 'site_online',
                        'command_title' => 'site_online'
                    ),
                    // New version check
                    'fieldset-new_version_check' => array(
                        'trail' => [
                            'settings',
                            // 'general_settings'
                        ],
                        'command' => 'version_autocheck',
                        'command_title' => 'version_autocheck'
                    ),
                    // MSM enabled
                    'fieldset-multiple_sites_enabled' => array(
                        'trail' => [
                            'settings'
                        ],
                        'command' => 'enable_msm',
                        'command_title' => 'enable_msm'
                    ),
                    // Show EE News
                    'fieldset-show_ee_news' => array(
                        'trail' => [
                            'settings'
                        ],
                        'command' => 'show_ee_news',
                        'command_title' => 'show_ee_news'
                    ),
                    // Default language
                    'fieldset-deft_lang' => array(
                        'trail' => [
                            'settings',
                            // 'general_settings'
                        ],
                        'command' => 'timezone',
                        'command_title' => 'timezone'
                    ),
                    // Date time format
                    'fieldset-date_format-time_format' => array(
                        'trail' => [
                            'settings',
                            // 'general_settings'
                        ],
                        'command' => 'date_time_fmt',
                        'command_title' => 'date_time_fmt'
                    ),
                    // Include Seconds
                    'fieldset-include_seconds' => array(
                        'trail' => [
                            'settings',
                            // 'general_settings'
                        ],
                        'command' => 'include_seconds',
                        'command_title' => 'include_seconds'
                    ),
                    // Week start
                    'fieldset-fieldset-week_start' => array(
                        'trail' => [
                            'settings',
                            // 'general_settings'
                        ],
                        'command' => 'week_start week_start_desc',
                        'command_title' => 'week_start'
                    ),
                )
            ),
            'systemSettingsUrls' => array(
                'icon' => 'fa-wrench',
                'command' => 'system_settings url_path_settings',
                'dynamic' => false,
                'addon' => false,
                'target' => 'settings/urls',
                'permission' => 'can_access_sys_prefs',
                'anchors' => array(
                    // Base URL
                    'fieldset-base_url' => array(
                        'trail' => [
                            'settings',
                            // 'url_path_settings'
                        ],
                        'command' => 'base_url',
                        'command_title' => 'base_url'
                    ),
                    // Base Path
                    'fieldset-base_path' => array(
                        'trail' => [
                            'settings',
                            // 'url_path_settings'
                        ],
                        'command' => 'base_path',
                        'command_title' => 'base_path'
                    ),
                    // Site Index
                    'fieldset-site_index' => array(
                        'trail' => [
                            'settings',
                            // 'url_path_settings'
                        ],
                        'command' => 'site_index',
                        'command_title' => 'site_index'
                    ),
                    // Website root upload_directory
                    'fieldset-site_url' => array(
                        'trail' => [
                            'settings',
                            // 'url_path_settings'
                        ],
                        'command' => 'site_url site_url_desc',
                        'command_title' => 'site_url'
                    ),
                    // CP URL
                    'fieldset-cp_url' => array(
                        'trail' => [
                            'settings',
                            // 'url_path_settings'
                        ],
                        'command' => 'cp_url cp_url_desc',
                        'command_title' => 'cp_url'
                    ),
                    // Themes directory
                    'fieldset-theme_folder_url' => array(
                        'trail' => [
                            'settings',
                            // 'url_path_settings'
                        ],
                        'command' => 'themes_url_desc themes_url',
                        'command_title' => 'themes_url'
                    ),
                    // Themes path
                    'fieldset-theme_folder_path' => array(
                        'trail' => [
                            'settings',
                            // 'url_path_settings'
                        ],
                        'command' => 'themes_path',
                        'command_title' => 'themes_path'
                    ),
                    // Category URL Segment
                    'fieldset-reserved_category_word' => array(
                        'trail' => [
                            'settings',
                            // 'url_path_settings'
                        ],
                        'command' => 'category_segment_trigger',
                        'command_title' => 'category_segment_trigger'
                    ),
                    // Category URL
                    'fieldset-use_category_name' => array(
                        'trail' => [
                            'settings',
                            // 'url_path_settings'
                        ],
                        'command' => 'category_url',
                        'command_title' => 'category_url'
                    ),
                    // URL title separator
                    'fieldset-word_separator' => array(
                        'trail' => [
                            'settings',
                            // 'url_path_settings'
                        ],
                        'command' => 'url_title_separator',
                        'command_title' => 'url_title_separator'
                    )
                )
            ),
            'systemSettingsEmail' => array(
                'icon' => 'fa-wrench',
                'command' => 'system_settings outgoing_email sending_options',
                'dynamic' => false,
                'addon' => false,
                'target' => 'settings/email',
                'permission' => 'can_access_sys_prefs',
                'anchors' => array(
                    // Outgoing email address
                    'fieldset-webmaster_email' => array(
                        'trail' => [
                            //'settings',
                            'outgoing_email'
                        ],
                        'command' => 'webmaster_email webmaster_email_desc outgoing_email',
                        'command_title' => 'webmaster_email'
                    ),
                    // Email sent from name
                    'fieldset-webmaster_name' => array(
                        'trail' => [
                            //'settings',
                            'outgoing_email'
                        ],
                        'command' => 'webmaster_name webmaster_name_desc outgoing_email',
                        'command_title' => 'webmaster_name'
                    ),
                    // Email character encoding
                    'fieldset-email_charset' => array(
                        'trail' => [
                            //'settings',
                            'outgoing_email'
                        ],
                        'command' => 'email_charset outgoing_email',
                        'command_title' => 'email_charset'
                    ),
                    // Email Protocal
                    'fieldset-mail_protocol' => array(
                        'trail' => [
                            //'settings',
                            'outgoing_email'
                        ],
                        'command' => 'mail_protocol_desc mail_protocol outgoing_email smtp_options',
                        'command_title' => 'mail_protocol'
                    ),
                    // Email New Line
                    'fieldset-email_newline' => array(
                        'trail' => [
                            //'settings',
                            'outgoing_email'
                        ],
                        'command' => 'email_newline outgoing_email',
                        'command_title' => 'email_newline'
                    ),
                    // Email Format
                    'fieldset-mail_format' => array(
                        'trail' => [
                            //'settings',
                            'outgoing_email'
                        ],
                        'command' => 'mail_format mail_format_desc outgoing_email',
                        'command_title' => 'mail_format'
                    ),
                    // Email word wrap
                    'fieldset-word_wrap' => array(
                        'trail' => [
                            //'settings',
                            'outgoing_email'
                        ],
                        'command' => 'word_wrap outgoing_email',
                        'command_title' => 'word_wrap'
                    ),
                )
            ),
            'systemSettingsDebugging' => array(
                'icon' => 'fa-wrench',
                'command' => 'system_settings  debugging_output use_newrelic',
                'dynamic' => false,
                'addon' => false,
                'target' => 'settings/debug-output',
                'permission' => 'can_access_sys_prefs',
                'anchors' => array(
                    // Error Visibility
                    'fieldset-fieldset-debug' => array(
                        'trail' => [
                            //'settings',
                            'debugging_output'
                        ],
                        'command' => 'enable_errors enable_errors_desc debug_0 debug_1 debug_2',
                        'command_title' => 'enable_errors'
                    ),
                    // Enable debugging?
                    'fieldset-show_profiler' => array(
                        'trail' => [
                            //'settings',
                            'debugging_output'
                        ],
                        'command' => 'show_profiler show_profiler_desc',
                        'command_title' => 'show_profiler'
                    ),
                    // Enable Developer Log Alerts?
                    'fieldset-enable_devlog_alerts' => array(
                        'trail' => [
                            //'settings',
                            'debugging_output'
                        ],
                        'command' => 'enable_devlog_alerts enable_devlog_alerts_desc',
                        'command_title' => 'enable_devlog_alerts'
                    ),
                    // Enable GZIP compression?
                    'fieldset-gzip_output' => array(
                        'trail' => [
                            //'settings',
                            'debugging_output'
                        ],
                        'command' => 'gzip_output',
                        'command_title' => 'gzip_output'
                    ),
                    // Force URL query strings?
                    'fieldset-force_query_string' => array(
                        'trail' => [
                            //'settings',
                            'debugging_output'
                        ],
                        'command' => 'force_query_string',
                        'command_title' => 'force_query_string'
                    ),
                    // Use HTTP page headers?
                    'fieldset-send_headers' => array(
                        'trail' => [
                            //'settings',
                            'debugging_output'
                        ],
                        'command' => 'send_headers',
                        'command_title' => 'send_headers'
                    ),
                    // Redirection type
                    'fieldset-redirect_method' => array(
                        'trail' => [
                            //'settings',
                            'debugging_output'
                        ],
                        'command' => 'redirect_method',
                        'command_title' => 'redirect_method'
                    ),
                    // Caching Driver
                    'fieldset-cache_driver' => array(
                        'trail' => [
                            //'settings',
                            'debugging_output'
                        ],
                        'command' => 'caching_driver',
                        'command_title' => 'caching_driver'
                    ),
                    // Cachable URIs
                    'fieldset-max_caches' => array(
                        'trail' => [
                            //'settings',
                            'debugging_output'
                        ],
                        'command' => 'max_caches max_caches_desc',
                        'command_title' => 'max_caches'
                    ),
                )
            ),
            'systemSettingsContentDesign' => array(
                'icon' => 'fa-wrench',
                'command' => 'system_settings content_and_design',
                'dynamic' => false,
                'addon' => false,
                'target' => 'settings/content-design',
                'permission' => 'can_access_sys_prefs',
                'anchors' => array(
                    // Clear cache for new entries?
                    'fieldset-new_posts_clear_caches' => array(
                        'trail' => [
                            //'settings',
                            'content_and_design'
                        ],
                        'command' => 'new_posts_clear_caches new_posts_clear_caches_desc',
                        'command_title' => 'new_posts_clear_caches'
                    ),
                    // Cache dynamic channel queries?
                    'fieldset-enable_sql_caching' => array(
                        'trail' => [
                            //'settings',
                            'content_and_design'
                        ],
                        'command' => 'enable_sql_caching',
                        'command_title' => 'enable_sql_caching'
                    ),
                    // Assign category parents?
                    'fieldset-auto_assign_cat_parents' => array(
                        'trail' => [
                            //'settings',
                            'content_and_design'
                        ],
                        'command' => 'auto_assign_cat_parents auto_assign_cat_parents_desc',
                        'command_title' => 'auto_assign_cat_parents'
                    ),
                    // Enable entry cloning
                    'fieldset-enable_entry_cloning' => array(
                        'trail' => [
                            //'settings',
                            'content_and_design'
                        ],
                        'command' => 'enable_entry_cloning enable_entry_cloning_desc',
                        'command_title' => 'enable_entry_cloning'
                    ),
                    // Compatibility mode?
                    'fieldset-file_manager_compatibility_mode' => array(
                        'trail' => [
                            //'settings',
                            'content_and_design'
                        ],
                        'command' => 'file_manager_compatibility_mode',
                        'command_title' => 'file_manager_compatibility_mode'
                    ),
                    // Image Resizing -- Protocol
                    'fieldset-image_resize_protocol' => array(
                        'trail' => [
                            //'settings',
                            'content_and_design'
                        ],
                        'command' => 'image_resize_protocol gd gd2 imagemagick netpbm image_resizing',
                        'command_title' => 'image_resize_protocol'
                    ),
                    // Image Resizing -- Converter path
                    'fieldset-image_library_path' => array(
                        'trail' => [
                            //'settings',
                            'content_and_design'
                        ],
                        'command' => 'image_library_path image_resizing',
                        'command_title' => 'image_library_path'
                    ),
                    // Enable emoticons?
                    'fieldset-enable_emoticons' => array(
                        'trail' => [
                            //'settings',
                            'content_and_design'
                        ],
                        'command' => 'enable_emoticons enable_emoticons_desc',
                        'command_title' => 'enable_emoticons'
                    ),
                    // emoticons URL
                    'fieldset-emoticon_url' => array(
                        'trail' => [
                            //'settings',
                            'content_and_design'
                        ],
                        'command' => 'emoticon_url emoticon_url_desc',
                        'command_title' => 'emoticon_url_desc'
                    )
                )
            ),
            'systemSettingsComments' => array(
                'icon' => 'fa-wrench',
                'command' => 'system_settings comment_settings',
                'dynamic' => false,
                'addon' => false,
                'target' => 'settings/comments',
                'permission' => 'can_access_sys_prefs',
                'anchors' => array(
                    // Enable comment module?
                    'fieldset-enable_comments' => array(
                        'trail' => [
                            //'settings',
                            'comment_settings'
                        ],
                        'command' => 'enable_comments',
                        'command_title' => 'enable_comments'
                    ),

                    // Enable word censoring?
                    'ffieldset-comment_word_censoring' => array(
                        'trail' => [
                            //'settings',
                            'comment_settings'
                        ],
                        'command' => 'comment_word_censoring comment_word_censoring_desc',
                        'command_title' => 'comment_word_censoring'
                    ),
                    // Moderate after comments expire?
                    'fieldset-comment_moderation_override' => array(
                        'trail' => [
                            //'settings',
                            'comment_settings'
                        ],
                        'command' => 'comment_moderation_override comment_moderation_override_desc',
                        'command_title' => 'comment_moderation_override'
                    ),
                    // Comment edit time limit (in seconds)
                    'fieldset-comment_edit_time_limit' => array(
                        'trail' => [
                            //'settings',
                            'comment_settings'
                        ],
                        'command' => 'comment_edit_time_limit comment_edit_time_limit_desc',
                        'command_title' => 'comment_edit_time_limit'
                    )
                )
            ),
            'systemSettingsButtons' => array(
                'icon' => 'fa-wrench',
                'command' => 'system_settings html_buttons',
                'dynamic' => false,
                'addon' => false,
                'target' => 'settings/buttons',
                'permission' => 'can_access_sys_prefs'
            ),
            'systemSettingsTemplate' => array(
                'icon' => 'fa-wrench',
                'command' => 'system_settings template_settings',
                'dynamic' => false,
                'addon' => false,
                'target' => 'settings/template',
                'permission' => 'can_access_sys_prefs',
                'anchors' => array(
                    // Enable Strict URLs
                    'fieldset-strict_urls' => array(
                        'trail' => [
                            //'settings',
                            'template_settings'
                        ],
                        'command' => 'strict_urls',
                        'command_title' => 'strict_urls'
                    ),
                    // 404 page
                    'fieldset-site_404' => array(
                        'trail' => [
                            //'settings',
                            'template_settings'
                        ],
                        'command' => 'site_404 site_404_desc',
                        'command_title' => 'site_404'
                    ),
                    // Save Template Revisions
                    'fieldset-save_tmpl_revisions' => array(
                        'trail' => [
                            //'settings',
                            'template_settings'
                        ],
                        'command' => 'save_tmpl_revisions',
                        'command_title' => 'save_tmpl_revisions'
                    ),
                    // Maximum Number of Revisions to Keep
                    'fieldset-max_tmpl_revisions' => array(
                        'trail' => [
                            //'settings',
                            'template_settings'
                        ],
                        'command' => 'max_tmpl_revisions max_tmpl_revisions_desc',
                        'command_title' => 'max_tmpl_revisions'
                    ),
                )
            ),
            'systemSettingsTracking' => array(
                'icon' => 'fa-wrench',
                'command' => 'system_settings tracking    ',
                'dynamic' => false,
                'addon' => false,
                'target' => 'settings/tracking',
                'permission' => 'can_access_sys_prefs',
                'anchors' => array(
                    // Enable tracking cookie?
                    'fieldset-enable_tracking_cookie' => array(
                        'trail' => [
                            //'settings',
                            'tracking'
                        ],
                        'command' => 'enable_tracking_cookie enable_tracking_cookie_desc',
                        'command_title' => 'enable_tracking_cookie'
                    ),
                    // Enable online user tracking?
                    'fieldset-enable_online_user_tracking' => array(
                        'trail' => [
                            //'settings',
                            'tracking'
                        ],
                        'command' => 'enable_online_user_tracking enable_online_user_tracking_desc',
                        'command_title' => 'enable_online_user_tracking'
                    ),
                    // Enable template hit tracking?
                    'fieldset-enable_hit_tracking' => array(
                        'trail' => [
                            //'settings',
                            'tracking'
                        ],
                        'command' => 'enable_hit_tracking enable_hit_tracking_desc',
                        'command_title' => 'enable_hit_tracking'
                    ),
                    // Enable entry view tracking?
                    'fieldset-enable_entry_view_tracking' => array(
                        'trail' => [
                            //'settings',
                            'tracking'
                        ],
                        'command' => 'enable_entry_view_tracking enable_entry_view_tracking_desc',
                        'command_title' => 'enable_entry_view_tracking'
                    ),
                    // Suspend threshold?
                    'fieldset-dynamic_tracking_disabling' => array(
                        'trail' => [
                            //'settings',
                            'tracking'
                        ],
                        'command' => 'dynamic_tracking_disabling dynamic_tracking_disabling_desc',
                        'command_title' => 'dynamic_tracking_disabling'
                    ),
                )
            ),
            'systemSettingsWordCensoring' => array(
                'icon' => 'fa-wrench',
                'command' => 'system_settings word_censoring ',
                'dynamic' => false,
                'addon' => false,
                'target' => 'settings/word-censor',
                'permission' => 'can_access_sys_prefs',
                'anchors' => array(
                    // Enable censorship?
                    'fieldset-enable_censoring' => array(
                        'trail' => [
                            //'settings',
                            'word_censoring'
                        ],
                        'command' => 'enable_censoring enable_censoring_desc',
                        'command_title' => 'enable_censoring'
                    ),
                    // Replacement characters
                    'fieldset-censor_replacement' => array(
                        'trail' => [
                            //'settings',
                            'word_censoring'
                        ],
                        'command' => 'censor_replacement censor_replacement_desc',
                        'command_title' => 'censor_replacement'
                    ),
                    // Words to censor
                    'fieldset-censored_words' => array(
                        'trail' => [
                            //'settings',
                            'word_censoring'
                        ],
                        'command' => 'censored_words',
                        'command_title' => 'censored_words'
                    ),
                )
            ),
            'systemSettingsMenuManager' => array(
                'icon' => 'fa-wrench',
                'command' => 'system_settings menu_manager menu_sets',
                'dynamic' => false,
                'addon' => false,
                'target' => 'settings/menu-manager',
                'permission' => 'can_access_sys_prefs'
            ),
            'systemSettingsFrontedit' => array(
                'icon' => 'fa-wrench',
                'command' => 'system_settings frontedit ',
                'dynamic' => false,
                'addon' => false,
                'target' => 'settings/pro/frontedit',
                'permission' => 'can_access_sys_prefs',
                'anchors' => array(
                    // Enable dock?
                    'fieldset-enable_dock' => array(
                        'trail' => [
                            //'settings',
                            'frontedit'
                        ],
                        'command' => 'enable_dock enable_dock_desc',
                        'command_title' => 'enable_dock'
                    ),
                    // Enable front-end editing
                    'fieldset-enable_frontedit' => array(
                        'trail' => [
                            //'settings',
                            'frontedit'
                        ],
                        'command' => 'enable_frontedit enable_frontedit_desc',
                        'command_title' => 'enable_frontedit'
                    ),
                    // Enable automatic front-end editing links?
                    'fieldset-automatic_frontedit_links' => array(
                        'trail' => [
                            //'settings',
                            'frontedit'
                        ],
                        'command' => 'automatic_frontedit_links automatic_frontedit_links_desc',
                        'command_title' => 'automatic_frontedit_links'
                    ),
                )
            ),
            'systemSettingsBranding' => array(
                'icon' => 'fa-wrench',
                'command' => 'system_settings branding ',
                'dynamic' => false,
                'addon' => false,
                'target' => 'settings/pro/branding',
                'permission' => 'can_access_sys_prefs',
                'anchors' => array(
                    // Logo to show on login screen
                    'fieldset-login_logo' => array(
                        'trail' => [
                            //'settings',
                            'branding'
                        ],
                        'command' => 'login_logo',
                        'command_title' => 'login_logo'
                    ),
                    // Favicon
                    'fieldset-favicon' => array(
                        'trail' => [
                            //'settings',
                            'branding'
                        ],
                        'command' => 'favicon',
                        'command_title' => 'favicon'
                    ),
                )
            ),
            'systemSettingsMembers' => array(
                'icon' => 'fa-wrench',
                'command' => 'system_settings member_settings',
                'dynamic' => false,
                'addon' => false,
                'target' => 'settings/members',
                'permission' => 'can_access_sys_prefs',
                'anchors' => array(
                    'fieldset-allow_member_registration' => array(
                        'trail' => [
                            'member_settings'
                        ],
                        'command' => 'allow_member_registration allow_member_registration_desc',
                        'command_title' => 'allow_member_registration'
                    ),
                    'fieldset-req_mbr_activation' => array(
                        'trail' => [
                            'member_settings'
                        ],
                        'command' => 'req_mbr_activation req_mbr_activation_desc',
                        'command_title' => 'req_mbr_activation'
                    ),
                    'fieldset-approved_member_notification' => array(
                        'trail' => [
                            'member_settings'
                        ],
                        'command' => 'approved_member_notification approved_member_notification_desc',
                        'command_title' => 'approved_member_notification'
                    ),
                    'fieldset-declined_member_notification' => array(
                        'trail' => [
                            'member_settings'
                        ],
                        'command' => 'declined_member_notification declined_member_notification_desc',
                        'command_title' => 'declined_member_notification'
                    ),
                    'fieldset-require_terms_of_service' => array(
                        'trail' => [
                            'member_settings'
                        ],
                        'command' => 'require_terms_of_service require_terms_of_service_desc',
                        'command_title' => 'require_terms_of_service'
                    ),
                    'fieldset-enable_mfa' => array(
                        'trail' => [
                            'member_settings'
                        ],
                        'command' => 'enable_mfa enable_mfa_desc MFA',
                        'command_title' => 'enable_mfa'
                    ),
                    'fieldset-allow_member_localization' => array(
                        'trail' => [
                            'member_settings'
                        ],
                        'command' => 'allow_member_localization allow_member_localization_desc',
                        'command_title' => 'allow_member_localization'
                    ),
                    'fieldset-default_primary_role' => array(
                        'trail' => [
                            'member_settings'
                        ],
                        'command' => 'default_primary_role default_primary_role_desc',
                        'command_title' => 'default_primary_role'
                    ),
                    'fieldset-member_listing_settings' => array(
                        'trail' => [
                            'member_settings'
                        ],
                        'command' => 'member_listing_settings member_listing_settings_desc',
                        'command_title' => 'member_listing_settings'
                    ),
                    'fieldset-new_member_notification' => array(
                        'trail' => [
                            'member_settings'
                        ],
                        'command' => 'new_member_notification new_member_notification_desc',
                        'command_title' => 'new_member_notification'
                    ),
                    'fieldset-mbr_notification_emails' => array(
                        'trail' => [
                            'member_settings'
                        ],
                        'command' => 'mbr_notification_emails mbr_notification_emails_desc',
                        'command_title' => 'mbr_notification_emails'
                    ),
                )
            ),
            'systemSettingsMessages' => array(
                'icon' => 'fa-wrench',
                'command' => 'system_settings messaging_settings',
                'dynamic' => false,
                'addon' => false,
                'target' => 'settings/messages',
                'permission' => 'can_access_sys_prefs',
                'anchors' => array(
                    'fieldset-prv_msg_max_chars' => array(
                        'trail' => [
                            'messaging_settings'
                        ],
                        'command' => 'prv_msg_max_chars prv_msg_max_chars_desc',
                        'command_title' => 'prv_msg_max_chars'
                    ),
                    'fieldset-prv_msg_html_format' => array(
                        'trail' => [
                            'messaging_settings'
                        ],
                        'command' => 'prv_msg_html_format prv_msg_html_format_desc',
                        'command_title' => 'prv_msg_html_format'
                    ),
                    'fieldset-prv_msg_auto_links' => array(
                        'trail' => [
                            'messaging_settings'
                        ],
                        'command' => 'prv_msg_auto_links prv_msg_auto_links_desc',
                        'command_title' => 'prv_msg_auto_links'
                    ),
                    'fieldset-prv_msg_upload_url' => array(
                        'trail' => [
                            'messaging_settings'
                        ],
                        'command' => 'prv_msg_upload_url prv_msg_upload_url_desc',
                        'command_title' => 'prv_msg_upload_url'
                    ),
                    'fieldset-prv_msg_upload_path' => array(
                        'trail' => [
                            'messaging_settings'
                        ],
                        'command' => 'prv_msg_upload_path prv_msg_upload_path_desc',
                        'command_title' => 'prv_msg_upload_path'
                    ),
                    'fieldset-attachment_settings' => array(
                        'trail' => [
                            'messaging_settings'
                        ],
                        'command' => 'attachment_settings attachment_settings_desc',
                        'command_title' => 'attachment_settings'
                    ),
                )
            ),
            'systemSettingsAvatars' => array(
                'icon' => 'fa-wrench',
                'command' => 'system_settings avatars',
                'dynamic' => false,
                'addon' => false,
                'target' => 'settings/avatars',
                'permission' => 'can_access_sys_prefs',
                'anchors' => array(
                    'fieldset-avatar_url' => array(
                        'trail' => [
                            //'settings',
                            'avatar_settings'
                        ],
                        'command' => 'avatar_url avatar_url_desc',
                        'command_title' => 'avatar_url'
                    ),
                    'fieldset-avatar_path' => array(
                        'trail' => [
                            //'settings',
                            'avatar_settings'
                        ],
                        'command' => 'avatar_path avatar_path_desc',
                        'command_title' => 'avatar_path'
                    ),
                    'fieldset-avatar_max_width' => array(
                        'trail' => [
                            //'settings',
                            'avatar_settings'
                        ],
                        'command' => 'avatar_max_width avatar_max_width_desc',
                        'command_title' => 'avatar_max_width'
                    ),
                    'fieldset-avatar_max_height' => array(
                        'trail' => [
                            //'settings',
                            'avatar_settings'
                        ],
                        'command' => 'avatar_max_height avatar_max_height_desc',
                        'command_title' => 'avatar_max_height'
                    ),
                    'fieldset-avatar_max_kb' => array(
                        'trail' => [
                            //'settings',
                            'avatar_settings'
                        ],
                        'command' => 'avatar_max_kb avatar_max_kb_desc',
                        'command_title' => 'avatar_max_kb'
                    ),
                )
            ),
            'systemSettingsSecurityPrivacy' => array(
                'icon' => 'fa-wrench',
                'command' => 'system_settings security_privacy',
                'dynamic' => false,
                'addon' => false,
                'target' => 'settings/security-privacy',
                'permission' => 'can_access_security_settings',
                'anchors' => array(
                    'fieldset-cp_session_type' => array(
                        'trail' => [
                            //'settings',
                            'security_privacy'
                        ],
                        'command' => 'cp_session_type cp_session_type_desc',
                        'command_title' => 'cp_session_type'
                    ),
                    'fieldset-website_session_type' => array(
                        'trail' => [
                            //'settings',
                            'security_privacy'
                        ],
                        'command' => 'website_session_type website_session_type_desc',
                        'command_title' => 'website_session_type'
                    ),
                    'fieldset-share_analytics' => array(
                        'trail' => [
                            //'settings',
                            'security_privacy'
                        ],
                        'command' => 'share_analytics share_analytics_desc',
                        'command_title' => 'share_analytics'
                    ),
                    'fieldset-cli_enabled' => array(
                        'trail' => [
                            //'settings',
                            'security_privacy'
                        ],
                        'command' => 'cli_enabled cli_enabled_shorthand cli_enabled_desc',
                        'command_title' => 'cli_enabled'
                    ),
                    'fieldset-cookie_domain' => array(
                        'trail' => [
                            'cookie_settings'
                        ],
                        'command' => 'cookie_domain cookie_domain_desc',
                        'command_title' => 'cookie_domain'
                    ),
                    'fieldset-cookie_path' => array(
                        'trail' => [
                            'cookie_settings'
                        ],
                        'command' => 'cookie_path cookie_path_desc',
                        'command_title' => 'cookie_path'
                    ),
                    'fieldset-cookie_prefix' => array(
                        'trail' => [
                            'cookie_settings'
                        ],
                        'command' => 'cookie_prefix cookie_prefix_desc',
                        'command_title' => 'cookie_prefix'
                    ),
                    'fieldset-cookie_httponly' => array(
                        'trail' => [
                            'cookie_settings'
                        ],
                        'command' => 'cookie_httponly cookie_httponly_desc',
                        'command_title' => 'cookie_httponly'
                    ),
                    'fieldset-cookie_secure' => array(
                        'trail' => [
                            'cookie_settings'
                        ],
                        'command' => 'cookie_secure cookie_secure_desc',
                        'command_title' => 'cookie_secure'
                    ),
                    'fieldset-require_cookie_consent' => array(
                        'trail' => [
                            'cookie_settings'
                        ],
                        'command' => 'require_cookie_consent require_cookie_consent_desc',
                        'command_title' => 'require_cookie_consent'
                    ),
                    'fieldset-allow_username_change' => array(
                        'trail' => [
                            'member_security_settings'
                        ],
                        'command' => 'allow_username_change allow_username_change_desc',
                        'command_title' => 'allow_username_change'
                    ),
                    'fieldset-un_min_len' => array(
                        'trail' => [
                            'member_security_settings'
                        ],
                        'command' => 'un_min_len un_min_len_desc',
                        'command_title' => 'un_min_len'
                    ),
                    'fieldset-allow_multi_logins' => array(
                        'trail' => [
                            'member_security_settings'
                        ],
                        'command' => 'allow_multi_logins allow_multi_logins_desc',
                        'command_title' => 'allow_multi_logins'
                    ),
                    'fieldset-require_ip_for_login' => array(
                        'trail' => [
                            'member_security_settings'
                        ],
                        'command' => 'require_ip_for_login require_ip_for_login_desc',
                        'command_title' => 'require_ip_for_login'
                    ),
                    'fieldset-password_lockout' => array(
                        'trail' => [
                            'member_security_settings'
                        ],
                        'command' => 'password_lockout password_lockout_desc',
                        'command_title' => 'password_lockout'
                    ),
                    'fieldset-password_lockout_interval' => array(
                        'trail' => [
                            'member_security_settings'
                        ],
                        'command' => 'password_lockout_interval password_lockout_interval_desc',
                        'command_title' => 'password_lockout_interval'
                    ),
                    'fieldset-password_security_policy' => array(
                        'trail' => [
                            'member_security_settings'
                        ],
                        'command' => 'password_security_policy password_security_policy_desc',
                        'command_title' => 'password_security_policy'
                    ),
                    'fieldset-pw_min_len' => array(
                        'trail' => [
                            'member_security_settings'
                        ],
                        'command' => 'pw_min_len pw_min_len_desc',
                        'command_title' => 'pw_min_len'
                    ),
                    'fieldset-allow_dictionary_pw' => array(
                        'trail' => [
                            'member_security_settings'
                        ],
                        'command' => 'allow_dictionary_pw allow_dictionary_pw_desc',
                        'command_title' => 'allow_dictionary_pw'
                    ),
                    'fieldset-deny_duplicate_data' => array(
                        'trail' => [
                            'form_security_settings'
                        ],
                        'command' => 'deny_duplicate_data deny_duplicate_data_desc',
                        'command_title' => 'deny_duplicate_data'
                    ),
                    'fieldset-require_ip_for_posting' => array(
                        'trail' => [
                            'form_security_settings'
                        ],
                        'command' => 'require_ip_for_posting require_ip_for_posting_desc',
                        'command_title' => 'require_ip_for_posting'
                    ),
                    'fieldset-xss_clean_uploads' => array(
                        'trail' => [
                            'form_security_settings'
                        ],
                        'command' => 'xss_clean_uploads xss_clean_uploads_desc',
                        'command_title' => 'xss_clean_uploads'
                    ),
                    'fieldset-enable_rank_denial' => array(
                        'trail' => [
                            'form_security_settings'
                        ],
                        'command' => 'enable_rank_denial enable_rank_denial_desc',
                        'command_title' => 'enable_rank_denial'
                    ),
                    'fieldset-force_interstitial' => array(
                        'trail' => [
                            'form_security_settings'
                        ],
                        'command' => 'force_interstitial force_interstitial_desc',
                        'command_title' => 'force_interstitial'
                    ),
                )
            ),
            'systemSettingsAccessThrottling' => array(
                'icon' => 'fa-wrench',
                'command' => 'system_settings access_throttling',
                'dynamic' => false,
                'addon' => false,
                'target' => 'settings/throttling',
                'permission' => 'can_access_security_settings',
                'anchors' => array(
                    'fieldset-enable_throttling' => array(
                        'trail' => [
                            //'settings',
                            'access_throttling'
                        ],
                        'command' => 'enable_throttling enable_throttling_desc',
                        'command_title' => 'enable_throttling'
                    ),
                    'fieldset-max_page_loads' => array(
                        'trail' => [
                            //'settings',
                            'access_throttling'
                        ],
                        'command' => 'max_page_loads max_page_loads_desc',
                        'command_title' => 'max_page_loads'
                    ),
                    'fieldset-time_interval' => array(
                        'trail' => [
                            //'settings',
                            'access_throttling'
                        ],
                        'command' => 'time_interval time_interval_desc',
                        'command_title' => 'time_interval'
                    ),
                    'fieldset-lockout_time' => array(
                        'trail' => [
                            //'settings',
                            'access_throttling'
                        ],
                        'command' => 'lockout_time lockout_time_desc',
                        'command_title' => 'lockout_time'
                    ),
                    'fieldset-banishment_type' => array(
                        'trail' => [
                            //'settings',
                            'access_throttling'
                        ],
                        'command' => 'banishment_type banishment_type_desc',
                        'command_title' => 'banishment_type'
                    ),
                    'fieldset-banishment_url' => array(
                        'trail' => [
                            //'settings',
                            'access_throttling'
                        ],
                        'command' => 'banishment_url banishment_url_desc',
                        'command_title' => 'banishment_url'
                    ),
                    'fieldset-banishment_message' => array(
                        'trail' => [
                            //'settings',
                            'access_throttling'
                        ],
                        'command' => 'banishment_message banishment_message_desc',
                        'command_title' => 'banishment_message'
                    ),
                )
            ),
            'systemSettingsCaptcha' => array(
                'icon' => 'fa-wrench',
                'command' => 'system_settings captcha_settings',
                'dynamic' => false,
                'addon' => false,
                'target' => 'settings/captcha',
                'permission' => 'can_access_security_settings',
                'anchors' => array(
                    'fieldset-require_captcha' => array(
                        'trail' => [
                            'captcha_settings'
                        ],
                        'command' => 'require_captcha require_captcha_desc',
                        'command_title' => 'require_captcha'
                    ),
                    'fieldset-captcha_font' => array(
                        'trail' => [
                            'captcha_settings'
                        ],
                        'command' => 'captcha_font captcha_font_desc',
                        'command_title' => 'captcha_font'
                    ),
                    'fieldset-captcha_rand' => array(
                        'trail' => [
                            'captcha_settings'
                        ],
                        'command' => 'captcha_rand captcha_rand_desc',
                        'command_title' => 'captcha_rand'
                    ),
                    'fieldset-captcha_require_members' => array(
                        'trail' => [
                            'captcha_settings'
                        ],
                        'command' => 'captcha_require_members captcha_require_members_desc',
                        'command_title' => 'captcha_require_members'
                    ),
                    'fieldset-captcha_url' => array(
                        'trail' => [
                            'captcha_settings'
                        ],
                        'command' => 'captcha_url captcha_url_desc',
                        'command_title' => 'captcha_url'
                    ),
                    'fieldset-captcha_path' => array(
                        'trail' => [
                            'captcha_settings'
                        ],
                        'command' => 'captcha_path captcha_path_desc',
                        'command_title' => 'captcha_path'
                    ),
                )
            ),
            'systemSettingsConsentRequests' => array(
                'icon' => 'fa-wrench',
                'command' => 'system_settings consent_requests',
                'dynamic' => false,
                'addon' => false,
                'target' => 'settings/consents',
                'permission' => 'can_manage_consents',
            ),
            'systemSettingsCookies' => array(
                'icon' => 'fa-wrench',
                'command' => 'system_settings cookie_settings',
                'dynamic' => false,
                'addon' => false,
                'target' => 'settings/pro/cookies',
                'permission' => 'can_access_sys_prefs',
            ),
            //utilities
            'systemUtilitiesCommunicate' => array(
                'icon' => 'fa-hammer',
                'command' => 'system_utilities communicate send_email',
                'dynamic' => false,
                'addon' => false,
                'target' => 'utilities/communicate',
                'permission' => 'can_access_comm'
            ),
            'systemUtilitiesCommunicateSent' => array(
                'icon' => 'fa-hammer',
                'command' => 'system_utilities communicate view_email_cache',
                'dynamic' => false,
                'addon' => false,
                'target' => 'utilities/communicate/sent',
                'permission' => 'can_access_comm'
            ),
            'systemUtilitiesTranslation' => array(
                'icon' => 'fa-hammer',
                'command' => 'system_utilities cp_translations',
                'dynamic' => false,
                'addon' => false,
                'target' => 'utilities/translate',
                'permission' => 'can_access_translate'
            ),
            'systemUtilitiesSyncConditionalFields' => array(
                'icon' => 'fa-hammer',
                'command' => 'system_utilities sync_conditional_fields',
                'dynamic' => false,
                'addon' => false,
                'target' => 'utilities/sync-conditional-fields',
                'permission' => 'edit_channel_fields'
            ),
            'systemUtilitiesPHPInfo' => array(
                'icon' => 'fa-hammer',
                'command' => 'system_utilities phpinfo',
                'dynamic' => false,
                'addon' => false,
                'target' => 'utilities/php',
                'permission' => 'can_access_utilities'
            ),
            'systemUtilitiesExtensions' => array(
                'icon' => 'fa-hammer',
                'command' => 'system_utilities debug_extensions',
                'dynamic' => false,
                'addon' => false,
                'target' => 'utilities/extensions',
                'permission' => 'can_access_utilities'
            ),
            'systemUtilitiesDebugTools' => array(
                'icon' => 'fa-hammer',
                'command' => 'system_utilities debug_tools',
                'dynamic' => false,
                'addon' => false,
                'target' => 'utilities/debug-tools',
                'permission' => 'is_super_admin'
            ),
            'systemUtilitiesDebugToolsTags' => array(
                'icon' => 'fa-hammer',
                'command' => 'system_utilities debug_tools_debug_tags',
                'dynamic' => false,
                'addon' => false,
                'target' => 'utilities/debug-tools/debug-tags',
                'permission' => 'is_super_admin'
            ),
            'systemUtilitiesDebugToolsFieldtypes' => array(
                'icon' => 'fa-hammer',
                'command' => 'system_utilities debug_tools_fieldtypes',
                'dynamic' => false,
                'addon' => false,
                'target' => 'utilities/debug-tools/debug-fieldtypes',
                'permission' => 'is_super_admin'
            ),
            'systemUtilitiesDebugDuplicateTemplateGroups' => array(
                'icon' => 'fa-hammer',
                'command' => 'system_utilities debug_tools_debug_duplicate_template_groups',
                'dynamic' => false,
                'addon' => false,
                'target' => 'utilities/debug-tools/duplicate-template-groups',
                'permission' => 'is_super_admin'
            ),
            'systemUtilitiesFileConverter' => array(
                'icon' => 'fa-hammer',
                'command' => 'system_utilities member_tools import_converter',
                'dynamic' => false,
                'addon' => false,
                'target' => 'utilities/import-converter',
                'permission' => 'can_access_import'
            ),
            'systemUtilitiesMemberImport' => array(
                'icon' => 'fa-hammer',
                'command' => 'system_utilities member_tools member_import',
                'dynamic' => false,
                'addon' => false,
                'target' => 'utilities/member-import',
                'permission' => 'can_access_import'
            ),
            'systemUtilitiesMassNotificationExport' => array(
                'icon' => 'fa-hammer',
                'command' => 'system_utilities member_tools mass_notification_export export_email_addresses_desc',
                'dynamic' => false,
                'addon' => false,
                'target' => 'utilities/export-email-addresses',
                'permission' => 'can_access_import'
            ),
            'systemUtilitiesBackupUtility' => array(
                'icon' => 'fa-hammer',
                'command' => 'system_utilities backup_database backup_tables',
                'dynamic' => false,
                'addon' => false,
                'target' => 'utilities/db-backup',
                'permission' => 'can_access_sql_manager'
            ),
            'systemUtilitiesSQLManager' => array(
                'icon' => 'fa-hammer',
                'command' => 'system_utilities sql_manager database_tables',
                'dynamic' => false,
                'addon' => false,
                'target' => 'utilities/sql',
                'permission' => 'can_access_sql_manager'
            ),
            'systemUtilitiesQueryForm' => array(
                'icon' => 'fa-hammer',
                'command' => 'system_utilities sql_manager database_tables query_form',
                'dynamic' => false,
                'addon' => false,
                'target' => 'utilities/query',
                'permission' => 'can_access_sql_manager'
            ),
            'systemUtilitiesContentReindex' => array(
                'icon' => 'fa-hammer',
                'command' => 'system_utilities search_reindex',
                'dynamic' => false,
                'addon' => false,
                'target' => 'utilities/reindex',
                'permission' => 'can_access_data'
            ),
            'systemUtilitiesFileUsage' => array(
                'icon' => 'fa-hammer',
                'command' => 'system_utilities update_file_usage',
                'dynamic' => false,
                'addon' => false,
                'target' => 'utilities/file-usage',
                'permission' => 'can_access_data'
            ),
            'systemUtilitiesStatistics' => array(
                'icon' => 'fa-hammer',
                'command' => 'system_utilities manage_stats',
                'dynamic' => false,
                'addon' => false,
                'target' => 'utilities/stats',
                'permission' => 'can_access_data'
            ),
            'systemUtilitiesSearchAndReplace' => array(
                'icon' => 'fa-hammer',
                'command' => 'system_utilities sandr',
                'dynamic' => false,
                'addon' => false,
                'target' => 'utilities/sandr',
                'permission' => 'can_access_data'
            ),

            //misc
            'toggleSidebar' => array(
                'icon' => 'fa-toggle-on',
                'command' => 'navigation_toggle',
                'dynamic' => false,
                'addon' => false,
                'target' => 'homepage/toggle-viewmode'
            ),
            /*'clearCaches' => array(
                'icon' => 'fa-database',
                'command' => 'btn_clear_caches',
                'dynamic' => true,
                'addon' => false,
                'target' => 'caches/clear'
            ),*/
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

        $items = ee()->cache->file->get('jumpmenu/' . md5(ee()->session->getMember()->getId()));
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
        ee()->cache->file->delete('/jumpmenu/');
    }

    /**
     * Returns items and rebuilds item list and caches it
     */
    public function primeCache()
    {
        ee()->cache->file->delete('jumpmenu/' . md5(ee()->session->getMember()->getId()));

        //load language for all the jumps
        ee()->lang->load('jump_menu');
        ee()->lang->load('settings');
        ee()->lang->load('addons');
        ee()->lang->load('myaccount');
        ee()->lang->load('content');
        ee()->lang->load('members');
        ee()->lang->load('channel');
        ee()->lang->load('filemanager');
        ee()->lang->load('admin_content');
        ee()->lang->load('design');
        ee()->lang->load('utilities');
        ee()->lang->load('logs');
        ee()->lang->load('pro');

        $items = self::$items;

        //logs have dynamically build titles

        $items[1] = array_merge($items[1], [
            'logsConsent' => array(
                'icon' => 'fal fa-scroll',
                'command' => 'view_consent_log',
                'command_title' => lang('view') . ' <b>' . lang('view_consent_log') . '</b>',
                'dynamic' => false,
                'addon' => false,
                'target' => 'logs/consent',
                'permission' => 'can_manage_consents'
            ),
            'logsCp' => array(
                'icon' => 'fal fa-scroll',
                'command' => 'view_cp_log',
                'command_title' => lang('view') . ' <b>' . lang('view_cp_log') . '</b>',
                'dynamic' => false,
                'addon' => false,
                'target' => 'logs/cp',
                'permission' => 'can_access_logs'
            ),
            'logsThrottle' => array(
                'icon' => 'fal fa-scroll',
                'command' => 'view_throttle_log',
                'command_title' => lang('view') . ' <b>' . lang('view_throttle_log') . '</b>',
                'dynamic' => false,
                'addon' => false,
                'target' => 'logs/throttle',
                'permission' => 'can_access_logs'
            ),
            'logsEmail' => array(
                'icon' => 'fal fa-scroll',
                'command' => 'view_email_logs',
                'command_title' => lang('view') . ' <b>' . lang('view_email_logs') . '</b>',
                'dynamic' => false,
                'addon' => false,
                'target' => 'logs/email',
                'permission' => 'can_access_logs'
            ),
            'logsSearch' => array(
                'icon' => 'fal fa-scroll',
                'command' => 'view_search_log',
                'command_title' => lang('view') . ' <b>' . lang('view_search_log') . '</b>',
                'dynamic' => false,
                'addon' => false,
                'target' => 'logs/search',
                'permission' => 'can_access_logs'
            ),
        ]);

        //MFA profile link if that is enabled
        if (ee()->config->item('enable_mfa') === 'y') {
            $items[1]['myProfileMfa'] = [
                'icon' => 'fa-user',
                'command' => 'my_profile my_account MFA',
                'command_title' => lang('jump_mfa'),
                'dynamic' => false,
                'addon' => false,
                'target' => 'members/profile/pro/mfa',
            ];
        }

        //add superadmin-only stuff
        if (ee('Permission')->isSuperAdmin()) {
            $items[1]['exportTemplates'] = array(
                'icon' => 'fa-download',
                'command' => 'templates export_all',
                'dynamic' => false,
                'addon' => false,
                'target' => 'design/export'
            );

            //logs
            $items[1]['logsDeveloper'] = array(
                'icon' => 'fal fa-scroll',
                'command' => 'view_developer_log',
                'command_title' => lang('view') . ' <b>' . lang('view_developer_log') . '</b>',
                'dynamic' => false,
                'addon' => false,
                'target' => 'logs/developer'
            );
        }

        $channels = ee('Model')->get('Channel')->order('channel_title', 'ASC')->limit(11)->all();
        foreach ($channels as $channel) {
            $items[1]['viewEntriesInChannel' . $channel->getId()] = array(
                'icon' => 'fa-eye',
                'command' => $channel->channel_title,
                'command_title' => str_replace('[channel]', $channel->channel_title, lang('jump_viewEntriesIn')),
                'dynamic' => false,
                'addon' => false,
                'target' => 'publish/edit&filter_by_channel=' . $channel->getId(),
                'permission' => ['can_edit_self_entries_channel_id_' . $channel->getId(),
                    'can_delete_self_entries_channel_id_' . $channel->getId(),
                    'can_edit_other_entries_channel_id_' . $channel->getId(),
                    'can_delete_all_entries_channel_id_' . $channel->getId(),
                    'can_assign_post_authors_channel_id_' . $channel->getId()
                ]
            );
        }

        // Member Profile Trigger word
        if (ee('Config')->getFile()->getBoolean('legacy_member_templates')) {
            $items[1]['systemTemplatesMembers'] = array(
                'icon' => 'fa-eye',
                'command' => 'member_profile_templates',
                'dynamic' => false,
                'addon' => false,
                'target' => 'design/members',
                'permission' => ['can_access_design', 'can_admin_mbr_templates']
            );

            $items[1]['systemSettingsMembers']['anchors']['fieldset-member_theme'] = array(
                'trail' => [
                    'member_settings'
                ],
                'command' => 'member_theme member_theme_desc',
                'command_title' => 'member_theme'
            );

            $items[1]['systemSettingsUrls']['anchors']['fieldset-profile_trigger'] = array(
                'trail' => [
                    'settings',
                    // 'url_path_settings'
                ],
                'command' => 'member_segment_trigger',
                'command_title' => 'member_segment_trigger'
            );
        }

        //check permissions for comment links
        if (! ee('Permission')->hasAny(
            'can_moderate_comments',
            'can_edit_own_comments',
            'can_delete_own_comments',
            'can_edit_all_comments',
            'can_delete_all_comments'
        )) {
            unset($items[1]['viewComments']);
            unset($items[1]['viewCommentsFor']);
        }

        //if this is multi-site install, add links
        if (ee()->config->item('multiple_sites_enabled') === 'y') {
            $site_list = ee()->session->userdata('assigned_sites');
            if (!empty($site_list) && count($site_list) > 1) {
                $items[1]['switchSite'] = array(
                    'icon' => 'fa-globe',
                    'command' => 'switch site msm',
                    'dynamic' => true,
                    'addon' => false,
                    'target' => 'sites/switch'
                );

                if (ee('Permission')->can('admin_sites')) {
                    $items[1]['editSite'] = array(
                        'icon' => 'fa-pencil-alt',
                        'command' => 'edit site msm',
                        'dynamic' => true,
                        'addon' => false,
                        'target' => 'sites/edit'
                    );
                }
            }
        }

        //add custom menu links (addons to be included later)
        $menuItems = ee('Model')->get('MenuItem')
            ->fields('MenuItem.*', 'Children.*')
            ->with(array('Set' => 'RoleSettings'), 'Children')
            ->filter('type', 'IN', ['link', 'submenu'])
            ->filter('RoleSettings.role_id', ee()->session->userdata('role_id'))
            ->order('MenuItem.sort')
            ->order('Children.sort')
            ->all();

        foreach ($menuItems as $item) {
            if ($item->type == 'submenu') {
                foreach ($item->Children as $child) {
                    $items[1]['custom_' . $child->item_id] = array(
                        'icon' => 'fa-link',
                        'command' => 'menu_manager ' . $child->name,
                        'command_title' => lang('jump_menuLink') . ': ' . $item->name . ' / ' . $child->name,
                        'dynamic' => false,
                        'target' => $child->data
                    );
                }
            } elseif ($item->parent_id == 0) {
                $items[1]['custom_' . $item->item_id] = array(
                    'icon' => 'fa-link',
                    'command' => 'menu_manager ' . $item->name,
                    'command_title' => lang('jump_menuLink') . ': ' . $item->name,
                    'dynamic' => false,
                    'target' => $item->data
                );
            }
        }

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
                if ($info->get('built_in') || ! $info->isInstalled() || ! $info->get('settings_exist')) {
                    continue;
                }

                if ($info->hasModule() && !in_array($info->getModuleClass(), $assigned_modules)) {
                    continue;
                }

                if ($info->hasExtension() || $info->hasControlPanel()) {
                    // Create a jump to the add-on itself.
                    $items[1]['addon_' . $name] = array(
                        'icon' => 'fa-puzzle-piece',
                        'command' => 'addon extension module ' . $name . ' ' . $info->getName(),
                        'command_title' => lang('addon') . ': ' . $info->getName(),
                        'dynamic' => false,
                        'addon' => true,
                        'target' => 'addons/settings/' . $name
                    );
                }

                if (!$info->hasJumpMenu()) {
                    continue;
                }

                //include addon's own jumps
                $items[1] = array_merge($items[1], $info->getJumps());
            }
        }

        //take out anchors and make them separate items
        foreach ($items[1] as $key => $item) {
            if (isset($item['anchors'])) {
                foreach ($item['anchors'] as $achor_key => $anchor) {
                    if (empty($anchor)) {
                        continue;
                    }
                    $trail = '';
                    if (isset($anchor['trail'])) {
                        if (is_array($anchor['trail'])) {
                            foreach ($anchor['trail'] as $tail) {
                                $trail .= lang($tail) . ' &raquo; ';
                            }
                        } else {
                            $trail = lang($anchor['trail']) . ' &raquo; ';
                        }
                    }
                    $items[1][$key . '_' . $achor_key] = array(
                        'icon' => $item['icon'],
                        'command' => $anchor['command'],
                        'command_title' => $trail . (isset($anchor['command_title']) ? lang($anchor['command_title']) : lang($anchor['command'])),
                        'dynamic' => isset($item['dynamic']) ? $item['dynamic'] : false,
                        'addon' => isset($item['addon']) ? $item['addon'] : false,
                        'target' => ee('CP/URL')->make($item['target'])->compile() . '#' . $achor_key,
                        'permission' => isset($item['permission']) ? $item['permission'] : null
                    );
                }
                unset($items[1][$key]['anchors']);
            }
        }

        //member quick links
        if (!empty(ee()->session->getMember()->quick_links)) {
            foreach (explode("\n", ee()->session->getMember()->quick_links) as $i => $row) {
                $x = explode('|', $row);
                $items[1]['quicklink_' . $i] = array(
                    'icon' => 'fa-link',
                    'command' => 'quick_link ' . $x[0],
                    'command_title' => lang('jump_quickLink') . ': ' . $x[0],
                    'dynamic' => false,
                    'target' => $x[1]
                );
            }
        }

        //translate the commands
        foreach ($items[1] as $index => $item) {
            if (isset($item['command'])) {
                $commands = explode(' ', $item['command']);
                $commands_translated = [];
                foreach ($commands as $command) {
                    $commands_translated[] = lang($command);
                }
                $items[1][$index]['command'] = implode(' ', $commands_translated);
            }
        }

        // Cache our items. We're bypassing the checks for the default
        // cache driver because we want this to be cached and working
        // even if the dev has set caching to disabled.
        ee()->cache->file->save('jumpmenu/' . md5(ee()->session->getMember()->getId()), $items, 3600);

        // Assign our combined item list back to our static variable.
        self::$items = $items;
    }
}

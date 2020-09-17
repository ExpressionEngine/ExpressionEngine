<?php

/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2020, Packet Tide, LLC (https://www.packettide.com)
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
            //cp
            'home' => array(
                'icon' => 'fa-home',
                'command' => 'nav_homepage dashboard overview',
                'dynamic' => false,
                'addon' => false,
                'target' => 'homepage'
            ),
            //entries
            'viewEntriesIn' => array(
                'icon' => 'fa-eye',
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
                'target' => 'members/fields/create',
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
            'viewMembers' => array(
                'icon' => 'fa-eye',
                'command' => 'view_members',
                'dynamic' => false,
                'addon' => false,
                'target' => 'members',
                'permission' => 'can_access_members'
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
                'target' => 'members/fields',
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
                'target' => 'members/ban-settings',
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
            //files
            'viewFiles' => array(
                'icon' => 'fa-eye',
                'command' => 'view all_files',
                'dynamic' => false,
                'addon' => false,
                'target' => 'files',
                'permission' => 'can_access_files'
            ),
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
                'dynamic' => true,
                'addon' => false,
                'target' => 'login/logout'
            ),
            'myProfile' => array(
                'icon' => 'fa-user',
                'command' => 'my_profile my_account',
                'dynamic' => false,
                'addon' => false,
                'target' => 'members/profile'
            ),
            //addons
            'viewAddons' => array(
                'icon' => 'fa-eye',
                'command' => 'view addons add-ons modules plugins extensions',
                'dynamic' => false,
                'addon' => false,
                'target' => 'addons',
                'permission' => 'can_access_addons'
            ),
            //channels
            'viewChannels' => array(
                'icon' => 'fa-eye',
                'command' => 'view manage_channels',
                'dynamic' => false,
                'addon' => false,
                'target' => 'channels',
                'permission' => 'can_admin_channels'
            ),
            'viewChannelFields' => array(
                'icon' => 'fa-eye',
                'command' => 'view edit_channel_fields custom_fields',
                'dynamic' => false,
                'addon' => false,
                'target' => 'fields',
                'permission' => ['can_create_channel_fields', 'can_edit_channel_fields']
            ),
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
            'viewTemplates' => array(
                'icon' => 'fa-eye',
                'command' => 'view templates template_manager',
                'dynamic' => false,
                'addon' => false,
                'target' => 'design',
                'permission' => 'can_access_design'
            ),
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
            'systemTemplatesMembers' => array(
                'icon' => 'fa-eye',
                'command' => 'member_profile_templates',
                'dynamic' => false,
                'addon' => false,
                'target' => 'design/members',
                'permission' => ['can_access_design', 'can_admin_mbr_templates']
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
                 // Member Profile Trigger word
                    'fieldset-profile_trigger' => array(
                        'trail' => [
                            'settings',
                            // 'url_path_settings'
                        ],
                        'command' => 'member_segment_trigger',
                        'command_title' => 'member_segment_trigger'
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
                            'settings',
                            // 'outgoing_email'
                        ],
                        'command' => 'webmaster_email webmaster_email_desc outgoing_email',
                        'command_title' => 'webmaster_email'
                    ),
                // Email sent from name
                    'fieldset-webmaster_name' => array(
                        'trail' => [
                            'settings',
                            // 'outgoing_email'
                        ],
                        'command' => 'webmaster_name webmaster_name_desc outgoing_email',
                        'command_title' => 'webmaster_name'
                    ),
                // Email character encoding
                    'fieldset-email_charset' => array(
                        'trail' => [
                            'settings',
                            // 'outgoing_email'
                        ],
                        'command' => 'email_charset outgoing_email',
                        'command_title' => 'email_charset'
                    ),
                // Email Protocal
                    'fieldset-mail_protocol' => array(
                        'trail' => [
                            'settings',
                            // 'outgoing_email'
                        ],
                        'command' => 'mail_protocol_desc mail_protocol outgoing_email smtp_options',
                        'command_title' => 'mail_protocol'
                    ),
                // Email New Line
                    'fieldset-email_newline' => array(
                        'trail' => [
                            'settings',
                            'outgoing_email'
                        ],
                        'command' => 'email_newline outgoing_email',
                        'command_title' => 'email_newline'
                    ),
                // Email Format
                    'fieldset-mail_format' => array(
                        'trail' => [
                            'settings',
                            'outgoing_email'
                        ],
                        'command' => 'mail_format mail_format_desc outgoing_email',
                        'command_title' => 'mail_format'
                    ),
                // Email word wrap
                    'fieldset-word_wrap' => array(
                        'trail' => [
                            'settings',
                            'outgoing_email'
                        ],
                        'command' => 'word_wrap outgoing_email',
                        'command_title' => 'word_wrap'
                    ),
                )
            ),
            'systemSettingsDebugging' => array(
                'icon' => 'fa-wrench',
                'command' => 'system_settings debugging_output enable_errors show_profiler enable_devlog_alerts gzip_output force_query_string send_headers redirect_method caching_driver max_caches use_newrelic',
                'dynamic' => false,
                'addon' => false,
                'target' => 'settings/debug-output',
                'permission' => 'can_access_sys_prefs'
            ),
            'systemSettingsContentDesign' => array(
                'icon' => 'fa-wrench',
                'command' => 'system_settings content_and_design new_posts_clear_caches enable_sql_caching auto_assign_cat_parents image_resize_protocol image_library_path thumbnail_suffix enable_emoticons emoticon_url',
                'dynamic' => false,
                'addon' => false,
                'target' => 'settings/content-design',
                'permission' => 'can_access_sys_prefs'
            ),
            'systemSettingsComments' => array(
                'icon' => 'fa-wrench',
                'command' => 'system_settings comment_settings enable_comments comment_word_censoring comment_moderation_override comment_edit_time_limit',
                'dynamic' => false,
                'addon' => false,
                'target' => 'settings/comments',
                'permission' => 'can_access_sys_prefs'
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
                'command' => 'system_settings template_settings strict_urls site_404 save_tmpl_revisions max_tmpl_revisions',
                'dynamic' => false,
                'addon' => false,
                'target' => 'settings/template',
                'permission' => 'can_access_sys_prefs'
            ),
            'systemSettingsHitTracking' => array(
                'icon' => 'fa-wrench',
                'command' => 'system_settings hit_tracking enable_online_user_tracking enable_hit_tracking enable_entry_view_tracking dynamic_tracking_disabling',
                'dynamic' => false,
                'addon' => false,
                'target' => 'settings/hit-tracking',
                'permission' => 'can_access_sys_prefs'
            ),
            'systemSettingsWordCensoring' => array(
                'icon' => 'fa-wrench',
                'command' => 'system_settings word_censoring enable_censoring censor_replacement censored_words',
                'dynamic' => false,
                'addon' => false,
                'target' => 'settings/word-censor',
                'permission' => 'can_access_sys_prefs'
            ),
            'systemSettingsMenuManager' => array(
                'icon' => 'fa-wrench',
                'command' => 'system_settings menu_manager menu_sets',
                'dynamic' => false,
                'addon' => false,
                'target' => 'settings/menu-manager',
                'permission' => 'can_access_sys_prefs'
            ),
            'systemSettingsMembers' => array(
                'icon' => 'fa-wrench',
                'command' => 'system_settings member_settings allow_member_registration req_mbr_activation approved_member_notification declined_member_notification require_terms_of_service allow_member_localization default_primary_role member_theme member_listing_settings new_member_notification mbr_notification_emails',
                'dynamic' => false,
                'addon' => false,
                'target' => 'settings/members',
                'permission' => 'can_access_sys_prefs'
            ),
            'systemSettingsMessages' => array(
                'icon' => 'fa-wrench',
                'command' => 'system_settings messaging_settings prv_msg_max_chars prv_msg_html_format prv_msg_auto_links prv_msg_upload_url prv_msg_upload_path attachment_settings',
                'dynamic' => false,
                'addon' => false,
                'target' => 'settings/messages',
                'permission' => 'can_access_sys_prefs'
            ),
            'systemSettingsAvatars' => array(
                'icon' => 'fa-wrench',
                'command' => 'system_settings avatars avatar_url avatar_path avatar_max_width avatar_max_height avatar_max_kb',
                'dynamic' => false,
                'addon' => false,
                'target' => 'settings/avatars',
                'permission' => 'can_access_sys_prefs'
            ),
            'systemSettingsSecurityPrivacy' => array(
                'icon' => 'fa-wrench',
                'command' => 'system_settings security_privacy cp_session_type website_session_type share_analytics cookie_domain cookie_path cookie_prefix cookie_httponly cookie_secure require_cookie_consent allow_username_change un_min_len allow_multi_logins require_ip_for_login password_lockout password_lockout_interval require_secure_passwords pw_min_len allow_dictionary_pw name_of_dictionary_file deny_duplicate_data require_ip_for_posting xss_clean_uploads enable_rank_denial force_interstitial',
                'dynamic' => false,
                'addon' => false,
                'target' => 'settings/security-privacy',
                'permission' => 'can_access_security_settings'
            ),
            'systemSettingsAccessThrottling' => array(
                'icon' => 'fa-wrench',
                'command' => 'system_settings access_throttling enable_throttling banish_masked_ips max_page_loads time_interval lockout_time banishment_type banishment_url banishment_message',
                'dynamic' => false,
                'addon' => false,
                'target' => 'settings/throttling',
                'permission' => 'can_access_security_settings'
            ),
            'systemSettingsCaptcha' => array(
                'icon' => 'fa-wrench',
                'command' => 'system_settings captcha_settings require_captcha captcha_font captcha_rand captcha_require_members captcha_url captcha_path',
                'dynamic' => false,
                'addon' => false,
                'target' => 'settings/captcha',
                'permission' => 'can_access_security_settings'
            ),
            'systemSettingsConsentRequests' => array(
                'icon' => 'fa-wrench',
                'command' => 'system_settings consent_requests',
                'dynamic' => false,
                'addon' => false,
                'target' => 'settings/consents',
                'permission' => 'can_manage_consents'
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
            'systemUtilitiesCacheManager' => array(
                'icon' => 'fa-database',
                'command' => 'system_utilities cache_manager caches_to_clear',
                'dynamic' => false,
                'addon' => false,
                'target' => 'utilities/cache',
                'permission' => 'can_access_data'
            ),
            'systemUtilitiesSearchReindex' => array(
                'icon' => 'fa-hammer',
                'command' => 'system_utilities search_reindex',
                'dynamic' => false,
                'addon' => false,
                'target' => 'utilities/reindex',
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
            'switchTheme' => array(
                'icon' => 'fa-random',
                'command' => 'switch theme light dark',
                'dynamic' => true,
                'addon' => false,
                'target' => 'themes/switch'
            ),
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

        //load language for all the jumps
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

        $items = self::$items;

        //take out anchors and make them separate items
        foreach ($items[1] as $key => $item) {
            if (isset($item['anchors'])) {
                foreach ($item['anchors'] as $achor_key => $anchor) {
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
                unset($items[1][$key]['acnchors']);
            }
        }

        //logs have dynamically build titles

        $items[1] = array_merge($items[1], [
            'logsConsent' => array(
                'icon' => 'fas fa-scroll',
                'command' => 'view_consent_log',
                'command_title' => lang('view') . ' <b>' . lang('view_consent_log') . '</b>',
                'dynamic' => false,
                'addon' => false,
                'target' => 'logs/cp',
                'permission' => 'can_manage_consents'
            ),
            'logsCp' => array(
                'icon' => 'fas fa-scroll',
                'command' => 'view_cp_log',
                'command_title' => lang('view') . ' <b>' . lang('view_cp_log') . '</b>',
                'dynamic' => false,
                'addon' => false,
                'target' => 'logs/consent',
                'permission' => 'can_access_logs'
            ),
            'logsThrottle' => array(
                'icon' => 'fas fa-scroll',
                'command' => 'view_throttle_log',
                'command_title' => lang('view') . ' <b>' . lang('view_throttle_log') . '</b>',
                'dynamic' => false,
                'addon' => false,
                'target' => 'logs/throttle',
                'permission' => 'can_access_logs'
            ),
            'logsEmail' => array(
                'icon' => 'fas fa-scroll',
                'command' => 'view_email_logs',
                'command_title' => lang('view') . ' <b>' . lang('view_email_logs') . '</b>',
                'dynamic' => false,
                'addon' => false,
                'target' => 'logs/email',
                'permission' => 'can_access_logs'
            ),
            'logsSearch' => array(
                'icon' => 'fas fa-scroll',
                'command' => 'view_search_log',
                'command_title' => lang('view') . ' <b>' . lang('view_search_log') . '</b>',
                'dynamic' => false,
                'addon' => false,
                'target' => 'logs/search',
                'permission' => 'can_access_logs'
            ),
        ]);

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
                'icon' => 'fas fa-scroll',
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


        //if this is multi-site install, add links
        if (ee()->config->item('multiple_sites_enabled') === 'y') {
            $site_list = ee()->session->userdata('assigned_sites');
            if (count($site_list) > 1) {
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

		foreach ($menuItems as $item)
		{
			if ($item->type == 'submenu')
			{
				foreach ($item->Children as $child)
				{
                    $items[1]['custom_' . $child->item_id] = array(
                        'icon' => 'fa-link',
                        'command' => 'menu_manager ' . $child->name,
                        'command_title' => lang('jump_menuLink') . ': ' . $item->name . ' / ' . $child->name,
                        'dynamic' => false,
                        'target' => $child->data
                    );
				}
			}
			elseif ($item->parent_id == 0)
			{
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

        //member quick links
        $member = ee('Model')->get('Member', ee()->session->userdata('member_id'))->first();
        if (!empty($member->quick_links)) {
			foreach (explode("\n", $member->quick_links) as $i=>$row) {
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
        ee()->cache->file->save('jumpmenu/' . ee()->session->getMember()->getId(), $items, 3600);

        // Assign our combined item list back to our static variable.
        self::$items = $items;
    }
}

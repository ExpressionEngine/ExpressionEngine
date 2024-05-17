<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Service\Generator\Enums;

use ExpressionEngine\Service\Generator\Traits\EnumTrait;

class Hooks
{
    use EnumTrait;

    public const CLI_BOOT = [
        'name' => 'cli_boot',
        'params' => '$cli',
        'library' => 'CLI',
    ];

    public const CLI_BEFORE_HANDLE = [
        'name' => 'cli_before_handle',
        'params' => '$cli, $commandObject, $commandClassName',
        'library' => 'CLI',
    ];

    public const CORE_BOOT = [
        'name' => 'core_boot',
        'params' => '',
        'library' => 'Core',
    ];

    public const CORE_TEMPLATE_ROUTE = [
        'name' => 'core_template_route',
        'params' => '$uri_string',
        'library' => 'Core',
    ];

    public const EMAIL_SEND = [
        'name' => 'email_send',
        'params' => '&$data',
        'library' => 'Email',
    ];

    public const FILE_AFTER_SAVE = [
        'name' => 'file_after_save',
        'params' => '$file_id, $data',
        'library' => 'Filemanager',
    ];

    public const FILES_AFTER_DELETE = [
        'name' => 'files_after_delete',
        'params' => '$deleted',
        'library' => 'Filemanager',
    ];

    public const CREATE_CAPTCHA_START = [
        'name' => 'create_captcha_start',
        'params' => '$old_word',
        'library' => 'Functions',
    ];

    public const FORM_DECLARATION_MODIFY_DATA = [
        'name' => 'form_declaration_modify_data',
        'params' => '$data',
        'library' => 'Functions',
    ];

    public const FORM_DECLARATION_RETURN = [
        'name' => 'form_declaration_return',
        'params' => '$data',
        'library' => 'Functions',
    ];

    public const GRID_QUERY = [
        'name' => 'grid_query',
        'params' => '$entry_ids, $field_id, $content_type, $data_table, $sql',
        'library' => 'Grid',
    ];

    public const GRID_SAVE = [
        'name' => 'grid_save',
        'params' => '$entry_id, $field_id, $content_type, $data_table, $data',
        'library' => 'Grid',
    ];

    public const SET_COOKIE_END = [
        'name' => 'set_cookie_end',
        'params' => '$data',
        'library' => 'Input',
    ];

    public const MEMBER_CREATE_START = [
        'name' => 'member_create_start',
        'params' => '$data, $cdata',
        'library' => 'Legacy Member Model',
    ];

    public const MEMBER_CREATE_END = [
        'name' => 'member_create_end',
        'params' => '$member_id, $data, $cdata',
        'library' => 'Legacy Member Model',
    ];

    public const MEMBER_UPDATE_START = [
        'name' => 'member_update_start',
        'params' => '$member_id, $data',
        'library' => 'Legacy Member Model',
    ];

    public const MEMBER_UPDATE_END = [
        'name' => 'member_update_end',
        'params' => '$member_id, $data',
        'library' => 'Legacy Member Model',
    ];

    public const MEMBER_DELETE = [
        'name' => 'member_delete',
        'params' => '$member_ids',
        'library' => 'Legacy Member Model',
    ];

    public const OUTPUT_SHOW_MESSAGE = [
        'name' => 'output_show_message',
        'params' => '$data, $output',
        'library' => 'Output',
    ];

    public const PAGINATION_CREATE = [
        'name' => 'pagination_create',
        'params' => '$obj, $count',
        'library' => 'Pagination',
    ];

    public const PAGINATION_FETCH_DATA = [
        'name' => 'pagination_fetch_data',
        'params' => '$obj',
        'library' => 'Pagination',
    ];

    public const RELATIONSHIPS_DISPLAY_FIELD = [
        'name' => 'relationships_display_field',
        'params' => '$entry_id, $field_id, $sql',
        'library' => 'Relationships Fieldtype Extension Hooks',
    ];

    public const RELATIONSHIPS_DISPLAY_FIELD_OPTIONS = [
        'name' => 'relationships_display_field_options',
        'params' => '$entry_id, $field_id, $sql',
        'library' => 'Relationships Fieldtype Extension Hooks',
    ];

    public const RELATIONSHIPS_POST_SAVE = [
        'name' => 'relationships_post_save',
        'params' => '$ships, $entry_id, $field_id',
        'library' => 'Relationships Fieldtype Extension Hooks',
    ];

    public const RELATIONSHIPS_QUERY = [
        'name' => 'relationships_query',
        'params' => '$field_name, $entry_ids, $depths, $sql',
        'library' => 'Relationships Fieldtype Extension Hooks_ids, $depths, $sql',
    ];

    public const RELATIONSHIPS_QUERY_RESULT = [
        'name' => 'relationships_query_result',
        'params' => '$entry_lookup',
        'library' => 'Relationships Fieldtype Extension Hooks',
    ];

    public const RELATIONSHIPS_MODIFY_ROWS = [
        'name' => 'relationships_modify_rows',
        'params' => '$rows, $node',
        'library' => 'Relationships Fieldtype Extension Hooks',
    ];

    public const SESSIONS_END = [
        'name' => 'sessions_end',
        'params' => '$obj',
        'library' => 'Session',
    ];

    public const SESSIONS_START = [
        'name' => 'sessions_start',
        'params' => '$obj',
        'library' => 'Session',
    ];

    public const TEMPLATE_FETCH_TEMPLATE = [
        'name' => 'template_fetch_template',
        'params' => '$row',
        'library' => 'Template',
    ];

    public const TEMPLATE_POST_PARSE = [
        'name' => 'template_post_parse',
        'params' => '$final_template, $is_partial, $site_id',
        'library' => 'Template',
    ];

    public const TYPOGRAPHY_PARSE_TYPE_START = [
        'name' => 'typography_parse_type_start',
        'params' => '$str, $obj, $prefs',
        'library' => 'Typography',
    ];

    public const TYPOGRAPHY_PARSE_TYPE_END = [
        'name' => 'typography_parse_type_end',
        'params' => '$str, $obj, $prefs',
        'library' => 'Typography',
    ];

    public const CUSTOM_FIELD_MODIFY_DATA = [
        'name' => 'custom_field_modify_data',
        'params' => 'EE_Fieldtype $ft, $method, $data',
        'library' => 'Channel Fields API Extension Hooks',
    ];

    public const CATEGORY_DELETE = [
        'name' => 'category_delete',
        'params' => '$cat_ids',
        'library' => 'Admin Content Controller',
    ];

    public const CATEGORY_SAVE = [
        'name' => 'category_save',
        'params' => '$cat_id, $data',
        'library' => 'Admin Content Controller',
    ];

    public const CP_CSS_END = [
        'name' => 'cp_css_end',
        'params' => '',
        'library' => 'CSS Controller',
    ];

    public const TEMPLATE_TYPES = [
        'name' => 'template_types',
        'params' => '',
        'library' => 'Design Controller',
    ];

    public const CP_JS_END = [
        'name' => 'cp_js_end',
        'params' => '',
        'library' => 'Javascript Controller',
    ];

    public const LOGIN_AUTHENTICATE_START = [
        'name' => 'login_authenticate_start',
        'params' => '',
        'library' => 'Login Controller',
    ];

    public const CP_MEMBER_LOGIN = [
        'name' => 'cp_member_login',
        'params' => '$hook_data',
        'library' => 'Login Controller',
    ];

    public const CP_MEMBER_LOGOUT = [
        'name' => 'cp_member_logout',
        'params' => '',
        'library' => 'Login Controller',
    ];

    public const CP_MEMBER_RESET_PASSWORD = [
        'name' => 'cp_member_reset_password',
        'params' => '',
        'library' => 'Login Controller',
    ];

    public const CP_MEMBERS_MEMBER_CREATE_START = [
        'name' => 'cp_members_member_create_start',
        'params' => '',
        'library' => 'Members Controller',
    ];

    public const CP_MEMBERS_MEMBER_CREATE = [
        'name' => 'cp_members_member_create',
        'params' => '$member_id, $data',
        'library' => 'Members Controller',
    ];

    public const CP_MEMBERS_MEMBER_DELETE_END = [
        'name' => 'cp_members_member_delete_end',
        'params' => '$member_ids',
        'library' => 'Members Controller',
    ];

    public const CP_MEMBERS_VALIDATE_MEMBERS = [
        'name' => 'cp_members_validate_members',
        'params' => '',
        'library' => 'Members Controller',
    ];

    public const CP_CUSTOM_MENU = [
        'name' => 'cp_custom_menu',
        'params' => '$menu',
        'library' => 'Control Panel Menu',
    ];

    public const MYACCOUNT_NAV_SETUP = [
        'name' => 'myaccount_nav_setup',
        'params' => '',
        'library' => 'My Account Controller',
    ];

    public const ENTRY_SAVE_AND_CLOSE_REDIRECT = [
        'name' => 'entry_save_and_close_redirect',
        'params' => '$entry',
        'library' => 'Publish Controller',
    ];

    public const PUBLISH_LIVE_PREVIEW_ROUTE = [
        'name' => 'publish_live_preview_route',
        'params' => '$entry_data, $uri, $template_id',
        'library' => 'Publish Controller',
    ];

    public const BEFORE_CATEGORY_FIELD_INSERT = [
        'name' => 'before_category_field_insert',
        'params' => '$category_field, $values',
        'library' => 'CategoryField Model',
    ];

    public const AFTER_CATEGORY_FIELD_INSERT = [
        'name' => 'after_category_field_insert',
        'params' => '$category_field, $values',
        'library' => 'CategoryField Model',
    ];

    public const BEFORE_CATEGORY_FIELD_UPDATE = [
        'name' => 'before_category_field_update',
        'params' => '$category_field, $values, $modified',
        'library' => 'CategoryField Model',
    ];

    public const AFTER_CATEGORY_FIELD_UPDATE = [
        'name' => 'after_category_field_update',
        'params' => '$category_field, $values, $modified',
        'library' => 'CategoryField Model',
    ];

    public const BEFORE_CATEGORY_FIELD_SAVE = [
        'name' => 'before_category_field_save',
        'params' => '$category_field, $values',
        'library' => 'CategoryField Model',
    ];

    public const AFTER_CATEGORY_FIELD_SAVE = [
        'name' => 'after_category_field_save',
        'params' => '$category_field, $values',
        'library' => 'CategoryField Model',
    ];

    public const BEFORE_CATEGORY_FIELD_DELETE = [
        'name' => 'before_category_field_delete',
        'params' => '$category_field, $values',
        'library' => 'CategoryField Model',
    ];

    public const AFTER_CATEGORY_FIELD_DELETE = [
        'name' => 'after_category_field_delete',
        'params' => '$category_field, $values',
        'library' => 'CategoryField Model',
    ];

    public const BEFORE_CATEGORY_FIELD_BULK_DELETE = [
        'name' => 'before_category_field_bulk_delete',
        'params' => '$delete_ids',
        'library' => 'CategoryField Model',
    ];

    public const AFTER_CATEGORY_FIELD_BULK_DELETE = [
        'name' => 'after_category_field_bulk_delete',
        'params' => '$delete_ids',
        'library' => 'CategoryField Model',
    ];

    public const BEFORE_CATEGORY_GROUP_INSERT = [
        'name' => 'before_category_group_insert',
        'params' => '$category_group, $values',
        'library' => 'CategoryGroup Model',
    ];

    public const AFTER_CATEGORY_GROUP_INSERT = [
        'name' => 'after_category_group_insert',
        'params' => '$category_group, $values',
        'library' => 'CategoryGroup Model',
    ];

    public const BEFORE_CATEGORY_GROUP_UPDATE = [
        'name' => 'before_category_group_update',
        'params' => '$category_group, $values, $modified',
        'library' => 'CategoryGroup Model',
    ];

    public const AFTER_CATEGORY_GROUP_UPDATE = [
        'name' => 'after_category_group_update',
        'params' => '$category_group, $values, $modified',
        'library' => 'CategoryGroup Model',
    ];

    public const BEFORE_CATEGORY_GROUP_SAVE = [
        'name' => 'before_category_group_save',
        'params' => '$category_group, $values',
        'library' => 'CategoryGroup Model',
    ];

    public const AFTER_CATEGORY_GROUP_SAVE = [
        'name' => 'after_category_group_save',
        'params' => '$category_group, $values',
        'library' => 'CategoryGroup Model',
    ];

    public const BEFORE_CATEGORY_GROUP_DELETE = [
        'name' => 'before_category_group_delete',
        'params' => '$category_group, $values',
        'library' => 'CategoryGroup Model',
    ];

    public const AFTER_CATEGORY_GROUP_DELETE = [
        'name' => 'after_category_group_delete',
        'params' => '$category_group, $values',
        'library' => 'CategoryGroup Model',
    ];

    public const BEFORE_CATEGORY_GROUP_BULK_DELETE = [
        'name' => 'before_category_group_bulk_delete',
        'params' => '$delete_ids',
        'library' => 'CategoryGroup Model',
    ];

    public const AFTER_CATEGORY_GROUP_BULK_DELETE = [
        'name' => 'after_category_group_bulk_delete',
        'params' => '$delete_ids',
        'library' => 'CategoryGroup Model',
    ];

    public const BEFORE_CATEGORY_INSERT = [
        'name' => 'before_category_insert',
        'params' => '$category, $values',
        'library' => 'Category Model',
    ];

    public const AFTER_CATEGORY_INSERT = [
        'name' => 'after_category_insert',
        'params' => '$category, $values',
        'library' => 'Category Model',
    ];

    public const BEFORE_CATEGORY_UPDATE = [
        'name' => 'before_category_update',
        'params' => '$category, $values, $modified',
        'library' => 'Category Model',
    ];

    public const AFTER_CATEGORY_UPDATE = [
        'name' => 'after_category_update',
        'params' => '$category, $values, $modified',
        'library' => 'Category Model',
    ];

    public const BEFORE_CATEGORY_SAVE = [
        'name' => 'before_category_save',
        'params' => '$category, $values',
        'library' => 'Category Model',
    ];

    public const AFTER_CATEGORY_SAVE = [
        'name' => 'after_category_save',
        'params' => '$category, $values',
        'library' => 'Category Model',
    ];

    public const BEFORE_CATEGORY_DELETE = [
        'name' => 'before_category_delete',
        'params' => '$category, $values',
        'library' => 'Category Model',
    ];

    public const AFTER_CATEGORY_DELETE = [
        'name' => 'after_category_delete',
        'params' => '$category, $values',
        'library' => 'Category Model',
    ];

    public const BEFORE_CATEGORY_BULK_DELETE = [
        'name' => 'before_category_bulk_delete',
        'params' => '$delete_ids',
        'library' => 'Category Model',
    ];

    public const AFTER_CATEGORY_BULK_DELETE = [
        'name' => 'after_category_bulk_delete',
        'params' => '$delete_ids',
        'library' => 'Category Model',
    ];

    public const BEFORE_CHANNEL_INSERT = [
        'name' => 'before_channel_insert',
        'params' => '$channel, $values',
        'library' => 'Channel Model',
    ];

    public const AFTER_CHANNEL_INSERT = [
        'name' => 'after_channel_insert',
        'params' => '$channel, $values',
        'library' => 'Channel Model',
    ];

    public const BEFORE_CHANNEL_UPDATE = [
        'name' => 'before_channel_update',
        'params' => '$channel, $values, $modified',
        'library' => 'Channel Model',
    ];

    public const AFTER_CHANNEL_UPDATE = [
        'name' => 'after_channel_update',
        'params' => '$channel, $values, $modified',
        'library' => 'Channel Model',
    ];

    public const BEFORE_CHANNEL_SAVE = [
        'name' => 'before_channel_save',
        'params' => '$channel, $values',
        'library' => 'Channel Model',
    ];

    public const AFTER_CHANNEL_SAVE = [
        'name' => 'after_channel_save',
        'params' => '$channel, $values',
        'library' => 'Channel Model',
    ];

    public const BEFORE_CHANNEL_DELETE = [
        'name' => 'before_channel_delete',
        'params' => '$channel, $values',
        'library' => 'Channel Model',
    ];

    public const AFTER_CHANNEL_DELETE = [
        'name' => 'after_channel_delete',
        'params' => '$channel, $values',
        'library' => 'Channel Model',
    ];

    public const BEFORE_CHANNEL_ENTRY_INSERT = [
        'name' => 'before_channel_entry_insert',
        'params' => '$entry, $values',
        'library' => 'Channel Entry Model',
    ];

    public const AFTER_CHANNEL_ENTRY_INSERT = [
        'name' => 'after_channel_entry_insert',
        'params' => '$entry, $values',
        'library' => 'Channel Entry Model',
    ];

    public const BEFORE_CHANNEL_ENTRY_UPDATE = [
        'name' => 'before_channel_entry_update',
        'params' => '$entry, $values, $modified',
        'library' => 'Channel Entry Model',
    ];

    public const AFTER_CHANNEL_ENTRY_UPDATE = [
        'name' => 'after_channel_entry_update',
        'params' => '$entry, $values, $modified',
        'library' => 'Channel Entry Model',
    ];

    public const BEFORE_CHANNEL_ENTRY_SAVE = [
        'name' => 'before_channel_entry_save',
        'params' => '$entry, $values',
        'library' => 'Channel Entry Model',
    ];

    public const AFTER_CHANNEL_ENTRY_SAVE = [
        'name' => 'after_channel_entry_save',
        'params' => '$entry, $values',
        'library' => 'Channel Entry Model',
    ];

    public const BEFORE_CHANNEL_ENTRY_DELETE = [
        'name' => 'before_channel_entry_delete',
        'params' => '$entry, $values',
        'library' => 'Channel Entry Model',
    ];

    public const AFTER_CHANNEL_ENTRY_DELETE = [
        'name' => 'after_channel_entry_delete',
        'params' => '$entry, $values',
        'library' => 'Channel Entry Model',
    ];

    public const BEFORE_CHANNEL_ENTRY_BULK_DELETE = [
        'name' => 'before_channel_entry_bulk_delete',
        'params' => '$delete_ids',
        'library' => 'Channel Entry Model',
    ];

    public const AFTER_CHANNEL_ENTRY_BULK_DELETE = [
        'name' => 'after_channel_entry_bulk_delete',
        'params' => '$delete_ids',
        'library' => 'Channel Entry Model',
    ];

    public const BEFORE_CHANNEL_FIELD_GROUP_INSERT = [
        'name' => 'before_channel_field_group_insert',
        'params' => '$channel_field_group, $values',
        'library' => 'ChannelFieldGroup Model',
    ];

    public const AFTER_CHANNEL_FIELD_GROUP_INSERT = [
        'name' => 'after_channel_field_group_insert',
        'params' => '$channel_field_group, $values',
        'library' => 'ChannelFieldGroup Model',
    ];

    public const BEFORE_CHANNEL_FIELD_GROUP_UPDATE = [
        'name' => 'before_channel_field_group_update',
        'params' => '$channel_field_group, $values, $modified',
        'library' => 'ChannelFieldGroup Model',
    ];

    public const AFTER_CHANNEL_FIELD_GROUP_UPDATE = [
        'name' => 'after_channel_field_group_update',
        'params' => '$channel_field_group, $values, $modified',
        'library' => 'ChannelFieldGroup Model',
    ];

    public const BEFORE_CHANNEL_FIELD_GROUP_SAVE = [
        'name' => 'before_channel_field_group_save',
        'params' => '$channel_field_group, $values',
        'library' => 'ChannelFieldGroup Model',
    ];

    public const AFTER_CHANNEL_FIELD_GROUP_SAVE = [
        'name' => 'after_channel_field_group_save',
        'params' => '$channel_field_group, $values',
        'library' => 'ChannelFieldGroup Model',
    ];

    public const BEFORE_CHANNEL_FIELD_GROUP_DELETE = [
        'name' => 'before_channel_field_group_delete',
        'params' => '$channel_field_group, $values',
        'library' => 'ChannelFieldGroup Model',
    ];

    public const AFTER_CHANNEL_FIELD_GROUP_DELETE = [
        'name' => 'after_channel_field_group_delete',
        'params' => '$channel_field_group, $values',
        'library' => 'ChannelFieldGroup Model',
    ];

    public const BEFORE_CHANNEL_FIELD_GROUP_BULK_DELETE = [
        'name' => 'before_channel_field_group_bulk_delete',
        'params' => '$delete_ids',
        'library' => 'ChannelFieldGroup Model',
    ];

    public const AFTER_CHANNEL_FIELD_GROUP_BULK_DELETE = [
        'name' => 'after_channel_field_group_bulk_delete',
        'params' => '$delete_ids',
        'library' => 'ChannelFieldGroup Model',
    ];

    public const BEFORE_CHANNEL_FIELD_INSERT = [
        'name' => 'before_channel_field_insert',
        'params' => '$channel_field, $values',
        'library' => 'ChannelField Model',
    ];

    public const AFTER_CHANNEL_FIELD_INSERT = [
        'name' => 'after_channel_field_insert',
        'params' => '$channel_field, $values',
        'library' => 'ChannelField Model',
    ];

    public const BEFORE_CHANNEL_FIELD_UPDATE = [
        'name' => 'before_channel_field_update',
        'params' => '$channel_field, $values, $modified',
        'library' => 'ChannelField Model',
    ];

    public const AFTER_CHANNEL_FIELD_UPDATE = [
        'name' => 'after_channel_field_update',
        'params' => '$channel_field, $values, $modified',
        'library' => 'ChannelField Model',
    ];

    public const BEFORE_CHANNEL_FIELD_SAVE = [
        'name' => 'before_channel_field_save',
        'params' => '$channel_field, $values',
        'library' => 'ChannelField Model',
    ];

    public const AFTER_CHANNEL_FIELD_SAVE = [
        'name' => 'after_channel_field_save',
        'params' => '$channel_field, $values',
        'library' => 'ChannelField Model',
    ];

    public const BEFORE_CHANNEL_FIELD_DELETE = [
        'name' => 'before_channel_field_delete',
        'params' => '$channel_field, $values',
        'library' => 'ChannelField Model',
    ];

    public const AFTER_CHANNEL_FIELD_DELETE = [
        'name' => 'after_channel_field_delete',
        'params' => '$channel_field, $values',
        'library' => 'ChannelField Model',
    ];

    public const BEFORE_CHANNEL_FIELD_BULK_DELETE = [
        'name' => 'before_channel_field_bulk_delete',
        'params' => '$delete_ids',
        'library' => 'ChannelField Model',
    ];

    public const AFTER_CHANNEL_FIELD_BULK_DELETE = [
        'name' => 'after_channel_field_bulk_delete',
        'params' => '$delete_ids',
        'library' => 'ChannelField Model',
    ];

    public const BEFORE_CHANNEL_FORM_SETTINGS_INSERT = [
        'name' => 'before_channel_form_settings_insert',
        'params' => '$channel, $values',
        'library' => 'ChannelFormSettings Model',
    ];

    public const AFTER_CHANNEL_FORM_SETTINGS_INSERT = [
        'name' => 'after_channel_form_settings_insert',
        'params' => '$channel, $values',
        'library' => 'ChannelFormSettings Model',
    ];

    public const BEFORE_CHANNEL_FORM_SETTINGS_UPDATE = [
        'name' => 'before_channel_form_settings_update',
        'params' => '$channel, $values, $modified',
        'library' => 'ChannelFormSettings Model',
    ];

    public const AFTER_CHANNEL_FORM_SETTINGS_UPDATE = [
        'name' => 'after_channel_form_settings_update',
        'params' => '$channel, $values, $modified',
        'library' => 'ChannelFormSettings Model',
    ];

    public const BEFORE_CHANNEL_FORM_SETTINGS_SAVE = [
        'name' => 'before_channel_form_settings_save',
        'params' => '$channel, $values',
        'library' => 'ChannelFormSettings Model',
    ];

    public const AFTER_CHANNEL_FORM_SETTINGS_SAVE = [
        'name' => 'after_channel_form_settings_save',
        'params' => '$channel, $values',
        'library' => 'ChannelFormSettings Model',
    ];

    public const BEFORE_CHANNEL_FORM_SETTINGS_DELETE = [
        'name' => 'before_channel_form_settings_delete',
        'params' => '$channel, $values',
        'library' => 'ChannelFormSettings Model',
    ];

    public const AFTER_CHANNEL_FORM_SETTINGS_DELETE = [
        'name' => 'after_channel_form_settings_delete',
        'params' => '$channel, $values',
        'library' => 'ChannelFormSettings Model',
    ];

    public const BEFORE_CHANNEL_LAYOUT_INSERT = [
        'name' => 'before_channel_layout_insert',
        'params' => '$channel, $values',
        'library' => 'ChannelLayout Model',
    ];

    public const AFTER_CHANNEL_LAYOUT_INSERT = [
        'name' => 'after_channel_layout_insert',
        'params' => '$channel, $values',
        'library' => 'ChannelLayout Model',
    ];

    public const BEFORE_CHANNEL_LAYOUT_UPDATE = [
        'name' => 'before_channel_layout_update',
        'params' => '$channel, $values, $modified',
        'library' => 'ChannelLayout Model',
    ];

    public const AFTER_CHANNEL_LAYOUT_UPDATE = [
        'name' => 'after_channel_layout_update',
        'params' => '$channel, $values, $modified',
        'library' => 'ChannelLayout Model',
    ];

    public const BEFORE_CHANNEL_LAYOUT_SAVE = [
        'name' => 'before_channel_layout_save',
        'params' => '$channel, $values',
        'library' => 'ChannelLayout Model',
    ];

    public const AFTER_CHANNEL_LAYOUT_SAVE = [
        'name' => 'after_channel_layout_save',
        'params' => '$channel, $values',
        'library' => 'ChannelLayout Model',
    ];

    public const BEFORE_CHANNEL_LAYOUT_DELETE = [
        'name' => 'before_channel_layout_delete',
        'params' => '$channel, $values',
        'library' => 'ChannelLayout Model',
    ];

    public const AFTER_CHANNEL_LAYOUT_DELETE = [
        'name' => 'after_channel_layout_delete',
        'params' => '$channel, $values',
        'library' => 'ChannelLayout Model',
    ];

    public const BEFORE_COMMENT_INSERT = [
        'name' => 'before_comment_insert',
        'params' => '$comment, $values',
        'library' => 'Comment Model',
    ];

    public const AFTER_COMMENT_INSERT = [
        'name' => 'after_comment_insert',
        'params' => '$comment, $values',
        'library' => 'Comment Model',
    ];

    public const BEFORE_COMMENT_UPDATE = [
        'name' => 'before_comment_update',
        'params' => '$comment, $values, $modified',
        'library' => 'Comment Model',
    ];

    public const AFTER_COMMENT_UPDATE = [
        'name' => 'after_comment_update',
        'params' => '$comment, $values, $modified',
        'library' => 'Comment Model',
    ];

    public const BEFORE_COMMENT_SAVE = [
        'name' => 'before_comment_save',
        'params' => '$comment, $values',
        'library' => 'Comment Model',
    ];

    public const AFTER_COMMENT_SAVE = [
        'name' => 'after_comment_save',
        'params' => '$comment, $values',
        'library' => 'Comment Model',
    ];

    public const BEFORE_COMMENT_DELETE = [
        'name' => 'before_comment_delete',
        'params' => '$comment, $values',
        'library' => 'Comment Model',
    ];

    public const AFTER_COMMENT_DELETE = [
        'name' => 'after_comment_delete',
        'params' => '$comment, $values',
        'library' => 'Comment Model',
    ];

    public const BEFORE_COMMENT_BULK_DELETE = [
        'name' => 'before_comment_bulk_delete',
        'params' => '$delete_ids',
        'library' => 'Comment Model',
    ];

    public const AFTER_COMMENT_BULK_DELETE = [
        'name' => 'after_comment_bulk_delete',
        'params' => '$delete_ids',
        'library' => 'Comment Model',
    ];

    public const BEFORE_FILE_INSERT = [
        'name' => 'before_file_insert',
        'params' => '$file, $values',
        'library' => 'File Model',
    ];

    public const AFTER_FILE_INSERT = [
        'name' => 'after_file_insert',
        'params' => '$file, $values',
        'library' => 'File Model',
    ];

    public const BEFORE_FILE_UPDATE = [
        'name' => 'before_file_update',
        'params' => '$file, $values, $modified',
        'library' => 'File Model',
    ];

    public const AFTER_FILE_UPDATE = [
        'name' => 'after_file_update',
        'params' => '$file, $values, $modified',
        'library' => 'File Model',
    ];

    public const BEFORE_FILE_SAVE = [
        'name' => 'before_file_save',
        'params' => '$file, $values',
        'library' => 'File Model',
    ];

    public const AFTER_FILE_SAVE = [
        'name' => 'after_file_save',
        'params' => '$file, $values',
        'library' => 'File Model',
    ];

    public const BEFORE_FILE_DELETE = [
        'name' => 'before_file_delete',
        'params' => '$file, $values',
        'library' => 'File Model',
    ];

    public const AFTER_FILE_DELETE = [
        'name' => 'after_file_delete',
        'params' => '$file, $values',
        'library' => 'File Model',
    ];

    public const BEFORE_FILE_BULK_DELETE = [
        'name' => 'before_file_bulk_delete',
        'params' => '$delete_ids',
        'library' => 'File Model',
    ];

    public const AFTER_FILE_BULK_DELETE = [
        'name' => 'after_file_bulk_delete',
        'params' => '$delete_ids',
        'library' => 'File Model',
    ];

    public const BEFORE_GLOBAL_VARIABLE_INSERT = [
        'name' => 'before_global_variable_insert',
        'params' => '$channel, $values',
        'library' => 'GlobalVariable Model',
    ];

    public const AFTER_GLOBAL_VARIABLE_INSERT = [
        'name' => 'after_global_variable_insert',
        'params' => '$channel, $values',
        'library' => 'GlobalVariable Model',
    ];

    public const BEFORE_GLOBAL_VARIABLE_UPDATE = [
        'name' => 'before_global_variable_update',
        'params' => '$channel, $values, $modified',
        'library' => 'GlobalVariable Model',
    ];

    public const AFTER_GLOBAL_VARIABLE_UPDATE = [
        'name' => 'after_global_variable_update',
        'params' => '$channel, $values, $modified',
        'library' => 'GlobalVariable Model',
    ];

    public const BEFORE_GLOBAL_VARIABLE_SAVE = [
        'name' => 'before_global_variable_save',
        'params' => '$channel, $values',
        'library' => 'GlobalVariable Model',
    ];

    public const AFTER_GLOBAL_VARIABLE_SAVE = [
        'name' => 'after_global_variable_save',
        'params' => '$channel, $values',
        'library' => 'GlobalVariable Model',
    ];

    public const BEFORE_GLOBAL_VARIABLE_DELETE = [
        'name' => 'before_global_variable_delete',
        'params' => '$channel, $values',
        'library' => 'GlobalVariable Model',
    ];

    public const AFTER_GLOBAL_VARIABLE_DELETE = [
        'name' => 'after_global_variable_delete',
        'params' => '$channel, $values',
        'library' => 'GlobalVariable Model',
    ];

    public const BEFORE_MEMBER_FIELD_INSERT = [
        'name' => 'before_member_field_insert',
        'params' => '$member_field, $values',
        'library' => 'MemberField Model',
    ];

    public const AFTER_MEMBER_FIELD_INSERT = [
        'name' => 'after_member_field_insert',
        'params' => '$member_field, $values',
        'library' => 'MemberField Model',
    ];

    public const BEFORE_MEMBER_FIELD_UPDATE = [
        'name' => 'before_member_field_update',
        'params' => '$member_field, $values, $modified',
        'library' => 'MemberField Model',
    ];

    public const AFTER_MEMBER_FIELD_UPDATE = [
        'name' => 'after_member_field_update',
        'params' => '$member_field, $values, $modified',
        'library' => 'MemberField Model',
    ];

    public const BEFORE_MEMBER_FIELD_SAVE = [
        'name' => 'before_member_field_save',
        'params' => '$member_field, $values',
        'library' => 'MemberField Model',
    ];

    public const AFTER_MEMBER_FIELD_SAVE = [
        'name' => 'after_member_field_save',
        'params' => '$member_field, $values',
        'library' => 'MemberField Model',
    ];

    public const BEFORE_MEMBER_FIELD_DELETE = [
        'name' => 'before_member_field_delete',
        'params' => '$member_field, $values',
        'library' => 'MemberField Model',
    ];

    public const AFTER_MEMBER_FIELD_DELETE = [
        'name' => 'after_member_field_delete',
        'params' => '$member_field, $values',
        'library' => 'MemberField Model',
    ];

    public const BEFORE_MEMBER_FIELD_BULK_DELETE = [
        'name' => 'before_member_field_bulk_delete',
        'params' => '$delete_ids',
        'library' => 'MemberField Model',
    ];

    public const AFTER_MEMBER_FIELD_BULK_DELETE = [
        'name' => 'after_member_field_bulk_delete',
        'params' => '$delete_ids',
        'library' => 'MemberField Model',
    ];

    public const BEFORE_MEMBER_GROUP_INSERT = [
        'name' => 'before_member_group_insert',
        'params' => '$member_group, $values',
        'library' => 'MemberGroup Model',
    ];

    public const AFTER_MEMBER_GROUP_INSERT = [
        'name' => 'after_member_group_insert',
        'params' => '$member_group, $values',
        'library' => 'MemberGroup Model',
    ];

    public const BEFORE_MEMBER_GROUP_UPDATE = [
        'name' => 'before_member_group_update',
        'params' => '$member_group, $values, $modified',
        'library' => 'MemberGroup Model',
    ];

    public const AFTER_MEMBER_GROUP_UPDATE = [
        'name' => 'after_member_group_update',
        'params' => '$member_group, $values, $modified',
        'library' => 'MemberGroup Model',
    ];

    public const BEFORE_MEMBER_GROUP_SAVE = [
        'name' => 'before_member_group_save',
        'params' => '$member_group, $values',
        'library' => 'MemberGroup Model',
    ];

    public const AFTER_MEMBER_GROUP_SAVE = [
        'name' => 'after_member_group_save',
        'params' => '$member_group, $values',
        'library' => 'MemberGroup Model',
    ];

    public const BEFORE_MEMBER_GROUP_DELETE = [
        'name' => 'before_member_group_delete',
        'params' => '$member_group, $values',
        'library' => 'MemberGroup Model',
    ];

    public const AFTER_MEMBER_GROUP_DELETE = [
        'name' => 'after_member_group_delete',
        'params' => '$member_group, $values',
        'library' => 'MemberGroup Model',
    ];

    public const BEFORE_MEMBER_GROUP_BULK_DELETE = [
        'name' => 'before_member_group_bulk_delete',
        'params' => '$delete_ids',
        'library' => 'MemberGroup Model',
    ];

    public const AFTER_MEMBER_GROUP_BULK_DELETE = [
        'name' => 'after_member_group_bulk_delete',
        'params' => '$delete_ids',
        'library' => 'MemberGroup Model',
    ];

    public const BEFORE_MEMBER_INSERT = [
        'name' => 'before_member_insert',
        'params' => '$member, $values',
        'library' => 'Member Model',
    ];

    public const AFTER_MEMBER_INSERT = [
        'name' => 'after_member_insert',
        'params' => '$member, $values',
        'library' => 'Member Model',
    ];

    public const BEFORE_MEMBER_UPDATE = [
        'name' => 'before_member_update',
        'params' => '$member, $values, $modified',
        'library' => 'Member Model',
    ];

    public const AFTER_MEMBER_UPDATE = [
        'name' => 'after_member_update',
        'params' => '$member, $values, $modified',
        'library' => 'Member Model',
    ];

    public const BEFORE_MEMBER_SAVE = [
        'name' => 'before_member_save',
        'params' => '$member, $values',
        'library' => 'Member Model',
    ];

    public const AFTER_MEMBER_SAVE = [
        'name' => 'after_member_save',
        'params' => '$member, $values',
        'library' => 'Member Model',
    ];

    public const BEFORE_MEMBER_DELETE = [
        'name' => 'before_member_delete',
        'params' => '$member, $values',
        'library' => 'Member Model',
    ];

    public const AFTER_MEMBER_DELETE = [
        'name' => 'after_member_delete',
        'params' => '$member, $values',
        'library' => 'Member Model',
    ];

    public const BEFORE_MEMBER_BULK_DELETE = [
        'name' => 'before_member_bulk_delete',
        'params' => '$delete_ids',
        'library' => 'Member Model',
    ];

    public const AFTER_MEMBER_BULK_DELETE = [
        'name' => 'after_member_bulk_delete',
        'params' => '$delete_ids',
        'library' => 'Member Model',
    ];

    public const MEMBER_ANONYMIZE = [
        'name' => 'member_anonymize',
        'params' => '$member',
        'library' => 'Member Model',
    ];

    public const BEFORE_SITE_INSERT = [
        'name' => 'before_site_insert',
        'params' => '$channel, $values',
        'library' => 'Site Model',
    ];

    public const AFTER_SITE_INSERT = [
        'name' => 'after_site_insert',
        'params' => '$channel, $values',
        'library' => 'Site Model',
    ];

    public const BEFORE_SITE_UPDATE = [
        'name' => 'before_site_update',
        'params' => '$channel, $values, $modified',
        'library' => 'Site Model',
    ];

    public const AFTER_SITE_UPDATE = [
        'name' => 'after_site_update',
        'params' => '$channel, $values, $modified',
        'library' => 'Site Model',
    ];

    public const BEFORE_SITE_SAVE = [
        'name' => 'before_site_save',
        'params' => '$channel, $values',
        'library' => 'Site Model',
    ];

    public const AFTER_SITE_SAVE = [
        'name' => 'after_site_save',
        'params' => '$channel, $values',
        'library' => 'Site Model',
    ];

    public const BEFORE_SITE_DELETE = [
        'name' => 'before_site_delete',
        'params' => '$channel, $values',
        'library' => 'Site Model',
    ];

    public const AFTER_SITE_DELETE = [
        'name' => 'after_site_delete',
        'params' => '$channel, $values',
        'library' => 'Site Model',
    ];

    public const BEFORE_TEMPLATE_SNIPPET_INSERT = [
        'name' => 'before_template_snippet_insert',
        'params' => '$channel, $values',
        'library' => 'Snippet Model',
    ];

    public const AFTER_TEMPLATE_SNIPPET_INSERT = [
        'name' => 'after_template_snippet_insert',
        'params' => '$channel, $values',
        'library' => 'Snippet Model',
    ];

    public const BEFORE_TEMPLATE_SNIPPET_UPDATE = [
        'name' => 'before_template_snippet_update',
        'params' => '$channel, $values, $modified',
        'library' => 'Snippet Model',
    ];

    public const AFTER_TEMPLATE_SNIPPET_UPDATE = [
        'name' => 'after_template_snippet_update',
        'params' => '$channel, $values, $modified',
        'library' => 'Snippet Model',
    ];

    public const BEFORE_TEMPLATE_SNIPPET_SAVE = [
        'name' => 'before_template_snippet_save',
        'params' => '$channel, $values',
        'library' => 'Snippet Model',
    ];

    public const AFTER_TEMPLATE_SNIPPET_SAVE = [
        'name' => 'after_template_snippet_save',
        'params' => '$channel, $values',
        'library' => 'Snippet Model',
    ];

    public const BEFORE_TEMPLATE_SNIPPET_DELETE = [
        'name' => 'before_template_snippet_delete',
        'params' => '$channel, $values',
        'library' => 'Snippet Model',
    ];

    public const AFTER_TEMPLATE_SNIPPET_DELETE = [
        'name' => 'after_template_snippet_delete',
        'params' => '$channel, $values',
        'library' => 'Snippet Model',
    ];

    public const BEFORE_SPECIALTY_TEMPLATE_INSERT = [
        'name' => 'before_specialty_template_insert',
        'params' => '$channel, $values',
        'library' => 'SpecialtyTemplate Model',
    ];

    public const AFTER_SPECIALTY_TEMPLATE_INSERT = [
        'name' => 'after_specialty_template_insert',
        'params' => '$channel, $values',
        'library' => 'SpecialtyTemplate Model',
    ];

    public const BEFORE_SPECIALTY_TEMPLATE_UPDATE = [
        'name' => 'before_specialty_template_update',
        'params' => '$channel, $values, $modified',
        'library' => 'SpecialtyTemplate Model',
    ];

    public const AFTER_SPECIALTY_TEMPLATE_UPDATE = [
        'name' => 'after_specialty_template_update',
        'params' => '$channel, $values, $modified',
        'library' => 'SpecialtyTemplate Model',
    ];

    public const BEFORE_SPECIALTY_TEMPLATE_SAVE = [
        'name' => 'before_specialty_template_save',
        'params' => '$channel, $values',
        'library' => 'SpecialtyTemplate Model',
    ];

    public const AFTER_SPECIALTY_TEMPLATE_SAVE = [
        'name' => 'after_specialty_template_save',
        'params' => '$channel, $values',
        'library' => 'SpecialtyTemplate Model',
    ];

    public const BEFORE_SPECIALTY_TEMPLATE_DELETE = [
        'name' => 'before_specialty_template_delete',
        'params' => '$channel, $values',
        'library' => 'SpecialtyTemplate Model',
    ];

    public const AFTER_SPECIALTY_TEMPLATE_DELETE = [
        'name' => 'after_specialty_template_delete',
        'params' => '$channel, $values',
        'library' => 'SpecialtyTemplate Model',
    ];

    public const BEFORE_STATUS_INSERT = [
        'name' => 'before_status_insert',
        'params' => '$channel, $values',
        'library' => 'Status Model',
    ];

    public const AFTER_STATUS_INSERT = [
        'name' => 'after_status_insert',
        'params' => '$channel, $values',
        'library' => 'Status Model',
    ];

    public const BEFORE_STATUS_UPDATE = [
        'name' => 'before_status_update',
        'params' => '$channel, $values, $modified',
        'library' => 'Status Model',
    ];

    public const AFTER_STATUS_UPDATE = [
        'name' => 'after_status_update',
        'params' => '$channel, $values, $modified',
        'library' => 'Status Model',
    ];

    public const BEFORE_STATUS_SAVE = [
        'name' => 'before_status_save',
        'params' => '$channel, $values',
        'library' => 'Status Model',
    ];

    public const AFTER_STATUS_SAVE = [
        'name' => 'after_status_save',
        'params' => '$channel, $values',
        'library' => 'Status Model',
    ];

    public const BEFORE_STATUS_DELETE = [
        'name' => 'before_status_delete',
        'params' => '$channel, $values',
        'library' => 'Status Model',
    ];

    public const AFTER_STATUS_DELETE = [
        'name' => 'after_status_delete',
        'params' => '$channel, $values',
        'library' => 'Status Model',
    ];

    public const BEFORE_TEMPLATE_GROUP_INSERT = [
        'name' => 'before_template_group_insert',
        'params' => '$template_group, $values',
        'library' => 'TemplateGroup Model',
    ];

    public const AFTER_TEMPLATE_GROUP_INSERT = [
        'name' => 'after_template_group_insert',
        'params' => '$template_group, $values',
        'library' => 'TemplateGroup Model',
    ];

    public const BEFORE_TEMPLATE_GROUP_UPDATE = [
        'name' => 'before_template_group_update',
        'params' => '$template_group, $values, $modified',
        'library' => 'TemplateGroup Model',
    ];

    public const AFTER_TEMPLATE_GROUP_UPDATE = [
        'name' => 'after_template_group_update',
        'params' => '$template_group, $values, $modified',
        'library' => 'TemplateGroup Model',
    ];

    public const BEFORE_TEMPLATE_GROUP_SAVE = [
        'name' => 'before_template_group_save',
        'params' => '$template_group, $values',
        'library' => 'TemplateGroup Model',
    ];

    public const AFTER_TEMPLATE_GROUP_SAVE = [
        'name' => 'after_template_group_save',
        'params' => '$template_group, $values',
        'library' => 'TemplateGroup Model',
    ];

    public const BEFORE_TEMPLATE_GROUP_DELETE = [
        'name' => 'before_template_group_delete',
        'params' => '$template_group, $values',
        'library' => 'TemplateGroup Model',
    ];

    public const AFTER_TEMPLATE_GROUP_DELETE = [
        'name' => 'after_template_group_delete',
        'params' => '$template_group, $values',
        'library' => 'TemplateGroup Model',
    ];

    public const BEFORE_TEMPLATE_GROUP_BULK_DELETE = [
        'name' => 'before_template_group_bulk_delete',
        'params' => '$delete_ids',
        'library' => 'TemplateGroup Model',
    ];

    public const AFTER_TEMPLATE_GROUP_BULK_DELETE = [
        'name' => 'after_template_group_bulk_delete',
        'params' => '$delete_ids',
        'library' => 'TemplateGroup Model',
    ];

    public const BEFORE_TEMPLATE_ROUTE_INSERT = [
        'name' => 'before_template_route_insert',
        'params' => '$template_route, $values',
        'library' => 'TemplateRoute Model',
    ];

    public const AFTER_TEMPLATE_ROUTE_INSERT = [
        'name' => 'after_template_route_insert',
        'params' => '$template_route, $values',
        'library' => 'TemplateRoute Model',
    ];

    public const BEFORE_TEMPLATE_ROUTE_UPDATE = [
        'name' => 'before_template_route_update',
        'params' => '$template_route, $values, $modified',
        'library' => 'TemplateRoute Model',
    ];

    public const AFTER_TEMPLATE_ROUTE_UPDATE = [
        'name' => 'after_template_route_update',
        'params' => '$template_route, $values, $modified',
        'library' => 'TemplateRoute Model',
    ];

    public const BEFORE_TEMPLATE_ROUTE_SAVE = [
        'name' => 'before_template_route_save',
        'params' => '$template_route, $values',
        'library' => 'TemplateRoute Model',
    ];

    public const AFTER_TEMPLATE_ROUTE_SAVE = [
        'name' => 'after_template_route_save',
        'params' => '$template_route, $values',
        'library' => 'TemplateRoute Model',
    ];

    public const BEFORE_TEMPLATE_ROUTE_DELETE = [
        'name' => 'before_template_route_delete',
        'params' => '$template_route, $values',
        'library' => 'TemplateRoute Model',
    ];

    public const AFTER_TEMPLATE_ROUTE_DELETE = [
        'name' => 'after_template_route_delete',
        'params' => '$template_route, $values',
        'library' => 'TemplateRoute Model',
    ];

    public const BEFORE_TEMPLATE_ROUTE_BULK_DELETE = [
        'name' => 'before_template_route_bulk_delete',
        'params' => '$delete_ids',
        'library' => 'TemplateRoute Model',
    ];

    public const AFTER_TEMPLATE_ROUTE_BULK_DELETE = [
        'name' => 'after_template_route_bulk_delete',
        'params' => '$delete_ids',
        'library' => 'TemplateRoute Model',
    ];

    public const BEFORE_TEMPLATE_INSERT = [
        'name' => 'before_template_insert',
        'params' => '$template, $values',
        'library' => 'Template Model',
    ];

    public const AFTER_TEMPLATE_INSERT = [
        'name' => 'after_template_insert',
        'params' => '$template, $values',
        'library' => 'Template Model',
    ];

    public const BEFORE_TEMPLATE_UPDATE = [
        'name' => 'before_template_update',
        'params' => '$template, $values, $modified',
        'library' => 'Template Model',
    ];

    public const AFTER_TEMPLATE_UPDATE = [
        'name' => 'after_template_update',
        'params' => '$template, $values, $modified',
        'library' => 'Template Model',
    ];

    public const BEFORE_TEMPLATE_SAVE = [
        'name' => 'before_template_save',
        'params' => '$template, $values',
        'library' => 'Template Model',
    ];

    public const AFTER_TEMPLATE_SAVE = [
        'name' => 'after_template_save',
        'params' => '$template, $values',
        'library' => 'Template Model',
    ];

    public const BEFORE_TEMPLATE_DELETE = [
        'name' => 'before_template_delete',
        'params' => '$template, $values',
        'library' => 'Template Model',
    ];

    public const AFTER_TEMPLATE_DELETE = [
        'name' => 'after_template_delete',
        'params' => '$template, $values',
        'library' => 'Template Model',
    ];

    public const BEFORE_TEMPLATE_BULK_DELETE = [
        'name' => 'before_template_bulk_delete',
        'params' => '$delete_ids',
        'library' => 'Template Model',
    ];

    public const AFTER_TEMPLATE_BULK_DELETE = [
        'name' => 'after_template_bulk_delete',
        'params' => '$delete_ids',
        'library' => 'Template Model',
    ];

    public const CHANNEL_ENTRIES_QUERY_RESULT = [
        'name' => 'channel_entries_query_result',
        'params' => '$obj, $query_result',
        'library' => 'Channel Module',
    ];

    public const CHANNEL_ENTRIES_TAGDATA = [
        'name' => 'channel_entries_tagdata',
        'params' => '$tagdata, $row, $obj',
        'library' => 'Channel Module',
    ];

    public const CHANNEL_ENTRIES_ROW = [
        'name' => 'channel_entries_row',
        'params' => '$obj, $row',
        'library' => 'Channel Module',
    ];

    public const FUNCTION_NAME = [
        'name' => 'function_name',
        'params' => '$tagdata, $row, $obj',
        'library' => 'Channel Module',
    ];

    public const CHANNEL_MODULE_CALENDAR_START = [
        'name' => 'channel_module_calendar_start',
        'params' => '',
        'library' => 'Channel Module',
    ];

    public const CHANNEL_MODULE_CATEGORIES_START = [
        'name' => 'channel_module_categories_start',
        'params' => '',
        'library' => 'Channel Module',
    ];

    public const CHANNEL_MODULE_CATEGORY_HEADING_START = [
        'name' => 'channel_module_category_heading_start',
        'params' => '',
        'library' => 'Channel Module',
    ];

    public const CHANNEL_FORM_ENTRY_FORM_ABSOLUTE_START = [
        'name' => 'channel_form_entry_form_absolute_start',
        'params' => '$channel_form_obj',
        'library' => 'Channel Form Extension Hooks',
    ];

    public const CHANNEL_FORM_ENTRY_FORM_TAGDATA_START = [
        'name' => 'channel_form_entry_form_tagdata_start',
        'params' => '$tagdata, $channel_form_obj',
        'library' => 'Channel Form Extension Hooks',
    ];

    public const CHANNEL_FORM_ENTRY_FORM_TAGDATA_END = [
        'name' => 'channel_form_entry_form_tagdata_end',
        'params' => '$return_tagdata, $channel_form_obj',
        'library' => 'Channel Form Extension Hooks',
    ];

    public const CHANNEL_FORM_SUBMIT_ENTRY_START = [
        'name' => 'channel_form_submit_entry_start',
        'params' => '$channel_form_obj',
        'library' => 'Channel Form Extension Hooks',
    ];

    public const CHANNEL_FORM_SUBMIT_ENTRY_END = [
        'name' => 'channel_form_submit_entry_end',
        'params' => '$channel_form_obj',
        'library' => 'Channel Form Extension Hooks',
    ];

    public const DELETE_COMMENT_ADDITIONAL = [
        'name' => 'delete_comment_additional',
        'params' => '$comment_ids',
        'library' => 'CP Comment Module',
    ];

    public const UPDATE_COMMENT_ADDITIONAL = [
        'name' => 'update_comment_additional',
        'params' => '$comment_id, $data',
        'library' => 'CP Comment Module',
    ];

    public const COMMENT_ENTRIES_QUERY_RESULT = [
        'name' => 'comment_entries_query_result',
        'params' => '$results',
        'library' => 'Frontend Comment Module',
    ];

    public const COMMENT_ENTRIES_COMMENT_IDS_QUERY = [
        'name' => 'comment_entries_comment_ids_query',
        'params' => '$db',
        'library' => 'Frontend Comment Module',
    ];

    public const COMMENT_ENTRIES_COMMENT_FORMAT = [
        'name' => 'comment_entries_comment_format',
        'params' => '$row',
        'library' => 'Frontend Comment Module',
    ];

    public const COMMENT_ENTRIES_TAGDATA = [
        'name' => 'comment_entries_tagdata',
        'params' => '$tagdata, $row',
        'library' => 'Frontend Comment Module',
    ];

    public const COMMENT_FORM_END = [
        'name' => 'comment_form_end',
        'params' => '$res',
        'library' => 'Frontend Comment Module',
    ];

    public const COMMENT_FORM_HIDDEN_FIELDS = [
        'name' => 'comment_form_hidden_fields',
        'params' => '$hidden_fields',
        'library' => 'Frontend Comment Module',
    ];

    public const COMMENT_FORM_TAGDATA = [
        'name' => 'comment_form_tagdata',
        'params' => '$tagdata',
        'library' => 'Frontend Comment Module',
    ];

    public const COMMENT_PREVIEW_COMMENT_FORMAT = [
        'name' => 'comment_preview_comment_format',
        'params' => '$row',
        'library' => 'Frontend Comment Module',
    ];

    public const COMMENT_PREVIEW_TAGDATA = [
        'name' => 'comment_preview_tagdata',
        'params' => '$tagdata',
        'library' => 'Frontend Comment Module',
    ];

    public const INSERT_COMMENT_START = [
        'name' => 'insert_comment_start',
        'params' => '',
        'library' => 'Frontend Comment Module',
    ];

    public const INSERT_COMMENT_END = [
        'name' => 'insert_comment_end',
        'params' => '$data, $comment_moderate, $comment_id',
        'library' => 'Frontend Comment Module',
    ];

    public const INSERT_COMMENT_INSERT_ARRAY = [
        'name' => 'insert_comment_insert_array',
        'params' => '$data',
        'library' => 'Frontend Comment Module',
    ];

    public const INSERT_COMMENT_PREFERENCES_SQL = [
        'name' => 'insert_comment_preferences_sql',
        'params' => '$sql',
        'library' => 'Frontend Comment Module',
    ];

    public const EMAIL_MODULE_SEND_EMAIL_END = [
        'name' => 'email_module_send_email_end',
        'params' => '$subject, $message, $approved_tos, $approved_recipients',
        'library' => 'Email Module',
    ];

    public const EMAIL_MODULE_TELLAFRIEND_OVERRIDE = [
        'name' => 'email_module_tellafriend_override',
        'params' => '$qstring, $obj',
        'library' => 'Email Module',
    ];

    public const FORUM_ADD_TEMPLATE = [
        'name' => 'forum_add_template',
        'params' => '$which, $classname',
        'library' => 'Forum Module',
    ];

    public const FORUM_INCLUDE_EXTRAS = [
        'name' => 'forum_include_extras',
        'params' => '$obj, $function, $element',
        'library' => 'Forum Module',
    ];

    public const FORUM_SUBMISSION_FORM_START = [
        'name' => 'forum_submission_form_start',
        'params' => '$obj, $str',
        'library' => 'Forum Module',
    ];

    public const FORUM_SUBMISSION_FORM_END = [
        'name' => 'forum_submission_form_end',
        'params' => '$obj, $str',
        'library' => 'Forum Module',
    ];

    public const FORUM_SUBMISSION_PAGE = [
        'name' => 'forum_submission_page',
        'params' => '$obj, $type',
        'library' => 'Forum Module',
    ];

    public const FORUM_SUBMIT_POST_START = [
        'name' => 'forum_submit_post_start',
        'params' => '$obj',
        'library' => 'Forum Module',
    ];

    public const FORUM_SUBMIT_POST_END = [
        'name' => 'forum_submit_post_end',
        'params' => '$obj, $data',
        'library' => 'Forum Module',
    ];

    public const FORUM_THREADS_TEMPLATE = [
        'name' => 'forum_threads_template',
        'params' => '$obj, $str, $tquery',
        'library' => 'Forum Module',
    ];

    public const FORUM_THREAD_ROWS_ABSOLUTE_END = [
        'name' => 'forum_thread_rows_absolute_end',
        'params' => '$obj, $data, $thread_rows',
        'library' => 'Forum Module',
    ];

    public const FORUM_THREAD_ROWS_LOOP_START = [
        'name' => 'forum_thread_rows_loop_start',
        'params' => '$obj, $data, $row, $temp',
        'library' => 'Forum Module',
    ];

    public const FORUM_THREAD_ROWS_LOOP_END = [
        'name' => 'forum_thread_rows_loop_end',
        'params' => '$obj, $data, $row, $temp',
        'library' => 'Forum Module',
    ];

    public const FORUM_THREAD_ROWS_START = [
        'name' => 'forum_thread_rows_start',
        'params' => '$obj, $template, $data, $is_announcement, $thread_review',
        'library' => 'Forum Module',
    ];

    public const FORUM_TOPICS_ABSOLUTE_END = [
        'name' => 'forum_topics_absolute_end',
        'params' => '$obj, $result, $str',
        'library' => 'Forum Module',
    ];

    public const FORUM_TOPICS_LOOP_START = [
        'name' => 'forum_topics_loop_start',
        'params' => '$obj, $result, $row, $temp',
        'library' => 'Forum Module',
    ];

    public const FORUM_TOPICS_LOOP_END = [
        'name' => 'forum_topics_loop_end',
        'params' => '$obj, $result, $row, $temp',
        'library' => 'Forum Module',
    ];

    public const FORUM_TOPICS_START = [
        'name' => 'forum_topics_start',
        'params' => '$obj, $str',
        'library' => 'Forum Module',
    ];

    public const MAIN_FORUM_TABLE_ROWS_TEMPLATE = [
        'name' => 'main_forum_table_rows_template',
        'params' => '',
        'library' => 'Forum Module',
    ];

    public const MEMBER_MANAGER = [
        'name' => 'member_manager',
        'params' => '$obj',
        'library' => 'Member Module',
    ];

    public const MEMBER_MEMBER_LOGIN_MULTI = [
        'name' => 'member_member_login_multi',
        'params' => '$hook_data',
        'library' => 'Member Module Authorization',
    ];

    public const MEMBER_MEMBER_LOGIN_SINGLE = [
        'name' => 'member_member_login_single',
        'params' => '$hook_data',
        'library' => 'Member Module Authorization',
    ];

    public const MEMBER_MEMBER_LOGIN_START = [
        'name' => 'member_member_login_start',
        'params' => '',
        'library' => 'Member Module Authorization',
    ];

    public const MEMBER_MEMBER_LOGOUT = [
        'name' => 'member_member_logout',
        'params' => '',
        'library' => 'Member Module Authorization',
    ];

    public const MEMBER_PROCESS_RESET_PASSWORD = [
        'name' => 'member_process_reset_password',
        'params' => '',
        'library' => 'Member Module Authorization',
    ];

    public const MEMBER_MEMBER_REGISTER = [
        'name' => 'member_member_register',
        'params' => '$data, $member_id',
        'library' => 'Member Module Registration',
    ];

    public const MEMBER_MEMBER_REGISTER_ERRORS = [
        'name' => 'member_member_register_errors',
        'params' => '$obj',
        'library' => 'Member Module Registration',
    ];

    public const MEMBER_MEMBER_REGISTER_START = [
        'name' => 'member_member_register_start',
        'params' => '',
        'library' => 'Member Module Registration',
    ];

    public const MEMBER_REGISTER_VALIDATE_MEMBERS = [
        'name' => 'member_register_validate_members',
        'params' => '$member_id',
        'library' => 'Member Module Registration',
    ];

    public const MEMBER_EDIT_PREFERENCES = [
        'name' => 'member_edit_preferences',
        'params' => '$element',
        'library' => 'Member Module Settings',
    ];

    public const MEMBER_UPDATE_PREFERENCES = [
        'name' => 'member_update_preferences',
        'params' => '$data',
        'library' => 'Member Module Settings',
    ];

    public const CHANNEL_SEARCH_MODIFY_SEARCH_QUERY = [
        'name' => 'channel_search_modify_search_query',
        'params' => '$sql, $hash',
        'library' => 'Search Module',
    ];

    public const CHANNEL_SEARCH_MODIFY_RESULT_QUERY = [
        'name' => 'channel_search_modify_result_query',
        'params' => '$sql, $hash',
        'library' => 'Search Module',
    ];

    public const SIMPLE_COMMERCE_EVALUATE_IPN_RESPONSE = [
        'name' => 'simple_commerce_evaluate_ipn_response',
        'params' => '$obj, $result',
        'library' => 'Simple Commerce Module',
    ];

    public const SIMPLE_COMMERCE_PERFORM_ACTIONS_END = [
        'name' => 'simple_commerce_perform_actions_end',
        'params' => '$obj, $row',
        'library' => 'Simple Commerce Module',
    ];

    public const SIMPLE_COMMERCE_PERFORM_ACTIONS_START = [
        'name' => 'simple_commerce_perform_actions_start',
        'params' => '$obj, $row',
        'library' => 'Simple Commerce Module',
    ];

    public const WIKI_START = [
        'name' => 'wiki_start',
        'params' => '$obj',
        'library' => 'Wiki Module',
    ];

    public const WIKI_ARTICLE_START = [
        'name' => 'wiki_article_start',
        'params' => '$obj, $title, $query',
        'library' => 'Wiki Module',
    ];

    public const WIKI_ARTICLE_END = [
        'name' => 'wiki_article_end',
        'params' => '$obj, $query',
        'library' => 'Wiki Module',
    ];

    public const WIKI_SPECIAL_PAGE = [
        'name' => 'wiki_special_page',
        'params' => '$obj, $topic',
        'library' => 'Wiki Module',
    ];

    public const EDIT_WIKI_ARTICLE_END = [
        'name' => 'edit_wiki_article_end',
        'params' => '$obj, $query',
        'library' => 'Wiki Module',
    ];

    public const EDIT_WIKI_ARTICLE_FORM_START = [
        'name' => 'edit_wiki_article_form_start',
        'params' => '$obj, $title, $query',
        'library' => 'Wiki Module',
    ];

    public const EDIT_WIKI_ARTICLE_FORM_END = [
        'name' => 'edit_wiki_article_form_end',
        'params' => '$obj, $query',
        'library' => 'Wiki Module',
    ];
}

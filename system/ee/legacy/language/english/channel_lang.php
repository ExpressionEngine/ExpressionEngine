<?php

$lang = array(

//----------------------------------------
// Required for MODULES page
//----------------------------------------

'channel_module_name' => 'Channel',

'channel_module_description' => 'Channel module',

//----------------------------------------

'channel_no_preview_template' => 'A preview template was not specified in your tag',

'channel_must_be_logged_in' => 'You must be a logged-in member of this site in order to perform this action.',

'channel_not_specified' => 'You must specify a channel in order to use the entry form.',

'channel_no_action_found' => 'Unable to load the resources needed to create the entry form',

/**
 * 3.0
 *
 * Channel Manager
 */

'section_search_results' => 'results for the search term <mark>%s</mark>',

'search_for' => 'Search for "%s"',

'channel_manager' => 'Channel Manager',

'manage_channels' => 'Manage Channels',

'channel' => 'Channel',

'channels' => 'Channels',

'short_name' => 'Short name',

'short_name_col' => 'Short Name',

'custom_fields' => 'Custom Fields',

'custom_fields_for' => 'Custom Fields for %s',

'field_group' => 'Field Group',

'field_groups' => 'Field Groups',

'category_groups' => 'Category Groups',

'no_channels' => 'No <b>Channels</b> found.',

'create_channel' => 'Create Channel',

'channels_removed' => 'Channels removed',

'channels_removed_desc' => '%d channels were removed.',

'maximum_channels_reached' => 'You have reached the maximum number of Channels allowed.',

'import' => 'Import',
'export_set' => 'Export Channel Set',
'btn_import' => 'Import',
'import_channel' => 'Import Channel',

'channel_set_not_exported' => 'Cannot Export Channel Set',
'channel_set_not_exported_desc' => 'Not a valid channel.',

'channel_set_upload_error' => 'Cannot Import Channel',
'channel_set_upload_error_desc' => 'We were unable to import the channel, please make sure your cache folder is writable.',

'channel_set_duplicates_error' => 'Import Creates Duplicates',
'channel_set_duplicates_error_desc' => 'This channel set uses names that already exist on your site. Please rename the following items.',

'channel_set_imported' => 'Channel Imported',
'channel_set_imported_desc' => 'The channel was successfully imported.',

'edit' => 'edit',

'settings' => 'settings',

'layout' => 'Layout',

'layouts' => 'Layouts',

'channel_form_layouts' => '%s &ndash; Form Layouts',

'member_group' => 'Member group',

'no_layouts' => 'No Layouts',

'create_form_layout' => 'Create Form Layout',

'layout_options' => 'Layout Options',

'layout_member_groups' => 'Member group(s)?',

'member_groups_desc' => 'Choose the member group(s) to apply this layout to.',

'create_layout_success' => 'Form Layout Created',

'create_layout_success_desc' => 'The form layout <b>%s</b> has been created.',

'create_layout_error' => 'Cannot Create Form Layout',

'create_layout_error_desc' => 'We were unable to update this group, please review and fix errors below.',

'btn_preview_layout' => 'Preview Layout',

'form_layouts' => 'Form Layouts',

'edit_form_layout' => 'Edit Form Layout &ndash; %s',

'edit_layout_success' => 'Form Layout Updated',

'edit_layout_success_desc' => 'The form layout <b>%s</b> has been updated.',

'edit_layout_error' => 'Cannot Update Form Layout',

'edit_layout_error_desc' => 'We were unable to update this group, please review and fix errors below.',

'layouts_removed_desc' => 'The following form layouts were removed',

'add_tab' => 'Add Tab',

'tab_name' => 'Tab Name',

'tab_name_desc' => 'Short name for this tab.',

'tab_name_required'	=>
'Please choose a name for your tab.',

'duplicate_tab_name' => 'A tab with this name already exists.',

'illegal_tab_name' => 'Tab names may not contain the following characters: *, >, :, +, (, ), [, ], =, |, ", \', ., #, or $',

'error_cannot_hide_tab' => 'Cannot Hide Tab',

'error_tab_has_required_fields' => '<b>%s</b> contains at least one required field and cannot be hidden. Please move the required field(s) to another tab.',

'error_cannot_remove_tab' => 'Cannot Remove Tab',

'error_tab_has_fields' => '<b>%s</b> contains at least one field and cannot be removed. Please move all fields to another tab.',

'assigned_to' => 'assigned to',

/**
 * Channel Create/Edit
 */

'edit_channel' => 'Edit Channel',

'channel_title' => 'Name',

'channel_duplicate' => 'Duplicate existing channel?',
'channel_duplicate_desc' => 'On creation, this channel will copy all settings from the selected channel.',

'channel_do_not_duplicate' => 'Do not duplicate',

'channel_publishing_options' => 'Publishing Options',

'channel_publishing_options_warning' => '<b>Warning</b>: Channels require custom field groups to collect any data other than title, and date.',
'channel_publishing_options_warning2' => 'If you need to collect additional data for this channel, it\'s best practice to create any <a href="%s">custom field groups</a>, first.',

'channel_max_entries' => 'Maximum number of entries',

'channel_max_entries_desc' => 'Leave blank to make unlimited &infin;.',

'default_status_group' => 'Default Statuses',

'status_groups_not_found' => 'No <b>status groups</b> found',

'create_new_status_group' => 'Create New Status Group',

'custom_field_group' => 'Custom field group',

'custom_field_groups_not_found' => 'No <b>custom field groups</b> found',

'create_new_field_group' => 'Create New Field Group',

'custom_fields_desc' => 'Choose the fields you would like to include in this field group.',

'category_groups_not_found' => 'No <b>category groups</b> found',

'create_new_category_group' => 'Create New Category Group',

'channel_created' => 'Channel Created',

'channel_created_desc' => 'The channel <b>%s</b> has been created.',

'channel_not_created' => 'Cannot Create Channel',

'channel_not_created_desc' => 'We were unable to create this channel, please review and fix errors below.',

'channel_updated' => 'Channel Updated',

'channel_updated_desc' => 'The channel <b>%s</b> has been updated.',

'channel_not_updated' => 'Cannot Update Channel',

'channel_not_updated_desc' => 'We were unable to update this channel, please review and fix errors below.',

'invalid_short_name' => 'Your channel name must contain only alpha-numeric characters and no spaces.',

'taken_channel_name' => 'This channel name is already taken.',

/**
 * Channel Settings
 */

'channel_settings' => 'Channel Settings',

'channel_description' => 'Description',

'channel_description_desc' => 'Brief description of this channel.',

'xml_language' => '<abbr title="Extensible Markup Language">XML</abbr> language',

'xml_language_desc' => 'Default language for <abbr title="Extensible Markup Language">XML</abbr> files, generated by this channel.',

'url_path_settings' => '<abbr title="Unified Resource Locator">URL</abbr> and Path Settings',

'channel_url_desc' => '<abbr title="Unified Resource Locator">URL</abbr> location of this channel.',

'comment_form' => 'Comment form',

'comment_form_desc' => '<abbr title="Unified Resource Locator">URL</abbr> location of comment form for this channel.',

'search_results' => 'Search results',

'search_results_desc' => '<abbr title="Unified Resource Locator">URL</abbr> location of search results for this channel.',

'rss_feed' => '<abbr title="Really Simple Syndication">RSS</abbr> feed',

'rss_feed_desc' => '<abbr title="Unified Resource Locator">URL</abbr> location of <abbr title="Really Simple Syndication">RSS</abbr> for this channel.',

'live_look_template' => 'Live Look template',

'live_look_template_desc' => 'Template to use for the <mark>Live Look</mark> feature.',

'channel_defaults' => 'Defaults',

'title_field_label' => 'Title field label',
'title_field_label_desc' => 'Changes the title field label in the Publish form for this channel.',

'default_title' => 'Generated title',

'default_title_desc' => 'Title assigned to all <b>new</b> entries in this channel.',

'url_title_prefix' => '<abbr title="Unified Resource Locator">URL</abbr> title prefix',

'url_title_prefix_desc' => '<abbr title="Unified Resource Locator">URL</abbr> title prefix assigned to all <b>new</b> entries in this channel.',

'default_status' => 'Status',

'default_status_desc' => 'Status assigned to all <b>new</b> entries in this channel.',

'default_category' => 'Category',

'default_category_desc' => 'Category assigned to all <b>new</b> entries in this channel.',

'search_excerpt' => 'Search excerpt',

'search_excerpt_desc' => 'Field used for all search result excerpts for this channel.',

'publishing' => 'Publishing',

'html_formatting' => '<abbr title="Hyper-Text Markup Language">HTML</abbr> formatting',

'extra_publish_controls' => 'Show extra publish controls?',

'extra_publish_controls_desc' => 'When set to <b>yes</b>, a second set of publish controls will appear at the top of the publish form for this channel.',

'convert_image_urls' => 'Allow image <abbr title="Unified Resource Locator">URL</abbr>s?',

'convert_image_urls_desc' => 'When set to <b>yes</b>, <abbr title="Unified Resource Locator">URL</abbr>s to image resources will be automagically rendered as images in this channel\'s entries.',

'convert_urls_emails_to_links' => 'Render <abbr title="Unified Resource Locator">URL</abbr>s and Email addresses as links?',

'convert_urls_emails_to_links_desc' => 'When set to <b>yes</b>, <abbr title="Unified Resource Locator">URL</abbr>s and Email address will be rendered as links in this channel\'s entries.',

'channel_form' => 'Channel Form',

'channel_form_status_desc' => 'Default status for forms in this channel.',

'channel_form_default_author' => 'Author',

'channel_form_default_author_desc' => 'Default author for guest entries posted via Channel Form.',

'allow_guest_submission' => 'Allow guest submissions?',

'allow_guest_submission_desc' => 'When set to <b>yes</b>, unregistered users will be able to submit forms for this channel.',

'versioning' => 'Versioning',

'enable_versioning' => 'Enable entry versioning?',

'enable_versioning_desc' => 'When set to <b>enable</b>, ExpressinEngine will save revisions of each entry for this channel.',

'max_versions' => 'Maximum versions per entry',

'max_versions_desc' => 'Maximum number of revisions to be saved per entry.',

'notifications' => 'Notifications',

'enable_author_notification' => 'Enable author notification?',

'enable_author_notification_desc' => 'When set to <b>enable</b>, the author of an entry will be notified when someone comments on their entry.',

'enable_channel_entry_notification' => 'Enable channel entry notification?',

'enable_channel_entry_notification_desc' => 'When set to <b>enable</b>, all recipients listed will receive Email notification when a new entry is published to this channel.</em>
<em>Separate multiple Emails with a <mark>comma (,)</mark>.',

'enable_comment_notification' => 'Enable comment notification?',

'enable_comment_notification_desc' => 'When set to <b>enable</b>, all recipients listed will receive Email notification when a new comment is submitted to this channel.</em>
<em>Separate multiple Emails with a <mark>comma (,)</mark>.',

'commenting' => 'Commenting',

'allow_comments' => 'Allow comments?',

'allow_comments_desc' => 'When set to <b>yes</b>, members can submit comments to this channel\'s entries.',

'allow_comments_checked' => 'Allow comments default?',

'allow_comments_checked_desc' => 'When set to <b>yes</b>, the "Allow comments" option will be set to "yes" by default',

'require_membership' => 'Require membership?',

'require_membership_desc' => 'When set to <b>yes</b>, only registered members can submit comments to this channel\'s entries.',

'require_email' => 'Require Email?',

'require_email_desc'=>
'When set to <b>yes</b>, a member must provide a valid Email address to submit comments to this channel\'s entries.',

'moderate_comments' => 'Moderate comments?',

'moderate_comments_desc' => 'When set to <b>yes</b>, submitted comments will be put into a moderation queue, and must be approved by a super admin or other member group with moderation permissions.',

'max_characters' => 'Maximum characters allowed?',

'max_characters_desc' => 'Total number of characters allowed for submitted comments.',

'comment_time_limit' => 'Comment time limit',

'comment_time_limit_desc' => 'Number of seconds that must pass before a member can submit another comment.</em>
<em>Enter 0 for no time limit.',

'comment_expiration' => 'Comment expiration',

'comment_expiration_desc' => 'Number of days after an entry is published, before comments are no longer accepted.</em>
<em>Leave blank for no expiration.',

'text_formatting' => 'Text formatting',

'text_formatting_desc' => 'Type of formatting for comment text.',

'comment_convert_image_urls_desc' => 'When set to <b>yes</b>, <abbr title="Unified Resource Locator">URL</abbr>s to image resources will be automagically rendered as images in this channel\'s comments.',

'comment_convert_urls_emails_to_links_desc' => 'When set to <b>yes</b>, <abbr title="Unified Resource Locator">URL</abbr>s and Email address will be rendered as links in this channel\'s comments.',

'btn_save_settings' => 'Save Settings',

'convert_to_entities' => 'Convert to HTML entities',

'allow_safe_html' => 'Allow only safe HTML',

'allow_all_html' => 'Allow all HTML',

'allow_all_html_not_recommended' => 'Allow all HTML (not recommended)',

'open' => 'Open',

'closed' => 'Closed',

'no_live_look_template' => 'No template chosen',

'invalid_url_title_prefix' => 'This field cannot contain spaces.',

'clear_versioning_data' => 'Delete all existing revision data in this channel?',

'apply_comment_enabled_to_existing' => 'Update all existing entries with this setting?',

'apply_expiration_to_existing' => 'Update all existing comments with this setting?',

'channel_form_default_status_empty' => '-- Use Channel Default --',

'channel_settings_saved' => 'Channel Settings Saved',

'channel_settings_saved_desc' => 'The settings for channel <b>%s</b> have been saved.',

'channel_settings_not_saved' => 'Cannot Save Channel Settings',

'channel_settings_not_saved_desc' => 'We were unable to save this channel\'s settings, please review and fix errors below.',

/**
 * Categories
 */

'categories' => 'Categories',

'category' => 'Category',

'category_group' => 'Category Group',

'group_name' => 'Group Name',

'no_category_groups' => 'No <b>Category Groups</b> found.',

'create_category_group' => 'Create Category Group',

'edit_category_group' => 'Edit Category Group',

'category_groups_removed' => 'Category groups removed',

'category_groups_removed_desc' => '%d category groups were removed.',

'categories_removed' => 'Categories removed',

'categories_removed_desc' => '%d categories were removed.',

'no_fields' => 'No <b>Fields</b> found.',

'fields' => 'Fields',

'categories_not_found' => 'No <b>Categories</b> found.',

'create_category' => 'Create Category',

'create_category_btn' => 'Create New Category',

'edit_category' => 'Edit Category',

'files' => 'Files',

'permissions' => 'Permissions',

'category_permissions_warning' => '<b>Warning</b>: Please be very careful with the access privileges you grant.',

'category_permissions_warning2' => 'Any setting marked with %s should only be granted to people you trust implicitly.',

'edit_categories' => 'Edit Categories',

'edit_categories_desc' => 'Users in selected groups will be allowed to edit categories in this category group.</em><em>Super Administrators are <b>always</b> allowed.',

'delete_categories' => 'Delete Categories',

'delete_categories_desc' => 'Users in selected groups will be allowed to delete categories in this category group.</em><em>Super Administrators are <b>always</b> allowed.',

'cat_group_no_member_groups_found' => 'No <b>member groups</b> with permissions found',

'edit_member_groups' => 'Edit Member Groups',

'exclude_group_form' => 'Exclude group from?',

'exclude_group_form_desc' => 'Prevent this category group from being offered as choice for assignment to channels and file directories.',

'category_group_created' => 'Category Group Created',

'category_group_created_desc' => 'The category group <b>%s</b> has been created.',

'category_group_not_created' => 'Cannot Create Category Group',

'category_group_not_created_desc' => 'We were unable to create this category group, please review and fix errors below.',

'category_group_updated' => 'Category Group Updated',

'category_group_updated_desc' => 'The category group <b>%s</b> has been updated.',

'category_group_not_updated' => 'Cannot Update Category Group',

'category_group_not_updated_desc' => 'We were unable to update this category group, please review and fix errors below.',

'cat_image_none' => 'None <i>&mdash; no image</i>',

'cat_image_choose' => 'Choose from directory',

'parent_category' => 'Parent category',

'category_created' => 'Category Created',

'category_created_desc' => 'The category <b>%s</b> has been created.',

'category_not_created' => 'Cannot Create Category',

'category_not_created_desc' => 'We were unable to create this category, please review and fix errors below.',

'category_updated' => 'Category Updated',

'category_updated_desc' => 'The category <b>%s</b> has been updated.',

'category_not_updated' => 'Cannot Update Category',

'category_not_updated_desc' => 'We were unable to update this category, please review and fix errors below.',

'category_field' => 'Category Field',

'category_fields' => 'Category Fields',

'no_category_fields' => 'No <b>Category Fields</b> found.',

'create_category_field' => 'Create Category Field',

'edit_category_field' => 'Edit Category Field',

'category_fields_removed' => 'Category fields removed',

'category_fields_removed_desc' => '%d category fields were removed.',

'category_ajax_reorder_fail' => 'Attention: Failed to save category order',

'category_ajax_reorder_fail_desc' => 'We failed to save your new categories order. Refresh the page and make sure you\'re logged in.',

'duplicate_category_group_name' => 'A category group already exists with the same name.',

'label' => 'Label',

'require_field' => 'Require field?',

'cat_require_field_desc' => 'When set to <b>yes</b>,  this field will be required to submit the publish form.',

'text_input' => 'Text Input',

'textarea' => 'Textarea',

'select_dropdown' => 'Select Dropdown',

'field' => 'Field',

'category_field_created' => 'Category Field Created',

'category_field_created_desc' => 'The category field <b>%s</b> has been created.',

'category_field_not_created' => 'Cannot Create Category Field',

'category_field_not_created_desc' => 'We were unable to create this category field, please review and fix errors below.',

'category_field_updated' => 'Category Field Updated',

'category_field_updated_desc' => 'The category field <b>%s</b> has been updated.',

'category_field_not_updated' => 'Cannot Update Category Field',

'category_field_not_updated_desc' => 'We were unable to update this category field, please review and fix errors below.',

'duplicate_field_name' => 'The field name you chose is already taken.',

'cat_field_ajax_reorder_fail' => 'Attention: Failed to save category field order',

'cat_field_ajax_reorder_fail_desc' => 'We failed to save your new category fields order. Refresh the page and make sure you\'re logged in.',

/**
 * Status Groups
 */

'status_groups' => 'Status Groups',

'status_group' => 'Status Group',

'status_groups_removed' => 'Status groups removed',

'status_groups_removed_desc' => '%d status groups were removed.',

'create_status_group' => 'Create Status Group',

'edit_status_group' => 'Edit Status Group',

'status_group_created' => 'Status Group Created',

'status_group_created_desc' => 'The status group <b>%s</b> has been created.',

'status_group_not_created' => 'Cannot Create Status Group',

'status_group_not_created_desc' => 'We were unable to create this status group, please review and fix errors below.',

'status_group_updated' => 'Status Group Updated',

'status_group_updated_desc' => 'The status group <b>%s</b> has been updated.',

'status_group_not_updated' => 'Cannot Update Status Group',

'status_group_not_updated_desc' => 'We were unable to update this status group, please review and fix errors below.',

'status_name' => 'Status Name',

'statuses' => 'Statuses',

'status' => 'Status',

'status_ajax_reorder_fail' => 'Attention: Failed to save status order',

'status_ajax_reorder_fail_desc' => 'We failed to save your new statuses order. Refresh the page and make sure you\'re logged in.',

'statuses_removed' => 'Statuses removed',

'statuses_removed_desc' => '%d statuses were removed.',

'create_status' => 'Create Status',

'edit_status' => 'Edit Status',

'status_name_desc' => 'Descriptive name of this status.',

'highlight_color' => 'Highlight color',

'highlight_color_desc' => 'Text color for this status. Accepts HEX codes.',

'status_access' => 'Status access',

'status_access_desc' => 'Users in selected groups will be allowed to access this status.</em><em>Super Administrators are <b>always</b> allowed.',

'status_created' => 'Status Created',

'status_created_desc' => 'The status <b>%s</b> has been created.',

'status_not_created' => 'Cannot Create Status',

'status_not_created_desc' => 'We were unable to create this status, please review and fix errors below.',

'status_updated' => 'Status Updated',

'status_updated_desc' => 'The status <b>%s</b> has been updated.',

'status_not_updated' => 'Cannot Update Status',

'status_not_updated_desc' => 'We were unable to update this status, please review and fix errors below.',

'duplicate_status_group_name' => 'A status group already exists with the same name.',

'duplicate_status_name' => 'A status already exists with the same name.',

'invalid_hex_code' => 'This field must contain a valid hex color code.',

''=>''
);

// EOF

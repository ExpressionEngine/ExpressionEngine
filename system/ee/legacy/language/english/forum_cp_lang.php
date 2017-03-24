<?php

$lang = array(

//----------------------------------------
// Required for MODULES page
//----------------------------------------

'forum_module_name' => 'Discussion Forum',
'forum_module_description' => 'Discussion Forum Module',

'forum_listing' => 'Forum Listing',
'forum_manager' => 'Forum Manager',

'new_category' => 'New Category',

// Sidebar
'templates' => 'Templates',
'forum_board' => 'Forum Board',
'forum_alias' => 'Forum Alias',
'forum_aliases' => 'Forum Aliases',
'create_new_board' => 'Create new forum board',

// List / Index
'create_new_category' => 'Create new category',
'categories' => 'Categories',

'new_forum' => 'New forum',
'create_new_forum' => 'Create new forum',

'forums_ajax_reorder_fail' => 'Attention: Failed to save forum order',
'forums_ajax_reorder_fail_desc' => 'We failed to save your new forum order. Refresh the page and make sure you\'re logged in.',

// "Common"
'recipients' => 'recipients',

// Create / Edit Forum Alias Form

'create_forum_alias' => 'Create Forum Alias',
'enable_alias' => 'Enable alias',
'alias_url' => 'Alias <abbr title="Unified Resource Locator">URL</abbr>',
'alias_url_desc' => '<abbr title="Unified Resource Locator">URL</abbr> location to the alias.',
'alias_url_segment' => 'Alias <abbr title="Unified Resource Locator">URL</abbr> segment',
'alias_url_segment_desc' => 'Word that triggers alias display. <b>Cannot</b> be the same as a template or template group.',
'forum_board_desc' => 'Choose which forum board you want to create an alias for.',

'btn_save_alias' => 'Save Alias',

'create_forum_alias_success' => 'Forum Alias Created',
'create_forum_alias_success_desc' => 'The forum alias <b>%s</b> has been created.',

'create_forum_alias_error' => 'Cannot Create Forum Alias',
'create_forum_alias_error_desc' => 'We were unable to create this forum alias, please review and fix errors below.',

'edit_forum_alias_success' => 'Forum Alias Updated',
'edit_forum_alias_success_desc' => 'The forum alias <b>%s</b> has been updated.',

'edit_forum_alias_error' => 'Cannot Update Forum Alias',
'edit_forum_alias_error_desc' => 'We were unable to update this forum alias, please review and fix errors below.',

'forum_alias_removed' => 'Forum Alias Removed',
'forum_alias_removed_desc' => 'The forum alias <b>%s</b> was removed.',

// Create / Edit Forum Board Form
'create_forum_board' => 'Create Forum Board',
'edit_forum_board' => 'Edit %s',
'board' => 'Board',
'forums' => 'Forums',
'permissions' => 'Permissions',

'enable' => 'Enable',
'disable' => 'Disable',
'input' => 'Input',
'output' => 'Output',

'enable_board' => 'Enable board',
'enable_board_desc' => 'When set to <b>enable</b>, this forum board will be accessible to member groups with proper permissions.',

'name' => 'Name',
'name_desc' => 'Full descriptive name of this board.',

'short_name' => 'Short name',
'short_name_desc' => 'Short name for this board.<br><i>No spaces. Underscores and dashes are allowed.</i>',

'forum_directory' => 'Forum directory',
'forum_directory_desc' => '<abbr title="Unified Resource Locator">URL</abbr> location to the forum.',

'site' => 'Site',

'forum_url_segment' => 'Forum <abbr title="Unified Resource Locator">URL</abbr> segment',
'forum_url_segment_desc' => 'Word that triggers forum display. <b>Cannot</b> be the same as a template or template group.',

'default_theme' => 'Default theme',

'php_parsing' => '<abbr title="PHP: Hypertext Preprocessor">PHP</abbr> Parsing',

'php_in_templates_warning' => '<b>Warning</b>: Allowing PHP in templates has security implications.',
'php_in_templates_warning2' => 'Any setting marked with %s should be used with caution.',

'allow_php' => 'Allow PHP?',
'allow_php_desc' => 'Allows the use of standard PHP within forum templates.',

'php_parsing_stage' => 'PHP parsing stage',
'php_parsing_stage_desc' => 'When set to <b>output</b>, PHP will be parsed after the template.',

'attachment_settings' => 'Attachment Settings',

'attachments_per_post' => 'Attachments per post',
'attachments_per_post_desc' => 'Maximum attachments allowed per post.',

'upload_directory' => 'Upload directory',
'upload_directory_desc' => 'Full path location of this <mark>upload</mark> directory.',

'allowed_file_types' => 'Allowed file types?',

'images_only' => 'Images only',
'all_files' => 'All file types',

'file_size' => 'File size',
'file_size_desc' => 'Maximum file size in megabytes.',

'image_width' => 'Image width',
'image_width_desc' => 'Maximum image width in pixels.',

'image_height' => 'Image height',
'image_height_desc' => 'Maximum image height in pixels.',

'enable_thumbnail_creation' => 'Enable thumbnail creation?',
'enable_thumbnail_creation_desc' => 'When set to <b>enable</b>, clickable thumbnails will be shown in posts.',

'thumbnail_width' => 'Thumbnail width',
'thumbnail_width_desc' => 'Maximum thumbnail width in pixels.',

'thumbnail_height' => 'Thumbnail height',
'thumbnail_height_desc' => 'Maximum thumbnail height in pixels.',

'topics_per_page' => 'Topics per page',
'topics_per_page_desc' => 'Maximum number of topics that will be shown per page.',

'posts_per_page' => 'Posts per page',
'posts_per_page_desc' => 'Maximum number of posts that will be shown per page.',

'topic_ordering' => 'Topic ordering',
'topic_ordering_desc' => 'Order of topics in forum listing.',

'most_recent_post' => 'By most recent post',
'most_recent_first' => 'Most recent first',
'most_recent_last' => 'Most recent last',

'post_ordering' => 'Post ordering',
'post_ordering_desc' => 'Order of posts in topic listing.',

'hot_topics' => 'Hot topics',
'hot_topics_desc' => 'Number of posts required to mark a topic as hot.',

'allowed_characters' => 'Allowed characters',
'allowed_characters_desc' => 'Maximum number of characters allowed within a single post.',

'posting_throttle' => 'Posting throttle',
'posting_throttle_desc' => 'Number of seconds that must pass before a member can post a new post or topic.',

'show_editing_dates' => 'Show editing dates?',
'show_editing_dates_desc' => 'When set to <b>yes</b>, the date and time a post was edited will be shown in the post.',

'notification_settings' => 'Notification Settings',

'topic_notifications' => 'Topic notifications',
'topic_notifications_desc' => 'When set to <b>enable</b>, all recipients listed will receive e-mail notification when a <b>new</b> topic is posted.</em><em>Separate multiple e-mails with a <mark>comma (,)</mark>.',

'reply_notification' => 'Reply notification',
'reply_notification_desc' => 'When set to <b>enable</b>, all recipients listed will receive e-mail notification when a <b>new</b> reply is made.</em><em>Separate multiple e-mails with a <mark>comma (,)</mark>.',

'text_and_html_formatting' => 'Text and HTML Formatting',

'text_formatting' => 'Text formatting',
'text_formatting_desc' => 'Type of formatting for comment text.',

'html_formatting' => '<abbr title="Hyper-Text Markup Language">HTML</abbr> formatting',

'html_all' => 'Allow all HTML',
'html_safe' => 'Allow only safe HTML',
'html_none' => 'Convert to HTML entities',

'autolink_urls' => 'Render <abbr title="Unified Resource Locator">URL</abbr>s and e-mail addresses as links?',
'autolink_urls_desc' => "When set to <b>yes</b>, <abbr title=\"Unified Resource Locator\">URL</abbr>s and e-mail address will be rendered as links in this forum's posts.",

'allow_image_hotlinking' => 'Allow image hot-linking?',
'allow_image_hotlinking_desc' => 'When set to <b>yes</b>, users will be allowed to hot-link an image in a forum post.',

'rss_settings' => '<abbr title="Really Simple Syndication">RSS</abbr> Settings',

'enable_rss' => 'Enable <abbr title="Really Simple Syndication">RSS</abbr>?',
'enable_rss_desc' => 'When set to <b>enable</b>, <abbr title="Really Simple Syndication">RSS</abbr> will be available for forums.',

'enable_http_auth_for_rss' => 'Enable <abbr title="Hyper Text Transfer Protocol">HTTP</abbr> authentication for <abbr title="Really Simple Syndication">RSS</abbr>?',
'enable_http_auth_for_rss_desc' => 'When set to <b>enable</b>, users will need to enter authentication to access <abbr title="Really Simple Syndication">RSS</abbr> for forums.',

'permissions_warning' => '<b>Warning</b>: Please be very careful with the access privileges you grant.',

'enable_default_permissions' => 'Enable Default Permissions?',
'enable_default_permissions_desc' => 'When set to <b>enable</b>, these permissions will be the default permissions for all created forums.',

'view_forums' => 'View forums',
'view_forums_desc' => 'Allow the following member groups to view forums.</em><em>Super Administrators are <b>always</b> allowed.',

'view_hidden_forums' => 'View hidden forums',
'view_hidden_forums_desc' => 'Allow the following member groups to view hidden forums.</em><em>Super Administrators are <b>always</b> allowed.',

'view_posts' => 'View posts',
'view_posts_desc' => 'Allow the following member groups to view posts.</em><em>Super Administrators are <b>always</b> allowed.',

'start_topics' => 'Start topics',
'start_topics_desc' => 'Allow the following member groups to start new topics.</em><em>Super Administrators are <b>always</b> allowed.',

'reply_to_topics' => 'Reply to topics',
'reply_to_topics_desc' => 'Allow the following member groups to reply to topics.</em><em>Super Administrators are <b>always</b> allowed.',

'upload' => 'Upload',
'upload_desc' => 'Allow the following member groups to use the upload feature.</em><em>Super Administrators are <b>always</b> allowed.',

'report' => 'Report',
'report_desc' => 'Allow the following member groups to use the report feature.</em><em>Super Administrators are <b>always</b> allowed.',

'search' => 'Search',
'search_desc' => 'Allow the following member groups to use the search feature.</em><em>Super Administrators are <b>always</b> allowed.',

'btn_save_board' => 'Save Board',

'invalid_upload_path' => 'The server path to your image upload folder does not appear to be valid.',
'unwritable_upload_path' => 'Your image upload folder is not writable.  Please make sure the file permissions are set to 777.',
'forum_trigger_unavailable' => 'The forum trigger you submitted is currently being used as the name of a template group or template so it is not available',

'create_forum_board_success' => 'Forum Board Created',
'create_forum_board_success_desc' => 'The forum board <b>%s</b> has been created.',

'create_forum_board_error' => 'Cannot Create Forum Board',
'create_forum_board_error_desc' => 'We were unable to create this forum board, please review and fix errors below.',

'edit_forum_board_success' => 'Forum Board Updated',
'edit_forum_board_success_desc' => 'The forum board <b>%s</b> has been updated.',

'edit_forum_board_error' => 'Cannot Update Forum Board',
'edit_forum_board_error_desc' => 'We were unable to update this forum board, please review and fix errors below.',

// Create/Edit Category Form

'create_category' => 'Create Category',
'edit_category' => 'Edit Category',

'description_desc' => 'Brief description of this category.',

'status_desc' => 'Status assigned to this category.',
'live' => 'Live',
'hidden' => 'Hidden',
'read_only' => 'Read Only',

'btn_save_category' => 'Save Category',

'create_category_success' => 'Category Created',
'create_category_success_desc' => 'The category <b>%s</b> has been created.',

'create_category_error' => 'Cannot Create Category',
'create_category_error_desc' => 'We were unable to create this category, please review and fix errors below.',

'edit_category_success' => 'Category Updated',
'edit_category_success_desc' => 'The category <b>%s</b> has been updated.',

'edit_category_error' => 'Cannot Update Category',
'edit_category_error_desc' => 'We were unable to update this category, please review and fix errors below.',

// Create/Edit Forum Form

'create_forum' => 'Create Forum',
'edit_forum' => 'Edit Forum',

'topic_and_post_settings' => 'Topic and Post Settings',

'btn_save_forum' => 'Save forum',

'create_forum_success' => 'Forum Created',
'create_forum_success_desc' => 'The forum <b>%s</b> has been created.',

'create_forum_error' => 'Cannot Create Forum',
'create_forum_error_desc' => 'We were unable to create this forum, please review and fix errors below.',

'edit_forum_success' => 'Forum Updated',
'edit_forum_success_desc' => 'The forum <b>%s</b> has been updated.',

'edit_forum_error' => 'Cannot Update Forum',
'edit_forum_error_desc' => 'We were unable to update this forum, please review and fix errors below.',

'forum_board_removed' => 'Forums Removed',
'forum_board_removed_desc' => 'The following forums were removed',

// Category Permissions

'category_permissions' => '%s Permissions',
'btn_save_permissions' => 'Save Permissions',

'view_category' => 'View category',
'view_category_desc' => 'Allow the following member groups to view this category.</em> <em>Super Administrators are <b>always</b> allowed.',

'view_hidden_category' => 'View hidden category',
'view_hidden_category_desc' => 'Allow the following member groups to view this category.</em> <em>Super Administrators are <b>always</b> allowed.',

'edit_category_settings_success' => 'Category Permissions Updated',
'edit_category_settings_success_desc' => 'The permissions for category <b>%s</b> have been updated.',

// Forum Permissions

'forum_permissions' => '%s Permissions',

'view_forum' => 'View forum',
'view_forum_desc' => 'Allow the following member groups to view this forum.</em><em>Super Administrators are <b>always</b> allowed.',

'view_hidden_forum' => 'View hidden forums',
'view_hidden_forum_desc' => 'Allow the following member groups to view this forum when hidden.</em><em>Super Administrators are <b>always</b> allowed.',

'edit_forum_settings_success' => 'Forum Permissions Updated',
'edit_forum_settings_success_desc' => 'The permissions for forum <b>%s</b> have been updated.',

// Member Ranks

'no_ranks' => 'No ranks available',
'create_new_rank' => 'Create new rank',

'member_ranks' => 'Member Ranks',
'create_member_rank' => 'Create Member Rank',
'edit_member_rank' => 'Edit Member Rank',

'posts' => 'Posts',
'stars' => 'Stars',

'rank_title' => 'Title',
'rank_title_desc' => 'Full descriptive name of this rank.',

'posts_desc' => 'Minimum number of posts a user must have to reach this rank.',
'stars_desc' => 'Number of stars to show with this rank.',

'btn_save_rank' => 'Save Rank',

'create_rank_success' => 'Rank Created',
'create_rank_success_desc' => 'The rank <b>%s</b> has been created.',

'create_rank_error' => 'Cannot Create Rank',
'create_rank_error_desc' => 'We were unable to create this rank, please review and fix errors below.',

'edit_rank_success' => 'Rank Updated',
'edit_rank_success_desc' => 'The rank <b>%s</b> has been updated.',

'edit_rank_error' => 'Cannot Update Rank',
'edit_rank_error_desc' => 'We were unable to update this rank, please review and fix errors below.',

'ranks_removed' => 'Member Ranks Removed',
'ranks_removed_desc' => 'The following ranks were removed',

// Administrators

'administrators' => 'Administrators',
'administrators_desc' => 'Have access to all administration tools for all forums in this board.',

'forum_admins' => 'Forum Administrators',

'create_new_admin' => 'Create new admin',

'group' => 'group',
'individual' => 'individual',

'create_administrator' => 'Create Administrator',

'administrator_type' => 'Administrator type',
'administrator_type_desc' => 'Select the type of administrator you want to add.',

'admin_type_member_group' => 'Member Group <i>&mdash; All members of chosen group</i>',
'admin_type_individual' => 'Individual <i>&mdash; username, <b>not</b> screenname</i>',

'btn_save_administrator' => 'Save administrator',

'create_administrator_success' => 'Administrator Created',
'create_administrator_success_desc' => 'The administrator <b>%s</b> has been created.',

'create_administrator_error' => 'Cannot Create Administrator',
'create_administrator_error_desc' => 'We were unable to create this administrator, please review and fix errors below.',

'invalid_member_group' => 'The member group you submitted does not appear to be valid',
'invalid_username' => 'The username you submitted does not appear to be valid',

'admins_removed' => 'Administrators Removed',
'admins_removed_desc' => 'The following administrators were removed',

// Moderators

'moderators' => 'Moderators',
'moderators_desc' => 'Have access to assigned tools for forums they are assigned to.',

'create_moderator' => 'Create Moderator',
'create_moderator_in' => 'Create Moderator in %s',

'edit_moderator' => 'Edit Moderator',
'edit_moderator_in' => 'Edit Moderator in %s',

'remove_moderator' => 'remove moderator',

'moderator_type' => 'Moderator type',
'moderator_type_desc' => 'Select the type of moderator you want to add.',

'moderator_type_member_group' => 'Member Group <i>&mdash; All members of chosen group</i>',
'moderator_type_individual' => 'Individual <i>&mdash; username, <b>not</b> screenname</i>',

'permissions_desc' => 'Moderators of this forum may take the following actions.',

'mod_can_edit' => 'Edit',
'mod_can_move' => 'Move',
'mod_can_delete' => 'Delete',
'mod_can_split' => 'Split',
'mod_can_merge' => 'Merge',
'mod_can_change_status' => 'Change status',
'mod_can_announce' => 'Create announcements',
'mod_can_view_ip' => 'View <abbr title="Internet Protocol">IP</abbr> addresses',

'btn_save_moderator' => 'Save moderator',

'create_moderator_success' => 'Moderator Created',
'create_moderator_success_desc' => 'The moderator <b>%s</b> has been created.',

'create_moderator_error' => 'Cannot Create Moderator',
'create_moderator_error_desc' => 'We were unable to create this moderator, please review and fix errors below.',

'edit_moderator_success' => 'Moderator Updated',
'edit_moderator_success_desc' => 'The moderator <b>%s</b> has been updated.',

'edit_moderator_error' => 'Cannot Update Moderator',
'edit_moderator_error_desc' => 'We were unable to update this moderator, please review and fix errors below.',

'moderator_removed' => 'Moderator Removed',
'moderator_removed_desc' => 'The moderator <b>%s</b> was removed.',

// Forum Publish Tab
'only_forum_topic_id' => 'Do not specify a forum Title or Body when setting a Forum Topic ID.',
'no_forum_topic_id' => 'There is no forum topic with that ID.',
'no_forum_permissions' => 'You do not have permissions to post to this forum.',

''=>''
);

// EOF

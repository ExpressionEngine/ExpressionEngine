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
'member_ranks' => 'Member Ranks',
'forum_board' => 'Forum Board',

// List / Index
'forum_listing' => 'Forum listing',
'no_categories' => 'No categories available',
'create_new_category' => 'Create new category',

'no_forums' => 'No forums available',
'new_forum' => 'New forum',
'create_new_forum' => 'Create new forum',

// "Common"
'recipients' => 'recipients',

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
'site_desc' => '<abbr title="Multiple Site Manager">MSM</abbr> site this forum board should appear under.',

'forum_url_segment' => 'Forum <abbr title="Unified Resource Locator">URL</abbr> segment',
'forum_url_segment_desc' => 'Word that triggers forum display. <b>Cannot</b> be the same as a template or template group.',

'default_theme' => 'Default theme',
'default_theme_desc' => '',

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
'upload_directory_desc' => '<abbr title="Uniform Resource Location">URL</abbr> location of this <mark>upload</mark> directory.',

'allowed_file_types' => 'Allowed file types?',
'allowed_file_types_desc' => '',

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

'most_recent_first' => 'Most recent first',
'most_recent_last' => 'Most recent last',

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

'none' => 'None',
'auto_br' => 'Auto &lt;br&gt;',
'xhtml' => 'XHTML',

'html_formatting' => '<abbr title="Hyper-Text Markup Language">HTML</abbr> formatting',
'html_formatting_desc' => 'Level of <abbr title="Hyper-Text Markup Language">HTML</abbr> allowed.',

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
'reply_to_topics_desc' => 'Allow the following member groups to replay to topics.</em><em>Super Administrators are <b>always</b> allowed.',

'upload' => 'Upload',
'upload_desc' => 'Allow the following member groups to use the upload feature.</em><em>Super Administrators are <b>always</b> allowed.',

'report' => 'Report',
'report_desc' => 'Allow the following member groups to use the report feature.</em><em>Super Administrators are <b>always</b> allowed.',

'search' => 'Search',
'search_desc' => 'Allow the following member groups to use the search feature.</em><em>Super Administrators are <b>always</b> allowed.',

'btn_save_board' => 'Save Board',

'invalid_upload_path' => 'The server path to your image upload folder does not appear to be valid.',
'unwritable_upload_path' => 'Your image upload folder is not writable.  Please make sure the file permissions are set to 777.',
'forum_trigger_unavailable' => 'The forum trigger you submitted is currently being used as the name of a template group so it is not available',

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

'reply_notifications' => 'Reply notification',
'reply_notifications_desc' => 'When set to <b>enable</b>, all recipients listed will receive e-mail notification when a <b>new</b> reply is made.</em><em>Separate multiple e-mails with a <mark>comma (,)</mark>.',

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

'posts_desc' => 'Minimum number of pots a user must have to reach this rank.',
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

'ranks_removed_desc' => 'The following ranks were removed',

// Administrators

'administrators' => 'Administrators',
'administrators_desc' => 'Have access to all administration tools for all forums in this board.',

'no_admins' => 'No administrators available',
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

/* 2.x Keys
//----------------------------------------

'update' => 'Update',

'update_and_return' => 'Update and Return',

'report_form' => 'Report Form',

'report_page' => 'Report Page',

'ignore_member_confirmation' => 'Ignore Member Confirmation',

'ignore_member_page' => 'Ignore Member Page',

'merge_interface' => 'Merge Interface',

'merge_page' => 'Merge Page',

'atom_page' => 'Atom Page',

'javascript_forum_array' => 'JavaScript Forum Array',

'javascript_set_show_hide' => 'JavaScript Set Show/Hide',

'javascript_show_hide_forums' => 'JavaScript Show/Hide Forums',

'forum_prefs_rss' => 'RSS Preferences',

'rss_page' => 'RSS Page',

'split_data' => 'Split Data',

'split_page' => 'Split Page',

'split_thread_rows' => 'Split Thread Rows',

'forum_jump' => 'Forum Jump',

'quoted_author' => 'Quoted Author',

'thread_review' => 'Thread Review',

'thread_review_rows' => 'Thread Review Rows',

'pref_enable_rss' => 'Enable RSS Feed for this forum',

'pref_use_http_auth' => 'Enable HTTP Authentication for RSS Feeds',

'mod_can_split' => 'Can Split Posts',

'mod_can_merge' => 'Can Merge Posts',

'forum_manager_menu' => 'Forum Manager Menu',

'user_manager_menu' => 'User Manager Menu',

'forum_home' => 'Forum Home',

'forum_mgr_menu' => 'Forum Management Menu',

'user_mgr_menu' => 'User Management Menu',

'forum_prefs' => 'Default Preferences',

'forum_manager' => 'Forum Manager',

'forum_management' => 'Forum Management',

'forum_moderators' => 'Moderators',

'forum_ranks' => 'Member Ranks',

'forum_extras' => 'Extras',

'forum_launch' => 'Visit Forums',

'forum_templates' => 'Templates',

'edit_template' => 'Edit Template',

'forum_add_category' => 'Add New Category',

'forum_name' => 'Forum Name',

'forum_description' => 'Forum Description',

'forum_cat_name' => 'Category Name',

'forum_cat_description' => 'Category Description',

'forum_parent' => 'Forum belongs to which category?',

'forum_add_new' => 'Add a new forum to this category',

'forum_cat_add_new' => 'Add a New Category',

'forum_status' => 'Forum Status',

'forum_status_short' => 'Status:',

'forum_cat_status' => 'Category Status',

'forum_open' => 'Live',

'forum_closed' => 'Hidden',

'forum_archived' => 'Read Only',

'edit' => 'Edit',

'remove' => 'Remove',

'forum_category' => 'Category',

'forum_permissions' => 'Permissions',

'permissions_for_forum' => 'Permissions For Forum:',

'permissions_for_cat' => 'Permissions For Category:',

'forum_edit' => 'Edit Forum Preferences',

'forum_edit_category' => 'Edit Category Preferences',

'forum_create' => 'Create New Forum',

'forum_create_category' => 'Create New Category',

'forum_preferences' => 'Preferences',

'forum_delete' => 'Delete Forum',

'forum_delete_confirm' => 'Delete Forum Confirmation',

'forum_delete_cat_confirm' => 'Delete Category Confirmation',

'forum_delete_msg' => 'Are you sure you want to delete the following forum?',

'forum_delete_cat_msg' => 'Are you sure you want to delete the following category?',

'forum_delete_warning' => 'All topics and posts in this forum will be permanently deleted!',

'forum_delete_cat_warning' => 'All forums and posts contained within this category will be permanently deleted!',

'forum_deleted' => 'Forum has been deleted',

'forum_resync' => 'Re-synchronize Forums',

'forum_resynched' => 'Forum display order has been re-synchronized',

'forum_stats' => 'Statistic',

'forum_value' => 'Value',

'forum_total_topics' => 'Total Topics',

'forum_total_posts' => 'Total Posts',

'forum_total_topics_perday' => 'Topics Per Day',

'forum_total_post_perday' => 'Posts Per Day',

'forum_per_day' => '%x per day',

'forum_total_forums' => 'Total Forums',

'forum_total_moderators' => 'Total Moderators',

'forum_announcements' => 'Forum Announcements',

'forum_prefs_updated' => 'Forum preferences have been updated',

'forum_cat_prefs_updated' => 'Category preferences have been updated',

'forum_missing_name' => 'You must submit a name for your forum',

'forum_new_forum_added' => 'New forum has been created',

'forum_new_cat_added' => 'New forum category has been created',

'forum_rank_title' => 'Rank Title',

'forum_rank_min_posts' => 'Min Posts',

'forum_rank_stars' => 'Stars',

'forum_add_rank' => 'Add a New Member Rank',

'forum_missing_ranks' => 'You left some form fields empty',

'forum_rank_added' => 'New Member Rank Added',

'forum_rank_updated' => 'Member Rank Updated',

'forum_delete_rank_confirm' => 'Delete Member Rank Confirmation',

'forum_delete_rank_msg' => 'Are you sure you want to delete the following member rank?',

'forum_rank_deleted' => 'Member Rank has been deleted',

'forum_global_permissions' => 'Default Permissions',

'forum_global_permissions_inst' => 'This grid lets you set the default permissions that are applied to all new forums you create.',

'forum_use_deft_permissions' => 'Enable Default Permissions',

'forum_member_group' => 'Member Group',

'forum_can_view' => 'Can View Forum',

'forum_can_view_hidden' => 'Can View Hidden Forum',

'forum_cat_can_view' => 'Can View Category',

'forum_cat_can_view_hidden' => 'Can View Hidden Category',

'forum_can_view_topics' => 'Can View Posts',

'forum_can_post' => 'Can Post',

'forum_can_report' => 'Can Report',

'forum_can_upload' => 'Can Upload',

'forum_can_search' => 'Can Search',

'forum_cur_name' => 'Forum:',

'forum_cur_cat_name' => 'Category:',

'forum_permissions_updated' => 'Permissions have been updated',

'forum_deft_permissions_updated' => 'Default permission settings have been updated',

'forum_moderator_add' => 'Add Moderator',

'forum_new_moderator' => 'New Moderator',

'forum_edit_moderator' => 'Edit Moderator',

'forum_moderator_name' => 'Moderator Name and Type',

'forum_moderator_inst' => 'A moderator can be an individual member or a member group.',

'forum_mod_type' => 'Moderator Type',

'forum_type_group' => 'Member Group',

'forum_type_member' => 'Individual Member',

'forum_current' => 'Current Forum:',

'forum_member_group' => 'Member Group',

'forum_find_user' => 'Find Username',

'forum_permission' => 'Permission',

'forum_value' => 'Value',

'mod_can_edit' => 'Can Edit Posts',

'mod_can_move' => 'Can Move Posts',

'mod_can_delete' => 'Can Delete Posts',

'mod_can_change_status' => 'Can Change Post Status',

'mod_can_announce' => 'Can Post Announcements',

'mod_can_view_ip' => 'Can View IP Addresses',

'mod_can_ban' => 'Can Ban Users',

'forum_moderator_added' => 'New Moderator Added',

'forum_moderator_updated' => 'Moderator Updated',

'forum_user_lookup' => 'Member Search',

'forum_mod_name_inst' => 'Note: Submit username - NOT screen name',

'forum_use_lookup_inst' => 'Submit a partial or complete name',

'forum_search_by_user' => 'Search by Username',

'forum_search_by_screen' => 'Search by Screen Name',

'forum_close_window' => 'Close Window',

'forum_no_results' => 'Your query did not produce any results',

'forum_screen_name' => 'Screen Name',

'forum_username' => 'Username',

'forum_moderator_search_res' => 'Search Results',

'forum_toomany_results' => 'Only the first 100 results were shown. Please edit your search term for fewer results.',

'forum_select_user' => 'Select',

'forum_username_error' => 'The username you submitted does not appear to be valid',

'forum_user_identifier_required' => 'A username or member group is required',

'forum_no_mods' => 'No Moderators',

'forum_remove_moderator_confirm' => 'Moderator Remove Confirm',

'forum_remove_moderator_msg' => 'Are you sure you want to remove the following moderator?',

'in_forum' => 'in forum:',

'invalid_mod_id' => 'Invalid Moderator ID',

'moderator_removed' => 'Moderator has been removed',

'forum_prefs_general' => 'General Settings',

'pref_forum_enabled' => 'Enable Forum',

'pref_forum_enabled_info' => 'When the forum is NOT enabled only Super Admins can view it.',

'forum_prefs_php' => 'PHP Parsing Preferences',

'pref_allow_php' => 'Allow PHP in Forum Templates?',

'forum_pref_php' => 'PHP Preferences',

'pref_php_stage' => 'PHP Parsing Stage',

'input' => 'Input',

'output' => 'Output',

'forum_prefs_themes' => 'Theme Preferences',

'pref_forum_name' => 'Forum Name',

'forum_prefs_topics' => 'Topics and Post Preferences',

'forum_prefs_image' => 'Attachment Preferences',

'pref_attach_types' => 'Allowed File Types',

'images_only' => 'Images Only',

'all_files' => 'All File Types',

'pref_forum_url' => 'Forum URL',

'pref_theme_url' => 'URL to Forum Themes Folder',

'pref_theme_path' => 'Server Path to Themes Folder',

'pref_default_theme' => 'Default Forum Theme',

'pref_topics_perpage' => 'Max Topics Per Page',

'pref_posts_perpage' => 'Max Post Per Page',

'pref_topic_order' => 'Topic Display Order',

'pref_post_order' => 'Post Display Order',

'pref_hot_topic' => 'Number of posts to indicate hot topic',

'pref_max_post_chars' => 'Maximum allowed characters per post',

'pref_sticky_prefix' => 'Prefix for sticky topics',

'pref_moved_prefix' => 'Prefix for moved topics',

'pref_upload_url' => 'Upload Directory URL',

'pref_upload_path' => 'Server Path to Upload Directory',

'pref_max_attach_perpost' => 'Maximum Number of Attachments Per Post',

'pref_max_attach_size' => 'Maximum Size of Attachments (in Kilobytes)',

'pref_use_img_thumbs' => 'Use Thumbnails of Uploaded Images in Posts',

'pref_image_protocol' => 'Image Resize Protocol',

'pref_image_lib_path' => 'Server Path to Image Manipulation Library',

'pref_thumb_width' => 'Thumbnail Max Width',

'pref_thumb_height' => 'Thumbnail Max Height',

'pref_max_width' => 'Maximum Allowed Image Width',

'pref_max_height' => 'Maximum Allowed Image Height',

'ascending' => 'Most Recent Last',

'descending' => 'Most Recent First',

'most_recent_topic' => 'By Most Recent Post',

'invalid_theme_path' => 'Error:  Invalid Theme Path',

'forum_new_install_msg' => 'To get started with the forum module, you must first create a forum board.',

'path_message' => 'Must be a server path, not a URL',

'path_lib_message' => 'If you choose Image Magick or NetPBM you must specify the server path to the library',

'will_show_in_pop' => 'Thumbnails will be clickable to show the full-size image',

'pref_forum_trigger' => 'Forum Triggering Word',

'pref_forum_trigger_notes' => 'When this word is encountered in your URL it will display your forum.  The word you choose cannot be the name of an existing template group.',

'config_not_writable' => 'Your config.php file does not appear to be writable.  Please set the file permissions to 666',

'forum_empty_fields' => 'You left the following fields empty:',

'invalid_theme_path' => 'The server path to your themes folder does not appear to be valid.',

'illegal_characters' => 'The forum trigger word you submitted may only contain alpha-numeric characters, underscores, and dashes',

'illegal_characters_shortname' => 'The forum board short name you submitted may only contain alpha-numeric characters, underscores, and dashes',

'pref_post_timelock' => 'Post re-submission Time Interval (in seconds)',

'pref_post_timelock_more' => 'The number of seconds that must pass before a user can submit another post. Leave blank or set to zero for no limit.',

'pref_display_edit_date' => 'Display Edit Dates',

'forum_prefs_formatting' => 'Text and HTML Formatting',

'pref_text_formatting' => 'Text Formatting in Posts',

'auto_br' => 'Auto &lt;br /&gt;',

'xhtml' => 'XHTML',

'pref_html_formatting' => 'HTML Formatting in Posts',

'safe' => 'Allow only safe HTML',

'all' => 'Allow all HTML (not recommended)',

'none' => 'Convert HTML into character entities',

'pref_allow_img_urls' => 'Allow Image Hot linking?',

'pref_auto_link_urls' => 'Auto-convert URLs and email addresses into links?',

'forum_prefs_notification' => 'Admin Notification Preferences',

'pref_notify_emails' => 'Email Address of Reply Notification Recipient(s)',

'pref_notify_emails_topics' => 'Email Address of Topic Notification Recipient(s)',

'pref_notify_emails_topics_more' => 'If you would like someone notified when there are new TOPICS in this forum enter their email address. Separate multiple email addresses with a comma.',

'pref_notify_emails_forums' => 'If you would like someone notified when there are new REPLIES in this forum enter their email address. Separate multiple email addresses with a comma.',

'pref_notify_emails_all' => 'If you would like someone notified when there are new REPLIES in ANY forums enter their email address. Separate multiple email addresses with a comma.',

'pref_notify_emails_topics_all' => 'If you would like someone notified when there are new TOPICS in ANY forums enter their email address. Separate multiple email addresses with a comma.',

'pref_notify_moderators' => 'Notify Moderators of New Posts?',

'show_preferences' => 'Show Preferences',

'forum_user_manager' => 'User Management',

'forum_admins' => 'Administrators',

'forum_admin_inst' => 'An administrator can be an individual member or a member group.',

'forum_admin_type' => 'Administrator Type',

'forum_is_admin' => 'Is Administrator',

'forum_no_admins' => 'There are no forum administrators',

'forum_create_admin' => 'Create a New Administrator',

'forum_new_admin' => 'Create New Administrator',

'forum_edit_admin' => 'Edit Administrator',

'forum_admin_added' => 'New Admin Added',

'forum_admin_type' => 'Admin Type',

'forum_individual' => 'Individual',

'forum_group' => 'Group',

'admin_removed' => 'Admin Removed',

'invalid_admin_id' => 'Invalid Administrator ID',

'forum_remove_admin_confirm' => 'Remove Admin Confirm',

'forum_remove_admin_msg' => 'Are you sure you want to remove the following Admin?',

'forum_user_mgr_info' => 'Any member of a group with administrator status ',

'unable_to_find_templates' => 'Unable to locate the specified templates',

'theme_offline' => 'Forum Offline Template',

'offline_page' => 'Forum Offline Page',

'theme_announcements' => 'Announcements Templates',

'theme_archives' => 'Archive Templates',

'theme_breadcrumb' => 'Breadcrumb Templates',

'theme_category' => 'Category Templates',

'theme_css' => 'CSS Templates',

'theme_delete_post' => 'Delete Post Templates',

'theme_emoticons' => 'Smiley Templates',

'theme_error' => 'Error Message Templates',

'theme_global' => 'Global Templates',

'theme_index' => 'Index Page Templates',

'theme_legends' => 'Topic Legend Templates',

'theme_login' => 'Login Form Templates',

'theme_member' => 'Member Profile Templates',

'theme_move_topic' => 'Topic Move Templates',

'theme_search' => 'Search Templates',

'theme_stats' => 'Stats Templates',

'theme_submission' => 'Post Submission Form Templates',

'theme_threads' => 'Thread View Templates',

'theme_topics' => 'Topic View Templates',

'file_not_writable' => 'This template is not writable',

'file_writing_instructions' => 'In order to update this template you must set this file\'s permissions to 666:',

'announcement_page' => 'Announcement Page',

'announcement_topics' => 'Announcement - Topic List',

'announcement_topic_rows' => 'Announcement - Topics Rows',

'announcement' => 'Announcement - View Post',

'recent_posts' => 'Recent Post Table',

'most_recent_topics' => 'Most Recent Topics Item',

'most_popular_posts' => 'Most Popular Post Item',

'breadcrumb' => 'Breadcrumb',

'breadcrumb_trail' => 'Breadcrumb Trail',

'breadcrumb_current_page' => 'Breadcrumb Current Page',

'forum_css' => 'CSS Stylesheet',

'category_page' => 'Category Page',

'delete_post_page' => 'Delete Post Page',

'delete_post_warning' => 'Delete Post Warning',

'error_page' => 'Error Page',

'error_message' => 'Error Message',

'html_header' => 'HTML Header',

'html_footer' => 'HTML Footer',

'meta_tags' => 'Meta Tags',

'top_bar' => 'Top Bar',

'top_bar_spacer' => 'Top Bar Spacer',

'page_header' => 'Page Header',

'page_header_simple' => 'Page Header - Simple',

'page_subheader' => 'Page Sub-header',

'page_subheader_simple' => 'Page Sub-header - Simple',

'private_message_box' => 'Private Message Box',

'forum_homepage' => 'Forum Main Index Page',

'main_forum_list' => 'Forum Main Wrapper',

'forum_table_heading' => 'Forum Table Heading',

'forum_table_rows' => 'Forum Table Rows',

'forum_table_footer' => 'Forum Table Footer',

'login_required_page' => 'Login Required Page',

'login_form' => 'Login Form',

'login_form_mini' => 'Login Form - Mini Version',

'update_un_pw_form' => 'Username/Password Reassignment Form',

'email_user_message' => 'Email User Message',

'emoticon_page' => 'Emoticon Page',

'submission_page' => 'Post Submission Page',

'preview_post' => 'Post Preview',

'submission_errors' => 'Submission Error Message',

'submission_form' => 'Post Submission Form',

'form_attachments' => 'Submission Form Attachments',

'form_attachment_rows' => 'Attachment Rows',

'advanced_search_page' => 'Advanced Search Page',

'search_results_page' => 'Search Results Page',

'forum_quick_search_form' => 'Forum Quick Search Form',

'quick_search_form' => 'Quick Search Form',

'reply_results' => 'Reply Results',

'search_thread_page' => 'Search Thread Page',

'thread_result_rows' => 'Thread Result Rows',

'thread_search_results' => 'Thread Search Results',

'advanced_search_form' => 'Advanced Search Form',

'search_results' => 'Search Results',

'result_rows' => 'Search Result Rows',

'no_search_result' => 'No Search Result Message',

'visitor_stats' => 'Visitor Stats',

'thread_page' => 'Thread Page',

'threads' => 'Threads',

'thread_rows' => 'Thread Rows',

'post_attachments' => 'Post Attachments',

'thumb_attachments' => 'Thumbnail Attachments',

'image_attachments' => 'Image Attachments',

'file_attachments' => 'File Attachments',

'signature' => 'Signature',

'forum_legend' => 'Forum Legend',

'topic_legend' => 'Topic Legend',

'move_topic_page' => 'Move Topic Page',

'move_topic_confirmation' => 'Move Topic Confirmation',

'theme_move_reply' => 'Move Reply Templates',

'move_reply_page' => 'Move Reply Page',

'move_reply_confirmation' => 'Move Reply Confirmation',

'topic_page' => 'Topic Page',

'topics' => 'Topics',

'topic_rows' => 'Topic Rows',

'topic_no_results' => 'Topic No Results Message',

'error_opening_template' => 'Unable to open the specified file',

'template_updated' => 'Template Updated',

'poll_answer_field' => 'Poll Answer Field',

'poll_vote_count_field' => 'Poll Vote Count Field',

'fast_reply_form' => 'Fast Reply Form',

'theme_poll' => 'Poll Templates',

'poll_questions' => 'Poll Questions',

'poll_question_rows' => 'Poll Question Rows',

'poll_answers' => 'Poll Answers',

'poll_answer_rows' => 'Poll Answer Rows',

'poll_graph_left' => 'Poll Graph - Left Side',

'poll_graph_middle' => 'Poll Graph - Middle',

'poll_graph_right' => 'Poll Graph - Right Side',

'theme_user_banning' => 'User Banning Form Templates',

'user_banning_page' => 'User Banning Page',

'user_banning_warning' => 'User Banning Warning',

'user_banning_report' => 'User Banning Report',

'forum_can_post_topic' => 'Can Post Topic',

'forum_can_post_reply' => 'Can Post Reply',

'forum_id' => 'ID',

'bulletin_board' => 'Bulletin Board',

'edit_ignore_list_form' => 'Ignore List Form',

'edit_ignore_list_rows' => 'Ignore List Rows',

'edit_preferences' => 'Edit Preferences Form',

'pref_notify_moderators_topics' => 'Notify Moderators of New Topics?',

'pref_notify_moderators_replies' => 'Notify Moderators of New Replies?',

'board_label' => 'Forum Board Label',

'board_name' => 'Forum Board Short Name',

'forum_board_prefs' => 'Forum Board Preferences',

'new_forum_board_prefs' => 'New Forum Board Preferences',

'forum_board_prefs_default' => 'Board Default Preferences',

'forum_board_prefs_default_inst' => 'These preferences are applied to new forums/categories you create on this board.',

'board_enabled' => 'Enable Forum Board',

'single_word_no_spaces' =>
'single word, no spaces',

'forum_name_unavailable' => 'Forum Board Short Name Unavailable',

'forum_board_home' => 'Forum Board Home',

'edit_forum_boards' => 'Edit Forum Boards',

'board_id' => 'Board ID',

'forum_board_enabled' => 'Enabled?',

'edit_forum_board' => 'Edit Board',

'add_forum_board' => 'Add a new Forum Board?',

'delete_board_confirmation' => 'Delete Forum Board Confirmation',

'delete_board_confirmation_message' => 'Are you sure you wish to delete this Forum Board?',

'board_deleted' => 'Forum Board Deleted',

'board_site_id' => 'Site for Forum',

'forum_trigger_taken' => 'Your Forum Trigger Word Is Already Taken for This Site',

'board_alias_label' => 'Forum Board Alias Label',

'board_alias_name' => 'Forum Board Alias Short Name',

'edit_alias' => 'Edit Alias',

'add_forum_board_alias' => 'Add a new Forum Board alias?',

'board_alias_id' => 'Forum Board Being Aliased',

'forum_board_alias_prefs' => 'Forum Board Alias Preferences',

'new_forum_board_alias_prefs' => 'New Forum Board Alias Preferences',

'no_forums_for_forum_board' => 'There are no forums for this Forum Board',

// Used by the tabs

'empty_title_field' => 'Your post must have a title',

'empty_body_field' => 'Your message field is empty',

'invalid_forum_id' => 'Invalid Forum ID',

'invalid_topic_id' => 'Invalid Topic ID',
*/

''=>''
);

/* End of file forum_cp_lang.php */
/* Location: ./system/expressionengine/language/english/forum_cp_lang.php */

<?php

$lang = array(

/**
 * Menu
 */

'general_settings' => 'General Settings',

'url_path_settings' => 'URL and Path Settings',

'outgoing_email' => 'Outgoing Email',

'debugging_output' => 'Debugging & Output',

'content_and_design' => 'Content & Design',

'comment_settings' => 'Comment Settings',

'html_buttons' => '<abbr title="Hyper-Text Markup Language">HTML</abbr> Buttons',

'template_settings' => 'Template Settings',

'hit_tracking' => 'Hit Tracking',

'pages_settings' => 'Pages Settings',

'upload_directories' => 'Upload Directories',

'word_censoring' => 'Word Censoring',

'menu_manager' => 'Menu Manager',

'members' => 'Members',

'messages' => 'Messages',

'avatars' => 'Avatars',

'security_privacy' => 'Security & Privacy',

'access_throttling' => 'Access Throttling',

'captcha' => 'CAPTCHA',

'system_settings' => 'System Settings',

/**
 * General Settings
 */

'site_name' => 'Name',

'site_short_name' => 'Short name',

'site_short_name_taken' => 'This short name is already taken.',

'site_online' => 'Website online?',

'site_online_desc' => 'When set to <b>offline</b>, only super admins and member groups with permissions will be able to browse your website.',

'version_autocheck' => 'New version auto check',

'version_autocheck_desc' => 'When set to <b>auto</b>, ExpressionEngine will automatically check for newer versions of the software.',

'enable_msm' => 'Enable Site Manager?',
'enable_msm_desc' => 'When set to <b>enable</b>, super admins and member groups with permissions will be able to manage additional websites from the <abbr title="Control Panel">CP</abbr>.',

'online' => 'Online',

'offline' => 'Offline',

'auto' => 'Auto',

'manual' => 'Manual',

'check_now' => 'Check now',

'defaults' => 'Defaults',

'language' => 'Language',

'date_time_settings' => 'Date &amp; Time Settings',

'site_default' => 'Use site default',

'timezone' => 'Timezone',

'date_time_fmt' => 'Date &amp; time format',

"24_hour" => "24-hour",

"12_hour" => "12-hour with AM/PM",

'include_seconds' => 'Show seconds?',

'include_seconds_desc' => 'When set to <b>yes</b>, date output will include seconds for display.',

'btn_save_settings' => 'Save Settings',

'running_current' => 'ExpressionEngine is up to date',

'running_current_desc' => 'ExpressionEngine %s is the latest version.',

'error_getting_version'	=> 'You are using ExpressionEngine %s. Unable to determine if a newer version is available at this time.',

'version_update_available' => 'A newer version of ExpressionEngine is available',

'version_update_inst' => 'ExpressionEngine %s is available. <a href="%s" rel="external">Download the latest version</a> and follow the <a href="%s" rel="external">update instructions</a>.',

/**
 * License & Registration
 */

'license_and_registration' => 'License &amp; Registration',
'license_and_registration_settings' => 'License &amp; Registration Settings',

'license_updated' => 'License &amp; Registration Updated',
'license_updated_desc' => 'Your license and registration information has been saved successfully.',

'license_file_upload_error' => 'Cannot Update License &amp; Registration',
'license_file_upload_error_desc' => 'We were unable to update the license &amp; registration, please review and fix errors below.',

'license_file_error' => 'License Invalid',
'license_file_corrupt_license_file' => 'The license file provided is missing data.',
'license_file_invalid_signature' => 'The license file provided has an invalid signature.',
'license_file_missing_pubkey' => 'The ExpressionEngine product is missing data. Pleas visit <a href="%s" rel="external">EllisLab.com</a> and download a fresh copy.',
'license_file_invalid_license_number' => 'The license file provided is invalid.',

'license_file_fail' => 'License not saved',
'license_file_permissions' => 'The license file could not be saved. Check the permissions on <b>%s</b>.',

'license_file' => 'License file',
'license_file_desc' => 'Found on your <a href="%s" rel="external">purchase management</a> page.',

'site_limit' => 'Site limit',

'features_limited' => 'Features Limited',
'features_limited_desc' => 'The Core version of ExpressionEngine is feature limited. <a href="%s" rel="external">Upgrade today.</a>',

/**
 * URLs and Path Settings
 */

'url_path_settings_title' => '<abbr title="Uniform Resource Location">URL</abbr> and Path Settings',

'base_url' => 'Default base URL',

'base_url_desc' => 'Use <code>{base_url}</code> to build URLs in control panel URL fields.',

'base_path' => 'Default base path',

'base_path_desc' => 'Use <code>{base_path}</code> to build paths in control panel path fields.',

'site_index' => 'Website index page',

'site_index_desc' => 'Most commonly <mark>index.php</mark>.',

'site_url' => 'Website root directory',

'site_url_desc' => '<abbr title="Uniform Resource Location">URL</abbr> location of your <mark>index.php</mark>.',

'cp_url' => 'Control panel directory',

'cp_url_desc' => '<abbr title="Uniform Resource Location">URL</abbr> location of your control panel.',

'themes_url' => 'Themes directory',

'themes_url_desc' => '<abbr title="Uniform Resource Location">URL</abbr> location of your <mark>themes</mark> directory.',

'themes_path' => 'Themes path',

'themes_path_desc' => 'Full path location of your <mark>themes</mark> directory.',

'docs_url' => 'Documentation directory',

'docs_url_desc' => '<abbr title="Uniform Resource Location">URL</abbr> location of your <mark>documentation</mark> directory.',

'member_segment_trigger' => 'Profile <abbr title="Uniform Resource Location">URL</abbr> segment',

'member_segment_trigger_desc' => 'Word that triggers member profile display. <b>Cannot</b> be the same as a template or template group.',

'category_segment_trigger' => 'Category <abbr title="Uniform Resource Location">URL</abbr> segment',

'category_segment_trigger_desc' => 'Word that triggers category display. <b>Cannot</b> be the same as a template or template group.',

'category_url' => 'Category <abbr title="Uniform Resource Location">URL</abbr>',

'category_url_desc' => 'When set to <b>titles</b>, category links will use category <abbr title="Uniform Resource Location">URL</abbr> titles instead of the category ids.',

'category_url_opt_titles' => 'Titles',

'category_url_opt_ids' => 'IDs',

'url_title_separator' => '<abbr title="Uniform Resource Location">URL</abbr> title separator',

'url_title_separator_desc' => 'Character used to separate words in generated <abbr title="Uniform Resource Location">URL</abbr>s, <mark>hyphens (-)</mark> are recommended.',

'url_title_separator_opt_hyphen' => 'Hyphen (different-words)',

'url_title_separator_opt_under' => 'Underscore (different_words)',

/**
 * Outgoing Email
 */

'webmaster_email' => 'Address',

'webmaster_email_desc' => 'Email address you want automated Email to come from. Without this, automated Email will likely be marked as spam.',

'webmaster_name' => 'From name',

'webmaster_name_desc' => 'Name you want automated Emails to use.',

'email_charset' => 'Character encoding',

'email_charset_desc' => 'Email require character encoding to be properly formatted. UTF-8 is recommended.',

'mail_protocol' => 'Protocol',

'mail_protocol_desc' => 'Preferred Email sending protocol. SMTP is recommended.',

'smtp_options' => 'SMTP Options',

'smtp_server' => 'Server address',

'smtp_server_desc' => 'URL location of your <mark>SMTP server</mark>.',

'smtp_port' => 'Server Port',

'sending_options' => 'Sending Options',

'mail_format' => 'Mail format',

'mail_format_desc' => 'Format that Emails are sent in. Plain Text is recommended.',

'word_wrap' => 'Enable word-wrapping?',

'word_wrap_desc' => 'When set to <b>enable</b>, the system will wrap long lines of text to a more readable width.',

'php_mail' => 'PHP Mail',

'sendmail' => 'Sendmail',

'smtp' => 'SMTP',

'plain_text' => 'Plain Text',

'html' => 'HTML',

'empty_stmp_fields' => 'This field is required for SMTP.',

/**
 * Debugging & Output
 */

'enable_errors' => 'Enable error reporting?',

'enable_errors_desc' => 'When set to <b>enable</b>, super admins will see PHP/MySQL errors when they occur.',

'show_profiler' => 'Enable debugging?',

'show_profiler_desc' => 'When set to <b>enable</b>, super admins will see benchmark results, all SQL queries, and submitted form data displayed at the bottom of the browser window.',

'enable_devlog_alerts' => 'Enable Developer Log Alerts?',

'enable_devlog_alerts_desc' => 'When set to <b>enable</b>, super admins will see control panel alerts when new <a href="%s">Developer Log</a> items need action. Currently <b>%s item(s)</b> are logged.',

'output_options' => 'Output Options',

'gzip_output' => 'Enable <abbr title="GNU Zip Compression">GZIP</abbr> compression?',

'gzip_output_desc' => 'When set to <b>yes</b>, your website will be compressed using GZIP compression, this will decrease page load times.',

'force_query_string' => 'Force <abbr title="Uniform Resource Location">URL</abbr> query strings?',

'force_query_string_desc' => 'When set to <b>yes</b>, servers that do not support <mark>PATH_INFO</mark> will use query string URLs instead.',

'send_headers' => 'Use <abbr title="Hypertext Transfer Protocol">HTTP</abbr> page headers?',

'send_headers_desc' => 'When set to <b>yes</b>, your website will generate <abbr title="Hypertext Transfer Protocol">HTTP</abbr> headers for all pages.',

'redirect_method' => 'Redirection type',

'redirect_method_desc' => 'Indicates type of page redirection the system will use for <mark>{redirect=\'\'}</mark> and other built in redirections.',

'redirect_method_opt_location' => 'Location (fastest)',

'redirect_method_opt_refresh' => 'Refresh (Windows only)',

'caching_driver' => 'Caching Driver',

'caching_driver_desc' => 'Caches can be stored in either a file-based or memory-based driver.',

'caching_driver_failover' => 'Cannot connect to %s, using %s driver instead.',

'caching_driver_file_fail' => 'Cannot use %s driver, check cache path permissions.',

'disable_caching' => 'Disable Caching',

'max_caches' => 'Cachable <abbr title="Uniform Resource Identifier">URI</abbr>s',

'max_caches_desc' => 'If you cache your pages or database, this limits the number of cache instances. We recommend 150 for small sites and 300 for large sites. The allowed maximum is 1000.',

'new_relic' => 'New Relic Options',

'use_newrelic' => 'Enable New Relic RUM JavaScript?',

'use_newrelic_desc' => 'When set to <b>yes</b>, New Relic will add <a href="https://docs.newrelic.com/docs/browser/new-relic-browser/page-load-timing-resources/instrumentation-browser-monitoring" rel="external">Real User Monitoring JavaScript</a> to all of your web pages.',

'newrelic_app_name' => 'New Relic application name',

'newrelic_app_name_desc' => 'Changes the name of the application that appears in the New Relic dashboard for this installation of ExpressionEngine.',

/**
 * Content & Design
 */

'new_posts_clear_caches' => 'Clear cache for new entries?',

'new_posts_clear_caches_desc' => 'When set to <b>yes</b>, all caches will be cleared when authors publish new entries.',

'enable_sql_caching' => 'Cache dynamic channel queries?',

'enable_sql_caching_desc' => 'When set to <b>yes</b>, the speed of dynamic channel pages will be improved. do <b>not</b> use if you need the "future entries" or "expiring entries" features.',

'categories_section' => 'Categories',

'auto_assign_cat_parents' => 'Assign category parents?',

'auto_assign_cat_parents_desc' => 'When set to <b>yes</b>, ExpressionEngine will automatically set the parent category when choosing a child category.',

'channel_manager' => 'Channel Manager',

'image_resizing' => 'Image Resizing',

'image_resize_protocol' => 'Protocol',

'image_resize_protocol_desc' => 'Ask your web host for server compatibility.',

'gd' => 'GD',

'gd2' => 'GD 2',

'netpbm' => 'NetPBM',

'imagemagick' => 'ImageMagick',

'image_library_path' => 'Converter path',

'image_library_path_desc' => 'Full path location of the <mark>image program</mark>.</em>
<em><b>Required</b> for ImageMagick and NetPBM.',

'invalid_image_library_path' => 'This field must contain a valid path to an image processing library if ImageMagick or NetPBM is the selected protocol.',

'thumbnail_suffix' => 'Thumbnail suffix',

'thumbnail_suffix_desc' => 'Added to all auto-generated thumbnails. <b>Example</b>: photo_thumb.jpg',

'emoticons' => 'Emoticons',

'enable_emoticons' => 'Enable emoticons?',

'enable_emoticons_desc' => 'When set to <b>yes</b>, text based emoticons will be converted to image based emoticons.',

'emoticon_url' => '<abbr title="Unified Resource Locator">URL</abbr>',

'emoticon_url_desc' => '<abbr title="Unified Resource Locator">URL</abbr> location of the <mark>emoticon</mark> directory.',

/**
 * Comment Settings
 */

'all_comments' => 'All Comments',

'enable_comments' => 'Enable comment module?',

'enable_comments_desc' => 'When set to <b>enable</b>, channels will be able to use the comment module.',

'options' => 'Options',

'comment_word_censoring' => 'Enable word censoring?',

'comment_word_censoring_desc' => 'When set to <b>enable</b>, commenting will use the <a href="%s">word censoring</a> filters.',

'comment_moderation_override' => 'Moderate expired entries?',

'comment_moderation_override_desc' => 'When set to <b>yes</b>, comments made on an expired entry will be submitted as closed and require review by a moderator.',

'comment_edit_time_limit' => 'Comment edit time limit (in seconds)',

'comment_edit_time_limit_desc' => 'Length of time that a user can edit their own comments, from submission. Use <b>0</b> for no limit.',

/**
 * Template Settings
 */

'template_manager' => 'Template Manager',

'strict_urls' => 'Enable strict <abbr title="Uniform Resource Location">URL</abbr>s?',

'strict_urls_desc' => 'When set to <b>enable</b>, ExpressioneEngine will apply stricter rules to <abbr title="Uniform Resource Location">URL</abbr> handling.',

'site_404' => '404 page',

'site_404_desc' => 'Template to be used as the 404 error page.',

'save_tmpl_revisions' => 'Save template revisions?',

'save_tmpl_revisions_desc' => 'When set to <b>yes</b>, ExpressionEngine will save up to <b>5</b> template revisions in the database.',

'max_tmpl_revisions' => 'Maximum revisions?',

'max_tmpl_revisions_desc' => 'Number of revisions stored in the database for each template. We recommend this be a low number, as this can cause you to have a larger than normal database.',

'save_tmpl_files' => 'Save templates as files?',

'save_tmpl_files_desc' => 'When set to yes, ExpressionEngine will store your templates as files on your server.',

/**
 * Hit Tracking
 */

'enable_online_user_tracking' => 'Enable online user tracking?',
'enable_online_user_tracking_desc' => 'When set to <b>yes</b>, ExpressionEngine will track logged in users.',

'enable_hit_tracking' => 'Enable template hit tracking?',
'enable_hit_tracking_desc' => 'When set to <b>yes</b>, ExpressionEngine will count how many times a template is viewed.',

'enable_entry_view_tracking' => 'Enable entry view tracking?',
'enable_entry_view_tracking_desc' => 'When set to <b>yes</b>, ExpressionEngine will count how many times a channel entry is viewed.',

'log_referrers' => 'Enable referrer tracking?',
'log_referrers_desc' => 'When set to <b>yes</b>, ExpressionEngine will track all incoming links.',

'max_referrers' => 'Maximum recent referrers to save',

'dynamic_tracking_disabling' => 'Suspend threshold?',
'dynamic_tracking_disabling_desc' => 'All tracking will be suspended when the number of online visitors exceeds this number.</em> <em>Online user tracking must be enabled to use this feature. <a href="%s" ref="external">Learn more</a>',

/**
 * Word Censoring
 */

'enable_censoring' => 'Enable censorship?',

'enable_censoring_desc' => 'When set to <b>enable</b>, words listed will be replaced with the specified replacement characters.',

'censor_replacement' => 'Replacement characters',

'censor_replacement_desc' => 'Words that match any word in the words to censor list will be replaced with these characters.',

'censored_words' => 'Words to censor',

'censored_words_desc' => 'One word per line. All words listed will be replaced with the above specified characters.',

/**
 * Member Settings
 */

'member_settings' => 'Member Settings',

'allow_member_registration' => 'Allow registrations?',

'allow_member_registration_desc' => 'When set to <b>yes</b>, users will be able to register member accounts.',

'req_mbr_activation' => 'Account activation type',

'req_mbr_activation_desc' => 'Choose how you want users to activate their registrations.',

'req_mbr_activation_opt_none' => 'No activation required',

'req_mbr_activation_opt_email' => 'Send activation Email',

'req_mbr_activation_opt_manual' => 'Manually moderated by administrator',

'approved_member_notification' => 'Notify members when approved?',

'approved_member_notification_desc' => 'When set to <b>yes</b>, members will receive an email notification when their member registration is approved.',

'declined_member_notification' => 'Notify members when declined?',

'declined_member_notification_desc' => 'When set to <b>yes</b>, members will receive an email notification when their member registration is declined.',

'require_terms_of_service' => 'Require terms of service?',

'require_terms_of_service_desc' => 'When set to <b>yes</b>, users must agree to terms of service during registration.',

'allow_member_localization' => 'Allow members to set time preferences?',

'allow_member_localization_desc' => 'When set to <b>yes</b>, members will be able to set a specific time and date localization for their account.',

'default_member_group' => 'Default member group',

'member_theme' => 'Member profile theme',

'member_theme_desc' => 'Default theme used for member profiles.',

'member_listing_settings' => 'Member Listing Settings',

'memberlist_order_by' => 'Sort by',

'memberlist_order_by_desc' => 'Sorting type for the member listing.',

'memberlist_order_by_opt_entries' => 'Total entries',

'memberlist_sort_order' => 'Order by',

'memberlist_sort_order_desc' => 'Sorting order for the member listing.',

'memberlist_sort_order_opt_asc' => 'Ascending (A-Z/Oldest-Newest)',

'memberlist_sort_order_opt_desc' => 'Descending (Z-A/Newest-Oldest)',

'memberlist_row_limit' => 'Total results',

'memberlist_row_limit_desc' => 'Total returned results per page for the member listing.',

'registration_notify_settings' => 'Registration Notification Settings',

'new_member_notification' => 'Enable new member notifications?',

'new_member_notification_desc' => 'When set to <b>enable</b>, the following Email addresses will be notified anytime a new registration occurs.',

'mbr_notification_emails' => 'Notification recipients',

'mbr_notification_emails_desc' => 'Separate multiple Emails with a comma.',

/**
 * Menu Manager
 */

'menu_sets' => 'Menu Sets',
'menu_set' => 'Menu Set',
'edit_menu_set' => 'Edit Menu Set',
'create_menu_set' => 'Create Menu Set',
'menu_set_updated' => 'Menu Set Updated',
'menu_set_created' => 'Menu Set Created',
'menu_set_created_desc' => 'The menu set <b>%s</b> has been updated.',
'menu_set_updated_desc' => 'The menu set <b>%s</b> has been updated.',
'menu_sets_removed' => 'Menu Sets removed',
'menu_sets_removed_desc' => '%d menu sets were removed.',
'no_menu_items' => 'No <b>Menu Items</b> found.',
'create_menu_item' => 'Create Menu Item',
'set_name' => 'Name',
'set_assigned' => 'Assigned',
'assigned_to' => 'assigned to',
'set_member_groups' => 'Member group(s)?',
'set_member_groups_desc' => 'Choose the member group(s) to apply this menu to.',
'menu_options' => 'Menu Options',
'menu_items' => 'Menu Items',
'menu_items_desc' => 'Manage this menu sets contents',
'menu_type' => 'Type',
'menu_addon' => 'Add-On',
'menu_single' => 'Single Link',
'menu_dropdown' => 'Dropdown',
'submenu' => 'Submenu',
'submenu_desc' => 'Links in dropdown',
'menu_label' => 'Name',
'menu_label_desc' => 'Link label',
'menu_url' => '<abbr title="Uniform Resource Locator">URL</abbr>',
'menu_url_desc' => 'Link <abbr title="Uniform Resource Locator">URL</abbr>',
'menu_addon' => 'Add-On',
'menu_addon_desc' => 'Navigation from installed Add-Ons',
'menu_no_addons' => 'No <b>Add-ons with menus</b> found.',
'edit_menu_item' => 'Edit Menu Item',
'add_menu_item' => 'Add Menu Item',

/**
 * Messages
 */

'messaging_settings' => 'Messaging Settings',

'prv_msg_max_chars' => 'Maximum characters',

'prv_msg_html_format' => 'Formatting',

"html_safe" => "Safe HTML only",

"html_all" => "All HTML (not recommended)",

"html_none" => "Convert HTML",

'prv_msg_auto_links' => 'Convert <abbr title="Uniform Resource Location">URL</abbr>s and Emails into links?',

'prv_msg_auto_links_desc' => 'When set to <b>yes</b>, All <abbr title="Uniform Resource Location">URL</abbr>s and Emails will be auto converted into hyper links.',

'attachment_settings' => 'Attachment Settings',

'prv_msg_upload_url' => 'Upload directory',

'prv_msg_upload_url_desc' => '<abbr title="Uniform Resource Location">URL</abbr> location of your <mark>attachments</mark> directory.',

'prv_msg_upload_path' => 'Upload path',

'prv_msg_upload_path_desc' => 'Full path location for your <mark>attachement</mark> directory.',

'prv_msg_max_attachments' => 'Maximum attachments',

'prv_msg_attach_maxsize' => 'Maximum file size (<abbr title="kilobyte">kb</abbr>)',

'prv_msg_attach_maxsize_desc' => 'Maximum allowed file size per attachment in personal messages.',

'prv_msg_attach_total' => 'Maximum total file size (<abbr title="megabyte">mb</abbr>)',

'prv_msg_attach_total_desc' => 'Maximum allowed file size for all attachments for each member.',

/**
 * Avatars
 */

'avatar_settings' => 'Avatar Settings',

'enable_avatars' => 'Allow avatars?',

'enable_avatars_desc' => 'When set to <b>yes</b>, members will be able to use avatars (representative images) in comments and forums.',

'allow_avatar_uploads' => 'Allow avatar uploads?',

'allow_avatar_uploads_desc' => 'When set to <b>yes</b>, members will be able to upload their own avatars (representative images).',

'avatar_url' => 'Avatar directory',

'avatar_url_desc' => '<abbr title="Uniform Resource Location">URL</abbr> location of your <mark>avatar</mark> directory.',

'avatar_path' => 'Avatar path',

'avatar_path_desc' => 'Full path location of your <mark>avatar</mark> directory.',

'avatar_file_restrictions' => 'Avatar File Restrictions',

'avatar_max_width' => 'Maximum width',

'avatar_max_height' => 'Maximum height',

'avatar_max_kb' => 'Maximum file size (<abbr title="kilobytes">kb</abbr>)',

/**
 * CAPTCHA
 */

'captcha_settings' => 'CAPTCHA Settings',

'captcha_settings_title' => '<abbr title="Completely Automated Public Turing test to tell Computers and Humans Apart">CAPTCHA</abbr> Settings',

'require_captcha' => 'Require <abbr title="Completely Automated Public Turing test to tell Computers and Humans Apart">CAPTCHA</abbr>?',

'require_captcha_desc' => 'When set to <b>yes</b>, visitors will be required to fill in a <abbr title="Completely Automated Public Turing test to tell Computers and Humans Apart">CAPTCHA</abbr> field for all front-end forms.',

'captcha_font' => 'Use TrueType font?',

'captcha_font_desc' => 'When set to <b>yes</b>, <abbr title="Completely Automated Public Turing test to tell Computers and Humans Apart">CAPTCHA</abbr> fields will use a TrueType font for display.',

'captcha_rand' => 'Add random number?',

'captcha_rand_desc' => 'When set to <b>yes</b>, <abbr title="Completely Automated Public Turing test to tell Computers and Humans Apart">CAPTCHA</abbr> fields will randomly generate numbers as well as letters.',

'captcha_require_members' => 'Require <abbr title="Completely Automated Public Turing test to tell Computers and Humans Apart">CAPTCHA</abbr> while logged in?',

'captcha_require_members_desc' => 'When set to <b>no</b>, logged in members will not be required to fill in <abbr title="Completely Automated Public Turing test to tell Computers and Humans Apart">CAPTCHA</abbr> fields.',

'captcha_url' => '<abbr title="Completely Automated Public Turing test to tell Computers and Humans Apart">CAPTCHA</abbr> directory',

'captcha_url_desc' => '<abbr title="Uniform Resource Location">URL</abbr> location of your <mark><abbr title="Completely Automated Public Turing test to tell Computers and Humans Apart">CAPTCHA</abbr></mark> directory.',

'captcha_path' => '<abbr title="Completely Automated Public Turing test to tell Computers and Humans Apart">CAPTCHA</abbr> path',

'captcha_path_desc' => 'Full path location of your <mark><abbr title="Completely Automated Public Turing test to tell Computers and Humans Apart">CAPTCHA</abbr></mark> directory.',

/**
 * Security & Privacy
 */

'security_tip' => '<b>Tip</b>: Site security is important.',

'security_tip_desc' => 'Any setting marked with <span title="security enhancement"></span> will further enhance and improve site security.',

'cp_session_type' => '<abbr title="Control Panel">CP</abbr> session type',

'website_session_type' => 'Website Session type',

'cs_session' => 'Cookies and session ID',

'c_session' => 'Cookies only',

's_session' => 'Session ID only',

'cookie_settings' => 'Cookie Settings',

'cookie_domain' => 'Domain',

'cookie_domain_desc' => 'Use <mark>.yourdomain.com</mark> for system-wide cookies.',

'cookie_path' => 'Path',

'cookie_path_desc' => 'Path to apply cookies to the above domain. (<a href="%s">more info</a>)',

'cookie_prefix' => 'Prefix',

'cookie_prefix_desc' => 'Only required when running multiple installations of ExpressionEngine.',

'cookie_httponly' => 'Send cookies over <abbr title="Hyper Text Transfer Protocol">HTTP</abbr> only?',

'cookie_httponly_desc' => 'When set to <b>yes</b>, cookies will <b>not</b> be accessible through JavaScript.',

'cookie_secure' => 'Send cookies securely?',

'cookie_secure_desc' => 'When set to <b>yes</b>, cookies will only be transmitted over a secure <abbr title="Hyper Text Transfer Protocol with Secure Sockets Layer">HTTPS</abbr> connection.</em><em>Your site <b>must</b> use <abbr title="Secure Sockets Layer">SSL</abbr> everywhere for this to work.',

'member_security_settings' => 'Member Security Settings',

'allow_username_change' => 'Allow members to change username?',

'allow_username_change_desc' => 'When set to <b>yes</b>, members will be able to change their username.',

'un_min_len' => 'Minimum username length',

'un_min_len_desc' => 'Minimum number of characters required for new members\' usernames.',

'allow_multi_logins' => 'Allow multiple sessions?',

'allow_multi_logins_desc' => 'When set to <b>no</b>, members will not be able to log in from another location or browser if they already have an active session.',

'require_ip_for_login' => 'Require user agent and <abbr title="Internet Protocol">IP</abbr> for login?',

'require_ip_for_login_desc' => 'When set to <b>yes</b>, members will be unable to login without a valid user agent and <abbr title="Internet Protocol">IP</abbr> address.',

'password_lockout' => 'Enable password lock out?',

'password_lockout_desc' => 'When set to <b>enable</b>, members will be locked out of the system after failed log in attempts.',

'password_lockout_interval' => 'Password lock out interval',

'password_lockout_interval_desc' => 'Number of minutes a member should be locked out after four invalid login attempts.',

'require_secure_passwords' => 'Require secure passwords?',

'require_secure_passwords_desc' => 'When set to <b>yes</b>, members will be required to choose passwords containing at least one uppercase, one lowercase, and one numeric character.',

'pw_min_len' => 'Minimum password length',

'pw_min_len_desc' => 'Minimum number of characters required for new members\' passwords.',

'allow_dictionary_pw' => 'Allow dictionary words in passwords?',

'allow_dictionary_pw_desc' => 'When set to <b>yes</b>, members will be able to use common dictionary words in their password. <mark>requires dictionary file to be installed to enforce.</mark>',

'name_of_dictionary_file' => 'Dictionary file',

'name_of_dictionary_file_desc' => 'Name of your <mark>dictionary</mark> file in your config folder.',

'form_security_settings' => 'Content Submission Settings',

'deny_duplicate_data' => 'Deny duplicate data?',

'deny_duplicate_data_desc' => 'When set to <b>yes</b>, forms will disregard any submission that is an exact duplicate of existing data.',

'require_ip_for_posting' => 'Require user agent and <abbr title="Internet Protocol">IP</abbr> for posting?',

'require_ip_for_posting_desc' => 'When set to <b>yes</b>, members will be unable to post without a valid user agent and <abbr title="Internet Protocol">IP</abbr> address.',

'xss_clean_uploads' => 'Apply <abbr title="Cross Site Scripting">XSS</abbr> filtering?',

'xss_clean_uploads_desc' => 'When set to <b>yes</b>, forms will apply <abbr title="Cross Site Scripting">XSS</abbr> filtering to submissions.',

'enable_rank_denial' => 'Enable Rank Denial to submitted links?',

'enable_rank_denial_desc' => 'When set to <b>enable</b>, all outgoing links are sent to a redirect page. This prevents spammers from <a href="%s" rel="external">gaining page rank</a>.',

/**
 * Access Throttling
 */

'enable_throttling' => 'Enable throttling?',

'enable_throttling_desc' => 'When set to <b>enable</b>, members will be locked out of the system when they meet the lock out requirement.',

'banish_masked_ips' => 'Require <abbr title="Internet Protocol">IP</abbr>?',

'banish_masked_ips_desc' => 'When set to <b>yes</b>, members will be denied access if they do not have a valid <abbr title="Internet Protocol">IP</abbr> address.',

'throttling_limit_settings' => 'Throttling Limit Settings',

"max_page_loads" => "Maximum page loads",

"max_page_loads_desc" => "The total number of times a user is allowed to load any of your web pages (within the time interval below) before being locked out.",

"time_interval" => "Time interval",

"time_interval_desc" => "The number of seconds during which the above number of page loads are allowed.",

"lockout_time" => "Lockout time",

"lockout_time_desc" => "The number of seconds a user should be locked out of your site if they exceed the limits.",

'banishment_type' => 'Lock out action',

'banish_404' => 'Send to 404',

'banish_redirect' => 'Redirect to URL',

'banish_message' => 'Display message',

'banishment_url' => 'Redirect',

'banishment_url_desc' => '<abbr title="Uniform Resource Location">URL</abbr> location for locked out members.',

'banishment_message' => 'Message',

/**
 * HTML Buttons
 */

'create_html_buttons_success' => '<abbr title="Hyper-Text Markup Language">HTML</abbr> Button Created',
'create_html_buttons_success_desc' => 'The <abbr title="Hyper-Text Markup Language">HTML</abbr> button <b>%s</b> has been created.',

'create_html_buttons_error' => 'Cannot Create <abbr title="Hyper-Text Markup Language">HTML</abbr> Button',
'create_html_buttons_error_desc' => 'We were unable to create this <abbr title="Hyper-Text Markup Language">HTML</abbr> button, please review and fix errors below.',

'edit_html_buttons_success' => '<abbr title="Hyper-Text Markup Language">HTML</abbr> Button Updated',
'edit_html_buttons_success_desc' => 'The <abbr title="Hyper-Text Markup Language">HTML</abbr> button <b>%s</b> has been updated.',

'edit_html_buttons_error' => 'Cannot Update <abbr title="Hyper-Text Markup Language">HTML</abbr> Button',
'edit_html_buttons_error_desc' => 'We were unable to update this <abbr title="Hyper-Text Markup Language">HTML</abbr> button, please review and fix errors below.',


''=>''
);

// EOF

<?php

$lang = array(

/* General word list */
'and' => 'and',

'and_n_others' => 'and %d others...',

'at' => 'at',

'auto_redirection' => 'You will be redirected automatically in %x seconds',

'back' => 'Back',

'by' => 'by',

'click_if_no_redirect' => 'Click here if you are not redirected automatically',

'disabled' => 'disabled',

'dot' => 'dot',

'enabled' => 'enabled',

'encoded_email' => '(JavaScript must be enabled to view this email address)',

'first' => 'First',

'id' => 'ID',

'last' => 'Last',

'next' => 'Next',

'no' => 'No',

'not_authorized' => 'You are not authorized to perform this action',

'not_available' => 'Not available',

'of' => 'of',

'off' => 'off',

'on' => 'on',

'or' => 'or',

'pag_first_link' => '&lsaquo; First',

'pag_last_link' => 'Last &rsaquo;',

'page' => 'Page',

'preference' => 'Preference',

'prev' => 'Previous',

'return_to_previous' => 'Return to Previous Page',

'search' => 'Search',

'setting' => 'Setting',

'site_homepage' => 'Site Homepage',

'submit' => 'Submit',

'system_off_msg' => 'This site is currently inactive.',

'thank_you' => 'Thank You!',

'update' => 'Update',

'updating' => 'Updating',

'yes' => 'Yes',

'required_fields' => 'Required Fields',

/* Errors */
'captcha_incorrect' => 'You did not submit the word exactly as it appears in the image',

'captcha_required' => 'You must submit the word that appears in the image',

'checksum_changed_accept' => 'Accept Changes',

'checksum_changed_warning' => 'One or more core files have been altered:',

'checksum_email_message' => 'ExpressionEngine has detected the modification of a core file on: {url}

The following files are affected:
{changed}

If you made these changes, please accept the modifications on the control panel homepage. If you did not alter these files it may indicate a hacking attempt. Check the files for any suspicious contents (JavaScript or iFrames) and see: '.DOC_URL.'troubleshooting/error_messages/expressionengine_has_detected_the_modification_of_a_core_file.html',

'checksum_email_subject' => 'A core file was modified on your site.',

'csrf_token_expired' => 'This form has expired. Please refresh and try again.',

'current_password_incorrect' => 'Your current password was not submitted correctly.',

'current_password_required' => 'Your current password is required.',

'curl_not_installed' => 'cURL is not installed on your server',

'error' => 'Error',

'file_not_found' => 'File %s does not exist.',

'general_error' => 'The following errors were encountered',

'generic_fatal_error' => 'Something has gone wrong and this URL cannot be processed at this time.',

'invalid_action' => 'The action you have requested is invalid.',

'invalid_url' => 'The URL you submitted is not valid.',

'missing_encryption_key' => 'You do not have value set for <code>%s</code> in your config.php. This may leave your install open to security vulnerabilities. Restore the keys or see <a href="%s">this troubleshooting article</a> in the user guide for help.',

'missing_mime_config' => 'Cannot import your mime-type whitelist: the file %s does not exist or cannot be read.',

'new_version_error' => 'An unexpected error occurred attempting to download the current ExpressionEngine version number. See this <a href="%s" rel="external noreferrer">troubleshooting document</a> for more information.',

'nonexistent_page' => 'The page you requested was not found',

'redirect_xss_fail' => 'The link you are being redirected to contained some potentially malicious or dangerous code. We recommend you hit the back button and email %s to report the link that generated this message.',

'submission_error' => 'The form you submitted contained the following errors',

'theme_folder_wrong' => 'Your theme folder path is incorrect. Please go to <a href="%s">URL and Path Settings</a> and check the <mark>Themes Path</mark> and <mark>Themes URL</mark>.',

'unable_to_load_field_type' => 'Unable to load requested field type file: %s.<br /> Confirm the fieldtype file is located in the /'.SYSDIR.'/user/addons/ directory',

'unwritable_cache_folder' => 'Your cache folder does not have proper permissions.<br />To fix: Set the cache folder (/'.SYSDIR.'/user/cache/) permissions to 777 (or equivalent for your server).',

'unwritable_config_file' => 'Your configuration file does not have the proper permissions.<br />To fix: Set the config file (/'.SYSDIR.'/user/config/config.php) permissions to 666 (or equivalent for your server).',

'version_mismatch' => 'Your ExpressionEngine installation&rsquo;s version (%s) is not consistent with the reported version (%s). <a href="'.DOC_URL.'installation/update.html" rel="external">Please update your installation of ExpressionEngine again</a>.',


/* Member Groups */
'banned' => 'Banned',

'guests' => 'Guests',

'members' => 'Members',

'pending' => 'Pending',

'super_admins' => 'Super Admins',


/* Template.php */
'error_fix_module_processing' => 'Please check that the \'%x\' module is installed and that \'%y\' is an available method of the module',

'error_fix_syntax' => 'Please correct the syntax in your template.',

'error_invalid_conditional' => 'You have an invalid conditional in your template. Please review your conditionals for an unclosed string, invalid operators, a missing }, or a missing {/if}.',

'error_layout_too_late' => 'Plugin or module tag found before layout declaration. Please move the layout tag to the top of your template.',

'error_multiple_layouts' => 'Multiple Layouts found, please ensure you only have one layout tag per template',

'error_tag_module_processing' => 'The following tag cannot be processed:',

'error_tag_syntax' => 'The following tag has a syntax error:',

'layout_contents_reserved' => 'The name "contents" is reserved for the template data and cannot be used as a layout variable (i.e. {layout:set name="contents"} or {layout="foo/bar" contents=""}).',

'template_load_order' => 'Template load order',

'template_loop' => 'You have caused a template loop due to improperly nested sub-templates (\'%s\' recursively called)',


/* Email */
'error_sending_email' => 'Unable to send email at this time.',

'forgotten_email_sent' => 'If this email address is associated with an account, instructions for resetting your password have just been emailed to you.',

'no_email_found' => 'The email address you submitted was not found in the database.',

'password_has_been_reset' => 'Your password was reset and a new one has been emailed to you.',

'password_reset_flood_lock' => 'You have tried to reset your password too many times today. Please check your inbox and spam folders for previous requests, or contact the site administrator.',

'your_new_login_info' => 'Login information',


/* Timezone */
'invalid_date_format' => 'The date format you submitted is invalid.',

'invalid_timezone' => 'The timezone you submitted is invalid.',

'no_timezones' => 'No Timezones',

'select_timezone' => 'Select Timezone',


/* Date */
'singular' => 'one',

'less_than' => 'less than',

'about' => 'about',

'past' => '%s ago',

'future' => 'in %s',

'ago' => '%x ago',

'year' => 'year',

'years' => 'years',

'month' => 'month',

'months' => 'months',

'fortnight' => 'fortnight',

'fortnights' => 'fortnights',

'week' => 'week',

'weeks' => 'weeks',

'day' => 'day',

'days' => 'days',

'hour' => 'hour',

'hours' => 'hours',

'minute' => 'minute',

'minutes' => 'minutes',

'second' => 'second',

'seconds' => 'seconds',

'am' => 'am',

'pm' => 'pm',

'AM' => 'AM',

'PM' => 'PM',

'Sun' => 'Sun',

'Mon' => 'Mon',

'Tue' => 'Tue',

'Wed' => 'Wed',

'Thu' => 'Thu',

'Fri' => 'Fri',

'Sat' => 'Sat',

'Su' => 'S',

'Mo' => 'M',

'Tu' => 'T',

'We' => 'W',

'Th' => 'T',

'Fr' => 'F',

'Sa' => 'S',

'Sunday' => 'Sunday',

'Monday' => 'Monday',

'Tuesday' => 'Tuesday',

'Wednesday' => 'Wednesday',

'Thursday' => 'Thursday',

'Friday' => 'Friday',

'Saturday' => 'Saturday',

'Jan' => 'Jan',

'Feb' => 'Feb',

'Mar' => 'Mar',

'Apr' => 'Apr',

'May' => 'May',

'Jun' => 'Jun',

'Jul' => 'Jul',

'Aug' => 'Aug',

'Sep' => 'Sep',

'Oct' => 'Oct',

'Nov' => 'Nov',

'Dec' => 'Dec',

'January' => 'January',

'February' => 'February',

'March' => 'March',

'April' => 'April',

'May_l' => 'May',

'June' => 'June',

'July' => 'July',

'August' => 'August',

'September' => 'September',

'October' => 'October',

'November' => 'November',

'December' => 'December',

'UM12' => '(UTC -12:00) Baker/Howland Island',

'UM11' => '(UTC -11:00) Niue',

'UM10' => '(UTC -10:00) Hawaii-Aleutian Standard Time, Cook Islands, Tahiti',

'UM95' => '(UTC -9:30) Marquesas Islands',

'UM9' => '(UTC -9:00) Alaska Standard Time, Gambier Islands',

'UM8' => '(UTC -8:00) Pacific Standard Time, Clipperton Island',

'UM7' => '(UTC -7:00) Mountain Standard Time',

'UM6' => '(UTC -6:00) Central Standard Time',

'UM5' => '(UTC -5:00) Eastern Standard Time, Western Caribbean Standard Time',

'UM45' => '(UTC -4:30) Venezuelan Standard Time',

'UM4' => '(UTC -4:00) Atlantic Standard Time, Eastern Caribbean Standard Time',

'UM35' => '(UTC -3:30) Newfoundland Standard Time',

'UM3' => '(UTC -3:00) Argentina, Brazil, French Guiana, Uruguay',

'UM2' => '(UTC -2:00) South Georgia/South Sandwich Islands',

'UM1' => '(UTC -1:00) Azores, Cape Verde Islands',

'UTC' => '(UTC) Greenwich Mean Time, Western European Time',

'UP1' => '(UTC +1:00) Central European Time, West Africa Time',

'UP2' => '(UTC +2:00) Central Africa Time, Eastern European Time, Kaliningrad Time',

'UP3' => '(UTC +3:00) East Africa Time, Arabia Standard Time',

'UP35' => '(UTC +3:30) Iran Standard Time',

'UP4' => '(UTC +4:00) Moscow Time, Azerbaijan Standard Time',

'UP45' => '(UTC +4:30) Afghanistan',

'UP5' => '(UTC +5:00) Pakistan Standard Time, Yekaterinburg Time',

'UP55' => '(UTC +5:30) Indian Standard Time, Sri Lanka Time',

'UP575' => '(UTC +5:45) Nepal Time',

'UP6' => '(UTC +6:00) Bangladesh Standard Time, Bhutan Time, Omsk Time',

'UP65' => '(UTC +6:30) Cocos Islands, Myanmar',

'UP7' => '(UTC +7:00) Krasnoyarsk Time, Cambodia, Laos, Thailand, Vietnam',

'UP8' => '(UTC +8:00) Australian Western Standard Time, Beijing Time, Irkutsk Time',

'UP875' => '(UTC +8:45) Australian Central Western Standard Time',

'UP9' => '(UTC +9:00) Japan Standard Time, Korea Standard Time, Yakutsk Time',

'UP95' => '(UTC +9:30) Australian Central Standard Time',

'UP10' => '(UTC +10:00) Australian Eastern Standard Time, Vladivostok Time',

'UP105' => '(UTC +10:30) Lord Howe Island',

'UP11' => '(UTC +11:00) Magadan Time, Solomon Islands, Vanuatu',

'UP115' => '(UTC +11:30) Norfolk Island',

'UP12' => '(UTC +12:00) Fiji, Gilbert Islands, Kamchatka Time, New Zealand Standard Time',

'UP1275' => '(UTC +12:45) Chatham Islands Standard Time',

'UP13' => '(UTC +13:00) Samoa Time Zone, Phoenix Islands Time, Tonga',

'UP14' => '(UTC +14:00) Line Islands',

);

// EOF

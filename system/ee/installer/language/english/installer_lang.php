<?php

$lang = array(

// Statuses
'paused'          => 'Paused',
'processing'      => 'Processing',
'completed'       => 'Completed',
'stopped'         => 'Stopped',
'required_fields' => 'Required Fields',
'required_field'  =>'required field',
'subtitle_step'   => 'Step %d of %d',

// Install Form
'install_title' => 'Install ExpressionEngine %s',
'install_note' => 'Read <a href="'.DOC_URL.'installation/installation.html" rel="external">Installing ExpressionEngine</a> <strong>before</strong> starting.',

	// Database Server
	'db_settings'           => 'Database Settings',
	'db_hostname'           => 'Server Address',
	'db_hostname_note'  => 'Commonly <b>localhost</b>, but your host may require something else.',
	'db_name'               => 'DB Name',
	'db_name_note'          => 'Make sure the database exists, the installer will <b>not</b> create it.',
	'db_username'           => 'DB Username',
	'db_password'           => 'DB Password',
	'db_prefix'             => 'Table Prefix',
	'db_prefix_note'        => 'Use <b>exp</b> unless you require/prefer a different prefix.',

	'utf8mb4_not_supported' => 'Your MySQL %s does not support Emoji 😞. Click install if you would like to install anyway, or <a href="'.DOC_URL.'troubleshooting/install_and_update/emoji_support.html" rel="external">read how to fix this before installing</a>.',
	'client'                => 'client',
	'server'                => 'server',
	'and'                   => 'and',

	// Account Creation
	'administrator_account' => 'Administrator Account',
	'username'              => 'Username',
	'e_mail'                => 'Email',
	'password'              => 'Password',

	// Default Theme
	'default_theme'              => 'Default theme',
	'install_default_theme'      => 'Install default theme?',
	'install_default_theme_info' => 'When enabled, ExpressionEngine will install a default theme.',

	// License Agreement
	'license_agreement'          => 'I agree to the license <a href="https://expressionengine.com/license/" rel="external">Terms and Conditions</a>',

	// Share Analytics
	'share_analytics' => 'I want to share analytics with the ExpressionEngine Development Team',
	'share_analytics_desc' => 'EllisLab asks users to help improve ExpressionEngine by occasionally <a href="'.DOC_URL.'cp/settings/security-privacy.html#share-analytics-with-the-expressionengine-development-team" rel="external noreferrer">providing analytics, diagnostic, and usage information</a>.',

	'start_installation'         => 'Install',

// Update Form
	'update_title'   => "Update ExpressionEngine from %s to %s",
	'start_update'   => 'Update',
	'update_note'    => '<b>Please</b> read <a href="'.DOC_URL.'installation/update.html" rel="external">Updating ExpressionEngine</a> <strong>before</strong> starting.',
	'update_backup'  => 'Please <b>back up</b> your database before updating ExpressionEngine',
	'updating_title' => "Updating ExpressionEngine to %s",
	'running_updates' => "Running updates for %s",
	'updating'       => 'Updating ExpressionEngine',

'error'               => 'ERROR',
'submit'              => 'Submit',
'install_failed'      => 'Install Failed',
'update_failed'       => 'Update Failed',
'error_occurred'      => 'Oops, looks like the install couldn\'t&nbsp;complete.',
'retry'               => 'Retry',
'version_update_text' => 'Running update...',

// Errors
'invalid_action'                     => 'The action you have requested is not valid.',
'unreadable_config'                  => 'Your config.php file (<code>system/user/config/config.php</code>) is unreadable. Please make sure the file exists and is writable. See the <a href="'.DOC_URL.'troubleshooting/general/file_permissions.html" rel="external">File Permissions Documentation</a> for more information.',
'unwritable_config'                  => 'Your config.php file (<code>system/user/config/config.php</code>) is not writable. See the <a href="'.DOC_URL.'troubleshooting/general/file_permissions.html" rel="external">File Permissions Documentation</a> for more information.',
'unwritable_templates'               => 'Your template directory (<code>system/user/templates</code>) is not writeable. See the <a href="'.DOC_URL.'troubleshooting/general/file_permissions.html" rel="external">File Permissions Documentation</a> for more information.',
'unwritable_themes_user'             => 'Your user themes directory (<code>themes/user/</code>) is not writeable. See the <a href="'.DOC_URL.'troubleshooting/general/file_permissions.html" rel="external">File Permissions Documentation</a> for more information.',
'json_parser_missing'                => 'Your instance of PHP does not support the <code>json_encode</code> and <code>json_decode</code> methods.',
'fileinfo_missing'                => 'The required Fileinfo PHP extension is not currently enabled.',
'unwritable_cache_folder'            => 'Your cache folder (<code>system/user/cache</code>) is not writable. See the <a href="'.DOC_URL.'troubleshooting/general/file_permissions.html" rel="external">File Permissions Documentation</a> for more information.',
'database_invalid_host'              => 'The database host you submitted is invalid.',
'database_invalid_database'          => 'The database name you submitted is invalid.',
'database_invalid_user'              => 'The database user and password combination you submitted is invalid.',
'database_no_config'                 => 'Unable to connect to your database using the configuration settings found in the following file: config/config.php file. Please correct the settings so that the update can proceed.',
'database_no_data'                   => 'Unable to locate any database connection information.',
'database_no_connect'                => 'Unable to connect to your database using the configuration settings you submitted.',
'database_no_pdo'                    => 'Unable to connect to your database. Please ask your server administrator to enable <a href="http://php.net/manual/en/book.pdo.php">PDO</a>.',
'database_prefix_invalid_characters' => 'There are invalid characters in the database prefix. Only 0-9, a-z, A-Z, $, and _ are allowed.',
'database_prefix_contains_exp_'      => 'The database prefix cannot contain the string "exp_".',
'database_prefix_too_long'           => 'The database prefix cannot be longer than 30 characters.',
'license_agreement_not_accepted'     => 'You must accept the terms and conditions of the license agreement.',
'unreadable_update'                  => 'Unable to read the contents of your /expressionengine/installer/updates folder. Please check the file permissions and re-run this installation wizard.',
'unreadable_files'                   => 'One of your update files is unreadable. Please make sure all of the files located in this folder are readable: system/ee/installer/updates/',
'unreadable_language'                => 'The language files needed for your current language selection (%x) are unavailable. Please put the language pack in this folder: system/user/language/',
'unreadable_email'                   => 'Unable to locate the file containing your email templates (email_data.php). Make sure you have uploaded all components of this software.',
'unreadable_schema'                  => 'Unable to locate the following folder: system/ee/installer/schema/ Please upload all components before proceeding.',
'unreadable_dbdriver'                => 'Unable to locate the database schema file in the following folder: sytem/ee/installer/schema/ Please upload all components before proceeding.',
'improper_grants'                    => 'Error: Unable to perform the SQL queries. Please make sure your SQL account has the proper GRANT privileges: CREATE, DROP, ALTER, INSERT, and DELETE',
'update_error'                       => 'An unexpected error occurred while performing the update',
'update_step_error'                  => 'An unexpected error occured while performing the update. Could not find update step: %x',
'install_detected_msg'               => 'ExpressionEngine appears to already be installed on your database, even though your config file is blank. If you are attempting to <b><i>update</i></b> ExpressionEngine from a previous version restore your config file first, then run this installation wizard again.',
'version_warning'                    => 'In order to install ExpressionEngine, your server must be running PHP version <mark><b>%s</b></mark> or newer. Your server is current running PHP version: <b>%s</b>. Contact your hosting provider to see if newer software is available for your server.',
'error_installing'                   => 'There was an error while installing %s',
'error_updating'                     => 'There was an error while updating %s to %s',
'requirements_checker_not_found'     => 'Could not find RequirementsChecker file.',
'requirements_checker_not_loaded'    => 'Could not load RequirementsChecker class.',

// Install/Upgrade Success
'install_success'       => "Install Complete!",
'install_success_note'  => '<b>ExpressionEngine</b> has been installed.',
'update_success'        => "Update Complete!",
'update_success_note'   => '<b>ExpressionEngine</b> has been updated.',
'success_delete'        => 'Please delete the <kbd>system/ee/installer</kbd> folder from your server before proceeding.',
'success_moved'         => 'The installer folder has been renamed to <kbd>system/ee/installer_%s</kbd>.',
'cp_login'              => 'Log In',
'download_mailing_list' => 'Download Mailing List',

// Surveys
'opt_in_survey'              => 'Opt-in Survey',
'help_with_survey'           => "Please consider helping us make ExpressionEngine better by completing the optional survey for this update.",
'participate_in_survey'      => 'Participate in Survey?',
'send_anonymous_server_data' => 'Send Anonymous Server Data?',
'what_server_data_is_sent'   => '<p>What data is sent? Answering yes to this survey question will transmit the following:</p>
	<ul>
		<li>A one-way hash of your site URL to prevent duplicate submissions (this cannot be used to identify you)</li>
		<li>PHP version and available extensions</li>
		<li>MySQL version, server operating system</li>
		<li>Server software name</li>
		<li>Whether or not you are using forced query strings on your server</li>
		<li>A list of the add-ons in your third party folder</li>
		<li>Whether or not you have Discussion Forums installed</li>
		<li>Whether or not you have Multiple Site Manager installed</li>
	</ul>
	<p>All data is transmitted anonymously and cannot be associated with your local machine, ExpressionEngine user
	account, your site, or your host.</p>',
'show_hide_to_see_server_data' => 'Show/hide the server data that will be sent',
'would_you_recommend'          => 'How likely is it that you will recommend ExpressionEngine to a colleague or friend?',
'unlikely'                     => 'Unlikely',
'highly_likely'                => 'Highly Likely',
'additional_comments'          => 'What could ExpressionEngine or EllisLab do to delight you?',

);

// EOF

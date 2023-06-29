<?php

$lang = array(

    'fatal_error_caught' =>
    'We\'ve encountered an unexpected error. Below is the raw text of the error which you can provide to ExpressionEngine support.',

    'could_not_complete' =>
    'Oops, looks like the updater couldn\'t&nbsp;complete.',

    'we_stopped_on' =>
    'We stopped on <b>%s</b>.',

    'manually_rollback' =>
    '<a href="%s">Click here</a> for instructions to manually rollback from this point.',

    'troubleshoot' =>
    'Troubleshoot, then <a href="#" data-post-url="%s">Continue</a>',

    'or_return_to_cp' =>
    'Or, <a href="%s">return to the control panel</a>.',

    'rollback_to' =>
    'Rollback to %s',

    'cannot_rollback' =>
    'Having trouble rolling back? <a href="%s" target="_blank">Find out</a> how to get your install back up and running again.',

    'view_stack_trace' =>
    'view stack trace',

    'update_stopped' =>
    'Update Stopped',

    'updating_to_from' =>
    'Updating <b>%s</b> from %s to %s',

    'prepMajorUpgrade_step' =>
    'Running checks and preparations',

    'preflight_moving_addons_to_user_folder' =>
    'Moving add-ons to user folder',

    'preflight_verifying_php_version' =>
    'Verifying PHP Version before upgrading to ExpressionEngine 7',

    'preflight_verifying_php_version_error' =>
    'ExpressionEngine 7 requires PHP 7.2.5 or higher.<br>Current PHP version: %s',

    'preflight_step' =>
    'Preflight check',

    'download_step' =>
    'Downloading update',

    'unpack_step' =>
    'Unpacking update',

    'updateFiles_step' =>
    'Updating files',

    'updateAddons_step' =>
    'Checking addons for automatic updates',

    'turnSystemOn_step' =>
    'Turning system on',

    'theme_folder_path_invalid' =>
    'The following theme folder path is not valid:

%s

Please set it to correct value as described in <a href="%s" target="_blank">the documentation</a>.',

    'files_not_writable' =>
    'The following paths are not writable:

%s

To troubleshoot, visit the documentation on <a href="%s" target="_blank">updating ExpressionEngine</a>.',

    'could_not_download' =>
    'Could not download update. Your internet connection may be down, or otherwise cannot reach the ExpressionEngine servers.

Status code returned: %s',

    'unexpected_mime' =>
    'Could not download update. The server returned an unexpected content type:

%s',

    'missing_signature_header' =>
    'Could not verify update file, "Package-Signature" header was not found in the response.',

    'could_not_verify_download' =>
    'Could not verify the signature or integrity of the downloaded update file. Got hash:

%s',

    'try_again_later' =>
    'Try again in a few minutes or contact support if the problem persists.',

    'could_not_unzip' =>
    'Could not unzip update archive. ZipArchive returned error code: %s',

    'could_not_find_files' =>
    'The following files could not be found:

%s',

    'could_not_verify_file_integrity' =>
    'The integrity of the following files could not be verified:

%s',

    'failed_verifying_extracted_archive' =>
    'There was a problem verifying the integrity of the extracted update file.

%s',

    'failed_moving_updater' =>
    'There was a problem moving the updater into place.

%s',

    'requirements_failed' =>
    'Your server has failed the requirements for this version of ExpressionEngine:

- %s

Correct the issues above and try updating again.',

    'out_of_date_admin_php' =>
    'It looks like your ExpressionEngine installation is using an out-of-date /admin.php or /system/index.php file.

Please make sure you have the latest version of each file in place and then try upgrading again.',

    'update_completed' =>
    'ExpressionEngine has been successfully updated to version %s!',

    'update_completed_desc' =>
    'To see what\'s new in ExpressionEngine %s, take a look at the <a href=\'%s\' rel=\'external\'>changelog</a>.',

    'update_rolledback' =>
    'ExpressionEngine has been rolled back to version %s',

    'update_rolledback_desc' =>
    'Contact support if you continue having trouble updating, or you can <a href=\'%s\' rel=\'external\'>manually update</a>.',

    'update_version_warning' => 'Please check system online status',

    'update_version_warning_desc' => 'Your current system status is set to <b>%s</b>. If you need to change that, please visit System Settings.',

    '' => ''
);

// EOF

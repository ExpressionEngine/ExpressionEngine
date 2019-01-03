<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2019, EllisLab Corp. (https://ellislab.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

/*
 * --------------------------------------------------------------------
 *  System Path
 * --------------------------------------------------------------------
 *
 * The following variable contains the server path to your
 * ExpressionEngine "system" folder. This is blank by default,
 * meaning that this file resides in the "system" folder itself.
 *
 * https://ellislab.com/expressionengine/user-guide/installation/best_practices.html
 *
 */
	$system_path = "";

/*
 * --------------------------------------------------------------------
 *  Error Reporting
 * --------------------------------------------------------------------
 *
 * PHP and database errors are normally displayed dynamically based
 * on the authorization level of each user accessing your site.
 * This variable allows the error reporting system to be overridden,
 * which can be useful for low level debugging during site development,
 * since errors happening before a user is authenticated will not normally
 * be shown.  Options:
 *
 *	$debug = 0;  Default setting. Errors shown based on authorization level
 *
 *	$debug = 1;  All errors shown regardless of authorization
 *
 * NOTE: Enabling this override can have security implications.
 * Enable it only if you have a good reason to.
 *
 */
	$debug = 1;


/*
 * --------------------------------------------------------------------
 *  CUSTOM CONFIG VALUES
 * --------------------------------------------------------------------
 */
//	$assign_to_config['cp_url'] = ''; // masked CP access only
//	$assign_to_config['site_name']  = ''; // MSM only


/*
 * --------------------------------------------------------------------
 *  MASKED CP ACCESS
 * --------------------------------------------------------------------
 *
 * This lets the system know whether or not the control panel is being
 * accessed from a location outside the system folder
 *
 * NOTE: If you set this, be sure that you set the $system_path and the
 * 'cp_url' item in the $assign_to_config array above!
 *
 */
//	define('MASKED_CP', TRUE);

/*
 * --------------------------------------------------------------------
 *  END OF USER CONFIGURABLE SETTINGS.  DO NOT EDIT BELOW THIS LINE
 * --------------------------------------------------------------------
 */

/*
 * --------------------------------------------------------------------
 *  Resolve the system path for increased reliability
 * --------------------------------------------------------------------
 */

	$system_path = $system_path ?: __DIR__;

	if (realpath($system_path) !== FALSE)
	{
		$system_path = realpath($system_path);
	}

	$system_path = rtrim($system_path, '/').'/';

/*
 * --------------------------------------------------------------------
 *  Now that we know the path, set the main constants
 * --------------------------------------------------------------------
 */

	// The name of this file
	define('SELF', basename(__FILE__));

	// Path to this file
	define('FCPATH', __DIR__.'/');

	// Path to the "system" folder
	define('SYSPATH', $system_path);

	// Name of the "system folder"
	define('SYSDIR', basename($system_path));

	// The $debug value as a constant for global access
	define('DEBUG', $debug);  unset($debug);

 	// The control panel access constant ensures the CP will be invoked.
	define('REQ', 'CP');

/*
 * --------------------------------------------------------------------
 *  Set the error reporting level
 * --------------------------------------------------------------------
 */
	if (DEBUG == 1)
	{
		error_reporting(E_ALL);
		@ini_set('display_errors', 1);
	}
	else
	{
		error_reporting(0);
	}

/*
 *---------------------------------------------------------------
 * LOAD THE BOOTSTRAP FILE
 *---------------------------------------------------------------
 *
 * And away we go...
 *
 */
	// Load the updater package if it's here
	if (file_exists(SYSPATH.'ee/updater/boot.php'))
	{
		require_once SYSPATH.'ee/updater/boot.php';
	}
	// Is the system path correct?
	elseif ( ! file_exists(SYSPATH.'ee/EllisLab/ExpressionEngine/Boot/boot.php'))
	{
		header('HTTP/1.1 503 Service Unavailable.', TRUE, '503');
		exit("Your system folder path does not appear to be set correctly. Please open the following file and correct this: ".pathinfo(__FILE__, PATHINFO_BASENAME));
	}
	else
	{
		require_once SYSPATH.'ee/EllisLab/ExpressionEngine/Boot/boot.php';
	}

// EOF

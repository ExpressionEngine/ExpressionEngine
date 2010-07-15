<?php
/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		ExpressionEngine Dev Team
 * @copyright	Copyright (c) 2003 - 2010, EllisLab, Inc.
 * @license		http://expressionengine.com/user_guide/license.html
 * @link		http://expressionengine.com
 * @since		Version 2.0
 */

/*
 * --------------------------------------------------------------------
 *  SYSTEM PATH
 * --------------------------------------------------------------------
 *
 * The following variable contains the server path to your
 * ExpressionEngine "system" folder.  By default the folder is named
 * "system" but it can be renamed for increased security.  If you
 * have changed the name of the system folder please indicate the
 * new name here, and include the server path.  The path can be a
 * simple relative path or it can be a full server path.
 * 
 */
	$system_path = './system';

/*
 * --------------------------------------------------------------------
 *  ERROR REPORTING OVERRIDE
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
	$debug = 0;

/*
 * --------------------------------------------------------------------
 *  CUSTOM CONFIG VALUES
 * --------------------------------------------------------------------
 *
 * The $assign_to_config array below will be passed dynamically to the
 * config class. This allows you to set custom config items or override
 * any default config values found in the config.php file.  This can
 * be handy as it permits you to share one application between more then
 * one front controller file, with each file containing different 
 * config values.
 *
 * Un-comment the $assign_to_config array below to use this feature
 *
 * NOTE: This feature can be used to run multiple EE "sites" using
 * the old style method.  Instead of individual variables you'll
 * set array indexes corresponding to them.
 *
 */
//	$assign_to_config['template_group'] = '';
//	$assign_to_config['template'] = '';
//	$assign_to_config['site_index'] = '';
//	$assign_to_config['site_404'] = '';
//	$assign_to_config['global_vars'] = array(); // This array must be associative

/**
 * If you are running the Multiple Site Manager, uncomment and assign the following variables 
 * See http://expressionengine.com/user_guide/cp/sites/domainsetup.html
 */
//	$assign_to_config['site_name'] = '';
//	$assign_to_config['cp_url'] = '';
//	$assign_to_config['site_url'] = '';

/*
 * --------------------------------------------------------------------
 *  END OF USER CONFIGURABLE SETTINGS.  DO NOT EDIT BELOW THIS LINE
 * --------------------------------------------------------------------
 */


/*
 * ---------------------------------------------------------------
 *  Disable all routing, send everything to the frontend
 * ---------------------------------------------------------------
 */
	$routing['directory'] = '';
	$routing['controller'] = 'ee';
	$routing['function'] = 'index';

/*
 * --------------------------------------------------------------------
 *  Mandatory config overrides
 * --------------------------------------------------------------------
 */
	$assign_to_config['enable_query_strings'] = TRUE;
	$assign_to_config['subclass_prefix'] = 'EE_';

/*
 * --------------------------------------------------------------------
 *  Resolve the system path for increased reliability
 * --------------------------------------------------------------------
 */

	if (realpath($system_path) !== FALSE)
	{
		$system_path = realpath($system_path).'/';
	}

	// ensure there's a trailing slash
	$system_path = rtrim($system_path, '/').'/';

	// Is the sytsem path correct?
	if ( ! is_dir($system_path))
	{
		exit("Your system folder path does not appear to be set correctly. Please open the following file and correct this: ".pathinfo(__FILE__, PATHINFO_BASENAME));
	}

/*
 * --------------------------------------------------------------------
 *  Now that we know the path, set the main constants
 * --------------------------------------------------------------------
 */	
	// The name of THIS file
	define('SELF', pathinfo(__FILE__, PATHINFO_BASENAME));

	// The PHP file extension
	define('EXT', '.php');

 	// Path to the system folder
	define('BASEPATH', str_replace("\\", "/", $system_path.'codeigniter/system/'));
	
	// Path to the "application" folder
	define('APPPATH', $system_path.'expressionengine/');
	
	// Path to the front controller (this file)
	define('FCPATH', str_replace(SELF, '', __FILE__));
	
	// Name of the "system folder"
	define('SYSDIR', trim(strrchr(trim(str_replace("\\", "/", $system_path), '/'), '/'), '/'));

	// The $debug value as a constant for global access
	define('DEBUG', $debug);  unset($debug);

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
 * --------------------------------------------------------------------
 *  Is the request a URL redirect redirect?
 * --------------------------------------------------------------------
 *
 * All external links that appear in the ExpressionEngine control panel
 * are redirected to this index.php file first, before being sent to the
 * final destination, so that the location of the control panel will not 
 * end up in the referrer logs of other sites.
 *
 */	
	if (isset($_GET['URL'])) 
	{ 
		if ( ! file_exists(APPPATH.'libraries/Redirect'.EXT))
		{
			exit('Some components appear to be missing from your ExpressionEngine installation.');	
		}
		require(APPPATH.'libraries/Redirect'.EXT);

		exit();  // We halt system execution since we're done
	}

/*
 *---------------------------------------------------------------
 * LOAD THE BOOTSTRAP FILE
 *---------------------------------------------------------------
 *
 * And away we go...
 *
 */
	require_once BASEPATH.'core/CodeIgniter'.EXT;

/* End of file index.php */
/* Location: ./index.php */

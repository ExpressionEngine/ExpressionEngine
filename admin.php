<?php
/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		ExpressionEngine Dev Team
 * @copyright	Copyright (c) 2003 - 2011, EllisLab, Inc.
 * @license		http://expressionengine.com/user_guide/license.html
 * @link		http://expressionengine.com
 * @since		Version 2.0
 */

/*
 * --------------------------------------------------------------------
 *  System Path
 * --------------------------------------------------------------------
 *
 * The following variable contains the server path to your
 * ExpressionEngine "system" folder.  By default the folder is named
 * "system" but it can be renamed or moved for increased security.
 * Indicate the new name and/or path here. The path can be relative
 * or it can be a full server path.
 *
 * http://expressionengine.com/user_guide/installation/best_practices.html
 * 
 */
	$system_path = './system';


/*
 * --------------------------------------------------------------------
 *  Multiple Site Manager
 * --------------------------------------------------------------------
 *
 * Uncomment the following variables if you are using the Multiple
 * Site Manager: http://expressionengine.com/user_guide/cp/sites
 *
 * The variables set the Short Name of the site this admin.php file
 * will log into, and its URL.
 *
 */
//	$assign_to_config['site_name']  = 'domain2_short_name';
//	$assign_to_config['cp_url'] = 'http://domain2.com/admin.php';


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
 *  END OF USER CONFIGURABLE SETTINGS.  DO NOT EDIT BELOW THIS LINE
 * --------------------------------------------------------------------
 */


	define('MASKED_CP', TRUE);

/*
 * --------------------------------------------------------------------
 *  Mandatory config overrides
 * --------------------------------------------------------------------
 */
	$assign_to_config['enable_query_strings'] = TRUE;
	$assign_to_config['subclass_prefix'] = 'EE_';
	$assign_to_config['directory_trigger'] = 'D';	
	$assign_to_config['controller_trigger'] = 'C';	
	$assign_to_config['function_trigger'] = 'M';

/*
 * --------------------------------------------------------------------
 *  Resolve the system path for increased reliability
 * --------------------------------------------------------------------
 */
	if ($system_path == '')
	{
		$system_path = pathinfo(__FILE__, PATHINFO_DIRNAME);
	}

	if (realpath($system_path) !== FALSE)
	{
		$system_path = realpath($system_path).'/';
	}
	
	// ensure there's a trailing slash
	$system_path = rtrim($system_path, '/').'/';

/*
 * --------------------------------------------------------------------
 *  Now that we know the path, set the main constants
 * --------------------------------------------------------------------
 */	
	// The PHP file extension
	define('EXT', '.php');
	
	// The name of THIS file
	define('SELF', pathinfo(__FILE__, PATHINFO_BASENAME));

 	// Path to the system folder
	define('BASEPATH', str_replace("\\", "/", $system_path.'codeigniter/system/'));
	
	// Path to the front controller (this file)
	define('FCPATH', str_replace(SELF, '', __FILE__));
	
	// Name of the "system folder"
	define('SYSDIR', trim(strrchr(trim(str_replace("\\", "/", $system_path), '/'), '/'), '/'));

	// The $debug value as a constant for global access
	define('DEBUG', $debug);  unset($debug);

/*
* --------------------------------------------------------------------
 *  EE Control Panel Constants
 * -------------------------------------------------------------------
 *
 * If the "installer" folder exists the $config['install_lock'] is off
 * we will load the installation wizard.  Otherwise we load the CP
 *
 */ 
 	// Is the installation folder present?
	if (is_dir($system_path.'installer/'))
	{
		// We need a different subclass prefix when we run the installer.
		// The reason is because the installer has it's on Config class extension
		// containing some specific functions needed by the installer.  By 
		// setting a unique prefix we can also load the main Config class extension
		// without a naming conflict
		$assign_to_config['subclass_prefix']	= 'Installer_';
		
		// This allows the installer application to be inside our normal EE application directory
		define('APPPATH', $system_path.'installer/');
		define('EE_APPPATH', $system_path.'expressionengine/');
	}
	else
	{
		define('APPPATH', $system_path.'expressionengine/');
	}

 	// The control panel access constant.  Without this, the CP will not be invoked
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
	// Is the system path correct?
	if ( ! file_exists(BASEPATH.'core/CodeIgniter'.EXT))
	{
		exit("Your system folder path does not appear to be set correctly. Please open the following file and correct this: ".pathinfo(__FILE__, PATHINFO_BASENAME));	
	}

	require_once BASEPATH.'core/CodeIgniter'.EXT;


/* End of file index.php */
/* Location: ./system/index.php */

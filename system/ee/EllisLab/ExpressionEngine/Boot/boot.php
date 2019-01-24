<?php  if ( ! defined('SYSPATH')) exit('No direct script access allowed');
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2019, EllisLab Corp. (https://ellislab.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

/*
 * ------------------------------------------------------
 *  Set and load the framework constants
 *
 *  BASEPATH - path to the legacy app folder. Most legacy
 *             files check for this (`if ! defined ...`)
 * ------------------------------------------------------
 */
	define('BASEPATH', SYSPATH.'ee/legacy/');

	// load user configurable constants
	$constants = require SYSPATH.'ee/EllisLab/ExpressionEngine/Config/constants.php';

	if (file_exists(SYSPATH.'user/config/constants.php'))
	{
		$user_constants = include SYSPATH.'user/config/constants.php';
		$constants = array_merge($constants, $user_constants);
	}

	foreach ($constants as $k => $v)
	{
		define($k, $v);
	}

/*
 * ------------------------------------------------------
 *  Load the autoloader and register it
 * ------------------------------------------------------
 */
	require SYSPATH.'ee/EllisLab/ExpressionEngine/Core/Autoloader.php';

	EllisLab\ExpressionEngine\Core\Autoloader::getInstance()
		->addPrefix('EllisLab', SYSPATH.'ee/EllisLab/')
		->addPrefix('Michelf', SYSPATH.'ee/legacy/libraries/typography/Markdown/Michelf/')
		->addPrefix('Mexitek', SYSPATH.'ee/Mexitek/')
		->register();

/*
 * ------------------------------------------------------
 *  Load the global functions
 * ------------------------------------------------------
 */
	require __DIR__.'/boot.common.php';

/*
 * ------------------------------------------------------
 *  Define a custom error handler so we can log PHP errors
 * ------------------------------------------------------
 */
	set_error_handler('_exception_handler');

/*
 * ------------------------------------------------------
 *  Check for the installer if we're booting the CP
 * ------------------------------------------------------
 */
	use EllisLab\ExpressionEngine\Core;

	if (
		defined('REQ') && in_array(REQ, ['CP', 'CLI']) &&
		is_dir(SYSPATH.'ee/installer/') &&
		( ! defined('INSTALL_MODE') OR INSTALL_MODE != FALSE)
	)
	{
		$core = new Core\Installer();
	}
	else
	{
		$core = new Core\ExpressionEngine();
	}

/*
 * ------------------------------------------------------
 *  Boot the core
 * ------------------------------------------------------
 */
	$core->boot();

/*
 * ------------------------------------------------------
 *  Set config items from the index.php file
 * ------------------------------------------------------
 */
	if (isset($assign_to_config))
	{
		$core->overrideConfig($assign_to_config);
	}

/*
 * ------------------------------------------------------
 *  Set routing overrides from the index.php file
 * ------------------------------------------------------
 */
	if (isset($routing))
	{
		$core->overrideRouting($routing);
	}

/*
 * ------------------------------------------------------
 *  Create global helper functions
 *
 *  Using `CI` for the global name, just in case someone
 *  is relying on that instead of get_instance()
 * ------------------------------------------------------
 */
	$CI = $core->getLegacyApp()->getFacade();

	function get_instance()
	{
		global $CI;
		return $CI;
	}

	function ee($dep = NULL)
	{
		$facade = get_instance();

		if (isset($dep) && isset($facade->di))
		{
			$args = func_get_args();
			return call_user_func_array(array($facade->di, 'make'), $args);
			return $facade->di->make($dep);
		}

		return $facade;
	}

/*
 * ------------------------------------------------------
 *  Parse the request
 * ------------------------------------------------------
 */
	$request = Core\Request::fromGlobals();

/*
 * ------------------------------------------------------
 *  Run the request and get a response
 * ------------------------------------------------------
 */
	$response = $core->run($request);

/*
 * ------------------------------------------------------
 *  Send the response
 * ------------------------------------------------------
 */
	if ($response)
	{
		$response->send();
	}

// EOF

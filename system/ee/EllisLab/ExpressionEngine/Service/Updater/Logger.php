<?php

namespace EllisLab\ExpressionEngine\Service\Updater;

use EllisLab\ExpressionEngine\Service\Logger\File;

/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2016, EllisLab, Inc.
 * @license		https://ellislab.com/expressionengine/user-guide/license.html
 * @link		http://ellislab.com
 * @since		Version 4.0
 * @filesource
 */

// ------------------------------------------------------------------------

/**
 * ExpressionEngine Updater Logger class
 *
 * @package		ExpressionEngine
 * @subpackage	Updater
 * @category	Service
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */
class Logger extends File {

	/**
	 * Formats the log message with pertanent information before
	 * sending it to the logger
	 *
	 * @param	string	$message	Message to log
	 */
	public function log($message)
	{
		// TODO: Add memory usage
		$message = '['.date('Y-M-d H:i:s O').'] ' . $message;

		parent::log($message);
	}
}

// EOF

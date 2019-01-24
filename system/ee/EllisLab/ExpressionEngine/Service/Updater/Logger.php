<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2019, EllisLab Corp. (https://ellislab.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace EllisLab\ExpressionEngine\Service\Updater;

use EllisLab\ExpressionEngine\Service\Logger\File;

/**
 * Updater Logger class
 *
 * Extends the File updater to also send messages to stdout if necessary, and
 * also adds a timestamp to the message
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
		if (REQ == 'CLI' && CLI_VERBOSE)
		{
			stdout($message);
		}

		$message = '['.date('Y-M-d H:i:s O').'] ' . $message;

		parent::log($message);
	}
}

// EOF

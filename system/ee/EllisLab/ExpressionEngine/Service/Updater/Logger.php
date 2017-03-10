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

	const NORMAL = 1;
	const SUCCESS = 2;
	const FAILURE = 3;
	const SUBDUED = 4;

	/**
	 * Formats the log message with pertanent information before
	 * sending it to the logger
	 *
	 * @param	string	$message	Message to log
	 */
	public function log($message)
	{
		if (defined('CLI_VERBOSE') && CLI_VERBOSE)
		{
			$this->stdout($message, self::SUBDUED);
		}

		$message = '['.date('Y-M-d H:i:s O').'] ' . $message;

		parent::log($message);
	}

	/**
	 * Sends a message to stdout if we're in the CLI
	 *
	 * @param	string	$message	Message to display
	 * @param	const	$status		Status of message, affects appearance
	 */
	public function stdout($message, $status = self::NORMAL)
	{
		switch ($status) {
			case self::SUCCESS:
				$arrow = '[0;32m';
				$text = '[1;37m';
				break;
			case self::SUBDUED:
				$arrow = $text = '[0;37m';
				break;
			default:
				$arrow = '[0;34m';
				$text = '[1;37m';
				break;
		}

		if (REQ == 'CLI' && ! empty($message))
		{
			echo "\033".$arrow."==> \033" . $text . $message . "...\n";
		}
	}
}

// EOF

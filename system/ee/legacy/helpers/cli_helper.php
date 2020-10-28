<?php

/**
 * Sends a message to stdout if we're in the CLI
 *
 * @param	string	$message	Message to display
 * @param	const	$status		Status of message, affects appearance
 */

defined('CLI_STDOUT_NORMAL') || define('CLI_STDOUT_NORMAL', 1);
defined('CLI_STDOUT_BOLD') || define('CLI_STDOUT_BOLD', 2);
defined('CLI_STDOUT_SUCCESS') || define('CLI_STDOUT_SUCCESS', 3);
defined('CLI_STDOUT_FAILURE') || define('CLI_STDOUT_FAILURE', 4);

if( ! function_exists('stdout') ) {

	function stdout($message, $status = CLI_STDOUT_NORMAL)
	{
		$text_color = '[1;37m';

		switch ($status) {
			case CLI_STDOUT_BOLD:
				$arrow_color = '[0;34m';
				$text_color = '[1;37m';
				break;
			case CLI_STDOUT_SUCCESS:
				$arrow_color = '[0;32m';
				break;
			case CLI_STDOUT_FAILURE:
				$arrow_color = '[0;31m';
				break;
			default:
				$arrow_color = $text_color = '[0m';
				break;
		}

		if (REQ == 'CLI' && ! empty($message))
		{

			$message = "\033".$arrow_color."==> \033" . $text_color . strip_tags($message) . "\033[0m\n";

			$stdout = fopen('php://stdout', 'w');

			fwrite($stdout, $message);

			fclose($stdout);

		}

	}

}
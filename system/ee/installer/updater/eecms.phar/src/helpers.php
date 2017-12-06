<?php

/**
 * Helper function to parse the global $argv into something useful; we cannot
 * use getopt() as we want to support subcommands
 *
 * @return array
 */
function parseArguments()
{
	global $argv;
	array_shift($argv);
	$out = array();
	foreach ($argv as $arg)
	{
		if (substr($arg, 0, 2) == '--')
		{
			$eqPos = strpos($arg, '=');
			if ($eqPos === false)
			{
				$key = substr($arg, 2);
				$out[$key] = isset($out[$key]) ? $out[$key] : true;
			}
			else
			{
				$key = substr($arg, 2, $eqPos - 2);
				$out[$key] = substr($arg, $eqPos + 1);
			}
		}
		else if (substr($arg, 0, 1) == '-')
		{
			if (substr($arg, 2, 1) == '=')
			{
				$key = substr($arg, 1, 1);
				$out[$key] = substr($arg, 3);
			}
			else
			{
				$chars = str_split(substr($arg, 1));
				foreach ($chars as $char)
				{
					$key = $char;
					$out[$key] = isset($out[$key]) ? $out[$key] : true;
				}
			}
		}
		else
		{
			$out[] = $arg;
		}
	}
	return $out;
}

/**
 * Runs a an eecms.phar command externally from the current process/scope
 *
 * @param	string	$command	Command to run, string that normally follows
 *   "eecms.phar" on the command line
 */
function runCommandExternally($command)
{
	if (CLI_VERBOSE)
	{
		$command .= ' -v';
	}

	system('php '.SYSPATH.'ee/eecms ' . $command);
}


define('CLI_STDOUT_NORMAL', 1);
define('CLI_STDOUT_BOLD', 2);
define('CLI_STDOUT_SUCCESS', 3);
define('CLI_STDOUT_FAILURE', 4);

/**
 * Sends a message to stdout if we're in the CLI
 *
 * @param	string	$message	Message to display
 * @param	const	$status		Status of message, affects appearance
 */
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

// EOF

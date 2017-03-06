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
	foreach($argv as $arg)
	{
		if(substr($arg, 0, 2) == '--')
		{
			$eqPos = strpos($arg, '=');
			if($eqPos === false)
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
		else if(substr($arg, 0, 1) == '-')
		{
			if(substr($arg, 2, 1) == '=')
			{
				$key = substr($arg, 1, 1);
				$out[$key] = substr($arg, 3);
			}
			else
			{
				$chars = str_split(substr($arg, 1));
				foreach($chars as $char)
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
	system('php '.SYSPATH.'ee/eecms.phar ' . $command);
}

// EOF

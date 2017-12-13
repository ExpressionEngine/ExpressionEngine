<?php

require('bootstrap.php');

$command = array_shift($argv);

$longopts = array(
	"count:",
	"timestamp-min:",
	"timestamp-max:",
	"description:",
	"help",
);

$options = getopt('h', $longopts);

if (isset($options['h']) || isset($options['help']))
{
	print <<<EOF
Usage: {$command} [options]
	--help                   This help message
	--count         <number> The number of developer logs to generate
	--timestamp-min <number> The minimum number of hours to subtract from "now"
	--timestamp-max <number> The maximum number of hours to subtract from "now"
	--description   <string> The description to use (forces description only logs)
EOF;
	exit();
}

$count = isset($options['count']) && is_numeric($options['count']) ? (int) $options['count'] : 20;
$timestamp_min = isset($options['timestamp-min']) && is_numeric($options['timestamp-min']) ? (int) $options['timestamp-min'] : 0;
$timestamp_max = isset($options['timestamp-max']) && is_numeric($options['timestamp-max']) ? (int) $options['timestamp-max'] : 24*60; // 2 months

$description = "Gibberish";
$description_only = FALSE;

if (isset($options['description']))
{
	$description = $options['description'];
	$description_only = TRUE;
}

/**
 * Types of Logs:
 *   (0) 1. Description only
 *   (1) 2. Function
 *   (2)    a. With file and line
 *   (3)    b. With addon_module and addon_method
 *   (4)      i. With snippets
 *   (5)    a. With deprecated_since
 *   (6)    a. With deprecated_use_instead
 */

for ($x = 0; $x < $count; $x++)
{
	$type = rand(0, 6);

	$fixture = ee('Model')->make('DeveloperLog');
	$fixture->timestamp = strtotime("-" . rand($timestamp_min*60, $timestamp_max*60) . " minutes");

	if ($type == 0 || $description_only)
	{
		$fixture->description = $description;
	}
	else
	{
		$fixture->function = "foo_bar()";

		if ($type >= 2 && rand(0, 1) == 1)
		{
			$fixture->line = rand(0, 100);
			$fixture->file = "system/expressionengine/third_party/foobar/foo_bar.php";
		}

		if ($type >= 3 && rand(0, 1) == 1)
		{
			$fixture->addon_module = "foo";
			$fixture->addon_method = "bar";
			$fixture->template_id = 1;
			$fixture->template_name = "index";
			$fixture->template_group = "site";

			if ($type == 4)
			{
				$fixture->snippets = 'foo|bar|baz';
			}
		}

		if ($type == 5)
		{
			$fixture->deprecated_since = rand(1, 2) . '.' . rand(0, 9);
		}

		if ($type == 6)
		{
			$fixture->use_instead = "Foo::bar()";
		}

	}

	$fixture->hash = md5(rand());
	$fixture->save();
}

<?php

require('bootstrap.php');

$count = isset($argv[1]) && is_numeric($argv[1]) ? (int) $argv[1] : 125;

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

	$fixture = $api->make('DeveloperLog');
	$fixture->timestamp = strtotime("-" . rand(0, 24*60) . " hours");

	if ($type == 0)
	{
		$fixture->description = "Gibberish";
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
			$fixture->template_name = "site";
			$fixture->template_group = "index";

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
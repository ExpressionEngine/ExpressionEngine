<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2019, EllisLab Corp. (https://ellislab.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

/**
 * Emoji Character Map
 */

global $el_config__emoji_map;

if (empty($el_config__emoji_map))
{
	// sourced from https://github.com/iamcal/emoji-data, MIT License
	// currently at v4.0.4 2018-04-16 build
	$emoji = @json_decode(file_get_contents(SYSPATH.'ee/EllisLab/ExpressionEngine/Config/emoji.json'));

	if (empty($emoji))
	{
		$el_config__emoji_map = [];
		return $el_config__emoji_map;
	}

	// index by :short_name: and handle aliases
	foreach ($emoji as $em)
	{
		$el_config__emoji_map[$em->short_name] = $em;

		if (strpos($em->unified, '-'))
		{
			$el_config__emoji_map[$em->short_name]->html_entity = '&#x'.implode(';&#x', explode('-', $em->unified)).';';
		}
		else
		{
			$el_config__emoji_map[$em->short_name]->html_entity = '&#x'.$em->unified.';';
		}

		foreach ($em->short_names as $short_name)
		{
			if ( ! isset($el_config__emoji_map[$short_name]))
			{
				$el_config__emoji_map[$short_name] = clone $em;
			}
		}
	}
}

return $el_config__emoji_map;

// EOF

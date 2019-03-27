<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2019, EllisLab Corp. (https://ellislab.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace EllisLab\ExpressionEngine\Library\CP\EntryManager\Columns;

use EllisLab\ExpressionEngine\Library\CP\EntryManager\Columns\Column;

/**
 * Channel Name Column
 */
class ChannelName extends Column
{
	public function getTableColumnLabel()
	{
		return 'channel';
	}

	public function renderTableCell($entry)
	{
		return ee('Format')->make('Text', $entry->Channel->channel_title)->convertToEntities();
	}
}

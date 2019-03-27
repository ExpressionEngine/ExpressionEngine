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
 * Entry Date Column
 */
class EntryDate extends Column
{
	public function getTableColumnLabel()
	{
		return 'column_entry_date';
	}

	public function renderTableCell($entry)
	{
		return ee()->localize->human_time($entry->entry_date);
	}
}

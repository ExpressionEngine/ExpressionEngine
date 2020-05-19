<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2020, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Library\CP\EntryManager\Columns;

use ExpressionEngine\Library\CP\EntryManager\Columns\Column;

/**
 * Author Column
 */
class Author extends Column
{
	public function getTableColumnLabel()
	{
		return 'author';
	}

	public function renderTableCell($entry)
	{
		return ee('Format')->make('Text', $entry->getAuthorName())->convertToEntities();
	}
}

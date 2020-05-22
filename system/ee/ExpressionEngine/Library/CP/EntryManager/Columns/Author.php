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
	public function __construct($identifier) {
		parent::__construct($identifier);
		$this->fields = ['author_id', 'Author.screen_name', 'Author.username'];
		$this->models = ['Author'];
		$this->sort_field = 'author_id';
	}

	public function getTableColumnLabel()
	{
		return 'author';
	}

	public function renderTableCell($custom_field_data = null, $custom_field_id = null, $entry)
	{
		return ee('Format')->make('Text', $entry->getAuthorName())->convertToEntities();
	}
}

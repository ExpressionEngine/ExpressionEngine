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
use ExpressionEngine\Library\CP\Table;
use Mexitek\PHPColors\Color;

/**
 * Status Column
 */
class Categories extends Column
{
	public function __construct($identifier) {
		parent::__construct($identifier);
		$this->fields = ['Categories.cat_name'];
		$this->models = ['Categories'];
	}

	public function getTableColumnLabel()
	{
		return 'column_categories';
	}

	public function getTableColumnConfig()
	{
		return [
			'type'	=> Table::COL_INFO
		];
	}

	public function renderTableCell($custom_field_data = null, $custom_field_id = null, $entry)
	{
		$categories = $entry->Categories->getDictionary('cat_id', 'cat_name');

		return implode(", ", $categories);
	}
}

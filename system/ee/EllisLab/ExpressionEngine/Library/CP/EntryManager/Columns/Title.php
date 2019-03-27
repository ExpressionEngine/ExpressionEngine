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
 * Title Column
 */
class Title extends Column
{
	public function getTableColumnLabel()
	{
		return 'column_title';
	}

	public function getTableColumnConfig()
	{
		return [
			'encode' => FALSE
		];
	}

	public function renderTableCell($entry)
	{
		$title = ee('Format')->make('Text', $entry->title)->convertToEntities();

		if ($this->canEdit($entry))
		{
			$edit_link = ee('CP/URL')->make('publish/edit/entry/' . $entry->entry_id);
			$title = '<a href="' . $edit_link . '">' . $title . '</a>';
		}

		if ($entry->Autosaves->count())
		{
			$title .= ' <span class="auto-save" title="' . lang('auto_saved') . '">&#10033;</span>';
		}

		return $title;
	}
}

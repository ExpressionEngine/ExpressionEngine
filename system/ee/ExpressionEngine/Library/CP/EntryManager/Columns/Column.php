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

use ExpressionEngine\Library\CP\EntryManager;

/**
 * Abstract Column class
 */
abstract class Column implements EntryManager\ColumnInterface
{
	protected $identifier;

	public function __construct($identifier)
	{
		$this->identifier = $identifier;
	}

	public function getTableColumnIdentifier()
	{
		return $this->identifier;
	}

	public function renderTableCell($entry)
	{
		return $this->renderCell($entry);
	}

	public function getTableColumnConfig()
	{
		return [];
	}

	protected function canEdit($entry)
	{
		return (ee()->cp->allowed_group('can_edit_other_entries')
				|| (ee()->cp->allowed_group('can_edit_self_entries') &&
					$entry->author_id == ee()->session->userdata('member_id')));
	}

	protected function canDelete($entry)
	{
		return (ee()->cp->allowed_group('can_delete_all_entries')
				|| (ee()->cp->allowed_group('can_delete_self_entries') &&
					$entry->author_id == ee()->session->userdata('member_id')));
	}
}

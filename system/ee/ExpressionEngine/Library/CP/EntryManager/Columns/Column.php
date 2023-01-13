<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
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

    public function getEntryManagerColumnModels()
    {
        return [];
    }

    public function getEntryManagerColumnFields()
    {
        return [];
    }

    public function getEntryManagerColumnSortField()
    {
        return $this->identifier;
    }

    public function getTableColumnIdentifier()
    {
        return $this->identifier;
    }

    public function renderTableCell($data, $field_id, $entry)
    {
        return '';
    }

    public function getTableColumnConfig()
    {
        return [];
    }

    protected function canEdit($entry)
    {
        return (ee('Permission')->can('edit_other_entries_channel_id_' . $entry->channel_id)
            || (ee('Permission')->can('edit_self_entries_channel_id_' . $entry->channel_id) &&
                $entry->author_id == ee()->session->userdata('member_id')));
    }

    protected function canDelete($entry)
    {
        return (ee('Permission')->can('delete_all_entries_channel_id_' . $entry->channel_id)
                || (ee('Permission')->can('delete_self_entries_channel_id_' . $entry->channel_id) &&
                    $entry->author_id == ee()->session->userdata('member_id')));
    }
}

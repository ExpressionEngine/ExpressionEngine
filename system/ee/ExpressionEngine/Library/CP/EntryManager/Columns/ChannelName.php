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

use ExpressionEngine\Library\CP\EntryManager\Columns\Column;

/**
 * Channel Name Column
 */
class ChannelName extends Column
{
    public function getEntryManagerColumnModels()
    {
        return ['Channel'];
    }

    public function getEntryManagerColumnFields()
    {
        return ['Channel.channel_title'];
    }

    public function getEntryManagerColumnSortField()
    {
        return 'channel_id';
    }

    public function getTableColumnLabel()
    {
        return 'channel';
    }

    public function renderTableCell($data, $field_id, $entry)
    {
        return ee('Format')->make('Text', $entry->Channel->channel_title);
    }
}

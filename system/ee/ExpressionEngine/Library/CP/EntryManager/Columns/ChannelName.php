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
 * Channel Name Column
 */
class ChannelName extends Column
{
	public function __construct($identifier) {
		parent::__construct($identifier);
		$this->fields = ['Channel.channel_title'];
		$this->models = ['Channel'];
		$this->sort_field = 'channel_id';
	}

	public function getTableColumnLabel()
	{
		return 'channel';
	}

	public function renderTableCell($custom_field_data = null, $custom_field_id = null, $entry)
	{
		return ee('Format')->make('Text', $entry->Channel->channel_title)->convertToEntities();
	}
}

<?php

namespace EllisLab\ExpressionEngine\Model\Channel\Gateway;

use EllisLab\ExpressionEngine\Model\Content\VariableColumnGateway;

class ChannelDataGateway extends VariableColumnGateway {

	protected static $_table_name = 'channel_data';
	protected static $_primary_key = 'entry_id';

	// Properties
	public $entry_id;
	public $channel_id;
	public $site_id;

}

// EOF

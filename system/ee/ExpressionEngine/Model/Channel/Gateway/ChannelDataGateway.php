<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Model\Channel\Gateway;

use ExpressionEngine\Model\Content\VariableColumnGateway;

/**
 * Channel Data Gateway
 */
class ChannelDataGateway extends VariableColumnGateway
{
    protected static $_table_name = 'channel_data';
    protected static $_primary_key = 'entry_id';
    protected static $_gateway_model = 'ChannelField'; // model that defines elements fetched by this gateway

    // Properties
    public $entry_id;
    public $channel_id;
    public $site_id;
}

// EOF

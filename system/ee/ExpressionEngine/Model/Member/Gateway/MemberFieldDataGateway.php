<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Model\Member\Gateway;

use ExpressionEngine\Model\Content\VariableColumnGateway;

/**
 * Member Field Data Table
 */
class MemberFieldDataGateway extends VariableColumnGateway
{
    protected static $_table_name = 'member_data';
    protected static $_primary_key = 'member_id';
    protected static $_gateway_model = 'MemberField'; // model that defines elements fetched by this gateway

    protected static $_related_gateways = array(
        'member_id' => array(
            'gateway' => 'MemberGateway',
            'key' => 'member_id'
        )
    );

    // Properties
    protected $member_id;
}

// EOF

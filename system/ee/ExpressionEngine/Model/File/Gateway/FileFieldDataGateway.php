<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Model\File\Gateway;

use ExpressionEngine\Model\Content\VariableColumnGateway;

/**
 * File Field Data Table
 */
class FileFieldDataGateway extends VariableColumnGateway
{
    protected static $_table_name = 'file_data';
    protected static $_primary_key = 'file_id';
    protected static $_gateway_model = 'FileField'; // model that defines elements fetched by this gateway

    protected static $_related_gateways = array(
        'file_id' => array(
            'gateway' => 'FileGateway',
            'key' => 'file_id'
        )
    );

    // Properties
    protected $file_id;
}

// EOF

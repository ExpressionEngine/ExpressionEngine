<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Addons\Rte\Model;

use ExpressionEngine\Service\Model\Model;

class Toolset extends Model
{
    protected static $_primary_key = 'toolset_id';
    protected static $_table_name = 'rte_toolsets';

    protected static $_typed_columns = array(
        'settings' => 'base64Serialized',
    );

    protected static $_validation_rules = array(
        'toolset_name' => 'required|xss|noHtml|unique',
    );

    protected $toolset_id;
    protected $toolset_type;
    protected $toolset_name;
    protected $settings;
}

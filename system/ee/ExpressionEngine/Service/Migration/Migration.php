<?php

/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Service\Migration;

/**
 * Migration abstract class
 */
abstract class Migration
{
    public function __construct()
    {
        ee()->load->database();
        ee()->load->dbforge();
    }

    abstract public function up();
    abstract public function down();
}

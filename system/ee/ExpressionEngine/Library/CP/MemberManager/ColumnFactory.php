<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Library\CP\MemberManager;
use ExpressionEngine\Library\CP\EntryManager;

/**
 * Member Manager Column Factory
 */
class ColumnFactory extends EntryManager\ColumnFactory
{
    protected static $standard_columns = [
        'member_id' => Columns\MemberId::class,
        'username' => Columns\Username::class,
        'email' => Columns\Email::class,
        'roles' => Columns\Roles::class,
        'join_date' => Columns\JoinDate::class,
        'last_visit' => Columns\LastVisit::class,
        'checkbox' => Columns\Checkbox::class,
    ];

    /**
     * Returns Column objects for all custom field columns
     *
     * @return array[Column]
     */
    protected static function getCustomFieldColumns($channel = false)
    {
        return [];
    }
}

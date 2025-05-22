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
        'manage' => Columns\Manage::class,
    ];

    /**
     * Returns an instance of a column given its identifier. This factory uses
     * the Flyweight pattern to only keep a single instance of a column around
     * as they don't really need to maintain state.
     *
     * @return Column
     */
    public static function getColumn($identifier)
    {
        if (isset(self::$instances[$identifier])) {
            return self::$instances[$identifier];
        }

        if (isset(static::$standard_columns[$identifier])) {
            $class = static::$standard_columns[$identifier];
            self::$instances[$identifier] = new $class($identifier);
        } elseif (strpos($identifier, 'm_field_id_') === 0 && $field = self::getCompatibleField($identifier)) {
            self::$instances[$identifier] = new Columns\MemberField($identifier, $field);
        } else {
            return null;
        }

        return self::$instances[$identifier];
    }

    /**
     * Returns Column objects for all custom field columns
     *
     * @return array[Column]
     */
    protected static function getCustomFieldColumns($channel = false)
    {
        $columns = ee('Model')->get('MemberField')
            ->all()
            ->filter(function ($field) {
                return in_array(
                    $field->m_field_type,
                    self::getCompatibleFieldtypes()
                );
            })
            ->map(function ($field) {
                return self::getColumn('m_field_id_' . $field->getId(), $field);
            });

        return $columns;
    }

    /**
     * Module tabs not supported
     *
     * @return array
     */
    protected static function getTabColumns()
    {
        return [];
    }

    /**
     * Returns a MemberField object given a field_id_x identifier
     *
     * @return MemberField
     */
    private static function getCompatibleField($identifier)
    {
        $field_id = str_replace('m_field_id_', '', $identifier);
        $field = ee('Model')->get('MemberField', $field_id)->first();

        if (
            $field &&
            in_array(
                $field->field_type,
                self::getCompatibleFieldtypes()
            )
        ) {
            return $field;
        }

        return null;
    }
}

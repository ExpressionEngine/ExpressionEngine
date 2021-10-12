<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2021, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Library\CP\EntryManager;

/**
 * Entry Manager Column Factory
 */
class ColumnFactory
{
    protected static $standard_columns = [
        'entry_id' => Columns\EntryId::class,
        'title' => Columns\Title::class,
        'url_title' => Columns\UrlTitle::class,
        'author' => Columns\Author::class,
        'status' => Columns\Status::class,
        'entry_date' => Columns\EntryDate::class,
        'expiration_date' => Columns\ExpirationDate::class,
        'channel' => Columns\ChannelName::class,
        'comments' => Columns\Comments::class,
        'categories' => Columns\Categories::class,
        'checkbox' => Columns\Checkbox::class
    ];

    private static $instances = [];

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
        } elseif (strpos($identifier, 'field_id_') === 0 && $field = self::getCompatibleField($identifier)) {
            self::$instances[$identifier] = new Columns\CustomField($identifier, $field);
        } else {
            return null;
        }

        return self::$instances[$identifier];
    }

    /**
     * Returns all available columns in the system, be it a system-standard
     * column, a custom field, or a column provided by an extension
     *
     * @return array[Column]
     */
    public static function getAvailableColumns($channel = false)
    {
        return array_merge(
            self::getStandardColumns(),
            self::getChannelFieldColumns($channel)
        );
    }

    /**
     * Returns Column objects for all system-standard columns
     *
     * @return array[Column]
     */
    private static function getStandardColumns()
    {
        return array_map(function ($identifier, $column) {
            return self::getColumn($identifier);
        }, array_keys(self::$standard_columns), self::$standard_columns);
    }

    /**
     * Returns Column objects for all custom field columns
     *
     * @return array[Column]
     */
    private static function getChannelFieldColumns($channel = false)
    {
        // Grab all the applicable fields based on the channel if there is one.
        if (! empty($channel)) {
            $customFields = $channel->getAllCustomFields();

            $columns = $customFields->filter(function ($field) {
                return in_array(
                    $field->field_type,
                    self::getCompatibleFieldtypes()
                );
            })
                ->map(function ($field) {
                    return self::getColumn('field_id_' . $field->getId(), $field);
                });
        } else {
            $columns = ee('Model')->get('ChannelField')
                ->all()
                ->filter(function ($field) {
                    return in_array(
                        $field->field_type,
                        self::getCompatibleFieldtypes()
                    );
                })
                ->map(function ($field) {
                    return self::getColumn('field_id_' . $field->getId(), $field);
                });
        }

        return $columns;
    }

    /**
     * Returns a ChannelField object given a field_id_x identifier
     *
     * @return ChannelField
     */
    private static function getCompatibleField($identifier)
    {
        $field_id = str_replace('field_id_', '', $identifier);
        $field = ee('Model')->get('ChannelField', $field_id)->first();

        if ($field && in_array(
            $field->field_type,
            self::getCompatibleFieldtypes()
        )) {
            return $field;
        }

        return null;
    }

    /**
     * Return list of fieldtypes that implement ColumnInterface
     *
     * @return array[string]
     */
    private static function getCompatibleFieldtypes()
    {
        static $fieldtypes;
        if (empty($fieldtypes)) {
            $cache_key = '/EntryManager/CompatibleFieldtypes';
            $fieldtypes = ee()->cache->get($cache_key);
            if (empty($fieldtypes)) {
                $fieldtypes = ee('Model')->get('Fieldtype')->all()->pluck('name');
                ee()->legacy_api->instantiate('channel_fields');
                $fieldtypes = array_filter($fieldtypes, function ($fieldtype) {
                    ee()->api_channel_fields->include_handler($fieldtype);

                    return self::isEntryManagerCompatibleFieldtype(self::getClassNameForFieldtype($fieldtype));
                });
                ee()->cache->save($cache_key, $fieldtypes);
            }
        }

        return $fieldtypes;
    }

    /**
     * Returns whether or not a given class supports Entry Manager columns
     * this can be either:
     * * implements ColumnInterface
     * * has entry_manager_compatible variable set to true
     * * extends EE_Fieldtype and has no array data
     *
     * @param string Full class name
     * @return boolean
     */
    private static function isEntryManagerCompatibleFieldtype($class)
    {
        if (self::implementsInterface($class)) {
            return true;
        }

        $reflection = new \ReflectionClass($class);
        $instance = $reflection->newInstanceWithoutConstructor();
        if (isset($instance->entry_manager_compatible)) {
            return (bool) $instance->entry_manager_compatible;
        }
        if (is_subclass_of($class, 'EE_Fieldtype')) {
            if (isset($instance->has_array_data)) {
                return (bool) !$instance->has_array_data;
            }
        }

        return false;
    }

    /**
     * Returns whether or not a given class implements ColumnInterface
     *
     * @param string Full class name
     * @return boolean
     */
    private static function implementsInterface($class)
    {
        $interfaces = class_implements($class);

        return isset($interfaces[ColumnInterface::class]);
    }

    /**
     * Returns class name for a given fieldtype
     *
     * @param string Fieldtype short name, i.e. checkboxes
     * @return boolean
     */
    private static function getClassNameForFieldtype($fieldtype)
    {
        return ucfirst($fieldtype) . '_ft';
    }
}

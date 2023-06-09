<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
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
        'sticky' => Columns\Sticky::class,
        'entry_date' => Columns\EntryDate::class,
        'edit_date' => Columns\EditDate::class,
        'expiration_date' => Columns\ExpirationDate::class,
        'channel' => Columns\ChannelName::class,
        'comments' => Columns\Comments::class,
        'categories' => Columns\Categories::class,
        'checkbox' => Columns\Checkbox::class
    ];

    protected static $instances = [];

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
        } elseif (strpos($identifier, 'tab_') === 0) {
            self::$instances[$identifier] = new Columns\ModuleTab($identifier);
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
        $columns = array_merge(
            static::getStandardColumns(),
            static::getCustomFieldColumns($channel),
            static::getTabColumns()
        );
        $availableColumns = [];
        foreach ($columns as $column) {
            $availableColumns[$column->getTableColumnIdentifier()] = $column;
        }
        return $availableColumns;
    }

    /**
     * Returns Column objects for all system-standard columns
     *
     * @return array[Column]
     */
    private static function getStandardColumns()
    {
        return array_filter(
            array_map(function ($identifier, $column) {
                if ($identifier != 'comments' || bool_config_item('enable_comments')) {
                    return static::getColumn($identifier);
                }
            }, array_keys(static::$standard_columns), static::$standard_columns),
            function ($column) {
                return (! empty($column));
            }
        );
    }

    /**
     * Returns Column objects for all custom field columns
     *
     * @return array[Column]
     */
    protected static function getCustomFieldColumns($channel = false)
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
     * Returns Column objects for modules with tabs
     *
     * @return array[Column]
     */
    protected static function getTabColumns()
    {
        return array_map(function ($tab) {
            if (strpos($tab, 'tab_') !== 0) {
                $tab = 'tab_' . $tab;
            }
            return self::getColumn($tab);
        }, self::getCompatibleTabs());
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
    protected static function getCompatibleFieldtypes()
    {
        static $fieldtypes = false;
        if ($fieldtypes === false) {
            $cache_key = '/EntryManager/CompatibleFieldtypes';
            $fieldtypes = ee()->cache->get($cache_key);
            if ($fieldtypes === false) {
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
     * Return list of tab files that implement ColumnInterface
     *
     * @return array[string]
     */
    private static function getCompatibleTabs()
    {
        static $tabs = false;
        if ($tabs === false) {
            $cache_key = '/EntryManager/CompatibleTabs';
            $tabs = ee()->cache->get($cache_key);
            if ($tabs === false) {
                $tabs = [];
                if (empty(ee()->cp->installed_modules)) {
                    ee()->cp->get_installed_modules();
                }
                foreach (ee()->cp->installed_modules as $module_name) {
                    $module = ee('Addon')->get($module_name);
                    if (!is_null($module)) {
                        $modulePath = $module->getPath();
                        ee()->load->add_package_path($modulePath);
                        if ($module->hasTab()) {
                            include_once($modulePath . '/tab.' . $module_name . '.php');
                            $class_name = ucfirst($module_name) . '_tab';
                            $OBJ = new $class_name();
                            if (method_exists($OBJ, 'renderTableCell') === true) {
                                $tabs[] = 'tab_' . $module_name;
                            }
                        }
                        ee()->load->remove_package_path($modulePath);
                    }
                }
                ee()->cache->save($cache_key, $tabs);
            }
        }

        return $tabs;
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

<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Model\Content;

use ExpressionEngine\Service\Model\Gateway;

/**
 * Content Variable Column Gateway
 */
class VariableColumnGateway extends Gateway
{
    /**
     *
     */
    public function getFieldList($cached = true)
    {
        if ($cached && isset(static::$_field_list_cache[get_class($this)])) {
            return static::$_field_list_cache[get_class($this)];
        }

        $all = ee('Database')
            ->newQuery()
            ->list_fields($this->getTableName());

        $known = parent::getFieldList();

        if (!is_array(static::$_field_list_cache)) {
            static::$_field_list_cache = [];
        }

        return static::$_field_list_cache[get_class($this)] = array_merge($known, $all);
    }
}

// EOF

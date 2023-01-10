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

use ExpressionEngine\Service\Model\Model;
use ExpressionEngine\Service\Model\VariableColumnModel;
use ExpressionEngine\Model\Content\FieldModel;

/**
 * ExpressionEngine FieldData Model
 */
class FieldData extends VariableColumnModel
{
    protected static $_primary_key = 'id';
    protected static $_table_name = 'channel_data_field_';

    protected $id;
    protected $entry_id;

    public function forField(FieldModel $field)
    {
        $this->_table_name = $field->getDataTable();

        return $this;
    }
}

// EOF

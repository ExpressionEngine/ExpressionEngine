<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Model\ConditionalFields;

/**
 * Category Condition model
 */
class CategoryCondition extends Condition
{
    protected static $_validation_rules = array(
        'condition_category_group_id' => 'required',
        'evaluation_rule' => 'required',
        'order' => 'integer'
    );

    public function onBeforeSave()
    {
        parent::onBeforeSave();
        $this->setProperty('model_type', 'CategoryCondition');
    }
}

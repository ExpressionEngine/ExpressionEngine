<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Model\Member\Display;

use ExpressionEngine\Model\Content\Display\DefaultLayout;

/**
 * Member Field Layout
 */
class MemberFieldLayout extends DefaultLayout
{
    public function transform(array $fields)
    {
        usort($fields, function ($a, $b) {
            return ($a->get('m_field_order') > $b->get('m_field_order')) ? 1 : -1;
        });

        return parent::transform($fields);
    }
}

// EOF

<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2020, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Service\Model\Relation;

use ExpressionEngine\Service\Model\Model;
use ExpressionEngine\Service\Model\Association\ToOne;

/**
 * HasOne Relation
 */
class HasOne extends HasOneOrMany
{

    /**
     *
     */
    public function createAssociation()
    {
        return new ToOne($this);
    }
}

// EOF

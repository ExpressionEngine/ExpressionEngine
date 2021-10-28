<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2021, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Service\Model\Relation;

use ExpressionEngine\Service\Model\Model;
use ExpressionEngine\Service\Model\Association\ToMany;

/**
 * HasMany Relation
 */
class HasMany extends HasOneOrMany
{
    /**
     *
     */
    public function createAssociation()
    {
        return new ToMany($this);
    }
}

// EOF

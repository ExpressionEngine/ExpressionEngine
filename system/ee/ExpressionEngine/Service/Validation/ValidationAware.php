<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2021, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Service\Validation;

/**
 * Objects that implement this are safe to treat as more
 * than just fancy arrays. Opens up access to internal
 * validate* callbacks and rules.
 */
interface ValidationAware
{
    /**
     * Return an array of validation data.
     */
    public function getValidationData();

    /**
     * Return an array of validation rules
     */
    public function getValidationRules();
}

<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Service\TemplateGenerator;

use ExpressionEngine\Model\Content\FieldModel;

/**
 * Requirements for the Field Template Generators
 */
interface FieldTemplateGeneratorInterface
{
    /**
     * Accept field model as a constructor argument (ChannelField etc.)
     *
     * @param FieldModel $field
     */
    public function __construct(FieldModel $field);

    /**
     * We only need to make sure field template generator
     * returns array of variables
     * that we'll use for replacement in stubs
     *
     * @return array
     */
    public function getVariables(): array;

}
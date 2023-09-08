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

abstract class AbstractFieldTemplateGenerator implements FieldTemplateGeneratorInterface
{
    /**
     * The field that we'll be working with
     *
     * @var FieldModel
     */
    protected $field;

    /**
     * Construct the class for given field
     *
     * @param FieldModel $field
     */
    public function __construct(FieldModel $field)
    {
        $this->field = $field;
    }
}

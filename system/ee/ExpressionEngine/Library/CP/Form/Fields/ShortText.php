<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Library\CP\Form\Fields;

use ExpressionEngine\Library\CP\Form\Field;

class ShortText extends Field
{
    /**
     * @var int[]
     */
    protected $field_prototype = [
        'label' => null
    ];

    /**
     * @return string|null
     */
    public function getLabel(): ?string
    {
        return $this->get('label');
    }

    /**
     * @param string $label
     * @return $this
     */
    public function setLabel(string $label): ShortText
    {
        $this->set('label', $label);
        return $this;
    }
}

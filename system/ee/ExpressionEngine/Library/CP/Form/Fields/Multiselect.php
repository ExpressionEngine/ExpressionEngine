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

use ExpressionEngine\Library\CP\Form\OptionsField;

class Multiselect extends OptionsField
{
    /**
     * @param string $name
     * @param $value
     * @param $label
     * @param array $choices
     * @return Multiselect
     */
    public function addDropdown(string $name, $value, $label, array $options = []): Multiselect
    {
        $choices = $this->getChoices();
        if (!is_array($choices)) {
            $choices = [];
        }

        $choices[$name] = [
            'label' => $label,
            'value' => $value,
            'choices' => $options
        ];

        $this->setChoices($choices);
        return $this;
    }

    /**
     * @param string $name
     * @return $this
     */
    public function removeDropdown(string $name): Multiselect
    {
        $choices = $this->getChoices();
        if (isset($choices[$name])) {
            unset($choices[$name]);
            $this->setChoices($choices);
        }

        return $this;
    }
}

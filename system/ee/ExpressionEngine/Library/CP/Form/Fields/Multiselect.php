<?php

namespace EeObjects\Forms\Form\Fields;

use EeObjects\Forms\Form\OptionsField;

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
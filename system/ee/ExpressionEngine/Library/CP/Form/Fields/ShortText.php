<?php

namespace EeObjects\Forms\Form\Fields;

use EeObjects\Forms\Form\Field;

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
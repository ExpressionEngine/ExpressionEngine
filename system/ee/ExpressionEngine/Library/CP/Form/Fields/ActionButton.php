<?php

namespace EeObjects\Forms\Form\Fields;

use EeObjects\Forms\Form\Field;

class ActionButton extends Field
{
    /**
     * @var string[]
     */
    protected $field_prototype = [
        'link' => '',
        'text' => '',
    ];

    /**
     * @return string|null
     */
    public function getLink(): ?string
    {
        return $this->get('link');
    }

    /**
     * @param string $link
     * @return $this
     */
    public function setLink(string $link): ActionButton
    {
        $this->set('link', $link);
        return $this;
    }

    /**
     * @return string|null
     */
    public function getText(): ?string
    {
        return $this->get('text');
    }

    /**
     * @param string $text
     * @return $this
     */
    public function setText(string $text): ActionButton
    {
        $this->set('text', $text);
        return $this;
    }
}
<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Library\CP\Form;

abstract class OptionsField extends Field
{
    /**
     * @var null[]
     */
    protected $field_prototype = [
        'choices' => [],
        'no_results' => null,
        'encode' => null,
        'disabled_choices' => null,
        'empty_text' => null,
        'selectable' => null,
        'reorderable' => null,
        'removable' => null,
    ];

    /**
     * @param string $text
     * @param string $link_text
     * @param string $link_href
     * @return $this
     */
    public function withNoResults(string $text, string $link_text, string $link_href): OptionsField
    {
        $this->set('no_results', ['text' => $text, 'link_href' => $link_href, 'link_text' => $link_text]);
        return $this;
    }

    /**
     * @return $this
     */
    public function withOutNoResults(): OptionsField
    {
        $this->set('no_results', null);
        return $this;
    }

    /**
     * @param array $choices
     * @return $this
     */
    public function setChoices(array $choices = []): OptionsField
    {
        $this->set('choices', $choices);
        return $this;
    }

    /**
     * @return array|null
     */
    public function getChoices(): ?array
    {
        return $this->get('choices');
    }

    /**
     * @param bool $encode
     * @return $this
     */
    public function setEncode(bool $encode): OptionsField
    {
        $this->set('encode', $encode);
        return $this;
    }

    /**
     * @return bool|null
     */
    public function getEncode(): ?bool
    {
        return $this->get('encode');
    }

    /**
     * @param array $choices
     * @return $this
     */
    public function setDisabledChoices(array $choices = []): OptionsField
    {
        $this->set('disabled_choices', $choices);
        return $this;
    }

    /**
     * @return array|null
     */
    public function getDisabledChoices(): ?array
    {
        return $this->get('disabled_choices');
    }

    /**
     * @param string $text
     * @return $this
     */
    public function setEmptyText(string $text): OptionsField
    {
        $this->set('empty_text', $text);
        return $this;
    }

    /**
     * @return string|null
     */
    public function getEmptyText(): ?string
    {
        return $this->get('empty_text');
    }

    /**
     * @param bool $reorderable
     * @return $this
     */
    public function setReorderable(bool $reorderable): OptionsField
    {
        $this->set('reorderable', $reorderable);
        return $this;
    }

    /**
     * @return bool|null
     */
    public function getReorderable(): ?bool
    {
        return $this->get('reorderable');
    }

    /**
     * @param bool $removable
     * @return $this
     */
    public function setRemovable(bool $removable): OptionsField
    {
        $this->set('removable', $removable);
        return $this;
    }

    /**
     * @return bool|null
     */
    public function getRemovable(): ?bool
    {
        return $this->get('removable');
    }
}

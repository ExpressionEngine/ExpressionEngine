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

abstract class Field
{
    /**
     * @var string
     */
    protected $name = '';

    /**
     * @var array
     */
    protected $default_prototype = [
        'class' => '',
        'margin_top' => null,
        'margin_left' => null,
        'note' => null,
        'attrs' => '',
        'disabled' => null,
        'value' => '',
        'group' => null,
        'group_toggle' => null,
        'required' => null,
        'maxlength' => null,
        'placeholder' => null
    ];

    /**
     * @var array
     */
    protected $field_prototype = [];

    /**
     * @var array
     */
    protected $prototype = [];

    /**
     * Field constructor.
     * @param string $name
     * @param string $type
     */
    public function __construct(string $name = '', string $type = '')
    {
        $this->name = $name;
        $this->prototype = array_merge($this->default_prototype, $this->field_prototype);
        $this->prototype['name'] = $name;
        $this->prototype['type'] = $type;
    }

    /**
     * @param string $name
     * @param mixed $value
     * @return $this
     */
    public function set(string $name, $value): Field
    {
        $this->prototype[$name] = $value;
        return $this;
    }

    /**
     * @param string $key
     * @return mixed
     */
    public function get(string $key)
    {
        if (isset($this->prototype[$key])) {
            return $this->prototype[$key];
        }
    }

    /**
     * @return array
     */
    public function toArray(): array
    {
        $return = [];
        foreach ($this->prototype as $key => $value) {
            if (!is_null($value)) {
                $return[$key] = $value;
            }
        }

        return $return;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return mixed
     */
    public function getClass(): ?string
    {
        return $this->get('class');
    }

    /**
     * @param string $class
     * @return $this
     */
    public function setClass(string $class): Field
    {
        $this->set('class', $class);
        return $this;
    }

    /**
     * @return Field
     */
    public function withMarginTop(): Field
    {
        $this->set('margin_top', true);
        return $this;
    }

    /**
     * @return Field
     */
    public function withOutMarginTop(): Field
    {
        $this->set('margin_top', false);
        return $this;
    }

    /**
     * @return Field
     */
    public function withMarginLeft(): Field
    {
        $this->set('margin_left', true);
        return $this;
    }

    /**
     * @return Field
     */
    public function withOutMarginLeft(): Field
    {
        $this->set('margin_left', false);
        return $this;
    }

    /**
     * @return mixed
     */
    public function getNote(): ?string
    {
        return $this->get('note');
    }

    /**
     * @param string $note
     * @return $this
     */
    public function setNote(string $note): Field
    {
        $this->set('note', $note);
        return $this;
    }

    /**
     * @return mixed
     */
    public function getAttrs(): ?string
    {
        return $this->get('attrs');
    }

    /**
     * @param string $attrs
     * @return $this
     */
    public function setAttrs(string $attrs): Field
    {
        $this->set('attrs', $attrs);
        return $this;
    }

    /**
     * @return mixed
     */
    public function getDisabled(): ?bool
    {
        return $this->get('disabled');
    }

    /**
     * @param bool $disabled
     * @return $this
     */
    public function setDisabled(bool $disabled): Field
    {
        $this->set('disabled', $disabled);
        return $this;
    }

    /**
     * @return mixed
     */
    public function getValue()
    {
        return $this->get('value');
    }

    /**
     * @param $value
     * @return $this
     */
    public function setValue($value): Field
    {
        $this->set('value', $value);
        return $this;
    }

    /**
     * @return mixed
     */
    public function getGroup(): ?string
    {
        return $this->get('group');
    }

    /**
     * @param $group
     * @return $this
     */
    public function setGroup(string $group): Field
    {
        $this->set('group', $group);
        return $this;
    }

    /**
     * @return mixed
     */
    public function getGroupToggle(): ?string
    {
        return $this->get('group_toggle');
    }

    /**
     * @param $group_toggle
     * @return $this
     */
    public function setGroupToggle(string $group_toggle): Field
    {
        $this->set('group_toggle', $group_toggle);
        return $this;
    }

    /**
     * @return mixed
     */
    public function getRequired(): ?bool
    {
        return $this->get('required');
    }

    /**
     * @param bool $required
     * @return $this
     */
    public function setRequired(bool $required): Field
    {
        $this->set('required', $required);
        return $this;
    }

    /**
     * @return mixed
     */
    public function getPlaceholder(): ?string
    {
        return $this->get('placeholder');
    }

    /**
     * @param string $placeholder
     * @return $this
     */
    public function setPlaceholder(string $placeholder): Field
    {
        $this->set('placeholder', $placeholder);
        return $this;
    }

    /**
     * @return int|null
     */
    public function getMaxlength(): ?int
    {
        return $this->get('maxlength');
    }

    /**
     * @param int $maxlength
     * @return $this
     */
    public function setMaxlength(int $maxlength): Field
    {
        $this->set('maxlength', $maxlength);
        return $this;
    }
}

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

class Button
{
    /**
     * @var mixed|string
     */
    protected $name = '';

    /**
     * @var array
     */
    protected $prototype = [
        'shortcut' => '',
        'attrs' => null,
        'value' => '',
        'name' => 'save',
        'type' => 'button',
        'class' => null,
        'html' => null,
        'text' => 'save',
        'working' => 'saving'
    ];

    /**
     * @var array
     */
    protected $structure = [];

    /**
     * Button constructor.
     * @param string $name
     */
    public function __construct(string $name = '')
    {
        $this->name = $this->prototype['name'] = $name;
        $this->prototype['type'] = 'button';
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @param string $name
     * @param $value
     * @return $this
     */
    public function set(string $name, $value): Button
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
     * @param string $shortcut
     * @return $this
     */
    public function setShortcut(string $shortcut): Button
    {
        $this->set('shortcut', $shortcut);
        return $this;
    }

    /**
     * @return string|null
     */
    public function getShortcut(): ?string
    {
        return $this->get('shortcut');
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
    public function setAttrs(string $attrs): Button
    {
        $this->set('attrs', $attrs);
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
    public function setValue($value): Button
    {
        $this->set('value', $value);
        return $this;
    }

    /**
     * @return mixed
     */
    public function getType(): ?string
    {
        return $this->get('type');
    }

    /**
     * @param $type
     * @return $this
     */
    public function setType(string $type): Button
    {
        $types = ['button', 'reset', 'submit'];
        if (!in_array($type, $types)) {
            $type = 'button';
        }

        $this->set('type', $type);
        return $this;
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
    public function setClass(string $class): Button
    {
        $this->set('class', $class);
        return $this;
    }

    /**
     * @return mixed
     */
    public function getHtml(): ?string
    {
        return $this->get('html');
    }

    /**
     * @param string $class
     * @return $this
     */
    public function setHtml(string $html): Button
    {
        $this->set('html', $html);
        return $this;
    }

    /**
     * @return mixed
     */
    public function getText(): ?string
    {
        return $this->get('text');
    }

    /**
     * @param string $text
     * @return $this
     */
    public function setText(string $text): Button
    {
        $this->set('text', $text);
        return $this;
    }

    /**
     * @return mixed
     */
    public function getWorking(): ?string
    {
        return $this->get('working');
    }

    /**
     * @param string $working
     * @return $this
     */
    public function setWorking(string $working): Button
    {
        $this->set('working', $working);
        return $this;
    }
}

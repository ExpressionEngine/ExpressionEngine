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

class Set
{
    /**
     * @var string
     */
    protected $name = '';

    /**
     * @var array
     */
    protected $prototype = [
        'title' => '',
        'desc' => null,
        'desc_cont' => null,
        'example' => null,
        'grid' => null,
        'wide' => null,
        'button' => null
    ];

    /**
     * @var array
     */
    protected $structure = [];

    /**
     * Set constructor.
     * @param string $name
     */
    public function __construct(string $name = '')
    {
        $this->name = $this->prototype['title'] = $name;
    }

    /**
     * @param string $name
     * @param $value
     * @return $this
     */
    public function set(string $name, $value): Set
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

        $fields = [];
        foreach ($this->structure as $structure) {
            $fields[$structure->getName()] = $structure->toArray();
        }

        $return['fields'] = $fields;
        return $return;
    }

    /**
     * @param string $name
     * @param string $type
     * @return Field
     */
    public function getField(string $name, string $type = ''): Field
    {
        $tmp_name = $this->buildTmpName($name);
        if (isset($this->structure[$tmp_name])) {
            return $this->structure[$tmp_name];
        }

        if (!$type) {
            $type = 'text';
        }

        $this->structure[$tmp_name] = $this->buildField($name, $type);
        return $this->structure[$tmp_name];
    }

    /**
     * @param string $name
     * @return bool
     */
    public function removeField(string $name): bool
    {
        $tmp_name = $this->buildTmpName($name);
        if (isset($this->structure[$tmp_name])) {
            unset($this->structure[$tmp_name]);
            return true;
        }

        return false;
    }

    /**
     * @param string $name
     * @param string $type
     * @return Field
     */
    protected function buildField(string $name, string $type): Field
    {
        $field = '\ExpressionEngine\Library\CP\Form\Fields\\' . $this->studly($type);
        if (class_exists($field)) {
            return new $field($name, $type);
        }

        return new Fields\Input($name, $type);
    }

    /**
     * @param string $value
     * @return string
     */
    protected function studly(string $value): string
    {
        return str_replace(' ', '',
            ucwords(str_replace(['-', '_'], ' ', $value))
        );
    }

    /**
     * @param string $text
     * @param string $rel
     * @param string $for
     * @return $this
     */
    public function withButton(string $text, string $rel = '', string $for = ''): Set
    {
        $this->set('button', ['text' => $text, 'rel' => $rel, 'for' => $for]);
        return $this;
    }

    /**
     * @param string $name
     * @return string
     */
    protected function buildTmpName(string $name): string
    {
        return '_field_' . $name;
    }

    /**
     * @return $this
     */
    public function withOutButton(): Set
    {
        $this->set('button', null);
        return $this;
    }

    /**
     * @return mixed
     */
    public function getTitle(): ?string
    {
        return $this->get('title');
    }

    /**
     * @param string $title
     * @return $this
     */
    public function setTitle(string $title): Set
    {
        $this->set('title', $title);
        return $this;
    }

    /**
     * @return mixed
     */
    public function getDesc(): ?string
    {
        return $this->get('desc');
    }

    /**
     * @param string $title
     * @return $this
     */
    public function setDesc(string $desc): Set
    {
        $this->set('desc', $desc);
        return $this;
    }

    /**
     * @return mixed
     */
    public function getDescCont(): ?string
    {
        return $this->get('desc_cont');
    }

    /**
     * @param string $desc_cont
     * @return $this
     */
    public function setDescCont(string $desc_cont): Set
    {
        $this->set('desc_cont', $desc_cont);
        return $this;
    }

    /**
     * @return mixed
     */
    public function getExample(): ?string
    {
        return $this->get('example');
    }

    /**
     * @param string $example
     * @return $this
     */
    public function setExample(string $example): Set
    {
        $this->set('example', $example);
        return $this;
    }

    /**
     * @return $this
     */
    public function withGrid(): Set
    {
        $this->set('grid', true);
        $this->set('wide', true);
        return $this;
    }

    /**
     * @return $this
     */
    public function withoutGrid(): Set
    {
        $this->set('grid', false);
        $this->set('wide', false);
        return $this;
    }
}

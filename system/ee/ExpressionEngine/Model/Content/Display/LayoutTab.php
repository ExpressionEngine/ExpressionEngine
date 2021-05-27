<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2021, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Model\Content\Display;

use ExpressionEngine\Service\Validation\Result;
use ExpressionEngine\Service\Alert\Alert;

/**
 * Display Layout Tab
 */
class LayoutTab
{
    public $id;
    public $title;

    protected $fields;
    protected $visible = true;
    protected $alert;

    public function __construct($id, $title, array $fields = array())
    {
        $this->id = $id;
        $this->title = $title;
        $this->fields = $fields;

        return $this;
    }

    public function setFields($fields)
    {
        $this->fields = $fields;

        return $this;
    }

    public function addField($field)
    {
        $this->fields[] = $field;

        return $this;
    }

    public function getFields()
    {
        return $this->fields;
    }

    public function setAlert(Alert $alert)
    {
        $this->alert = $alert;
    }

    public function renderAlert()
    {
        return ($this->alert) ? $this->alert->render() : '';
    }

    public function hide()
    {
        $this->visible = false;

        return $this;
    }

    public function show()
    {
        $this->visible = true;

        return $this;
    }

    public function isVisible()
    {
        return $this->visible;
    }

    public function hasErrors(Result $errors)
    {
        if ($errors->isValid()) {
            return false;
        }

        foreach ($this->fields as $field) {
            if ($errors->hasErrors($field->getName())) {
                return true;
            }
        }

        return false;
    }
}

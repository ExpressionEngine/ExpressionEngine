<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
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
        // not sure this is the best place for this check, but:
        // we need to show alert if the status is not available,
        // and this is the point where we for sure have all data
        if ($field->getId() == 'status') {
            if (! array_key_exists($field->getData(), $field->get('field_list_items'))) {
                ee('CP/Alert')->makeInline('status-not-available')
                    ->asWarning()
                    ->cannotClose()
                    ->withTitle(lang('status_not_available'))
                    ->addToBody(sprintf(lang('status_not_available_desc'), $field->getData()))
                    ->now();
            }
        }

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

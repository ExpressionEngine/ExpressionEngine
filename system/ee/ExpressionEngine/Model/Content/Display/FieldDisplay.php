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

/**
 * Content Field Display
 */
class FieldDisplay
{
    protected $field;
    protected $collapsed = false;
    protected $visible = true;

    public function __construct($field)
    {
        $this->field = $field;
        $this->collapsed = (bool) $field->getItem('field_is_hidden');
    }

    public function get($key)
    {
        return $this->field->getItem($key);
    }

    public function getId()
    {
        return $this->field->getId();
    }

    public function getType()
    {
        return $this->field->getItem('field_type');
    }

    public function getTypeName()
    {
        return $this->field->getTypeName();
    }

    public function getName()
    {
        return $this->field->getName();
    }

    public function getShortName()
    {
        return $this->field->getShortName();
    }

    public function getStatus()
    {
        return $this->field->getStatus();
    }

    public function getLabel()
    {
        return $this->field->getItem('field_label');
    }

    public function getForm()
    {
        return $this->field->getForm();
    }

    public function getFormat()
    {
        return $this->field->getFormat();
    }

    public function getInstructions()
    {
        return $this->field->getItem('field_instructions');
    }

    public function isRequired()
    {
        return $this->field->getItem('field_required') == 'y';
    }

    public function collapse()
    {
        $this->collapsed = true;
    }

    public function expand()
    {
        $this->collapsed = false;
    }

    public function isCollapsed()
    {
        return $this->collapsed;
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

    public function getSetting($item)
    {
        $settings = $this->field->initField();

        return isset($settings[$item]) ? $settings[$item] : null;
    }

    public function setIsInModalContext($in_modal)
    {
        $this->field->setItem('in_modal_context', $in_modal);

        return $this;
    }
}

// EOF

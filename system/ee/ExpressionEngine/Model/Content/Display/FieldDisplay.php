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

/**
 * Content Field Display
 */
class FieldDisplay
{
    protected $field;
    protected $collapsed = false;
    protected $visible = true;
    protected $conditional = false;
    protected $width = 100;

    public function __construct($field)
    {
        $this->field = $field;
        $this->collapsed = (bool) $field->getItem('field_is_hidden');
        $this->conditional = $field->getItem('field_is_conditional');
    }

    public function get($key)
    {
        return $this->field->getItem($key);
    }

    public function getId()
    {
        return $this->field->getId();
    }

    public function getData()
    {
        return $this->field->getData();
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

    public function getNameBadge($field_name_prefix = '')
    {
        return $this->field->getNameBadge($field_name_prefix);
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

    public function titleIsHidden()
    {
        return (bool) $this->getSetting('field_hide_title');
    }

    public function publishLayoutCollapseIsHidden()
    {
        return (bool) $this->getSetting('field_hide_publish_layout_collapse');
    }

    public function setWidth($field_width)
    {
        $this->width = $field_width;

        return $this;
    }

    public function getWidth()
    {
        return (float) $this->width;
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

    public function isConditional()
    {
        return get_bool_from_string($this->conditional);
    }

    public function isConditionallyHidden()
    {
        return get_bool_from_string($this->field->getHidden());
    }

    public function renderAlert()
    {
        if (!empty($this->field->getAlertText())) {
            return ee('CP/Alert')->makeInline('__inline_alert_' . $this->getShortName())
                ->asWarning()
                ->cannotClose()
                ->addToBody($this->field->getAlertText())
                ->render();
        }
        return '';
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

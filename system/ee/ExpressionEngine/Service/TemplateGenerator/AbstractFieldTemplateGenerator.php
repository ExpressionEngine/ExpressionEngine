<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Service\TemplateGenerator;

use ExpressionEngine\Model\Content\FieldModel;
use ExpressionEngine\Addons\Grid\Model\GridColumn;

abstract class AbstractFieldTemplateGenerator implements FieldTemplateGeneratorInterface
{
    protected $input;

    /**
     * The field that we'll be working with
     *
     * @var FieldModel|GridColumn
     */
    protected $field;

    /**
     * Settings of field or Grid column
     *
     * @var array
     */
    public $settings = [];

    /**
     * Construct the class for given field or Grid column
     *
     * @param FieldModel|GridColumn $field
     * @param array $settings
     */
    public function __construct($field, $settings = [])
    {
        $this->field = $field;
        $this->input = new Input;

        if ($field instanceof FieldModel) {
            $this->settings = $this->field->getSettingsValues()['field_settings'];
        } elseif ($field instanceof GridColumn) {
            $this->settings = $this->field->col_settings;
        }
        $this->settings = array_merge($this->settings, $settings);
    }

    /**
     * We only need to make sure field template generator
     * returns array of variables
     * that we'll use for replacement in stubs
     *
     * @return array
     */
    public function getVariables(): array {
        return [];
    }

    public function setInput(Input $input)
    {
        $this->input = $input;

        return $this;
    }

    public function makeField($fieldtype, $field, $settings = [])
    {
        $generator = ee('TemplateGenerator')->makeField($fieldtype, $field, $settings);

        return ($generator) ? $generator->setInput($this->input) : null;
    }
}

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
    public $settings;

    /**
     * Construct the class for given field or Grid column
     *
     * @param FieldModel|GridColumn $field
     * @param array $settings
     */
    public function __construct($field, $settings = [])
    {
        $this->field = $field;
        if ($field instanceof FieldModel) {
            $this->settings = $this->field->getSettingsValues()['field_settings'];
        } elseif ($field instanceof GridColumn) {
            $this->settings = $this->field->col_settings;
        }
        $this->settings = array_merge($this->settings, $settings);
    }
}

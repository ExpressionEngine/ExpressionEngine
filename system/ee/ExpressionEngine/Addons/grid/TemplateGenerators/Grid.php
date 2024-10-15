<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Addons\Grid\TemplateGenerators;

use ExpressionEngine\Service\TemplateGenerator\AbstractFieldTemplateGenerator;

class Grid extends AbstractFieldTemplateGenerator
{
    public function getVariables(): array
    {
        $prefix = isset($this->settings['field_prefix']) ? $this->settings['field_prefix'] : $this->field->field_name;
        $vars = [
            'columns' => []
        ];

        //get the list of columns for this field
        foreach ($this->field->GridColumns as $column) {
            $fieldtypeGenerator = ee('TemplateGenerator')->getFieldtype($column->col_type);

            $vars['columns']['grid_col_' . $column->col_id] = [
                'col_type' => $column->col_type,
                'col_name' => $column->col_name,
                'col_label' => $column->col_label,
                'field_type' => $column->col_type,
                'field_name' => $prefix . ':' . $column->col_name,
                'field_label' => $column->col_label,
                'stub' => $fieldtypeGenerator['stub'],
                'docs_url' => $fieldtypeGenerator['docs_url'],
                'is_tag_pair' => $fieldtypeGenerator['is_tag_pair'],
            ];

            $generator = $this->makeField($column->col_type, $column);

            // if the field has its own generator, instantiate the field and pass to generator
            if ($generator) {
                $generator->settings = array_merge($this->settings, $generator->settings); // file grid settings are saved in different place
                $vars['columns']['grid_col_' . $column->col_id] = array_merge(
                    $vars['columns']['grid_col_' . $column->col_id], $generator->getVariables()
                );
            }
        }

        return $vars;
    }
}

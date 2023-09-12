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
use ExpressionEngine\Service\TemplateGenerator\FieldTemplateGeneratorInterface;

class Grid extends AbstractFieldTemplateGenerator implements FieldTemplateGeneratorInterface
{
    public function getVariables(): array
    {
        $vars = [
            'columns' => []
        ];
        $stubsAndGenerators = ee('TemplateGenerator')->getFieldtypeStubsAndGenerators();
        //get the list of columns for this field
        foreach ($this->field->GridColumns as $column) {
            $vars['columns']['grid_col_' . $column->col_id] = [
                'col_type' => $column->col_type,
                'col_name' => $column->col_name,
                'col_label' => $column->col_label,
                'field_type' => $column->col_type,
                'field_name' => $this->field->field_name . ':' . $column->col_name,
                'field_label' => $column->col_label,
                'stub' => $stubsAndGenerators[$column->col_type]['stub'],
                'docs_url' => $stubsAndGenerators[$column->col_type]['docs_url'],
                'is_tag_pair' => $stubsAndGenerators[$column->col_type]['is_tag_pair'],
            ];

            // if the field has its own generator, instantiate the field and pass to generator
            if (!empty($stubsAndGenerators[$column->col_type]['generator'])) {
                $interfaces = class_implements($stubsAndGenerators[$column->col_type]['generator']);
                if (!empty($interfaces) && in_array(FieldTemplateGeneratorInterface::class, $interfaces)) {
                    $generator = new $stubsAndGenerators[$column->col_type]['generator']($column);
                    $generator->settings = array_merge($this->settings, $generator->settings); // file grid settings are saved in different place
                    $vars['columns']['grid_col_' . $column->col_id] = array_merge($vars['columns']['grid_col_' . $column->col_id], $generator->getVariables());
                }
            }
        }

        return $vars;
    }
}

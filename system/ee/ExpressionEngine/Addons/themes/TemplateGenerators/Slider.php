<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Addons\Themes\TemplateGenerators;

use ExpressionEngine\Addons\Channel\TemplateGenerators\Entries;
use ExpressionEngine\Service\TemplateGenerator\TemplateGeneratorInterface;

class Slider extends Entries implements TemplateGeneratorInterface
{
    protected $name = 'Slider Template Generator';

    protected $templates = [
        'slider' => 'Full-page Slider'
    ];

    public function getVariables(): array
    {
        $variables = parent::getVariables();

        // we grab one (first) field of each type
        $fields = [];
        foreach ($variables['fields'] as $field) {
            if ($field['field_type'] == 'file' || $field['field_type'] == 'textarea') {
                if (!isset($fields[$field['field_type']])) {
                    $fields[$field['field_type']] = $field;
                }
                if (count($fields) == 2) {
                    break;
                }
            }
        }
        $variables['fields'] = $fields;

        return $variables;
    }
}

<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Addons\File\TemplateGenerators;

use ExpressionEngine\Service\TemplateGenerator\AbstractFieldTemplateGenerator;
use ExpressionEngine\Service\TemplateGenerator\FieldTemplateGeneratorInterface;

class File extends AbstractFieldTemplateGenerator implements FieldTemplateGeneratorInterface
{
    public function getVariables(): array
    {
        $vars = [];
        $dimensions = ee('Model')->get('FileDimension')
            ->filter('site_id', ee('TemplateGenerator')->site_id);
        if (isset($this->settings['allowed_directories']) && $this->settings['allowed_directories'] != 'all') {
            $dimensions->filter('upload_location_id', $this->settings['allowed_directories']);
        }
        $vars['dimensions'] = $dimensions->all()->getDictionary('short_name', 'short_name');

        return $vars;
    }
}

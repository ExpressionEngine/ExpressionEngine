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

class File extends AbstractFieldTemplateGenerator
{
    public function getVariables(): array
    {
        $dimensions = ee('Model')->get('FileDimension')
            ->filter('site_id', (int) $this->input->get('site_id', 1));
        if (isset($this->settings['allowed_directories']) && $this->settings['allowed_directories'] != 'all') {
            $dimensions->filter('upload_location_id', $this->settings['allowed_directories']);
        }

        return [
            'dimensions' => $dimensions->all()->getDictionary('short_name', 'short_name')
        ];
    }
}

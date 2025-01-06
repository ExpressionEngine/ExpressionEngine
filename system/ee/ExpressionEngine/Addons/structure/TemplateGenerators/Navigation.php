<?php

/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Structure\TemplateGenerators;

use ExpressionEngine\Service\TemplateGenerator\AbstractTemplateGenerator;

class Navigation extends AbstractTemplateGenerator
{
    protected $name = 'Structure Navigation';

    protected $templates = [
        'navigation' => 'A customizable navigation template',
        'sitemap' => ['name' => 'XML sitemap built from Structure entries', 'type' => 'xml'],
    ];

    public function getVariables(): array
    {
        return [];
    }

}

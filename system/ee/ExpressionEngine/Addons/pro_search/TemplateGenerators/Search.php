<?php

/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace Pro\Search\TemplateGenerators;

use ExpressionEngine\Service\TemplateGenerator\AbstractTemplateGenerator;

class Search extends AbstractTemplateGenerator
{
    protected $name = 'Pro Search';

    protected $templates = [
        'keyword' => 'Keyword Search',
    ];

    public function getVariables(): array
    {
        return [];
    }
}

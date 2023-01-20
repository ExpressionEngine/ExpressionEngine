<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Service\Generator\Enums;

use ExpressionEngine\Service\Generator\Traits\EnumTrait;

class FieldtypeCompatibility
{
    use EnumTrait;

    public const TYPES = [
        'date' => 'Date',
        'file' => 'File',
        'grid' => 'Grid',
        'list' => 'Checkboxes, Radio Buttons, Select, Multiselect',
        'relationship' => 'Relationships',
        'text' => 'Email Address, Rich Text Editor, Text Input, Textarea,URL',
    ];
}

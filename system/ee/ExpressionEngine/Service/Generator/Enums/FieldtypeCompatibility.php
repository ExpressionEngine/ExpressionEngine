<?php

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

<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2026, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Library\CP\FileManager\Columns;

use ExpressionEngine\Library\CP\EntryManager;
use function htmlentities;

/**
 * FileName Column
 */
class FileName extends EntryManager\Columns\Column
{
    public function getTableColumnLabel()
    {
        return 'name';
    }

    public function getTableColumnConfig()
    {
        return [
            'encode' => false,
        ];
    }

    public function renderTableCell($data, $field_id, $file, $viewtype = 'list')
    {
        if ($viewtype == 'list') {
            return [
                'html' => htmlentities($file->file_name, ENT_QUOTES, 'UTF-8'),
                'attrs' => [
                    'class' => 'filemanager-filename-cell',
                ],
            ];
        }

        return $file->file_name;
    }
}

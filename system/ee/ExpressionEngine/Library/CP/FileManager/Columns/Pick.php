<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Library\CP\FileManager\Columns;

use ExpressionEngine\Library\CP\EntryManager;
use ExpressionEngine\Library\CP\Table;

/**
 * Picker Column
 */
class Pick extends EntryManager\Columns\Column
{
    public function getTableColumnLabel()
    {
        return '';
    }

    public function getTableColumnConfig()
    {
        return [
            'type' => Table::COL_TOOLBAR,
        ];
    }

    public function renderTableCell($data, $field_id, $file)
    {
        $toolbar = [];
        if ($file->model_type == 'File') {
            $toolbar['original'] = array(
                'href' => $file->getAbsoluteURL(),
                'title' => lang('original'),
            );
            $toolbar['thumbs'] = array(
                'href' => $file->getAbsoluteURL(),
                'title' => lang('thumbnail'),
            );
        }

        return [
            'toolbar_items' => $toolbar,
            'toolbar_type' => 'dropdown',
        ];
    }
}

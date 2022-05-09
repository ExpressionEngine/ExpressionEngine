<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2022, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Library\CP\FileManager\Columns;

use ExpressionEngine\Library\CP\EntryManager;
use ExpressionEngine\Library\CP\Table;

/**
 * Manage Column
 */
class Manage extends EntryManager\Columns\Column
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
        $toolbar = array(
            'edit' => array(
                'href' => '',
                'rel' => 'modal-view-file',
                'class' => 'm-link',
                'title' => lang('edit'),
                'data-file-id' => $file->file_id
            ),
            'link' => array(
                'href' => $file->getAbsoluteURL(),
                'title' => lang('link'),
                'target' => '_blank',
            ),
            'crop' => array(
                'href' => ee('CP/URL')->make('files/file/crop/' . $file->file_id),
                'title' => lang('crop'),
            ),
            'download' => array(
                'href' => ee('CP/URL')->make('files/file/download/' . $file->file_id),
                'title' => lang('download'),
            ),
        );

        if (! ee('Permission')->can('edit_files') || ! $file->isEditableImage()) {
            unset($toolbar['crop']);
        }

        return [
            'toolbar_items' => $toolbar
        ];
    }
}

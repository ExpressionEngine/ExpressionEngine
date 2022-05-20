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
                'href' => ee('CP/URL')->make('files/file/view/' . $file->file_id),
                // 'rel' => 'modal-view-file',
                'class' => '',
                'title' => lang('edit'),
                'data-file-id' => $file->file_id
            ),
            'download' => array(
                'href' => ee('CP/URL')->make('files/file/download/' . $file->file_id),
                'title' => lang('download'),
            ),
            'link' => array(
                'href' => $file->getAbsoluteURL(),
                'title' => lang('copy_link'),
                'target' => '_blank',
            ),
            'move' => array(
                'href' => '',
                'title' => lang('move'),
            ),
            'replace' => array(
                'href' => '',
                'title' => lang('replace_file'),
            ),
            'delete' => array(
                'href' => '',
                'class' => 'm-link',
                'rel' => 'modal-confirm-delete-file',
                'data-delete-file' => 'delete-trigger',
                'data-file-id' => $file->file_id,
                'data-file-name' => $file->file_name,
                'title' => lang('delete'),
            )
        );

        if (! ee('Permission')->can('edit_files') || ! $file->isEditableImage()) {
            unset($toolbar['crop']);
        }

        return [
            'toolbar_items' => $toolbar,
            'toolbar_type' => 'dropdown',
        ];
    }
}

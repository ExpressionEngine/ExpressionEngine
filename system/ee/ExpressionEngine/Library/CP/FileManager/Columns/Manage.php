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
        $confirmationUrl = ee('CP/URL')->make('files/confirm')->compile();
        $toolbar = [];
        if ($file->model_type == 'Directory') {
            $toolbar['open'] = array(
                'href' => ee('CP/URL')->make('files/directory/' . $file->upload_location_id, ['directory_id' => $file->file_id]),
                'title' => lang('open_cmd'),
            );
            $toolbar['rename'] = array(
                'href' => '#',
                'title' => lang('rename_cmd'),
                'rel' => 'modal-confirm-rename-file',
                'class' => 'm-link',
                'data-file-id' => $file->file_id,
                'data-file-name' => $file->file_name,
                'data-confirm-ajax' => $confirmationUrl,
            );
            $toolbar['move'] = array(
                'href' => '#',
                'title' => lang('move'),
                'rel' => 'modal-confirm-move-file',
                'data-file-id' => $file->file_id,
                'data-file-name' => $file->file_name,
                'data-confirm-ajax' => $confirmationUrl,
            );
        }
        if ($file->model_type == 'File') {
            if (ee('Permission')->can('edit_files')) {
                $toolbar['edit'] = array(
                    'href' => ee('CP/URL')->make('files/file/view/' . $file->file_id),
                    // 'rel' => 'modal-view-file',
                    'class' => '',
                    'title' => lang('edit'),
                    'data-file-id' => $file->file_id
                );
            }
            $toolbar['download'] = array(
                'href' => ee('CP/URL')->make('files/file/download/' . $file->file_id),
                'title' => lang('download'),
            );
            $toolbar['link'] = array(
                'href' => $file->getAbsoluteURL(),
                'class' => 'js-copy-url-button',
                'title' => lang('copy_link'),
            );
            if (ee('Permission')->can('edit_files') && ee()->uri->segment(3) == 'directory') {
                $toolbar['move'] = array(
                    'href' => '',
                    'title' => lang('move'),
                    'rel' => 'modal-confirm-move-file',
                    'data-file-id' => $file->file_id,
                    'data-file-name' => $file->file_name,
                    'data-confirm-ajax' =>$confirmationUrl,
                );
            }
        }

        if (ee('Permission')->can('delete_files')) {
            $toolbar['delete'] = [
                'href' => '',
                'class' => 'm-link with-divider',
                'rel' => 'modal-confirm-delete-file',
                'data-file-id' => $file->file_id,
                'data-file-name' => $file->file_name,
                'data-confirm-ajax' => $confirmationUrl,
                'title' => lang('delete'),
            ];
        }

        return [
            'toolbar_items' => $toolbar,
            'toolbar_type' => 'dropdown',
        ];
    }
}

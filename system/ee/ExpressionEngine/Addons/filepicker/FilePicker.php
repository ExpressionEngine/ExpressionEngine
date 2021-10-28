<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2021, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Addons\FilePicker;

use ExpressionEngine\Library\CP\Table;
use ExpressionEngine\Library\Data\Collection;
use ExpressionEngine\Model\File\UploadDestination;

/**
 * FilePicker
 */
class FilePicker
{
    public $base_url = 'addons/settings/filepicker/';
    public $controller = 'addons/settings/filepicker/modal';

    public function inject($view)
    {
        // Insert the modal
        $modal_vars = array('name' => 'modal-file', 'contents' => '');
        $modal = ee('View')->make('ee:_shared/modal')->render($modal_vars);
        ee('CP/Modal')->addModal('modal-file', $modal);

        ee()->cp->add_js_script(array(
            'file' => array(
                'cp/files/picker'
            ),
        ));
    }

    public function link($text, $dir = 'all', $data = array())
    {
        $qs = array('directories' => $dir);

        if (! empty($data['image'])) {
            $qs['type'] = 'thumb';
        } else {
            $qs['type'] = 'list';
        }

        if (isset($data['hasFilters'])) {
            $qs['hasFilters'] = $data['hasFilters'];
        }

        if (isset($data['hasUpload'])) {
            $qs['hasUpload'] = $data['hasUpload'];
        }

        $href = ee('CP/URL')->make($this->controller, $qs);
        $extra = "";
        $class = "";

        if (! empty($data['image'])) {
            $extra .= " data-input-image='{$data['image']}'";
        }

        if (! empty($data['input'])) {
            $extra .= " data-input-value='{$data['input']}'";
        }

        if (! empty($data['name'])) {
            $extra .= " data-input-name='{$data['name']}'";
        }

        if (! empty($data['selected'])) {
            $extra .= " data-selected='{$data['selected']}'";
        }

        if (! empty($data['callback'])) {
            $extra .= " data-callback='{$data['callback']}'";
        }

        if (! empty($data['class'])) {
            $class .= $data['class'];
        }

        return "<a class='m-link filepicker $class' rel='modal-file' href='$href' $extra>" . $text . "</a>";
    }

    public function buildTableFromFileCollection($files, $limit = 20, $selected = null)
    {
        $table = ee('CP/Table', array(
            'limit' => $limit,
            'class' => 'file-list tbl-fixed',
            'autosort' => false
        ));

        $table->setColumns(
            array(
                'title_or_name' => array(
                    'encode' => false,
                    'attrs' => array(
                        'width' => '40%'
                    ),
                ),
                'file_type',
                'date_added',
            )
        );
        $table->setNoResultsText(lang('no_uploaded_files'));

        if (empty($_GET['sort_col'])) {
            $table->config['sort_col'] = 'date_added';
            $table->config['sort_dir'] = 'desc';
        }

        $data = array();
        $i = 0;

        foreach ($files as $file) {
            $i++;

            if ($file instanceof \SplFileObject) {
                $new_file = new \StdClass();
                $new_file->title = $file->getFilename();
                $new_file->file_name = $file->getFilename();
                $new_file->mime_type = $file->getMimeType();
                $new_file->file_id = $i++;
                $new_file->upload_date = $file->getMTime();
                $file = $new_file;
            }

            $column = array(
                $file->title . '<br><em class="faded">' . $file->file_name . '</em>',
                $file->mime_type,
                ee()->localize->human_time($file->upload_date),
            );

            $attrs = array(
                'data-id' => $file->file_id,
                'data-url' => ee('CP/URL')->make($this->controller, array('file' => $file->file_id))
            );

            if ($file->file_id == $selected) {
                $attrs = array('class' => 'selected');
                $column[0] = '<span></span>' . $column[0];
            }

            $data[] = array(
                'attrs' => $attrs,
                'columns' => $column
            );
        }

        $table->setData($data);

        return $table;
    }

    public function hasFileGroupAccessPrivileges(UploadDestination $dir)
    {
        return $dir->memberHasAccess(ee()->session->getMember());
    }
}

// EOF

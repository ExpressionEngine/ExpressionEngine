<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */
if (! defined('BASEPATH')) {
    exit('No direct script access allowed');
}

/**
 * Pro Select Files variable type
 */
class Pro_select_files extends Pro_variables_type
{
    public $info = array(
        'name' => 'Select Files'
    );

    public $default_settings = array(
        'folders'         => array(),
        'upload'          => '',
        //'overwrite' => 'n',
        'multiple'        => 'n',
        'separator'       => 'newline',
        'multi_interface' => 'select'
    );

    // --------------------------------------------------------------------

    /**
     * Display settings sub-form for this variable type
     */
    public function display_settings()
    {
        $var_settings = $this->settings();

        // -------------------------------------
        //  Init return value
        // -------------------------------------

        $r = array();

        // -------------------------------------
        //  Get all upload folders
        // -------------------------------------

        $folders = ee('Model')
            ->get('UploadDestination')
            ->filter('site_id', ee()->config->item('site_id'))
            ->order('name')
            ->all();

        $choices = $folders->getDictionary('id', 'name');

        $r[] = array(
            'title' => 'file_folders',
            'fields' => array(
                $this->setting_name('folders') => array(
                    'type'  => 'checkbox',
                    'value' => $this->settings('folders'),
                    'wrap'  => true,
                    'choices' => $choices
                )
            )
        );

        // -------------------------------------
        //  Upload prefs
        // -------------------------------------

        $r[] = array(
            'title' => 'upload_folder',
            'desc' => 'upload_folder_help',
            'fields' => array(
                $this->setting_name('upload') => array(
                    'type'  => 'select',
                    'value' => $this->settings('upload'),
                    'choices' => array(lang('no_uploads')) + $choices
                )
            )
        );

        // -------------------------------------
        //  Overwrite?
        // -------------------------------------

        // $r[] = array(
        //  'title' => 'overwrite_existing_files_label',
        //  'fields' => array(
        //      $this->setting_name('overwrite') => array(
        //          'type'  => 'yes_no',
        //          'value' => $this->settings('overwrite') ?: 'n'
        //      )
        //  )
        // );

        // -------------------------------------
        //  Build setting: multiple?
        // -------------------------------------

        $r[] = PVUI::setting('multiple', $this->setting_name('multiple'), $this->settings('multiple'));

        // -------------------------------------
        //  Build setting: separator
        // -------------------------------------

        $r[] = PVUI::setting('separator', $this->setting_name('separator'), $this->settings('separator'));

        // -------------------------------------
        //  Build setting: multi interface
        // -------------------------------------

        $r[] = PVUI::setting('interface', $this->setting_name('multi_interface'), $this->settings('multi_interface'), 'drag-list-thumbs');

        // -------------------------------------
        //  Return output
        // -------------------------------------

        return $this->settings_form($r);
    }

    // --------------------------------------------------------------------

    /**
     * Display input field for regular user
     */
    public function display_field($var_data)
    {
        // -------------------------------------
        //  Get upload folders
        // -------------------------------------

        if (! ($ids = $this->settings('folders'))) {
            return lang('no_folders_selected');
        }

        // -------------------------------------
        //  Get files
        // -------------------------------------

        $files = ee('Model')
            ->get('File')
            ->with('UploadDestination')
            ->filter('site_id', ee()->config->item('site_id'))
            ->filter('upload_location_id', 'IN', $ids)
            ->order('title')
            ->all();

        $options = array();

        foreach ($files as $file) {
            // Skip non-existing files
            if (! $file->exists()) {
                continue;
            }

            $options[] = array(
                'id'    => $file->file_id,
                'name'  => $file->title,
                'url'   => $file->getAbsoluteURL(),
                'thumb' => $file->getAbsoluteThumbnailURL()
            );
        }

        if (empty($options)) {
            return '<div class="no-results">No files found</div>';
        }

        // -------------------------------------
        //  Input name
        // -------------------------------------

        $data = array('name' => $this->name);

        // -------------------------------------
        //  Single choice
        // -------------------------------------

        if ($this->settings('multiple') != 'y') {
            $data['choices'] = array('' => '--') + pro_flatten_results($options, 'name', 'url');
            $data['value'] = $var_data;
            $view = 'select';
        } else {
            //  Multiple choice
            $data['value'] = PVUI::explode($this->settings('separator'), $var_data);
            $data['thumbs'] = false;
            $data['multiple'] = true;

            // What's the interface?
            $view = $this->settings('multi_interface');

            // Thumbs!
            if ($view == 'drag-list-thumbs') {
                $view = 'drag-list';
                $data['thumbs'] = true;
            }

            // Generate choices
            foreach ($options as $file) {
                $name = htmlspecialchars($file['name'], ENT_QUOTES);

                if ($data['thumbs'] && $file['thumb']) {
                    $name = sprintf('<img src="%s" alt="" />', $file['thumb']) . $name;
                }

                $data['choices'][$file['url']] = $name;
            }
        }

        // Process view
        $html = PVUI::view_field($view, $data);

        // -------------------------------------
        //  Add upload file thing?
        // -------------------------------------

        if ($this->settings('upload')) {
            $html .= PVUI::view_field('upload', array('name' => 'newfile-' . $this->id));
        }

        // -------------------------------------
        //  Return the custom HTML
        // -------------------------------------

        return array(array(
            'type' => 'html',
            'content' => $html
        ));
    }

    /**
     * Show wide field with thumbs
     */
    public function wide()
    {
        return $this->settings('multi_interface') == 'drag-list-thumbs';
    }

    // --------------------------------------------------------------------

    /**
     * Prep variable data for saving
     */
    public function save($var_data)
    {
        // Optional upload folder ID
        $key = 'newfile-' . $this->id;
        $folder_id = $this->settings('upload');
        $newfile = $folder_id ? ee('Request')->file($key) : false;

        // if we're uploading, DO IT
        if ($folder_id && ! empty($newfile['name'])) {
            // Go ahead and upload
            $response = $this->upload_file($folder_id, $key);

            // If something went wrong, we'll know
            if (is_string($response)) {
                $this->error_msg = $response;

                return false;
            }

            // Compose new file name
            $file = $response['upload_directory_prefs']['url'] . $response['file_name'];

            // Add to multiple files
            if ($this->settings('multiple') == 'y' && is_array($var_data)) {
                $var_data[] = $file;
            } else {
                // Or just overwrite
                $var_data = $file;
            }
        }

        // Return stuff
        return is_array($var_data)
            ? PVUI::implode($this->settings('separator'), $var_data)
            : $var_data;
    }

    // --------------------------------------------------------------------

    /**
     * Output different versions
     *
     * @access     public
     * @param      string
     * @param      array
     * @return     string
     */
    public function replace_tag($tagdata)
    {
        // Get the variable's name
        $name = $this->name();

        // Get manipulations from var names
        if ($tagdata && preg_match_all("/\{{$name}:([\w-]+)\}/", $tagdata, $matches)) {
            $manip = array_unique($matches[1]);
        } elseif ($param = ee()->TMPL->fetch_param('manipulation')) {
            // Get manipulation from tag param
            $manip = array($param);
        } else {
            // No manipulations
            $manip = array();
        }

        // Get the files as an array
        $files = PVUI::explode($this->settings('separator'), $this->data());

        // Initiate rows
        $rows = array();

        foreach ($files as $file) {
            $row = array(
                $name => $file
            );

            // Position of the last slash
            $pos = strrpos($file, '/');
            $tmpl = substr($file, 0, $pos) . '/_%s' . substr($file, $pos);

            // Add all manipulation vars to the array
            foreach ($manip as $dir) {
                $row[$name . ':' . $dir] = sprintf($tmpl, $dir);
            }

            $rows[] = $row;
        }

        // Return parsed tagdata if used as var pair
        if ($tagdata) {
            return ee()->TMPL->parse_variables($tagdata, $rows);
        } else {
            // Or just return the first thing there
            $key = (count($manip) === 1)
                ? $name . ':' . $manip[0]
                : $name;

            return $rows[0][$key];
        }
    }

    // --------------------------------------------------------------------

    /**
     * Upload a file
     */
    private function upload_file($dir_id, $file_name)
    {
        ee()->load->library('filemanager');

        $response = ee()->filemanager->upload_file($dir_id, $file_name);

        if (isset($response['error'])) {
            return $response['error'];
        } else {
            $file = ee('Model')->get('File', $response['file_id'])->first();
            $file->upload_location_id = $dir_id;
            $file->site_id = ee()->config->item('site_id');

            $file->uploaded_by_member_id = ee()->session->userdata('member_id');
            $file->upload_date = ee()->localize->now;
            $file->modified_by_member_id = ee()->session->userdata('member_id');
            $file->modified_date = ee()->localize->now;

            $file->save();

            return $response;
        }
    }
}

<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

/**
 * File Field
 */
class File_field
{
    public $_files = array();

    public $_file_names = array();
    public $_file_ids = array();
    public $_dir_ids = array();

    public $_manipulations = array();
    public $_upload_prefs = array();

    /**
     * Creates a front-end-friendly file field
     *
     * @param string $field_name The name of the field
     * @param string $data The data stored in the file field
     *              e.g. {filedir_x}filename.ext
     * @param string $allowed_file_dirs The allowed file directory
     *              Either 'all' or ONE directory ID
     * @param string $content_type The content type allowed.
     *              Either 'all' or 'image'
     * @return string Fully rendered file field
     */
    public function field($field_name, $data = '', $allowed_file_dirs = 'all', $content_type = 'all', $filebrowser = true, $existing_limit = null)
    {
        // Load necessary library, helper, model and langfile
        ee()->load->library('filemanager');
        ee()->load->helper(array('html', 'form'));
        ee()->load->model(array('file_model', 'file_upload_preferences_model'));
        ee()->lang->loadfile('fieldtypes');

        $vars = array(
            'filedir' => '',
            'filename' => '',
            'upload_location_id' => ''
        );
        $allowed_file_dirs = ($allowed_file_dirs == 'all') ? '' : $allowed_file_dirs;
        $specified_directory = ($allowed_file_dirs == '') ? 'all' : $allowed_file_dirs;

        // Parse field data
        if (! empty($data) and ($parsed_field = $this->parse_field($data)) !== false) {
            $vars = $parsed_field;

            // Old format will not have extension broken out
            if (! empty($vars['extension'])) {
                $vars['filename'] = $vars['filename'] . '.' . $vars['extension'];
            }
        }

        // Retrieve all directories that are both allowed for this user and
        // for this field
        $upload_dirs[''] = lang('directory');
        $upload_dirs = ee()->file_upload_preferences_model->get_dropdown_array(
            null,
            $allowed_file_dirs,
            $upload_dirs
        );

        if ($specified_directory != 'all') {
            $vars['upload_location_id'] = $specified_directory;
        }

        // Get the thumbnail
        $thumb_info = ee()->filemanager->get_thumb(rawurldecode($vars['filename']), $vars['upload_location_id']);
        $vars['thumb'] = $thumb_info['thumb'];
        $vars['alt'] = $vars['filename'];

        $file = $this->getFileModelForFieldData($data);
        $vars['thumb'] = ee('Thumbnail')->get($file)->tag;

        //  Legacy paths will not have a filename but DO have a file_name
        // Show it
        if (empty($vars['filename']) && ! empty($vars['file_name'])) {
            $vars['filename'] = $vars['file_name'];
        }

        // Create the hidden fields for the file and directory
        $vars['hidden'] = form_hidden($field_name . '_hidden_file', rawurldecode($vars['filename']));
        $vars['hidden'] .= form_hidden($field_name . '_hidden_dir', $vars['upload_location_id']);

        // Create a standard file upload field and dropdown for folks
        // without javascript
        $vars['upload'] = form_upload(array(
            'name' => $field_name,
            'value' => $vars['filename'],
            'data-content-type' => $content_type,
            'data-directory' => $specified_directory
        ));

        $vars['allowed_file_dirs'] = $allowed_file_dirs;
        $vars['directory'] = form_hidden($field_name . '_directory', $vars['upload_location_id']);
        $vars['dropdown'] = form_dropdown($field_name . '_directory', $upload_dirs, $vars['upload_location_id']);

        // Check to see if they have access to any directories to create an upload link
        // Note- the count is at least one because first select option is Directory
        $vars['upload_link'] = (count($upload_dirs) > 1) ? '<a href="#" class="choose_file' . ($vars['filename'] ? ' js_hide' : '') . '" data-directory="' . $specified_directory . '">' . lang('add_file') . '</a>' : lang('directory_no_access');
        $vars['undo_link'] = '<a href="#" class="undo_remove js_hide">' . lang('file_undo_remove') . '</a>';
        $vars['remove_file_link'] = '<a href="#" class="remove_file" title="' . lang('remove_file') . '">' . lang('remove_file') . '</a>';

        // If we have a file, show the thumbnail, filename and remove link
        $vars['set_class'] = $vars['filename'] ? '' : 'js_hide';
        $vars['filebrowser'] = $filebrowser;

        $existing_files = null;
        $compatibilityMode = bool_config_item('file_manager_compatibility_mode');

        if (! $filebrowser && isset($existing_limit) && $specified_directory != 'all') {
            $options = array(
                'order' => array('file_name' => 'asc')
            );

            if ($existing_limit) { // 0 == all files
                $options['limit'] = $existing_limit;
            }

            // Load files in from database
            $files_from_db = ee()->file_model->get_files(
                $specified_directory,
                $options
            );

            $files = array(
                '' => lang('file_ft_select_existing')
            );

            // Put database files into list
            if ($files_from_db['results'] !== false) {
                foreach ($files_from_db['results']->result() as $file) {
                    if ($compatibilityMode) {
                        $files[$file->file_name] = $file->file_name;
                    } else {
                        $files[$file->file_id] = $file->file_name;
                    }
                }
            }

            $existing_files = form_dropdown($field_name . '_existing', $files);
        }

        $vars['existing_files'] = $existing_files;

        ee()->load->add_package_path(PATH_THEMES . 'cp/default');

        return ee('View')->make('_shared/file/field')->render($vars);
    }

    /**
     * Creates a drag-and-drop, control-panel only file field
     *
     * @param string $field_name The name of the field
     * @param string $data The data stored in the file field
     *              e.g. {filedir_x}filename.ext
     * @param string $allowed_file_dirs The allowed file directory
     *              Either 'all' or ONE directory ID
     * @param string $content_type The content type allowed.
     *              Either 'all' or 'image'
     * @return string Fully rendered file field
     */
    public function dragAndDropField($field_name, $data = '', $allowed_file_dirs = 'all', $content_type = 'all')
    {
        ee()->lang->loadfile('fieldtypes');

        $dir = null;
        $role_allowed_dirs = array();
        if ($allowed_file_dirs != 'all' && (int) $allowed_file_dirs) {
            $dir = ee('Model')->get('UploadDestination', $allowed_file_dirs)
                ->first();
        } elseif ($allowed_file_dirs == 'all') {
            //the field might be set to 'all' directories
            //but the role could have just one allowed
            $upload_directories = $this->_get_upload_prefs();
            foreach ($upload_directories as $dir_id => $destination) {
                if ($destination->module_id != 0 || $destination->memberHasAccess(ee()->session->getMember()) === false) {
                    continue;
                }
                $role_allowed_dirs[] = $dir_id;
            }
            if (count($role_allowed_dirs) == 1) {
                $allowed_file_dirs = $role_allowed_dirs[0];
                $dir = $upload_directories[$role_allowed_dirs[0]];
            }
        }

        if ($allowed_file_dirs == '' or ! $dir) {
            $allowed_file_dirs = 'all';
        }

        $fp = ee('CP/FilePicker')->make($allowed_file_dirs);

        $fp_link = $fp->getLink()
            ->withValueTarget($field_name)
            ->withNameTarget($field_name)
            ->withImage($field_name);

        // If we are showing a single directory respect its default modal view
        if ($dir) {
            switch ($dir->default_modal_view) {
                case 'thumb':
                    $fp_link->asThumbs();

                    break;

                default:
                    $fp_link->asList();

                    break;
            }
        }

        $fp_edit = clone $fp_link;
        $fp_edit
            ->setHtml('<i class="fal fa-pen"></i><span class="hidden">' . lang('edit') . '</span>')
            ->setAttribute('title', lang('edit'))
            ->setAttribute('class', 'file-field-filepicker button button--default');

        $file = $this->getFileModelForFieldData($data);

        if ($file) {
            $fp_edit->setSelected($file->file_id);
            $title = $file->title;
        } else {
            $title = $data;
            if (preg_match('/^{filedir_(\d+)}/', (string) $data, $matches)) {
                $title = str_replace($matches[0], '', $data);
            }
        }

        ee()->cp->add_js_script(array(
            'file' => array(
                'fields/file/control_panel',
                'fields/file/file_field_drag_and_drop',
                'cp/publish/entry-list'
            ),
        ));

        ee()->javascript->set_global([
            'fileManager.fileDirectory.createUrl' => ee('CP/URL')->make('files/uploads/create')->compile(),
        ]);

        ee()->file_field->loadDragAndDropAssets();

        return ee('View')->make('file:publish')->render(array(
            'field_name' => $field_name,
            'value' => $data,
            'file' => $file,
            'title' => $title,
            'is_image' => ($file && $file->isImage()),
            'thumbnail' => ee('Thumbnail')->get($file)->url,
            'fp_url' => $fp->getUrl(),
            'fp_edit' => $fp_edit,
            'allowed_directory' => $allowed_file_dirs,
            'role_allowed_dirs' => $role_allowed_dirs,
            'content_type' => $content_type
        ));
    }

    /**
     * Takes a string like `{filedir_1}somefile.jpg` and returns a file model for it
     *
     * @param string $data Standard file field data string
     * @return File Model
     */
    public function getFileModelForFieldData($data)
    {
        $file = null;

        if (empty($data)) {
            return $file;
        }

        // If file field is just a file ID
        if (! empty($data) && is_numeric($data)) {
            $file = ee('Model')->get('File', $data)->with('UploadDestination')->first(true);
        } elseif (is_string($data) && preg_match('/^{file\:(\d+)\:url}/', $data, $matches)) {
            // If the file field is in the "{file:XX:url}" format
            // Set upload directory ID and file name
            $file_id = $matches[1];
            $file = ee('Model')->get('File', $file_id)->with('UploadDestination')->first(true);
        } elseif (is_string($data) && preg_match('/^{filedir_(\d+)}/', $data, $matches)) {
            // If the file field is in the "{filedir_n}image.jpg" format
            // Set upload directory ID and file name
            $dir_id = $matches[1];
            $file_name = str_replace($matches[0], '', $data);

            $file = ee('Model')->get('File')
                ->with('UploadDestination')
                ->filter('file_name', $file_name)
                ->filter('upload_location_id', $dir_id)
                ->filter('site_id', ee()->config->item('site_id'))
                ->first(true);
        }

        return $file;
    }

    /**
     * Initialize the file browser given a configuration array and an endpoint url
     * @param array $config Associative array containing five different keys and values:
     *  - publish: set to TRUE if you're on the publish page, optionally
     *      just pass an empty array or none at all for the same behavior
     *  - trigger: the selector to pass to jQuery to create a trigger for
     *      the file browser
     *  - field_name: the field you're operating on. If undefined, it will
     *      assume the name is userfile
     *  - settings: JSON object defining the content type and directory
     *      e.g. {"content_type": "all/image", "directory": "all/<directory_id>"}
     *  - callback: Javascript function that will be called when an image
     *      is selected. e.g. function (file, field) { console.log(file, field); }
     *      file is an object of the selected file's data, and field is a
     *      jQuery object representing the field from the field_name given
     * @param string $endpoint_url The URL the file browser will hit
     */
    public function browser($config = array(), $endpoint_url = 'addons/settings/filepicker/modal')
    {
        ee()->lang->loadfile('content');

        // Are we on the publish page? If so, go ahead and load up the publish
        // page javascript files
        if (empty($config) or (isset($config['publish']) and $config['publish'] === true)) {
            ee()->javascript->set_global(array(
                'filebrowser' => array(
                    'publish' => true
                )
            ));
        } elseif (isset($config['trigger'], $config['callback'])) {
            // No? Make sure we at least have a trigger and a callback
            $field_name = (isset($config['field_name'])) ? "'{$config['field_name']}'," : '';

            $settings = (isset($config['settings'])) ? $config['settings'] . ', ' : '';

            ee()->javascript->ready("
				$.ee_filebrowser.add_trigger('{$config['trigger']}', {$field_name}{$settings}{$config['callback']});
			");
        } else {
            return;
        }

        $this->_browser_css();
        $this->_browser_javascript($endpoint_url);
    }

    /**
     * Validate's the data by checking to see if they used the normal file
     * field or the file browser
     *
     * USE THIS BEFORE format_data()
     *
     * @param string $data The data in the field we're validating
     * @param string $field_name The name of the field we're validating
     * @param string $required Set to 'y' if the field is required
     * @param array  $grid Array of data needed to validate a Grid field
     * @return array Associative array containing ONLY the name of the
     *              file uploaded or an empty value and an error if not valid
     */
    public function validate($data, $field_name, $required = 'n', $grid = array())
    {
        $entry_id = (int) ee()->input->post('entry_id');

        $dir_field = $field_name . '_directory';
        $existing_field = $field_name . '_existing';
        $hidden_field = $field_name . '_hidden_file';
        $hidden_dir = (ee()->input->post($field_name . '_hidden_dir')) ? ee()->input->post($field_name . '_hidden_dir') : ee()->input->post($field_name . '_directory');
        $allowed_dirs = array();

        $directory_input = ee()->input->post($dir_field);
        $existing_input = ee()->input->post($existing_field);
        $hidden_input = ee()->input->post($hidden_field);

        // Default to blank, set POST to allow form validation to modify the value
        $filename = $_POST[$field_name] = '';

        // Default directory
        $upload_directories = $this->_get_upload_prefs();

        // Directory selected?
        $filedir = $directory_input ? $directory_input : $hidden_dir;

        foreach ($upload_directories as $row) {
            $allowed_dirs[] = $row->id;
        }

        // Upload or maybe just a path in the hidden field?
        if ($existing_input) {
            $filename = $existing_input;
        } elseif ($hidden_input) {
            $filename = $hidden_input;
        } elseif (isset($_FILES[$field_name]) && (! empty($_FILES[$field_name]['name'])) && in_array($filedir, $allowed_dirs)) {
            ee()->load->library('filemanager');
            $data = ee()->filemanager->upload_file($filedir, $field_name);

            if (array_key_exists('error', $data)) {
                return $data['error'];
            } else {
                if (bool_config_item('file_manager_compatibility_mode')) {
                    $filename = $data['file_name'];
                } else {
                    $filename = $data['file_id'];
                }
            }
        } else {
            // Check we're not exceeding PHP's post_max_size
            ee()->load->library('filemanager');

            if (! ee()->filemanager->validate_post_data()) {
                ee()->lang->load('upload');

                return array('value' => '', 'error' => lang('upload_file_exceeds_limit'));
            }
        }

        // If the current file directory is not one the user has access to
        // make sure it is an edit and value hasn't changed
        if ($filename && ! in_array($filedir, $allowed_dirs)) {
            // Some legacy fields will have only a full path specified
            if ($filedir == '') {
                return array('value' => $filename);
            }

            if (! $entry_id) {
                return array('value' => '', 'error' => lang('directory_no_access'));
            }

            // The existing directory couldn't be selected because they didn't have permission to upload
            // Let's make sure that the existing file in that directory is the one that's going back in

            ee()->db->select($field_name);
            $table = 'channel_data';

            // Different DB selection criteria for Grid
            if (! empty($grid['grid_row_id'])) {
                ee()->db->where('row_id', $grid['grid_row_id']);
                $table = 'grid_field_' . $grid['grid_field_id'];
            } else {
                ee()->db->where('entry_id', $entry_id);
                $table = 'channel_data';
            }

            $query = ee()->db->get($table);

            if ($query->num_rows() == 0) {
                return array('value' => '', 'error' => lang('directory_no_access'));
            }

            if ('{filedir_' . $hidden_dir . '}' . $filename != $query->row($field_name) || (is_numeric($filename) && '{file:' . $filename . ':url}' != $query->row($field_name))) {
                return array('value' => '', 'error' => lang('directory_no_access'));
            }
        }

        if ($required == 'y' && ! $filename) {
            return array('value' => '', 'error' => lang('required'));
        }

        return array(
            'value' => (string) $this->format_data($filename, $hidden_dir)
        );
    }

    /**
     * Format's the data of a file field given the name of the file and
     * the directory_id
     *
     * @param string $data The name of the file
     * @param integer $directory_id The directory ID
     * @return string The formatted field data e.g. {filedir_1}file.ext
     */
    public function format_data($file_name, $directory_id = 0)
    {
        if ($file_name != '') {
            if (is_numeric($file_name) && !bool_config_item('file_manager_compatibility_mode')) {
                return '{file:' . $file_name . ':url}';
            }
            if (! empty($directory_id)) {
                return '{filedir_' . $directory_id . '}' . $file_name;
            }

            return $file_name;
        }
    }

    /**
     * Caches file data about to be parsed by the channel module. Instead of querying
     * for individual files inside the entries loop, we'll query for everything we need
     * before the loop starts, significantly reducing queries.
     *
     * @param array $data   Array of file field data we're going to query for
     * @return void
     */
    public function cache_data($data = array())
    {
        if (empty($data)) {
            return false;
        }

        ee()->load->model('file_model');

        // We'll keep track of file names and file IDs collected
        $file_names = array();
        $file_ids = array();
        $dir_ids = array();

        // Don't deal with duplicate data, files are the same from entry to entry
        $data = array_unique($data);

        foreach ($data as $field_data) {
            // If the file field is in the "{file:XX:url}" format
            if (preg_match('/^{file\:(\d+)\:url}/', (string) $field_data, $matches)) {
                $file_ids[] = $matches[1];
            } elseif (preg_match('/^{filedir_(\d+)}/', (string) $field_data, $matches)) {
                // If the file field is in the "{filedir_n}image.jpg" format
                $dir_ids[] = $matches[1];
                $file_names[] = str_replace($matches[0], '', $field_data);
            } elseif (! empty($field_data) && is_numeric($field_data)) {
                // If file field is just a file ID, much simpler
                $file_ids[] = $field_data;
            }
        }

        $dir_ids = array_unique($dir_ids);

        $file_names = array_diff($file_names, $this->_file_names);
        $this->_file_names = array_merge($this->_file_names, $file_names);

        // Query for files based on file names and directory ID
        if (! empty($file_names)) {
            $file_names = $this->get_files_by_name($file_names, $dir_ids);
        }

        $file_ids = array_diff($file_ids, $this->_file_ids);
        $this->_file_ids = array_merge($this->_file_ids, $file_ids);

        // Query for files based on file ID
        if (! empty($file_ids)) {
            $file_ids = ee()->file_model->get_files_by_id($data)->result_array();
        }

        // Merge our results into our cached array
        $this->_files = array_merge(
            $this->_files, // Merge itself in case more than one file field is processed
            $file_names,
            $file_ids
        );
    }

    /**
     * Queries for files based on file name using the Models and returns a
     * File_field library-compatible array of file information
     *
     * @param   array   $file_names Array of file names to search for
     * @param   int     $dir_ids    Array of directory IDs to search in
     * @return  array   File data as array, with model object accessible on 'model_object' key
     */
    private function get_files_by_name($file_names, $dir_ids)
    {
        if (empty($file_names) or empty($dir_ids)) {
            return false;
        }

        if (! is_array($file_names)) {
            $file_names = array($file_names);
        }

        if (! is_array($dir_ids)) {
            $dir_ids = array($dir_ids);
        }

        $files = ee('Model')->get('File')
            ->with('UploadDestination')
            ->filter('file_name', 'IN', $file_names)
            ->filter('upload_location_id', 'IN', $dir_ids)
            ->all();

        $files_as_array = array();

        foreach ($files as $file) {
            $files_as_array[] = array_merge($file->toArray(), array('model_object' => $file));
        }

        return $files_as_array;
    }

    /**
     * Searches the local _files array for a particular file based on a specific
     * key and value, and queries the database for the file if it doesn't exist.
     *
     * @param mixed $file_reference File ID or file name of file to return
     * @param string $dir_id        Directory ID file is in, if searching by file name
     * @return array                File information
     */
    public function get_file($file_reference = null, $dir_id = null)
    {
        // This is what we're returning, we'll be overwriting it by the end of
        // the function if all goes well
        $file = false;

        // URL-decode the reference bfore searching or querying
        $file_reference = rawurldecode($file_reference);

        if ($file_reference != null) {
            // Assign the key (field) and value we'll be searching by
            $key = (is_numeric($file_reference)) ? 'file_id' : 'file_name';

            // Loop through cached files
            foreach ($this->_files as $file) {
                // See if key exists
                if (isset($file[$key])) {
                    // If value exists, return the file and stop the search
                    if (
                        ($key == 'file_id' and $file[$key] == $file_reference) or
                        // If we're searching by file name, make sure we're grabbing the
                        // correct file in the case that we cached two files with the same
                        // name but in different upload directories.
                        ($key == 'file_name' and $file[$key] == $file_reference
                            and $file['upload_location_id'] == $dir_id)
                    ) {
                        return $file;
                    }
                }
            }

            // If we got here, we need to query for the file
            // Query based on file ID
            if (is_numeric($file_reference)) {
                $query = ee('Model')->get('File')
                ->with('UploadDestination')
                ->filter('file_id', '=', $file_reference);

                if ($dir_id) {
                    $query->filter('upload_location_id', '=', $dir_id);
                }

                $file = $query->first();
                if (!empty($file)) {
                    $file = array_merge($file->toArray(), array('model_object' => $file));
                }
            } else {
                // Query based on file name and directory ID
                $files = $this->get_files_by_name($file_reference, $dir_id);
                $file = array_shift($files);
            }

            $this->_files[] = $file;
        }

        return $file;
    }

    /**
     * Parse field contents, which may be in the {filedir_n} format for may be
     * a file ID.
     *
     * @access  public
     * @param   string $data Field contents
     * @return  array|boolean Information about file and upload directory, false if there is no file
     */
    public function parse_field($data)
    {
        if (empty($data)) {
            return false;
        }

        // If file field is just a file ID
        if (is_numeric($data)) {
            $file = $this->get_file($data);
        } elseif (is_string($data) && preg_match('/^{file\:(\d+)\:url}/', $data, $matches)) {
            // If the file field is in the "{file:XX:url}" format
            // Set upload directory ID and file name
            $file_id = $matches[1];
            $file = $this->get_file($file_id);
        } elseif (is_string($data) && preg_match('/^{filedir_(\d+)}/', (string) $data, $matches)) {
            // If the file field is in the "{filedir_n}image.jpg" format
            // Set upload directory ID and file name
            $dir_id = $matches[1];
            $file_name = str_replace($matches[0], '', $data);

            $file = $this->get_file($file_name, $dir_id);
        }

        // If there is no file, but data was passed in, create a dummy file
        // array to pass back the data we were given. This is likely to
        // happen when the old style of category_image is passed in and we
        // don't recognize it because it's in a URL format. So, we'll return
        // the URL so that people's category images continue to work.
        if (empty($file) and ! empty($data)) {
            if (is_array($data)) {
                $file = $data;
            } else {
                $file = array(
                    'url' => $data,
                    'file_name' => $data,
                    'filename' => '',
                    'extension' => '',
                    'path' => '',
                    'upload_location_id' => '',
                    'file_hw_original' => ''
                );
                return $file;
            }
        } elseif (empty($file) and empty($data)) {
            return false;
        }

        // Get the cached upload preferences
        $upload_dir = $this->_get_upload_prefs();

        if (! isset($upload_dir[$file['upload_location_id']])) {
            return false;
        }

        $upload_dir = $upload_dir[$file['upload_location_id']];

        // save the file name for use in the file system as well as the URL
        $fs_file_name = $file['file_name'];
        $file['file_name'] = rawurlencode($file['file_name']);

        // Set additional data based on what we've gathered
        $file['raw_output'] = $data;
        $file['raw_content'] = $data;
        $file['extension'] = substr(strrchr($file['file_name'], '.'), 1);
        $file['filename'] = basename($file['file_name'], '.' . $file['extension']); // backwards compatibility

        if (!empty($file['model_object'])) {
            $file['path'] = $file['model_object']->getBaseUrl();
            $file['url'] = $file['model_object']->getAbsoluteURL();
        } else {
            $filesystem = $upload_dir->getFilesystem();
            try {
                $file['path'] = $filesystem->getUrl();
                $file['url'] = $filesystem->getUrl($file['file_name']);
            } catch (\Exception $e) {
                $file['path'] = '';
                $file['url'] = $file['file_name'];
            }
        }

        $dimensions = explode(" ", $file['file_hw_original']);

        $file['width'] = isset($dimensions[1]) ? $dimensions[1] : '';
        $file['height'] = isset($dimensions[0]) ? $dimensions[0] : '';

        // Pre and post formatting
        $file['image_pre_format'] = $upload_dir->pre_format;
        $file['image_post_format'] = $upload_dir->post_format;
        $file['file_pre_format'] = $upload_dir->file_pre_format;
        $file['file_post_format'] = $upload_dir->file_post_format;

        // Image/file properties
        $file['image_properties'] = $upload_dir->properties;
        $file['file_properties'] = $upload_dir->file_properties;

        $file['file_size:human'] = (string) ee('Format')->make('Number', $file['file_size'])->bytes();
        $file['file_size:human_long'] = (string) ee('Format')->make('Number', $file['file_size'])->bytes(false);

        $file['folder_id'] = $file['directory_id'];

        $file['directory_id'] = $file['upload_location_id'];
        $file['directory_title'] = $upload_dir->name;

        $manipulations = $this->_get_dimensions_by_dir_id($file['upload_location_id']);

        if (! empty($manipulations)) {

            $isManipulated = isset($file['mime_type']) && strpos($file['mime_type'], 'image/') === 0 && strpos($file['mime_type'], 'image/svg') !== 0;

            foreach ($manipulations as $manipulation) {
                $manipulationDir = $isManipulated ? '_' . $manipulation->short_name . '/' : '';
                $file['url:' . $manipulation->short_name] = $file['path'] . $manipulationDir . $file['file_name'];
                if (!empty($file['model_object'])) {
                    if ($upload_dir->getFilesystem()->exists($file['model_object']->getAbsoluteManipulationPath($manipulation->short_name))) {
                        $file['path:' . $manipulation->short_name] = $file['model_object']->getAbsoluteManipulationPath($manipulation->short_name);
                    } else {
                        $file['path:' . $manipulation->short_name] = $file['model_object']->getAbsolutePath();
                    }
                } else {
                    $file['path:' . $manipulation->short_name] = '';
                }

                $dimensions = $manipulation->getNewDimensionsOfFile($file['model_object']);

                $size = $upload_dir->getFilesystem()->getSize($manipulationDir . $fs_file_name);

                $file['width:' . $manipulation->short_name] = $manipulation->width;
                $file['height:' . $manipulation->short_name] = $manipulation->height;
                $file['file_size:' . $manipulation->short_name] = $size;
                $file['file_size:' . $manipulation->short_name . ':human'] = (string) ee('Format')->make('Number', $size)->bytes();
                $file['file_size:' . $manipulation->short_name . ':human_long'] = (string) ee('Format')->make('Number', $size)->bytes(false);

                if ($dimensions) {
                    $file['width:' . $manipulation->short_name] = $dimensions['width'];
                    $file['height:' . $manipulation->short_name] = $dimensions['height'];
                }

                // backwards compat variable
                $file[$manipulation->short_name . '_size'] = $file['file_size:' . $manipulation->short_name];
                $file[$manipulation->short_name . '_height'] = $file['height:' . $manipulation->short_name];
                $file[$manipulation->short_name . '_width'] = $file['width:' . $manipulation->short_name];
                $file[$manipulation->short_name . '_file_url'] = $file['url:' . $manipulation->short_name];
            }
        }

        return $file;
    }

    /**
     * Unlike parse(), this parses all occurances of {filedir_n} from a given
     * string to their actual values and returns the processed string.
     *
     * @access  public
     * @param   string $data The string to parse {filedir_n} in
     * @param   bool   $parse_encoded  Set to TRUE to parse encoded (e.g. &123;)
     *                                 tags
     * @return  string The original string with all {filedir_n}'s parsed
     */
    public function parse_string($data, $parse_encoded = false)
    {
        if (empty($data)) {
            return '';
        }

        if (strpos((string) $data, 'file:') !== false) {
            if (preg_match_all('/{file\:(\d+)\:url}/', (string) $data, $matches, PREG_SET_ORDER)) {
                $file_ids = [];
                foreach ($matches as $match) {
                    $file_ids[] = $match[1];
                }
                $files = ee('Model')->get('File', $file_ids)->with('UploadDestination')->all();
                foreach ($files as $file) {
                    $data = str_replace('{file:' . $file->file_id . ':url}', $file->getAbsoluteURL(), $data);
                }
            }
        }

        $pattern = ($parse_encoded)
            ? '/(?:{|&#123;)filedir_(\d+)(?:}|&#125;)/'
            : '/{filedir_(\d+)}/';

        // Find each instance of {filedir_n}
        if (strpos((string) $data, 'filedir_') !== false && preg_match_all($pattern, (string) $data, $matches, PREG_SET_ORDER)) {
            ee()->load->model('file_upload_preferences_model');
            $file_dirs = ee()->file_upload_preferences_model->get_paths();

            // Replace each match
            foreach ($matches as $match) {
                if (isset($file_dirs[$match[1]])) {
                    $data = str_replace($match[0], $file_dirs[$match[1]], $data);
                }
            }
        }

        return $data;
    }

    /**
     * Get upload preferences and keep it cached in the class
     *
     * @return array Array of upload preferences
     */
    private function _get_upload_prefs()
    {
        if (empty($this->_upload_prefs)) {
            $this->_upload_prefs = $directories = ee('Model')->get('UploadDestination')->all()->indexBy('id');
        }

        return $this->_upload_prefs;
    }

    /**
     * Add the file browser CSS to the head
     */
    private function _browser_css()
    {
        ee()->cp->add_to_head(ee()->view->head_link('css/file_browser.css'));
    }

    /**
     * Loads up javascript dependencies and global variables for the file
     * browser and file uploader
     */
    private function _browser_javascript($endpoint_url)
    {
        ee()->cp->add_js_script('plugin', array('tmpl', 'ee_table'));

        // Include dependencies
        ee()->cp->add_js_script(array(
            'file' => array(
                'vendor/underscore',
                'files/publish_fields'
            ),
            'plugin' => array(
                'ee_filebrowser',
                'ee_fileuploader',
                'tmpl'
            )
        ));

        ee()->load->helper('html');

        ee()->javascript->set_global(array(
            'lang' => array(
                'resize_image' => lang('resize_image'),
                'or' => lang('or'),
                'return_to_publish' => lang('return_to_publish')
            ),
            'filebrowser' => array(
                'endpoint_url' => $endpoint_url,
                'window_title' => lang('file_manager'),
                'next' => anchor(
                    '#',
                    img(
                        ee()->cp->cp_theme_url . 'images/pagination_next_button.gif',
                        array(
                            'alt' => lang('next'),
                            'width' => 13,
                            'height' => 13
                        )
                    ),
                    array(
                        'class' => 'next'
                    )
                ),
                'previous' => anchor(
                    '#',
                    img(
                        ee()->cp->cp_theme_url . 'images/pagination_prev_button.gif',
                        array(
                            'alt' => lang('previous'),
                            'width' => 13,
                            'height' => 13
                        )
                    ),
                    array(
                        'class' => 'previous'
                    )
                )
            ),
            'fileuploader' => array(
                'window_title' => lang('file_upload'),
                'delete_url' => 'C=content_files&M=delete_files'
            )
        ));
    }

    /**
     * Gets dimensions for an upload directory and caches them
     *
     * @param int $dir_id   ID of upload directory
     * @return array        Array of image manipulation settings
     */
    private function _get_dimensions_by_dir_id($dir_id)
    {
        if (! isset($this->_manipulations[$dir_id])) {
            $this->_manipulations[$dir_id] = ee('Model')->get('FileDimension')
                ->filter('upload_location_id', $dir_id)
                ->all();
        }

        return $this->_manipulations[$dir_id];
    }

    /**
     * Loads proper JavaScript for drag and drop uploading
     */
    public function loadDragAndDropAssets()
    {
        $upload_prefs = $this->_get_upload_prefs();

        $upload_destinations = [];
        foreach ($upload_prefs as $upload_pref) {
            if (
                ($upload_pref->site_id == 0 || $upload_pref->site_id == ee()->config->item('site_id')) &&
                $upload_pref->module_id == 0
            ) {
                $upload_destinations[$upload_pref->id] = [
                    'label' => $upload_pref->name,
                    'path' => '',
                    'upload_location_id' => $upload_pref->id,
                    'children' => !bool_config_item('file_manager_compatibility_mode') ? $upload_pref->buildDirectoriesDropdown($upload_pref->getId()) : []
                ];
            }
        }

        if (REQ == 'CP') {
            ee()->javascript->set_global([
                'lang.file_dnd_choose_directory' => lang('file_dnd_choose_directory'),
                'lang.file_dnd_choose_file_directory' => lang('file_dnd_choose_file_directory'),
                'lang.file_dnd_upload_to' => lang('file_dnd_upload_to'),
                'lang.file_dnd_choose_directory_before_uploading' => lang('file_dnd_choose_directory_before_uploading'),
                'lang.file_dnd_choose_directory_btn' => lang('file_dnd_choose_directory_btn'),
                'lang.file_dnd_choose_existing' => lang('file_dnd_choose_existing'),
                'lang.file_dnd_dismiss' => lang('file_dnd_dismiss'),
                'lang.file_dnd_drop_file' => lang('file_dnd_drop_file'),
                'lang.file_dnd_drop_files' => lang('file_dnd_drop_files'),
                'lang.file_dnd_file_name' => lang('file_dnd_file_name'),
                'lang.file_dnd_filter_directories' => lang('file_dnd_filter_directories'),
                'lang.file_dnd_images_only' => lang('file_dnd_images_only'),
                'lang.file_dnd_progress' => lang('file_dnd_progress'),
                'lang.file_dnd_resolve_conflict' => lang('file_dnd_resolve_conflict'),
                'lang.file_dnd_conflict' => lang('file_dnd_conflict'),
                'lang.file_dnd_single_file_allowed' => lang('file_dnd_single_file_allowed'),
                'lang.file_dnd_unexpected_error' => lang('file_dnd_unexpected_error'),
                'lang.file_dnd_uploading_to' => lang('file_dnd_uploading_to'),
                'lang.file_dnd_upload_new' => lang('file_dnd_upload_new'),
                'lang.file_dnd_create_directory' => lang('file_dnd_create_directory'),
                'lang.hidden_input' => lang('hidden_input'),
                'lang.file_dnd_no_directories' => lang('file_dnd_no_directories'),
                'lang.file_dnd_no_directories_desc' => lang('file_dnd_no_directories_desc'),

                'dragAndDrop.uploadDesinations' => ee('View/Helpers')->normalizedChoices($upload_destinations),
                'dragAndDrop.endpoint' => ee('CP/URL')->make('addons/settings/filepicker/ajax-upload')->compile(),
                'dragAndDrop.resolveConflictEndpoint' => ee('CP/URL')->make('addons/settings/filepicker/ajax-overwrite-or-rename')->compile(),
                'dragAndDrop.filepickerEndpoint' => ee('CP/FilePicker')->make('all')->getUrl()->compile(),
                'dragAndDrop.filepickerUploadEndpoint' => ee('CP/URL')->make('addons/settings/filepicker/upload')->compile()
            ]);

            ee()->cp->add_js_script([
                'file' => [
                    'fields/file/file_field_drag_and_drop',
                    'fields/file/concurrency_queue',
                    'fields/file/file_upload_progress_table',
                    'fields/file/drag_and_drop_upload',
                    'fields/grid/file_grid'
                ],
            ]);
        }
    }
}

// END File_field class

// EOF

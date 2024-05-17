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
 * Filemanager
 */
class Filemanager
{
    public $config;
    public $theme_url;

    public $upload_errors = false;
    public $upload_data = null;
    public $upload_warnings = false;

    private $_errors = array();
    private $_upload_dirs = array();
    private $_upload_dir_prefs = array();

    private $_xss_on = true;
    private $_memory_tweak_factor = 1.8;

    /**
     * Constructor
     *
     * @access public
     */
    public function __construct()
    {
        ee()->load->library('javascript');
        ee()->lang->loadfile('filemanager');

        ee()->router->set_class('cp');
        ee()->load->library('cp');
        ee()->router->set_class('ee');
        $this->theme_url = ee()->cp->cp_theme_url;
    }

    public function _set_error($error)
    {
        return;
    }

    /**
     * A compatibility version of the `clean_subdir_and_filename` function
     * Does not include server path (but may include subdirectories)
     * Safe to use in EE7 for compatibility with subdirs and cloud storages
     *
     * Cleans the filename to prep it for the system, mostly removing spaces
     * sanitizing the file name and checking for duplicates.
     *
     * @param string $filename The filename to clean the name of
     * @param integer $dir_id The ID of the directory in which we'll check for duplicates
     * @param array $parameters Associative array containing optional parameters
     *   'convert_spaces' (Default: TRUE) Setting this to FALSE will not remove spaces
     *   'ignore_dupes' (Default: TRUE) Setting this to FALSE will check for duplicates
     *
     * @return string Subdirectory path and filename of the file
     */
    public function clean_subdir_and_filename($filename, $dir_id, $parameters = array())
    {
        // at one time the third parameter was (bool) $dupe_check
        if (! is_array($parameters)) {
            $parameters = array('ignore_dupes' => ! $parameters);
        }

        // Establish the default parameters
        $default_parameters = array(
            'convert_spaces' => true,
            'ignore_dupes' => true
        );

        // Get the actual set of parameters and go
        $parameters = array_merge($default_parameters, $parameters);

        $prefs = $this->fetch_upload_dir_prefs($dir_id, true);
        $filesystem = $prefs['directory']->getFilesystem();

        $basename = $filesystem->basename($filename);
        $dirname = ($filesystem->dirname($filename) !== '.') ? $filesystem->dirname($filename) . '/' : '';

        // Remove invisible control characters
        $basename = preg_replace('#\\p{C}+#u', '', $basename);

        // clean up the filename
        if ($parameters['convert_spaces'] === true) {
            $basename = preg_replace("/\s+/", "_", $basename);
        }

        $basename = ee()->security->sanitize_filename($basename);
        $filename = $dirname . $basename;

        if ($parameters['ignore_dupes'] === false) {
            $filename = $prefs['directory']->getFilesystem()->getUniqueFilename($filename);
        }

        return $filename;
    }

    /**
     * Cleans the filename to prep it for the system, mostly removing spaces
     * sanitizing the file name and checking for duplicates.
     *
     * @param string $filename The filename to clean the name of
     * @param integer $dir_id The ID of the directory in which we'll check for duplicates
     * @param array $parameters Associative array containing optional parameters
     *   'convert_spaces' (Default: TRUE) Setting this to FALSE will not remove spaces
     *   'ignore_dupes' (Default: TRUE) Setting this to FALSE will check for duplicates
     *
     * @return string Full path and filename of the file, use `clean_subdir_and_filename` instead to just
     *   get the filename and subdirectory
     */
    public function clean_filename($filename, $dir_id, $parameters = array())
    {
        $filename = $this->clean_subdir_and_filename($filename, $dir_id, $parameters);

        $prefs = $this->fetch_upload_dir_prefs($dir_id, true);

        if ($prefs['adapter'] == 'local') {
            $filename = $prefs['server_path'] . $filename;
        }

        return $filename;
    }

    public function set_upload_dir_prefs($dir_id, array $prefs)
    {
        $required = array_flip(
            array('name', 'server_path', 'url', 'allowed_types', 'max_height', 'max_width')
        );

        $defaults = array(
            'dimensions' => array()
        );

        // make sure all required keys are in there
        if (count(array_diff_key($required, $prefs))) {
            return false;
        }

        // add defaults for optional fields
        foreach ($defaults as $key => $val) {
            if (! isset($prefs[$key])) {
                $prefs[$key] = $val;
            }
        }

        $prefs['max_height'] = ($prefs['max_height'] == '') ? 0 : $prefs['max_height'];
        $prefs['max_width'] = ($prefs['max_width'] == '') ? 0 : $prefs['max_width'];

        $this->_upload_dir_prefs[$dir_id] = $prefs;

        return $prefs;
    }

    /**
     * Get the upload directory preferences for an individual directory
     *
     * @param integer $dir_id ID of the directory to get preferences for
     * @param bool $ignore_site_id If TRUE, returns upload destinations for all sites
     */
    public function fetch_upload_dir_prefs($dir_id, $ignore_site_id = false)
    {
        if (isset($this->_upload_dir_prefs[$dir_id])) {
            return $this->_upload_dir_prefs[$dir_id];
        }

        $dir = ee('Model')->get('UploadDestination', $dir_id);

        if (! $ignore_site_id) {
            $dir->filter('site_id', 'IN', [0, ee()->config->item('site_id')]);
        }

        if ($dir->count() < 1) {
            return false;
        }

        $dir = $dir->first();
        $prefs = $dir->getValues();
        $prefs['directory'] = $dir;

        // Add dimensions to prefs
        $prefs['dimensions'] = array();

        foreach ($dir->FileDimensions as $dimension) {
            $data = array(
                'short_name' => $dimension->short_name,
                'width' => $dimension->width,
                'height' => $dimension->height,
                'watermark_id' => $dimension->watermark_id,
                'resize_type' => $dimension->resize_type,
                'quality' => $dimension->quality
            );

            // Add watermarking prefs
            if ($dimension->Watermark) {
                $data = array_merge($data, $dimension->Watermark->getValues());
            }

            $prefs['dimensions'][$dimension->getId()] = $data;
        }

        // check keys and cache
        return $this->set_upload_dir_prefs($dir_id, $prefs);
    }

    /**
     * Turn XSS cleaning on
     */
    public function xss_clean_on()
    {
        $this->_xss_on = true;
    }

    public function xss_clean_off()
    {
        $this->_xss_on = false;
    }

    /**
     * Checks to see if the image is an editable/resizble image
     *
     * @param string $file_path The full path to the file to check
     * @param string $mime  The file's mimetype
     * @return boolean TRUE if the image is editable, FALSE otherwise
     */
    public function is_editable_image($file_path, $mime)
    {
        if (!file_exists($file_path)) {
            return false;
        }

        if (! $this->is_image($mime)) {
            return false;
        }

        if (function_exists('getimagesize')) {
            if (false === @getimagesize($file_path)) {
                return false;
            }
        }

        if ($mime == 'image/webp' && !defined('IMAGETYPE_WEBP')) {
            return false;
        }

        $imageMimes = [
            'image/gif', // .gif
            'image/jpeg', // .jpg, .jpe, .jpeg
            'image/pjpeg', // .jpg, .jpe, .jpeg
            'image/png', // .png
            'image/x-png', // .png
            'image/webp' // .webp
        ];
        if (!in_array($mime, $imageMimes)) {
            return false;
        }

        return true;
    }

    /**
     * Gets Image Height and Width
     *
     * @param string $file_path The full path to the file to check
     * @return mixed False if function not available, associative array otherwise
     */
    public function get_image_dimensions($file_path, $filesystem = null)
    {
        if (!$filesystem) {
            // Set the filesystem to basedir of $file_path to accommodate tmp dir
            $adapter = new \ExpressionEngine\Library\Filesystem\Adapter\Local(['path' => dirname($file_path)]);
            $filesystem = new \ExpressionEngine\Library\Filesystem\Filesystem($adapter);
        }

        if (! $filesystem->exists($file_path)) {
            return false;
        }

        $mime = $filesystem->getMimetype($file_path);
        if (! $this->is_image($mime)) {
            return false;
        }

        // PHP7.4 does not come with GD JPEG processing by default
        // So, we need to run this check.
        if (function_exists('getimagesize')) {
            $imageSize = $filesystem->actLocally($file_path, function ($path) {
                return @getimagesize($path);
            });

            if ($imageSize && is_array($imageSize)) {
                $imageSizeParsed = [
                    'height' => $imageSize['1'],
                    'width' => $imageSize['0']
                ];

                return $imageSizeParsed;
            }
        }

        // The file is either not an image, or there was an error.
        return false;
    }

    /**
     * Save File
     *
     * @access public
     * @param boolean $check_permissions Whether to check permissions or not
     */
    public function save_file($file_path, $directory, $prefs = array(), $check_permissions = true)
    {
        if (is_numeric($directory)) {
            $directory = $this->fetch_upload_dirs()[$directory];
        }

        if (! $file_path || ! $directory) {
            return $this->_save_file_response(false, lang('no_path_or_dir'));
        }

        if ($check_permissions === true and ! $this->_check_permissions($directory['id'])) {
            // This person does not have access, error?
            return $this->_save_file_response(false, lang('no_permission'));
        }

        // fetch preferences & merge with passed in prefs
        $dir_prefs = $this->fetch_upload_dir_prefs($directory['id'], true);

        if (! $dir_prefs) {
            // something went way wrong!
            return $this->_save_file_response(false, lang('invalid_directory'));
        }

        $prefs['upload_location_id'] = $directory['id'];

        $prefs = array_merge($prefs, $dir_prefs);

        if (! isset($prefs['dimensions'])) {
            $prefs['dimensions'] = array();
        }

        // Figure out the mime type
        ee()->load->helper(array('file', 'xss'));

        $safeForUpload = false;
        $mime = (isset($prefs['mime_type']) && !empty($prefs['mime_type'])) ? $prefs['mime_type'] : $directory['upload_destination']->getFilesystem()->getMimetype($file_path);
        if (empty($mime)) {
            //S3 return false as mime for dirs, need to check that
            $fileInfo = $directory['upload_destination']->getFilesystem()->getWithMetadata($file_path);
            if ($fileInfo['type'] == 'dir' && $directory['upload_destination']->allow_subfolders && !bool_config_item('file_manager_compatibility_mode')) {
                $mime = 'directory';
            } else {
                return $this->_save_file_response(false, lang('security_failure'));
            }
        }

        if ($mime == 'directory' && $directory['upload_destination']->allow_subfolders && !bool_config_item('file_manager_compatibility_mode')) {
            $safeForUpload = true;
        }

        if (! $safeForUpload) {
            if (in_array('all', $prefs['allowed_types'])) {
                $safeForUpload = ee('MimeType')->isSafeForUpload($mime) ? $mime : false;
            } else {
                foreach ($prefs['allowed_types'] as $allowed_type) {
                    if (ee('MimeType')->isOfKind($mime, $allowed_type)) {
                        $safeForUpload = true;

                        break;
                    }
                }
            }
        }

        // We need to be able to turn this off!

        //Apply XSS Filtering to uploaded files?
        if ($this->_xss_on and
            xss_check() and
            ! ee('Security/XSS')->clean($file_path, ee('MimeType')->isImage($mime))) {
            $safeForUpload = false;
        }

        if ($safeForUpload === false) {
            // security check failed
            return $this->_save_file_response(false, lang('security_failure'));
        }

        $prefs['mime_type'] = $mime;

        // Check to see if its an editable image, if it is, try and create the thumbnail
        $image_path = isset($prefs['temp_file']) && !empty($prefs['temp_file']) ? $prefs['temp_file'] : $file_path;
        if ($this->is_editable_image($image_path, $mime)) {
            // Check to see if we have GD and can resize images
            if (! (extension_loaded('gd') && function_exists('gd_info'))) {
                return $this->_save_file_response(false, lang('gd_not_installed'));
            }

            if(!($prefs['image_processed'] ?? false)) {
                // Check and fix orientation
                $orientation = $this->orientation_check($image_path, $prefs);

                if (! empty($orientation)) {
                    $prefs = $orientation;
                }

                $prefs = $this->max_hw_check($image_path, array_merge($prefs, [
                    // If we're using a temp image we need to pass along a null filesystem in some cases
                    'filesystem' => isset($prefs['temp_file']) && !empty($prefs['temp_file']) ? null : $directory['upload_destination']->getFilesystem()
                ]));
            }

            if (! $prefs) {
                return $this->_save_file_response(false, lang('image_exceeds_max_size'));
            }

            // Write $image_path to $file_path
            if($image_path !== $file_path) {
                $directory['upload_destination']->getFilesystem()->write($file_path, file_get_contents($image_path), true);
            }

            // It is important to use the same upload destination object because of filesystem caching
            $prefs['directory'] = $directory['upload_destination'];

            if (! $this->create_thumb($file_path, $prefs)) {
                return $this->_save_file_response(false, lang('thumb_not_created'));
            }
        }

        // Insert the file metadata into the database
        $file = null;
        $model = ($prefs['mime_type'] == 'directory') ? 'Directory' : 'File';
        if (isset($prefs['file_id'])) {
            $file = ee('Model')->get($model, $prefs['file_id'])->first();
        }
        if (empty($file)) {
            $file = ee('Model')->make($model);
        }

        if (! isset($prefs['modified_by_member_id'])) {
            $prefs['modified_by_member_id'] = ee()->session->userdata('member_id');
        }

        if (! isset($prefs['modified_date'])) {
            $prefs['modified_date'] = ee()->localize->now;
        }

        if (isset($prefs['file_name']) or isset($prefs['title'])) {
            $prefs['title'] = (! isset($prefs['title'])) ? $prefs['file_name'] : $prefs['title'];
        }

        $file->set($prefs);

        if ($file->save()) {
            $response = $this->_save_file_response(true, $file->getId());
        } else {
            $response = $this->_save_file_response(false, lang('file_not_added_to_db'));
        }

        $this->_xss_on = true;

        return $response;
    }

    /**
     * Reorient main image if exif info indicates we should
     *
     * @access public
     * @return void
     */
    public function orientation_check($file_path, $prefs)
    {
        if (! function_exists('exif_read_data')) {
            return;
        }

        // Not all images are supported
        $exif = @exif_read_data($file_path);

        if (! $exif or ! isset($exif['Orientation'])) {
            return;
        }

        $orientation = $exif['Orientation'];

        if ($orientation == 1) {
            return;
        }

        // Image is rotated, let's see by how much
        $deg = 0;

        switch ($orientation) {
            case 3:
                $deg = 180;

                break;
            case 6:
                $deg = 270;

                break;
            case 8:
                $deg = 90;

                break;
        }

        if ($deg) {
            ee()->load->library('image_lib');

            ee()->image_lib->clear();

            // Set required memory
            try {
                ee('Memory')->setMemoryForImageManipulation($file_path);
            } catch (\Exception $e) {
                log_message('error', $e->getMessage() . ': ' . $file_path);

                return;
            }

            $config = array(
                'rotation_angle' => $deg,
                'library_path' => ee()->config->item('image_library_path'),
                'image_library' => ee()->config->item('image_resize_protocol'),
                'source_image' => $file_path
            );

            ee()->image_lib->initialize($config);

            if (! ee()->image_lib->rotate()) {
                return;
            }

            $new_image = ee()->image_lib->get_image_properties('', true);
            ee()->image_lib->clear();

            // We need to reset some prefs
            if ($new_image) {
                ee()->load->helper('file');
                $f_size = get_file_info($file_path);
                $prefs['file_height'] = $new_image['height'];
                $prefs['file_width'] = $new_image['width'];
                $prefs['file_hw_original'] = $new_image['height'] . ' ' . $new_image['width'];
                $prefs['height'] = $new_image['height'];
                $prefs['width'] = $new_image['width'];
            }

            return $prefs;
        }
    }

    /**
     * Resizes main image if it exceeds max heightxwidth- adds metadata to file_data array
     *
     * @access public
     * @return void
     */
    public function max_hw_check($file_path, $prefs)
    {
        $force_master_dim = false;

        // Make sure height and width are set
        if (! isset($prefs['height']) or ! isset($prefs['width'])) {
            $upload_dir = $this->fetch_upload_dirs()[$prefs['upload_location_id']];
            $filesystem = array_key_exists('filesystem', $prefs) ? $prefs['filesystem'] : $upload_dir['upload_destination']->getFilesystem();
            $dim = $this->get_image_dimensions($file_path, $filesystem);

            if ($dim == false) {
                return false;
            }

            $prefs['height'] = $dim['height'];
            $prefs['width'] = $dim['width'];
            $prefs['file_height'] = $prefs['height'];
            $prefs['file_width'] = $prefs['width'];
        }

        if (empty($prefs['max_width']) && empty($prefs['max_height'])) {
            return $prefs;
        }

        $config['width'] = $prefs['max_width'];
        $config['height'] = $prefs['max_height'];

        ee()->load->library('image_lib');

        ee()->image_lib->clear();

        // If either h/w unspecified, calculate the other here
        if (empty($prefs['max_width'])) {
            $config['width'] = ((int) $prefs['width'] / (int) $prefs['height']) * (int) $prefs['max_height'];
            $force_master_dim = 'height';
        } elseif (empty($prefs['max_height'])) {
            // Old h/old w * new width
            $config['height'] = ((int) $prefs['height'] / (int) $prefs['width']) * (int) $prefs['max_width'];
            $force_master_dim = 'width';
        }

        // If the original is smaller than the thumb hxw, we'll make a copy rather than upsize
        if (($force_master_dim == 'height' && $prefs['height'] <= $prefs['max_height']) or
                ($force_master_dim == 'width' && $prefs['width'] <= $prefs['max_width']) or
                ($force_master_dim == false && $prefs['width'] <= $prefs['max_width']) or
                ($force_master_dim == false && $prefs['height'] <= $prefs['max_height'])) {
            return $prefs;
        }

        unset($prefs['width']);
        unset($prefs['height']);

        // Set required memory
        try {
            ee('Memory')->setMemoryForImageManipulation($file_path);
        } catch (\Exception $e) {
            log_message('error', $e->getMessage() . ': ' . $file_path);

            return false;
        }

        // Resize

        $config['source_image'] = $file_path;
        $config['maintain_ratio'] = true;
        $config['image_library'] = ee()->config->item('image_resize_protocol');
        $config['library_path'] = ee()->config->item('image_library_path');

        ee()->image_lib->initialize($config);

        if (! ee()->image_lib->resize()) {
            return false;
        }

        $new_image = ee()->image_lib->get_image_properties('', true);

        // We need to reset some prefs
        if ($new_image) {
            ee()->load->helper('file');
            $f_size = get_file_info($file_path);

            $prefs['file_size'] = ($f_size) ? $f_size['size'] : 0;

            $prefs['file_height'] = $new_image['height'];
            $prefs['file_width'] = $new_image['width'];
            $prefs['file_hw_original'] = $new_image['height'] . ' ' . $new_image['width'];
            $prefs['height'] = $new_image['height'];
            $prefs['width'] = $new_image['width'];
        }

        return $prefs;
    }

    /**
     * Checks the permissions of the current user and directory
     * Returns TRUE if they have access FALSE otherwise
     *
     * @access private
     * @param int|string $dir_id  Directory to check permissions on
     * @return boolean  TRUE if current user has access, FALSE otherwise
     */
    private function _check_permissions($dir_id)
    {
        if (ee('Permission')->isSuperAdmin()) {
            return true;
        }

        $member = ee()->session->getMember();
        if (!$member && isset(ee()->channel_form)) {
            ee()->load->add_package_path(PATH_ADDONS . 'channel');
            ee()->load->library('channel_form/channel_form_lib');
            ee()->channel_form_lib->fetch_logged_out_member();
            if (!empty(ee()->channel_form_lib->logged_out_member_id)) {
                $member = ee('Model')->get('Member', ee()->channel_form_lib->logged_out_member_id)->first();
            }
            ee()->load->remove_package_path(PATH_ADDONS . 'channel');
        }
        $assigned_upload_destinations = $member ? $member->getAssignedUploadDestinations()->indexBy('id') : [];

        return isset($assigned_upload_destinations[$dir_id]);
    }

    /**
     * Send save_file response
     *
     * @param boolean  $status  TRUE if save_file passed, FALSE otherwise
     * @param string  $message Message to send
     * @return array  Associative array containing the status and message/file_id
     */
    private function _save_file_response($status, $message = '')
    {
        $key = '';

        if ($status === true) {
            $key = 'file_id';
        } else {
            $key = 'message';
        }

        return array(
            'status' => $status,
            $key => $message
        );
    }

    /**
     * Process Request
     *
     * Main Backend Handler
     *
     * @access public
     * @param mixed configuration options
     * @return void
     */
    public function process_request($config = array())
    {
        $this->_initialize($config);

        $type = ee()->input->get('action');

        switch ($type) {
            case 'setup':
                $this->setup();

                break;
            case 'setup_upload':
                $this->setup_upload();

                break;
            case 'directory':
                $this->directory(ee()->input->get('directory'), true);

                break;
            case 'directories':
                $this->directories(true);

                break;
            case 'directory_contents':
                $this->directory_contents();

                break;
            case 'directory_info':
                $this->directory_info();

                break;
            case 'file_info':
                $this->file_info();

                break;
            case 'upload':
                $this->upload_file(ee()->input->get_post('upload_dir'), false, true);

                break;
            case 'edit_image':
                $this->edit_image();

                break;
            case 'ajax_create_thumb':
                $this->ajax_create_thumb();

                break;
            default:
                exit('Invalid Request');
        }
    }

    /**
     * Initialize
     *
     * @access private
     * @param mixed configuration options
     * @return void
     */
    public function _initialize($config)
    {
        // Callbacks!
        foreach (array('directories', 'directory_contents', 'directory_info', 'file_info', 'upload_file') as $key) {
            $this->config[$key . '_callback'] = isset($config[$key . '_callback']) ? $config[$key . '_callback'] : array($this, '_' . $key);
        }

        unset($config);
    }

    /**
     * Setup
     *
     * The real filebrowser bootstrapping function. Generates the required html.
     *
     * @access private
     * @param mixed configuration options
     * @return void
     */
    public function setup()
    {
        // Make sure there are directories
        $dirs = $this->directories(false, true);
        if (empty($dirs)) {
            return ee()->output->send_ajax_response(array(
                'error' => lang('no_upload_dirs')
            ));
        }

        if (REQ != 'CP') {
            ee()->load->helper('form');
            $action_id = '';

            ee()->db->select('action_id');
            ee()->db->where('class', 'Channel');
            ee()->db->where('method', 'filemanager_endpoint');
            $query = ee()->db->get('actions');

            if ($query->num_rows() > 0) {
                $row = $query->row();
                $action_id = $row->action_id;
            }

            $vars['filemanager_backend_url'] = str_replace('&amp;', '&', ee()->functions->fetch_site_index(0, 0) . QUERY_MARKER) . 'ACT=' . $action_id;
        } else {
            $vars['filemanager_backend_url'] = ee()->cp->get_safe_refresh();
        }

        unset($_GET['action']); // current url == get_safe_refresh()

        $vars['filemanager_directories'] = $this->directories(false);

        // Generate the filters
        // $vars['selected_filters'] = form_dropdown('selected', array('all' => lang('all'), 'selected' => lang('selected'), 'unselected' => lang('unselected')), 'all');
        // $vars['category_filters'] = form_dropdown('category', array());
        $vars['view_filters'] = form_dropdown(
            'view_type',
            array(
                'list' => lang('list'),
                'thumb' => lang('thumbnails')
            ),
            'list',
            'id="view_type"'
        );

        $data = $this->datatables(key($vars['filemanager_directories']));
        $vars = array_merge($vars, $data);

        $filebrowser_html = ee()->load->ee_view('_shared/file/browser', $vars, true);

        ee()->output->send_ajax_response(array(
            'manager' => str_replace(array("\n", "\t"), '', $filebrowser_html), // reduces transfer size
            'directories' => $vars['filemanager_directories']
        ));
    }

    public function datatables($first_dir = null)
    {
        ee()->load->model('file_model');

        // Argh
        ee()->set('_mcp_reference', $this);

        ee()->load->library('table');
        // @todo put .AMP. back ...
        ee()->table->set_base_url('C=content_publish&M=filemanager_actions&action=directory_contents');
        ee()->table->set_columns(array(
            'file_name' => array('header' => lang('name')),
            'file_size' => array('header' => lang('size')),
            'mime_type' => array('header' => lang('kind')),
            'date' => array('header' => lang('date'))
        ));

        $per_page = ee()->input->get_post('per_page');
        $dir_id = ee()->input->get_post('dir_choice');
        $keywords = ee()->input->get_post('keywords');
        $tbl_sort = ee()->input->get_post('tbl_sort');

        // Default to file_name sorting if tbl_sort isn't set
        $state = (is_array($tbl_sort)) ? $tbl_sort : array('sort' => array('file_name' => 'asc'));

        $params = array(
            'per_page' => $per_page ? $per_page : 15,
            'dir_id' => $dir_id,
            'keywords' => $keywords
        );

        if ($first_dir) {
            // @todo rename
            ee()->table->force_initial_load();

            $params['dir_id'] = $first_dir;
        }

        $data = ee()->table->datasource('_file_datasource', $state, $params);

        // End Argh
        ee()->remove('_mcp_reference');

        return $data;
    }

    public function _file_datasource($state, $params)
    {
        $per_page = $params['per_page'];

        $dirs = $this->directories(false, true);
        $dir = $dirs[$params['dir_id']];

        // Check to see if we're sorting on date, if so, change the key to sort on
        if (isset($state['sort']['date'])) {
            $state['sort']['modified_date'] = $state['sort']['date'];
            unset($state['sort']['date']);
        }

        $file_params = array(
            'type' => $dir['allowed_types'],
            'order' => $state['sort'],
            'limit' => $per_page,
            'offset' => $state['offset']
        );

        if (isset($params['keywords'])) {
            $file_params['search_value'] = $params['keywords'];
            $file_params['search_in'] = 'all';
        }

        // Mask the URL if we're coming from the CP
        $sync_files_url = (REQ == "CP") ?
            ee()->cp->masked_url(DOC_URL . 'cp/files/uploads/sync.html') :
            DOC_URL . 'cp/files/uploads/sync.html';

        return array(
            'rows' => $this->_browser_get_files($dir, $file_params),
            'no_results' => sprintf(
                lang('no_uploaded_files'),
                $sync_files_url,
                BASE . AMP . 'C=content_files' . AMP . 'M=file_upload_preferences'
            ),
            'pagination' => array(
                'per_page' => $per_page,
                'total_rows' => ee()->file_model->count_files($params['dir_id'])
            )
        );
    }

    public function setup_upload()
    {
        $base = (defined('BASE')) ? BASE : ee()->functions->fetch_site_index(0, 0) . QUERY_MARKER;

        $vars = array(
            'base_url' => $base . AMP . 'C=content_files_modal'
        );

        ee()->output->send_ajax_response(array(
            'uploader' => ee()->load->ee_view('_shared/file_upload/upload_modal', $vars, true)
        ));
    }

    /**
     * Directory
     *
     * Get information for a single directory
     *
     * @access public
     * @param int  directory id
     * @param bool ajax request (optional)
     * @param bool return all info (optional)
     * @return mixed directory information
     */
    public function directory($dir_id, $ajax = false, $return_all = false, $ignore_site_id = false)
    {
        $return_all = ($ajax) ? false : $return_all;  // safety - ajax calls can never get all info!

        $dirs = $this->directories(false, $return_all, $ignore_site_id);

        $return = isset($dirs[$dir_id]) ? $dirs[$dir_id] : false;

        if ($ajax) {
            die(json_encode($return));
        }

        return $return;
    }

    /**
     * Directories
     *
     * Get all directory information
     *
     * @access public
     * @param bool ajax request (optional)
     * @param bool return all info (optional)
     * @return mixed directory information
     */
    public function directories($ajax = false, $return_all = false, $ignore_site_id = false)
    {
        static $dirs;
        $return = array();

        if ($ajax === false) {
            $this->_initialize($this->config);
        }

        if (! is_array($dirs)) {
            $dirs = call_user_func($this->config['directories_callback'], array('ignore_site_id' => $ignore_site_id));
        }

        if ($return_all and ! $ajax) { // safety - ajax calls can never get all info!
            $return = $dirs;
        } else {
            foreach ($dirs as $dir_id => $info) {
                $return[$dir_id] = $info['name'];
            }
        }

        if ($ajax) {
            ee()->output->send_ajax_response($return);
        }

        return $return;
    }

    /**
     * Directory Contents
     *
     * Get all files in a directory
     *
     * @access public
     * @return mixed directory information
     */
    public function directory_contents()
    {
        $this->datatables();

        $dir_id = ee()->input->get('directory_id');
        $dir = $this->directory($dir_id, false, true);

        $offset = ee()->input->get('offset');
        $limit = ee()->input->get('limit');

        $data = $dir ? call_user_func($this->config['directory_contents_callback'], $dir, $limit, $offset) : array();

        if (count($data) == 0) {
            echo '{}';
        } else {
            $data['files'] = $this->find_thumbs($dir, $data['files']);

            foreach ($data['files'] as &$file) {
                unset($file['encrypted_path']);
            }

            $data['id'] = $dir_id;
            echo json_encode($data);
        }
        exit;
    }

    /**
     * Get the quantities for both files and images within a directory
     */
    public function directory_info()
    {
        $dir_id = ee()->input->get('directory_id');
        $dir = $this->directory($dir_id, false, true);

        $data = $dir ? call_user_func($this->config['directory_info_callback'], $dir) : array();

        if (count($data) == 0) {
            echo '{}';
        } else {
            $data['id'] = $dir_id;
            echo json_encode($data);
        }
        exit;
    }

    /**
     * Get the file information for an individual file (by ID)
     */
    public function file_info()
    {
        $file_id = ee()->input->get('file_id');

        $data = $file_id ? call_user_func($this->config['file_info_callback'], $file_id) : array();

        if (count($data) == 0) {
            echo '{}';
        } else {
            echo json_encode($data);
        }
        exit;
    }

    /**
     * Upload File
     *
     * Upload a files
     *
     * @access public
     * @param int  $dir_id  Upload Directory ID
     * @param string $field  Upload Field Name (optional - defaults to first upload field)
     * @param  boolean $image_only Override to restrict uploads to images
     * @return mixed uploaded file info
     */
    public function upload_file($dir_id = '', $field = false, $image_only = false, $subfolder_id = 0)
    {
        // Fetches all info and is site_id independent
        $dir = $this->directory($dir_id, false, true, true);
        // get model here instead of array?

        // TODO: Check $image_only value to verify it's correct and then clarify
        // with Kevin

        // Override the allowed types of the dir if we're restricting to images
        if ($image_only) {
            $dir->allowed_types = ['img'];
        }

        $data = array('error' => 'No File');

        if (! $dir) {
            $data = array('error' => "You do not have access to this upload directory.");
        } elseif (count($_FILES) > 0) {
            // If the field isn't set, default to first upload field
            if (! $field && is_array(current($_FILES))) {
                $field = key($_FILES);
            }

            // If we actually found the image, go ahead and send it to the
            // callback, most likely _upload_file
            if (isset($_FILES[$field])) {
                $data = call_user_func($this->config['upload_file_callback'], $dir, $field, $subfolder_id);
            }
        }

        return $data;
    }

    /**
     * Set Image Memory for Image Resizing
     *
     * Deprecated in 4.1.0
     *
     * @see ExpressionEngine\Service\Memory\Memory::setMemoryForImageManipulation()
     */
    public function set_image_memory($filename)
    {
        ee()->load->library('logger');
        ee()->logger->deprecated('4.1.0', "ee('Memory')->setMemoryForImageManipulation()");

        try {
            ee('Memory')->setMemoryForImageManipulation($filename);

            return true;
        } catch (\Exception $e) {
            // legacy behavior, error display and logging is handled by the caller
            return false;
        }
    }

    /**
     * Create Thumbnails
     *
     * Create Thumbnails for a file
     *
     * @access public
     * @param string file path
     * @param array file and directory information
     * @param bool Whether or not to create a thumbnail; will do so
     *  regardless of missing_only setting because directory syncing
     *  needs to update thumbnails even if no image manipulations are
     *  updated.
     * @param bool Whether or not to replace missing image
     *  manipulations only (TRUE) or replace them all (FALSE).
     * @return bool success / failure
     */
    public function create_thumb($file_path, $prefs, $thumb = true, $missing_only = true)
    {
        ee()->load->library('image_lib');
        ee()->load->helper('file');

        $filesystem = $prefs['directory']->getFilesystem();
        $file_path = str_replace('\\', '/', $filesystem->absolute($file_path));

        $img_path = ($prefs['directory']->adapter == 'local') ? rtrim(str_replace('\\', '/', $prefs['server_path']), '/') . '/' : '';
        $dirname = rtrim(str_replace('\\', '/', $filesystem->absolute($filesystem->subdirectory($file_path))), '/') . '/';
        if (empty($img_path) || strpos($dirname, $img_path) === 0) {
            $img_path = $dirname;
        }

        // We need to get a temporary local copy of the file in case it's stored on
        // another filesystem. This seems a little wasteful for uploaded files
        // since there is a temporary file already in the $_FILES super global
        $tmp = $filesystem->copyToTempFile($file_path);

        if (! isset($prefs['mime_type'])) {
            // Figure out the mime type
            $prefs['mime_type'] = $filesystem->getMimetype($file_path);
        }

        if (! $this->is_editable_image($tmp['path'], $prefs['mime_type'])) {
            return false;
        }

        // Make sure we have enough memory to process
        try {
            ee('Memory')->setMemoryForImageManipulation($tmp['path']);
        } catch (\Exception $e) {
            log_message('error', $e->getMessage() . ': ' . $file_path);

            return false;
        }

        $dimensions = $prefs['dimensions'];

        if ($thumb) {
            $dimensions[] = array(
                'short_name' => 'thumbs',
                'width' => 380, //73,
                'height' => 380, //60
                'quality' => 90,
                'watermark_id' => 0,
                'resize_type' => 'resize'
            );
        }

        $protocol = ee()->config->item('image_resize_protocol');
        $lib_path = ee()->config->item('image_library_path');

        // Make sure height and width are set
        if (! isset($prefs['height']) or ! isset($prefs['width'])) {
            $dim = $this->get_image_dimensions($tmp['path']);

            if ($dim == false) {
                return false;
            }

            $prefs['height'] = $dim['height'];
            $prefs['width'] = $dim['width'];
        }

        foreach ($dimensions as $size_id => $size) {
            // May be FileDimension object
            if (! is_array($size)) {
                $size = $size->toArray();
            }

            ee()->image_lib->clear();
            $force_master_dim = false;

            $resized_path = $img_path . '_' . $size['short_name'] . '/';

            if (! $filesystem->isDir($resized_path)) {
                $filesystem->mkDir($resized_path);
                $filesystem->addIndexHtml($resized_path);
            } elseif (! $filesystem->isWritable($resized_path)) {
                return false;
            }

            $destination = $resized_path . $prefs['file_name'];

            // Does the thumb image exist
            if ($filesystem->exists($destination)) {
                // Only skip images that are custom image manipulations and when missing_only
                // has been set to TRUE, but always make sure we update normal thumbnails
                if (($missing_only && $size['short_name'] != 'thumbs') ||
                    ($size['short_name'] == 'thumbs' && $thumb == false)) {
                    continue;
                }

                // Delete the image to make way for a new one
                $filesystem->delete($destination);
            }

            // If the size doesn't have a valid height and width, skip resize
            if ($size['width'] <= 0 && $size['height'] <= 0) {
                $size['resize_type'] = 'none';
            }

            if ($size['short_name'] == 'thumbs') {
                if ($prefs['width'] > $prefs['height']) {
                    $size['height'] = 0;
                } else {
                    $size['width'] = 0;
                }
            }

            // If either h/w unspecified, calculate the other here
            if ($size['width'] == '' or $size['width'] == 0) {
                $size['width'] = ($prefs['width'] / $prefs['height']) * $size['height'];
                $force_master_dim = 'height';
            } elseif ($size['height'] == '' or $size['height'] == 0) {
                // Old h/old w * new width
                $size['height'] = ($prefs['height'] / $prefs['width']) * $size['width'];
                $force_master_dim = 'width';
            }

            // Resize
            // $destination = $resized_path . $prefs['file_name'];
            $new = $filesystem->createTempFile();

            $config['source_image'] = $tmp['path'];
            $config['new_image'] = $new['path'];
            $config['maintain_ratio'] = true;
            $config['image_library'] = $protocol;
            $config['library_path'] = $lib_path;
            $config['width'] = $size['width'];
            $config['height'] = $size['height'];
            $config['quality'] = $size['quality'];

            // If the original is smaller than the thumb hxw, we'll make a copy rather than upsize
            if (($force_master_dim == 'height' && $prefs['height'] < $size['height']) or
                ($force_master_dim == 'width' && $prefs['width'] < $size['width']) or
                ($force_master_dim == false &&
                    ($prefs['width'] < $size['width'] && $prefs['height'] < $size['height'])
                ) or
                $size['resize_type'] == 'none') {
                $config['new_image'] = $config['source_image'];
            } elseif (isset($size['resize_type']) and $size['resize_type'] == 'crop') {
                // Scale the larger dimension up so only one dimension of our
                // image fits within the desired dimension
                if ($prefs['width'] > $prefs['height']) {
                    $config['width'] = round($prefs['width'] * $size['height'] / $prefs['height']);

                    // If the new width ends up being smaller than the
                    // resized width
                    if ($config['width'] < $size['width']) {
                        $config['width'] = $size['width'];
                        $config['master_dim'] = 'width';
                    }
                } elseif ($prefs['height'] > $prefs['width']) {
                    $config['height'] = round($prefs['height'] * $size['width'] / $prefs['width']);

                    // If the new height ends up being smaller than the
                    // desired resized height
                    if ($config['height'] < $size['height']) {
                        $config['height'] = $size['height'];
                        $config['master_dim'] = 'height';
                    }
                }
                // If we're dealing with a perfect square image
                elseif ($prefs['height'] == $prefs['width']) {
                    // And the desired image is landscape, edit the
                    // square image's width to fit
                    if ($size['width'] > $size['height'] ||
                        $size['width'] == $size['height']) {
                        $config['width'] = $size['width'];
                        $config['master_dim'] = 'width';
                    }
                    // If the desired image is portrait, edit the
                    // square image's height to fit
                    elseif ($size['width'] < $size['height']) {
                        $config['height'] = $size['height'];
                        $config['master_dim'] = 'height';
                    }
                }

                // First resize down to smallest possible size (greater of height and width)
                ee()->image_lib->initialize($config);

                if (! ee()->image_lib->resize()) {
                    return false;
                }

                // Next set crop accordingly
                $resized_image_dimensions = $this->get_image_dimensions($config['new_image']);
                $config['source_image'] = $config['new_image'];
                $config['x_axis'] = (($resized_image_dimensions['width'] / 2) - ($size['width'] / 2));
                $config['y_axis'] = (($resized_image_dimensions['height'] / 2) - ($size['height'] / 2));
                $config['maintain_ratio'] = false;

                // Change height and width back to the desired size
                $config['width'] = $size['width'];
                $config['height'] = $size['height'];

                ee()->image_lib->initialize($config);

                if (! @ee()->image_lib->crop()) {
                    return false;
                }
            } else {
                $config['master_dim'] = $force_master_dim;

                ee()->image_lib->initialize($config);

                if (! ee()->image_lib->resize()) {
                    return false;
                }
            }

            // Does the thumb require watermark?
            if ($size['watermark_id'] != 0) {
                if (! $this->create_watermark($config['new_image'], $size)) {
                    log_message('error', 'Image Watermarking Failed: ' . $prefs['file_name']);

                    return false;
                }
            }

            // Write transformed file into correct location
            $filesystem->writeStream($destination, fopen($config['new_image'], 'r+'));
            $filesystem->ensureCorrectAccessMode($destination);

            // Clean up newly created temporary file
            fclose($new['file']);
        }

        // Clean up source temporary file
        fclose($tmp['file']);

        return true;
    }

    /**
     * Create Watermark
     *
     * Create a Watermarked Image
     *
     * @access public
     * @param string full path to image
     * @param array file information
     * @return bool success / failure
     */
    public function create_watermark($image_path, $data)
    {
        ee()->image_lib->clear();

        $config = $this->set_image_config($data, 'watermark');
        $config['source_image'] = $image_path;

        ee()->image_lib->initialize($config);

        // watermark it!

        if (! ee()->image_lib->watermark()) {
            return false;
        }

        ee()->image_lib->clear();

        return true;
    }

    /**
     * Ajax Create Thumbnail
     *
     * Create a Thumbnail for a file
     *
     * @access public
     * @param mixed directory information
     * @param mixed file information
     * @return bool success / failure
     */
    public function ajax_create_thumb()
    {
        $data = array('name' => ee()->input->get_post('image'));
        $dir = $this->directory(ee()->input->get_post('dir'), false, true);

        if (! $this->create_thumb($dir, $data)) {
            header('HTTP', true, 500); // Force ajax error
            exit;
        } else {
            // Worked, let's return the thumb path
            echo rtrim($dir['server_path'], '/') . '/' . '_thumbs/' . 'thumb_' . $data['name'];
        }
    }

    /**
     * Get's the thumbnail for a particular image in a directory
     * This assumes the thumbnail has already been created
     *
     * @param array $file Response from save_file, should be an associative array
     *  and minimally needs to contain the file_name and the mime_type/file_type
     *  Optionally, you can use the file name in the event you don't have the
     *  full response from save_file
     * @param integer $directory_id The ID of the upload directory the file is in
     * @param bool $ignore_site_id If TRUE, returns upload destinations for all sites
     * @return string URL to the thumbnail
     */
    public function get_thumb($file, $directory_id, $ignore_site_id = false, $filesystem = null)
    {
        $thumb_info = array(
            'thumb' => PATH_CP_GBL_IMG . 'missing.jpg',
            'thumb_path' => '',
            'thumb_class' => 'no_image',
        );

        if (empty($file)) {
            return $thumb_info;
        }

        $prefs = $this->fetch_upload_dir_prefs($directory_id, $ignore_site_id);
        $filesystem = ($filesystem) ?: $prefs['directory']->getFilesystem();

        // If the raw file name was passed in, figure out the mime_type
        if (! is_array($file) or ! isset($file['mime_type'])) {
            ee()->load->helper('file');

            $file = array(
                'file_name' => $filesystem->relative($file),
                'mime_type' => $filesystem->getMimetype($file)
            );
        }

        // If it's an image, use it's thumbnail, otherwise use the default
        if ($this->is_image($file['mime_type'])) {
            $site_url = str_replace('index.php', '', ee()->config->site_url());

            $subdir = $filesystem->subdirectory($file['file_name']);
            $path = str_replace("$subdir/", "$subdir/_thumbs/", $file['file_name']);

            $thumb_info['thumb'] = $prefs['url'] . $path;
            $thumb_info['thumb_path'] = $filesystem->absolute($path);
            $thumb_info['thumb_class'] = 'image';
        }

        return $thumb_info;
    }

    /**
     * Finds Thumbnails
     *
     * Creates a list of available thumbnails based on the supplied information
     *
     * @access public
     * @param mixed directory information
     * @param mixed list of files
     * @return mixed list of files with added 'has_thumb' boolean key
     */
    public function find_thumbs($dir, $files)
    {
        $thumb_path = rtrim($dir['server_path'], '/') . '/_thumbs';

        if (! is_dir($thumb_path)) {
            return $files;
        }

        ee()->load->helper('directory');
        $map = directory_map($thumb_path, true);

        foreach ($files as $key => &$file) {
            // Hide the thumbs directory
            if ($file['file_name'] == '_thumbs' or ! $file['mime_type'] /* skips folders */) {
                unset($files[$key]);

                continue;
            }

            $file['date'] = ee()->localize->human_time($file['modified_date'], true);
            //$file['size'] = number_format($file['file_size']/1000, 1).' '.lang('file_size_unit');
            $file['has_thumb'] = (in_array('thumb_' . $file['file_name'], $map));
        }

        // if we unset a directory in the loop above our
        // keys are no longer sequential and json won't turn
        // into an array (which is what we need)
        return array_values($files);
    }

    /**
     * This used to only delete files. We decided we do not like that behavior
     * so now it does nothing.
     *
     * @param mixed $dir_id
     * @access public
     * @return void
     */
    public function sync_database($dir_id)
    {
        return;
    }

    /**
     * set_image_config
     *
     * @param  mixed  $data Image configuration array
     * @param  string $type Setting type (e.g. watermark)
     * @access public
     * @return array  Final configuration array
     */
    public function set_image_config($data, $type = 'watermark')
    {
        $config = array();

        if ($type == 'watermark') {
            // Verify the watermark settings actually exist
            if (! isset($data['wm_type']) and isset($data['watermark_id'])) {
                ee()->load->model('file_model');
                $qry = ee()->file_model->get_watermark_preferences($data['watermark_id']);
                $qry = $qry->row_array();
                $data = array_merge($data, $qry);
            }

            $wm_prefs = array('source_image', 'wm_padding', 'wm_vrt_alignment', 'wm_hor_alignment',
                'wm_hor_offset', 'wm_vrt_offset');

            $i_type_prefs = array('wm_overlay_path', 'wm_opacity', 'wm_x_transp', 'wm_y_transp');

            $t_type_prefs = array('wm_text', 'wm_font_path', 'wm_font_size', 'wm_font_color',
                'wm_shadow_color', 'wm_shadow_distance');

            $config['wm_type'] = ($data['wm_type'] == 't' or $data['wm_type'] == 'text') ? 'text' : 'overlay';

            if ($config['wm_type'] == 'text') {
                // If dropshadow not enabled, let's blank the related values
                if (isset($data['wm_use_drop_shadow']) && $data['wm_use_drop_shadow'] == 'n') {
                    $data['wm_shadow_color'] = '';
                    $data['wm_shadow_distance'] = '';
                }

                foreach ($t_type_prefs as $name) {
                    if (isset($data[$name]) && $data[$name] != '') {
                        $config[$name] = $data[$name];
                    }
                }

                if (isset($data['wm_use_font']) && isset($data['wm_font']) && $data['wm_use_font'] == 'y') {
                    $path = APPPATH . '/fonts/';
                    $config['wm_font_path'] = $path . $data['wm_font'];
                }
            } else {
                foreach ($i_type_prefs as $name) {
                    if (isset($data[$name]) && $data[$name] != '') {
                        $config[$name] = $data[$name];
                    }
                }

                $config['wm_overlay_path'] = $data['wm_image_path'];
            }

            foreach ($wm_prefs as $name) {
                if (isset($data[$name]) && $data[$name] != '') {
                    $config[$name] = $data[$name];
                }
            }
        }

        return $config;
    }
    // Default Callbacks
    /**
     * Directories Callback
     *
     * The function that retrieves the actual directory information
     *
     * @access private
     * @return mixed directory list
     */
    private function _directories($params = array())
    {
        $dirs = array();
        $ignore_site_id = (isset($params['ignore_site_id']) && $params['ignore_site_id'] == false) ? false : true;
        $directories = ee('Model')->get('UploadDestination');

        if (!$ignore_site_id) {
            $directories->filter('site_id', 'IN', [0, ee()->config->item('site_id')]);
        }

        $dirs = $directories->all()->indexBy('id');
        foreach ($dirs as $i => $dir) {
            $dirs[$i] = array_merge($dir->toArray(), ['upload_destination' => $dir]);
        }

        return $dirs;
    }

    /**
     * Directory Contents Callback
     *
     * The function that retrieves the actual files from a directory
     *
     * @access private
     * @return mixed directory list
     */
    public function _directory_contents($dir, $limit, $offset)
    {
        return array(
            'files' => $this->_browser_get_files($dir, $limit, $offset)
        );
    }

    /**
     * Gets the files for a particular directory
     * Also, adds short name and file size
     *
     * @param array $dir Associative array containg directory information
     * @param integer $limit Number of files to retrieve
     * @param integer $offset Where to start
     *
     * @access private
     * @return array List of files
     */
    private function _browser_get_files($dir, $limit = 15, $offset = 0)
    {
        ee()->load->model('file_model');
        ee()->load->helper(array('text', 'number'));

        if (is_array($limit)) {
            $params = $limit;
        } else {
            $params = array(
                'type' => $dir['allowed_types'],
                'order' => array(
                    'file_name' => 'asc'
                ),
                'limit' => $limit,
                'offset' => $offset
            );
        }

        $files = ee()->file_model->get_files(
            $dir['id'],
            $params
        );

        if ($files['results'] === false) {
            return array();
        }

        $files = $files['results']->result_array();

        foreach ($files as &$file) {
            $file['file_name'] = rawurlencode($file['file_name']);

            // Get thumb information
            $thumb_info = $this->get_thumb($file, $dir['id']);

            // Copying file_name to name for addons
            $file['name'] = $file['file_name'];

            // Setup the link
            $file['file_name'] = '
                <a href="#"
                    title="' . $file['file_name'] . '"
                    onclick="$.ee_filebrowser.placeImage(' . $file['file_id'] . '); return false;"
                >
                    ' . urldecode($file['file_name']) . '
                </a>';

            $file['short_name'] = ellipsize($file['title'], 13, 0.5);
            $file['file_size'] = byte_format($file['file_size']);
            $file['date'] = ee()->localize->format_date('%F %j, %Y %g:%i %a', $file['modified_date']);
            $file['thumb'] = $thumb_info['thumb'];
            $file['thumb_class'] = $thumb_info['thumb_class'];
        }

        return $files;
    }

    /**
     * Validate Post Data
     *
     * Validates that the POST data did not get dropped, this happens when
     * the content-length of the request is larger than PHP's post_max_size
     *
     *
     * @return bool
     */
    public function validate_post_data()
    {
        ee()->load->helper('number_helper');
        $post_limit = get_bytes(ini_get('post_max_size'));

        return $_SERVER['CONTENT_LENGTH'] <= $post_limit;
    }

    /**
     * Directory Info Callback
     *
     * Returns the file count, image count and url of the directory
     *
     * @param array $dir Directory info associative array
     */
    private function _directory_info($dir)
    {
        ee()->load->model('file_model');

        return array(
            'url' => $dir['url'],
            'file_count' => ee()->file_model->count_files($dir['id']),
            'image_count' => ee()->file_model->count_images($dir['id'])
        );
    }

    /**
     * File Info Callback
     *
     * Returns the file information for use when placing a file
     *
     * @param integer $file_id The File's ID
     */
    private function _file_info($file_id)
    {
        $file = ee('Model')->get('File', $file_id)->first();
        $file_info = $file->toArray();

        $file_info['is_image'] = $file->isImage();

        $thumb_info = $this->get_thumb($file->getAbsolutePath(), $file_info['upload_location_id']);
        $file_info['thumb'] = $thumb_info['thumb'];

        return $file_info;
    }

    /**
     * Upload File Callback
     *
     * The function that handles the file upload logic (allowed upload? etc.)
     *
     * 1. Establish the allowed types for the directory
     *  - If the field is a custom field, make sure it's permissions aren't stricter
     * 2. Upload the file
     *  - Checks to see if XSS cleaning needs to be on
     *  - Returns errors
     * 3. Send file to save_file, which does more security, creates thumbs
     *  and adds it to the database.
     *
     * @access private
     * @param object  $dir   Directory information from the database in array form
     * @param string $field_name Provide the field name in case it's a custom field
     * @return  array  Array of file_data sent to Filemanager->save_file
     */
    private function _upload_file($dir, $field_name, $directory_id = 0)
    {
        // --------------------------------------------------------------------
        // Make sure the file is allowed

        // Is this a custom field?
        if (strpos($field_name, 'field_id_') === 0) {
            $field_id = str_replace('field_id_', '', $field_name);

            ee()->db->select('field_type, field_settings');
            $type_query = ee()->db->get_where('channel_fields', array('field_id' => $field_id));

            if ($type_query->num_rows()) {
                $settings = unserialize(base64_decode($type_query->row('field_settings')));

                // Permissions can only get more strict!
                if (isset($settings['field_content_type']) && $settings['field_content_type'] == 'image') {
                    $allowed_types = 'gif|jpg|jpeg|png|jpe|svg|webp';
                }
            }

            $type_query->free_result();
        }

        // --------------------------------------------------------------------
        // Upload the file

        $field = ($field_name) ? $field_name : 'userfile';
        $original_filename = $_FILES[$field]['name'];
        $clean_filename = basename($this->clean_subdir_and_filename(
            $_FILES[$field]['name'],
            $dir['id'],
            array('ignore_dupes' => true)
        ));

        $upload_path = $dir['server_path'];

        if ($directory_id != 0) {
            $upload_path = ee('Model')->get('Directory', $directory_id)->first()->getAbsolutePath();
        }

        $config = array(
            'upload_destination' => $dir['upload_destination'],
            'file_name' => $clean_filename,
            'upload_path' => $upload_path,
            'max_size' => round((int) $dir['max_size'], 3),
            // @todo If we put these here we don't need to do a dimension check later...
            'max_width' => $dir['max_width'],
            'max_height' => $dir['max_height'],
            'auto_resize' => true,
        );

        // Restricted upload directory?
        if ($dir['allowed_types'] == ['img']) {
            $config['is_image'] = true;
        }

        ee()->load->helper('xss');

        // Check to see if the file needs to be XSS Cleaned
        if (xss_check()) {
            $config['xss_clean'] = true;
        } else {
            $config['xss_clean'] = false;
            $this->xss_clean_off();
        }

        /* -------------------------------------------
        /* Hidden Configuration Variable
        /* - channel_form_overwrite => Allow authors to overwrite their own files via Channel Form
        /* -------------------------------------------*/

        if (bool_config_item('channel_form_overwrite')) {
            $original = ee('Model')->get('File')
                ->filter('file_name', $clean_filename)
                ->filter('upload_location_id', $dir['id'])
                ->first();

            if ($original && $original->uploaded_by_member_id == ee()->session->userdata('member_id')) {
                $config['overwrite'] = true;
            }
        }

        // Upload the file
        ee()->load->library('upload');
        ee()->upload->initialize($config);

        if (! ee()->upload->do_upload($field_name)) {
            return $this->_upload_error(
                ee()->upload->display_errors()
            );
        }

        $file = ee()->upload->data();

        // (try to) Set proper permissions
        @chmod($file['full_path'], FILE_WRITE_MODE);

        // --------------------------------------------------------------------
        // Add file the database

        // Make sure the file has a valid MIME Type
        if (! $file['file_type']) {
            return $this->_upload_error(
                lang('invalid_mime'),
                array(
                    'file_name' => $file['file_name'],
                    'directory_id' => $dir['id']
                )
            );
        }

        $thumb_info = $this->get_thumb($file['full_path'], $dir['id'], false, $dir['upload_destination']->getFilesystem());

        // Build list of information to save and return
        $file_data = array(
            'upload_location_id' => $dir['id'],
            'directory_id' => $directory_id,
            'site_id' => ee()->config->item('site_id'),
            'temp_file' => $_FILES[$field]['tmp_name'] ?? null,

            'file_name' => $file['file_name'],
            'relative_path' => $dir['upload_destination']->getFilesystem()->relative($file['full_path']),
            'orig_name' => $original_filename, // name before any upload library processing
            'file_data_orig_name' => $file['orig_name'], // name after upload lib but before duplicate checks

            'is_image' => $file['is_image'],
            'mime_type' => $file['file_type'],

            'file_thumb' => $thumb_info['thumb'],
            'thumb_class' => $thumb_info['thumb_class'],

            'modified_by_member_id' => ee()->session->userdata('member_id'),
            'uploaded_by_member_id' => ee()->session->userdata('member_id'),

            'file_size' => $file['file_size'] * 1024, // Bring it back to Bytes from KB
            'file_height' => $file['image_height'],
            'file_width' => $file['image_width'],
            'file_hw_original' => $file['image_height'] . ' ' . $file['image_width'],
            'max_width' => $dir['max_width'],
            'max_height' => $dir['max_height']
        );

        /* -------------------------------------------
        /* Hidden Configuration Variable
        /* - channel_form_overwrite => Allow authors to overwrite their own files via Channel Form
        /* -------------------------------------------*/

        if (isset($config['overwrite']) && $config['overwrite'] === true) {
            $file_data['file_id'] = $original->file_id;
        }

        // Check to see if its an editable image, if it is, check max h/w
        // @todo remove - This is already done in Upload Library Line 250 and again in $this->save_file()
        // $file['image_processed'] is set by Upload library to avoid running this code twice
        if (!($file['image_processed'] ?? false) && $this->is_editable_image($file['file_temp'], $file['file_type'])) {
            // Check and fix orientation
            $orientation = $this->orientation_check($file['file_temp'], $file_data);

            if (! empty($orientation)) {
                $file_data = $orientation;
            }

            $file_data = $this->max_hw_check($file['file_temp'], array_merge($file_data, ['filesystem' => null]));

            if (! $file_data) {
                return $this->_upload_error(
                    lang('exceeds_max_dimensions'),
                    array(
                        'file_name' => $file['file_name'],
                        'directory_id' => $dir['id']
                    )
                );
            }

            $file_data['image_processed'] = true;
        }

        // Save file to database
        $saved = $this->save_file($file_data['relative_path'], $dir, $file_data);

        // Return errors from the filemanager
        if (! $saved['status']) {
            return $this->_upload_error(
                $saved['message'],
                array(
                    'file_name' => $file['file_name'],
                    'directory_id' => $dir['id']
                )
            );
        }

        // Merge in information from database
        // This is already populated above
        $file_info = ee('Model')->get('File', $saved['file_id'])->first()->toArray();
        $file_data = array_merge($file_data, $file_info);

        // Stash upload directory prefs in case
        $file_data['upload_directory_prefs'] = $dir;
        $file_data['directory'] = $dir['id'];

        // Change file size to human readable
        ee()->load->helper('number');
        $file_data['file_size'] = byte_format($file_data['file_size']);
        unset($file_data['temp_file']);

        return $file_data;
    }

    /**
     * Sends an upload error and delete's the file based upon
     * available information
     *
     * @param string $error_message The error message to send
     * @param array $file_info Array containing file_id or file_name and directory_id
     * @return array Associative array with error message in it
     */
    public function _upload_error($error_message, $file_info = array())
    {
        if (isset($file_info['file_id'])) {
            ee()->load->model('file_model');
            ee()->file_model->delete_files($file_info['file_id']);
        } elseif (isset($file_info['file_name']) and isset($file_info['directory_id'])) {
            ee()->load->model('file_model');
            ee()->file_model->delete_raw_file($file_info['file_name'], $file_info['directory_id']);
        }

        return array('error' => $error_message);
    }

    /**
     * Overwrite OR Rename Files Manually
     *
     * @access public
     * @param integer $file_id The ID of the file in exp_files
     * @param string $new_file_name The new file name for the file
     * @param string $replace_file_name The temporary replacement name for the file
     * @return mixed TRUE if successful, otherwise it returns the error
     */
    public function rename_file($file_id, $new_file_name, $replace_file_name = '')
    {
        $replace = false;

        // Get the file data form the database
        $previous_data = ee('Model')->get('File', $file_id)->with('UploadDestination')->first();
        $path = $previous_data->getSubfoldersPath();

        // If the new name is the same as the previous, get out of here
        if ($new_file_name == $previous_data->file_name) {
            return array(
                'success' => true,
                'replace' => $replace,
                'file_id' => $file_id
            );
        }

        $old_file_name = $previous_data->file_name;
        $upload_directory = $previous_data->UploadDestination;

        // If they renamed, we need to be sure the NEW name doesn't conflict
        if ($replace_file_name != '' && $new_file_name != $replace_file_name) {
            if ($upload_directory->getFilesystem()->exists($path . $new_file_name)) {
                $replace_data = ee('Model')->get('File')
                    ->filter('file_name', $new_file_name)
                    ->filter('directory_id', $previous_data->directory_id)
                    ->filter('upload_location_id', $upload_directory->id)
                    ->first();

                if ($replace_data) {
                    return array(
                        'success' => false,
                        'error' => 'retry',
                        'replace_filename' => $replace_data->file_name,
                        'file_id' => $file_id
                    );
                }

                return array(
                    'success' => false,
                    'error' => lang('file_exists_replacement_error'),
                    'file_id' => $file_id
                );
            }
        }

        // Check to see if a file with that name already exists
        if ($upload_directory->getFilesystem()->exists($path . $new_file_name)) {
            // If it does, delete the old files and remove the new file
            // record in the database

            $replace = true;
            $previous_data = $this->_replace_file($previous_data, $new_file_name, $upload_directory->id);
            $file_id = $previous_data->file_id;
        }

        // Delete the thumbnails
        $previous_data->deleteGeneratedFiles();

        // Rename the actual file
        $file_path = $this->_rename_raw_file(
            $path . $old_file_name,
            $path . $new_file_name,
            $upload_directory->id
        );

        $new_file_name = basename($file_path);

        // If renaming the file sparked an error return it
        if (is_array($file_path)) {
            return array(
                'success' => false,
                'error' => $file_path['error']
            );
        }

        // Update the file record
        $updated_data = array(
            'file_id' => $file_id,
            'file_name' => $new_file_name,
        );

        // Change title if it was automatically set
        if ($previous_data->title == $previous_data->file_name) {
            $updated_data['title'] = $new_file_name;
        }

        $file = $this->save_file(
            $file_path,
            $upload_directory->id,
            $updated_data
        );

        return array(
            'success' => true,
            'replace' => $replace,
            'file_id' => ($replace) ? $file['file_id'] : $file_id
        );
    }

    /**
     * Deletes the old raw files, and the new file's database records
     *
     * @param object $new_file The data coming from the database for the deleted file
     * @param string $file_name The file name, the existing files are deleted
     *  and the new files are renamed within Filemanager::rename_file
     * @param integer $directory_id The directory ID where the file is located
     * @return object Object from database representing the data of the old item
     */
    public function _replace_file($new_file, $file_name, $directory_id)
    {
        // Get the ID of the existing file
        $existing_file = ee('Model')->get('File')
            ->filter('file_name', '=', $file_name)
            ->filter('upload_location_id', '=', $directory_id)
            ->first();

        // Delete the existing file's raw files, but leave the database record
        $upload_directory = $this->fetch_upload_dir_prefs($directory_id);
        $upload_directory->deleteFiles($file_name);

        // It is possible the file exists but is NOT in the DB yet
        if (empty($existing_file)) {
            $new_file->modified_by_member_id = ee()->session->userdata('member_id');

            return $new_file;
        }

        // Delete the new file's database record, but leave the files
        // ee()->file_model->delete_files($new_file->file_id, false);
        $existing_file->delete(); // This won't leave the files due to events

        // Update file_hw_original, filesize, modified date and modified user
        ee()->file_model->save_file(array(
            'file_id' => $existing_file->file_id, // Use the old file_id
            'file_size' => $new_file->file_size,
            'file_hw_original' => $new_file->file_hw_original,
            'modified_date' => $new_file->modified_date,
            'modified_by_member_id' => ee()->session->userdata('member_id')
        ));

        $existing_file->file_size = $new_file->file_size;
        $existing_file->file_hw_original = $new_file->file_hw_original;
        $existing_file->modified_date = $new_file->modified_date;
        $existing_file->modified_by_member_id = ee()->session->userdata('member_id');

        return $existing_file;
    }

    /**
     * Renames a raw file, doesn't touch the database
     *
     * @param string $old_file_name The old file name
     * @param string $new_file_name The new file name
     * @param integer $directory_id The ID of the directory the file is in
     * @return string The path of the newly renamed file
     */
    public function _rename_raw_file($old_file_name, $new_file_name, $directory_id)
    {
        // Make sure the filename is clean
        $new_file_name = $this->clean_subdir_and_filename($new_file_name, $directory_id);

        // Check they have permission for this directory and get directory info
        $upload_directory = $this->fetch_upload_dir_prefs($directory_id);

        // If this directory doesn't exist then we can't do anything
        if (! $upload_directory) {
            return ['error' => lang('no_known_file')];
        }

        // Check to make sure the file doesn't already exist
        if ($upload_directory['directory']->getFilesystem()->exists($new_file_name)) {
            return ['error' => lang('file_exists') ?? 'file_exists'];
        }

        if (!$upload_directory['directory']->getFilesystem()->rename($old_file_name, $new_file_name)) {
            return ['error' => lang('copy_error') ?? 'copy_error'];
        }

        return $upload_directory['server_path'] . $new_file_name;
    }

    /**
     * Handle the edit actions
     *
     * @access public
     * @return mixed
     */
    public function edit_image()
    {
        ee()->load->library('logger');
        ee()->logger->deprecated('7.0.0');

        ee()->output->set_header("Cache-Control: no-store, no-cache, must-revalidate");
        ee()->output->set_header("Pragma: no-cache");

        $file = str_replace(DIRECTORY_SEPARATOR, '/', ee('Encrypt')->decode(rawurldecode(ee()->input->get_post('file')), ee()->config->item('session_crypt_key')));

        if ($file == '') {
            // nothing for you here
            ee()->session->set_flashdata('message_failure', ee()->lang->line('choose_file'));
            ee()->functions->redirect(BASE . AMP . 'C=content_files');
        }

        // crop takes precendence over resize
        // we need at least a width
        if (ee()->input->get_post('crop_width') != '' and ee()->input->get_post('crop_width') != 0) {
            $config['width'] = ee()->input->get_post('crop_width');
            $config['maintain_ratio'] = false;
            $config['x_axis'] = ee()->input->get_post('crop_x');
            $config['y_axis'] = ee()->input->get_post('crop_y');
            $action = 'crop';

            if (ee()->input->get_post('crop_height') != '') {
                $config['height'] = ee()->input->get_post('crop_height');
            } else {
                $config['master_dim'] = 'width';
            }
        } elseif (ee()->input->get_post('resize_width') != '' and ee()->input->get_post('resize_width') != 0) {
            $config['width'] = ee()->input->get_post('resize_width');
            $config['maintain_ratio'] = ee()->input->get_post("constrain");
            $action = 'resize';

            if (ee()->input->get_post('resize_height') != '') {
                $config['height'] = ee()->input->get_post('resize_height');
            } else {
                $config['master_dim'] = 'width';
            }
        } elseif (ee()->input->get_post('rotate') != '' and ee()->input->get_post('rotate') != 'none') {
            $action = 'rotate';
            $config['rotation_angle'] = ee()->input->get_post('rotate');
        } else {
            if (ee()->input->get_post('is_ajax')) {
                header('HTTP', true, 500);
                exit(ee()->lang->line('width_needed'));
            } else {
                show_error(ee()->lang->line('width_needed'));
            }
        }

        $config['image_library'] = ee()->config->item('image_resize_protocol');
        $config['library_path'] = ee()->config->item('image_library_path');
        $config['source_image'] = $file;

        $path = substr($file, 0, strrpos($file, '/') + 1);
        $filename = substr($file, strrpos($file, '/') + 1, -4); // All editable images have 3 character file extensions
        $file_ext = substr($file, -4); // All editable images have 3 character file extensions

        $image_name_reference = $filename . $file_ext;

        if (ee()->input->get_post('source') == 'resize_orig') {
            $config['new_image'] = $file;
        } else {
            // Add to db using save- becomes a new entry
            $thumb_suffix = ee()->config->item('thumbnail_prefix');

            $new_filename = ee('Filesystem')->getUniqueFilename($path . $filename . '_' . $thumb_suffix . $file_ext);
            $new_filename = str_replace($path, '', $new_filename);

            $image_name_reference = $new_filename;
            $config['new_image'] = $new_filename;
        }

        //  $config['dynamic_output'] = TRUE;

        ee()->load->library('image_lib', $config);

        $errors = '';

        // Cropping and Resizing
        if ($action == 'resize') {
            if (! ee()->image_lib->resize()) {
                $errors = ee()->image_lib->display_errors();
            }
        } elseif ($action == 'rotate') {
            if (! ee()->image_lib->rotate()) {
                $errors = ee()->image_lib->display_errors();
            }
        } else {
            if (! ee()->image_lib->crop()) {
                $errors = ee()->image_lib->display_errors();
            }
        }

        // Any reportable errors? If this is coming from ajax, just the error messages will suffice
        if ($errors != '') {
            if (ee()->input->get_post('is_ajax')) {
                header('HTTP', true, 500);
                exit($errors);
            } else {
                show_error($errors);
            }
        }

        $dimensions = ee()->image_lib->get_image_properties('', true);
        ee()->image_lib->clear();

        // Rebuild thumb
        $this->create_thumb(
            array('server_path' => $path),
            array('name' => $image_name_reference)
        );

        exit($image_name_reference);
    }

    /**
     * Fetch Upload Directories
     *
     * self::_directories() caches upload dirs in _upload_dirs, so we don't
     * query twice if we don't need to.
     *
     * @return array
     */
    public function fetch_upload_dirs($params = array())
    {
        if (empty($this->_upload_dirs)) {
            $this->_upload_dirs = $this->_directories($params);
        }

        return $this->_upload_dirs;
    }

    /**
     *
     *
     */
    public function fetch_files($file_dir_id = null, $files = array(), $get_dimensions = false)
    {
        ee()->load->model('file_upload_preferences_model');

        $upload_dirs = ee()->file_upload_preferences_model->get_file_upload_preferences(
            null,
            $file_dir_id
        );

        $dirs = new stdclass();
        $dirs->files = array();

        // Nest the array one level deep if single row is
        // returned so the loop can do the same work
        if ($file_dir_id != null) {
            $upload_dirs = array($upload_dirs);
        }

        foreach ($upload_dirs as $dir) {
            $dirs->files[$dir['id']] = array();

            $files = ee()->file_model->get_raw_files($dir['server_path'], $dir['allowed_types'], '', false, $get_dimensions, $files);

            foreach ($files as $file) {
                $dirs->files[$dir['id']] = $files;
            }
        }

        return $dirs;
    }

    /**
     * Create a Directory Map
     *
     * Reads the specified directory and builds an array
     * representation of it.  Sub-folders contained with the
     * directory will be mapped as well.
     *
     * @param  string $source_dir path to source
     * @param  int    $directory_depth depth of directories to traverse
     *   (0 = fully recursive, 1 = current dir, etc)
     * @param  bool   $hidden Include hidden files (default: FALSE)
     * @param  string $allowed_types Either "img" for images or "all" for
     *   everything
     * @return array|bool FALSE if we cannot open the directory, an array of
     *   files otherwise.
     */
    public function directory_files_map(\ExpressionEngine\Library\Filesystem\Filesystem $source, $directory_depth = 0, $hidden = false, $allowed_types = 'all')
    {
        if (!$source->isReadable()) {
            return false;
        }

        ee()->load->helper(array('file', 'directory'));
        ee()->load->library('mime_type');

        $filedata = array();
        $new_depth = $directory_depth - 1;
        $indexFiles = array('index.html', 'index.htm', 'index.php');

        foreach ($source->getDirectoryContents() as $path) {
            // Remove '.', '..', and hidden files [optional]
            if (!trim($path, '.') || ($hidden == false && $path[0] == '.')) {
                continue;
            }

            if (!$source->isDir($path) && !in_array($path, $indexFiles)) {
                if ($allowed_types == 'img' && !ee('MimeType')->isImage($source->getMimetype($path))) {
                    continue;
                }

                $filedata[] = $path;
            } elseif (($directory_depth < 1 || $new_depth > 0) && $source->isDir($path)) {
                $filedata[$path] = $source->getDirectoryContents($path, false, $hidden); //directory_map($source_dir . $file . DIRECTORY_SEPARATOR, $new_depth, $hidden);
            }
        }

        sort($filedata);

        return $filedata;
    }

    /**
     * Download Files.
     *
     * This is a helper wrapper around the zip lib and download helper
     *
     * @param  mixed   string or array of urlencoded file names
     * @param  string file directory the files are located in.
     * @param  string optional name of zip file to download
     * @return  mixed  nuttin' or boolean false if everything goes wrong.
     */
    public function download_files($files, $zip_name = 'downloaded_files.zip')
    {
        ee()->load->model('file_upload_preferences_model');

        $upload_prefs = ee()->file_upload_preferences_model->get_file_upload_preferences(1);

        if (count($files) === 1) {
            // Get the file Location:
            $file_data = ee()->db->select('upload_location_id, file_name')
                ->from('files')
                ->where('file_id', $files[0])
                ->get()
                ->row();

            $file_path = reduce_double_slashes(
                $upload_prefs[$file_data->upload_location_id]['server_path'] . '/' . $file_data->file_name
            );

            if (! file_exists($file_path)) {
                return false;
            }

            $file = file_get_contents($file_path);
            $file_name = $file_data->file_name;

            ee()->load->helper('download');
            force_download($file_name, $file);

            return true;
        }

        // Zip up a bunch of files for download
        ee()->load->library('zip');

        $files_data = ee()->db->select('upload_location_id, file_name')
            ->from('files')
            ->where_in('file_id', $files)
            ->get();

        if ($files_data->num_rows() === 0) {
            return false;
        }

        foreach ($files_data->result() as $row) {
            $file_path = reduce_double_slashes(
                $upload_prefs[$row->upload_location_id]['server_path'] . '/' . $row->file_name
            );
            ee()->zip->read_file($file_path);
        }

        ee()->zip->download($zip_name);

        return true;
    }

    /**
     * Get file info
     *
     * At this time, this is a basic wrapper around the CI image lib
     * It's here to make things forward compatible for if/when image uploads
     * could be tossed in the database.
     *
     * @param  string  full system path to the image to examine
     * @return  array
     */
    public function get_file_info($file)
    {
        ee()->load->library('image_lib');

        return ee()->image_lib->get_image_properties($file, true);
    }

    /**
     * Is Image
     *
     * This function has been lifted from the CI file upload class, and tweaked
     * just a bit.
     *
     * @param  string   path to file
     * @return  boolean  TRUE if image, FALSE if not
     */
    public function is_image($mime)
    {
        return ee('MimeType')->isImage($mime);
    }

    /**
     * Fetch Fontlist
     *
     * Retrieves available font file names, returns associative array
     *
     * @return  array
     */
    public function fetch_fontlist()
    {
        $path = APPPATH . '/fonts/';

        $font_files = array();

        if ($fp = @opendir($path)) {
            while (false !== ($file = readdir($fp))) {
                if (stripos(substr($file, -4), '.ttf') !== false) {
                    $name = substr($file, 0, -4);
                    $name = ucwords(str_replace("_", " ", $name));

                    $font_files[$file] = $name;
                }
            }

            closedir($fp);
        }

        return $font_files;
    }

    /**
     * image processing
     *
     * Figures out the full path to the file, and sends it to the appropriate
     * method to process the image.
     *
     * Needs a few POST variables:
     *  - file_id: ID of the file
     *  - file_name: name of the file without full path
     *  - upload_dir: Directory ID
     */
    public function _do_image_processing($redirect = true)
    {
        $file_id = ee()->input->post('file_id');

        $actions = ee()->input->post('action');
        if (! is_array($actions)) {
            $actions = array($actions);
        }

        // Check to see if a file was actually sent...
        if (! ($file_name = ee()->input->post('file_name'))) {
            ee()->session->set_flashdata('message_failure', lang('choose_file'));
            ee()->functions->redirect(BASE . AMP . 'C=content_files');
        }

        // Get the upload directory preferences
        $upload_dir_id = ee()->input->post('upload_dir');
        $upload_prefs = $this->fetch_upload_dir_prefs($upload_dir_id);

        // Clean up the filename and add the full path
        $file_name = ee()->security->sanitize_filename(urldecode($file_name));
        $file_path = reduce_double_slashes(
            $upload_prefs['server_path'] . DIRECTORY_SEPARATOR . $file_name
        );

        // Loop over the actions
        foreach ($actions as $action) {
            // Where are we going with this?
            switch ($action) {
                case 'rotate':
                    $response = $this->_do_rotate($file_path);

                    break;
                case 'crop':
                    $response = $this->_do_crop($file_path);

                    break;
                case 'resize':
                    $response = $this->_do_resize($file_path);

                    break;
                default:
                    return ''; // todo, error
            }

            // Did we break anything?
            if (isset($response['errors'])) {
                if (AJAX_REQUEST) {
                    ee()->output->send_ajax_response($response['errors'], true);
                }

                show_error($response['errors']);
            }
        }

        ee()->load->model('file_model');

        // Update database
        ee()->file_model->save_file(array(
            'file_id' => $file_id,
            'file_hw_original' => $response['dimensions']['height'] . ' ' . $response['dimensions']['width'],
            'file_size' => $response['file_info']['size']
        ));

        // Get dimensions for thumbnail
        $dimensions = ee()->file_model->get_dimensions_by_dir_id($upload_dir_id);
        $dimensions = $dimensions->result_array();

        // Regenerate thumbnails
        $this->create_thumb(
            $file_path,
            array(
                'server_path' => $upload_prefs['server_path'],
                'file_name' => basename($file_name),
                'dimensions' => $dimensions
            ),
            true, // Regenerate thumbnails
            false // Regenerate all images
        );

        // If we're redirecting send em on
        if ($redirect === true) {
            // Send the dimensions back for Ajax requests
            if (AJAX_REQUEST) {
                ee()->output->send_ajax_response(array(
                    'width' => $response['dimensions']['width'],
                    'height' => $response['dimensions']['height']
                ));
            }

            // Otherwise redirect
            ee()->session->set_flashdata('message_success', lang('file_saved'));
            ee()->functions->redirect(
                BASE . AMP .
                'C=content_files' . AMP .
                'M=edit_image' . AMP .
                'upload_dir=' . ee()->input->post('upload_dir') . AMP .
                'file_id=' . ee()->input->post('file_id')
            );
        }
        // Otherwise return the response from the called method
        else {
            return $response;
        }
    }

    /**
     * Image crop
     */
    public function _do_crop($file_path, $filesystem = null)
    {
        $filesystem = ($filesystem) ?: ee('Filesystem');
        $source = $filesystem->copyToTempFile($file_path);
        $new = $filesystem->createTempFile();

        $config = array(
            'width' => ee()->input->post('crop_width'),
            'maintain_ratio' => false,
            'x_axis' => ee()->input->post('crop_x'),
            'y_axis' => ee()->input->post('crop_y'),
            'height' => (ee()->input->post('crop_height')) ? ee()->input->post('crop_height') : null,
            'master_dim' => 'width',
            'library_path' => ee()->config->item('image_library_path'),
            'image_library' => ee()->config->item('image_resize_protocol'),
            'source_image' => $source['path'],
            'new_image' => $new['path']
        );

        // Must initialize seperately in case image_lib was loaded previously
        ee()->load->library('image_lib');
        $return = ee()->image_lib->initialize($config);

        if ($return === false) {
            $errors = ee()->image_lib->display_errors();
        } else {
            if (! ee()->image_lib->crop()) {
                $errors = ee()->image_lib->display_errors();
            }
        }

        $response = array();

        if (isset($errors)) {
            $response['errors'] = $errors;
        } else {
            ee()->load->helper('file');
            $response = array(
                'dimensions' => ee()->image_lib->get_image_properties($new['path'], true),
                'file_info' => get_file_info($new['path'])
            );
            ee('Filesystem')->forceCopy($new['path'], $file_path, $filesystem);
        }

        ee()->image_lib->clear();

        return $response;
    }

    /**
     * Do image rotation.
     */
    public function _do_rotate($file_path, $filesystem = null)
    {
        $filesystem = ($filesystem) ?: ee('Filesystem');
        $source = $filesystem->copyToTempFile($file_path);
        $new = $filesystem->createTempFile();

        $config = array(
            'rotation_angle' => ee()->input->post('rotate'),
            'library_path' => ee()->config->item('image_library_path'),
            'image_library' => ee()->config->item('image_resize_protocol'),
            'source_image' => $source['path'],
            'new_image' => $new['path']
        );

        // Must initialize seperately in case image_lib was loaded previously
        ee()->load->library('image_lib');
        $return = ee()->image_lib->initialize($config);

        if ($return === false) {
            $errors = ee()->image_lib->display_errors();
        } else {
            if (! ee()->image_lib->rotate()) {
                $errors = ee()->image_lib->display_errors();
            }
        }

        $response = array();

        if (isset($errors)) {
            $response['errors'] = $errors;
        } else {
            ee()->load->helper('file');
            $response = array(
                'dimensions' => ee()->image_lib->get_image_properties($new['path'], true),
                'file_info' => get_file_info($new['path'])
            );
            ee('Filesystem')->forceCopy($new['path'], $file_path, $filesystem);
        }

        ee()->image_lib->clear();

        return $response;
    }

    /**
     * Do image resizing.
     */
    public function _do_resize($file_path, $filesystem = null)
    {
        $filesystem = ($filesystem) ?: ee('Filesystem');
        $source = $filesystem->copyToTempFile($file_path);
        $new = $filesystem->createTempFile();

        $config = array(
            'width' => ee()->input->get_post('resize_width'),
            'maintain_ratio' => ee()->input->get_post('constrain'),
            'library_path' => ee()->config->item('image_library_path'),
            'image_library' => ee()->config->item('image_resize_protocol'),
            'source_image' => $source['path'],
            'new_image' => $new['path']
        );

        if (ee()->input->get_post('resize_height') != '') {
            $config['height'] = ee()->input->get_post('resize_height');
        } else {
            $config['master_dim'] = 'width';
        }

        // Must initialize seperately in case image_lib was loaded previously
        ee()->load->library('image_lib');
        $return = ee()->image_lib->initialize($config);

        if ($return === false) {
            $errors = ee()->image_lib->display_errors();
        } else {
            if (! ee()->image_lib->resize()) {
                $errors = ee()->image_lib->display_errors();
            }
        }

        $response = array();

        if (isset($errors)) {
            $response['errors'] = $errors;
        } else {
            ee()->load->helper('file');
            $response = array(
                'dimensions' => ee()->image_lib->get_image_properties($new['path'], true),
                'file_info' => get_file_info($new['path'])
            );
            ee('Filesystem')->forceCopy($new['path'], $file_path, $filesystem);
        }

        ee()->image_lib->clear();

        return $response;
    }
}

// END Filemanager class

// EOF

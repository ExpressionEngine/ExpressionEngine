<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

use ExpressionEngine\Addons\FilePicker\FilePicker;
use ExpressionEngine\Library\CP\EntryManager\ColumnInterface;
use ExpressionEngine\Library\CP\Table;

/**
 * File Fieldtype
 */
class File_ft extends EE_Fieldtype implements ColumnInterface
{
    public $info = array(
        'name' => 'File',
        'version' => '1.1.0'
    );

    public $has_array_data = true;

    public $can_be_cloned = true;

    public $supportedEvaluationRules = ['isEmpty', 'isNotEmpty', 'contains'];

    public $defaultEvaluationRule = 'isNotEmpty';

    public $_dirs = array();

    /**
     * Constructor
     *
     * @access  public
     */
    public function __construct()
    {
        parent::__construct();
        ee()->load->library('file_field');
    }

    /**
     * Validate the upload
     *
     * @access  public
     */
    public function validate($data)
    {
        // Is it required but empty?
        if (
            ($this->settings['field_required'] === true
            || $this->settings['field_required'] == 'y')
                && empty($data)
        ) {
            return array('value' => '', 'error' => lang('required'));
        }

        // Is it optional and empty?
        if (
            ($this->settings['field_required'] === false
            || $this->settings['field_required'] == 'n')
                && empty($data)
        ) {
            return array('value' => '');
        }

        $file = ee()->file_field->getFileModelForFieldData($data);

        if ($file) {
            $check_permissions = false;

            // Is this an edit?
            if ($this->content_id) {
                // Are we validating on grid data?
                if (isset($this->settings['grid_row_id']) || isset($this->settings['grid_row_name'])) {
                    $fluid_field_data_id = (isset($this->settings['fluid_field_data_id'])) ? $this->settings['fluid_field_data_id'] : 0;

                    ee()->load->model('grid_model');
                    $rows = ee()->grid_model->get_entry_rows(
                        $this->content_id,
                        $this->settings['grid_field_id'],
                        $this->settings['grid_content_type'],
                        array(),
                        false,
                        $fluid_field_data_id
                    );

                    // If this filed was we need to check permissions.
                    if (! isset($this->settings['grid_row_id']) || $rows[$this->content_id][$this->settings['grid_row_id']] != $data) {
                        $check_permissions = true;
                    }
                } else {
                    $entry = ee('Model')->get('ChannelEntry', $this->content_id)->first();
                    $field_name = $this->name();

                    // If this filed was we need to check permissions.
                    if ($entry && $entry->$field_name != $data) {
                        $check_permissions = true;
                    }
                }
            } else {
                $check_permissions = true;
            }

            if ($check_permissions) {
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
                if (!$member || $file->memberHasAccess($member) == false) {
                    return array('value' => '', 'error' => lang('directory_no_access'));
                }
            }

            return array('value' => $data);
        }

        return array('value' => '', 'error' => lang('invalid_selection'));
    }

    /**
     * Save the correct value {fieldir_\d}filename.ext
     *
     * @access  public
     */
    public function save($data)
    {
        // validate does all of the work.
        return $data;
    }

    /**
     * Show the publish field
     *
     * @access  public
     */
    public function display_field($data)
    {
        $allowed_file_dirs = (isset($this->settings['allowed_directories']) && $this->settings['allowed_directories'] != 'all')
            ? $this->settings['allowed_directories']
            : 'all';
        $content_type = (isset($this->settings['field_content_type'])) ? $this->settings['field_content_type'] : 'all';
        $existing_limit = (isset($this->settings['num_existing'])) ? $this->settings['num_existing'] : 0;
        $show_existing = (isset($this->settings['show_existing'])) ? $this->settings['show_existing'] : 'n';
        $filebrowser = (REQ == 'CP');

        ee()->javascript->set_global([
            'file.publishCreateUrl' => ee('CP/URL')->make('files/file/view/###', ['modal_form' => 'y'])->compile(),
        ]);

        if (REQ == 'CP') {
            ee()->cp->add_js_script(array(
                'file' => array(
                    'cp/publish/entry-list',
                ),
            ));
            return ee()->file_field->dragAndDropField($this->field_name, $data, $allowed_file_dirs, $content_type);
        }

        $this->_frontend_js();

        return ee()->file_field->field(
            $this->field_name,
            $data,
            $allowed_file_dirs,
            $content_type,
            $filebrowser,
            ($show_existing == 'y') ? $existing_limit : null
        );
    }

    /**
     * Display the field for Pro Variables
     *
     */
    public function var_display_field($data)
    {
        return $this->display_field($data);
    }

    /**
     * Return a status of "warning" if the file is missing, otherwise "ok"
     *
     * @return string "warning" if the file is missing, "ok" otherwise
     */
    public function get_field_status($data)
    {
        $status = 'ok';

        $file = ee()->file_field->getFileModelForFieldData($data);

        if ($file && ! $file->exists()) {
            $status = 'warning';
        }

        return $status;
    }

    /**
     * Basic javascript interaction on the frontend
     *
     * @access  public
     */
    protected function _frontend_js()
    {
        ee()->load->library('javascript');

        if (empty(ee()->session->cache['file_field']['js'])) {
            ee()->session->cache['file_field']['js'] = true;

            $script = <<<JSC
			$(document).ready(function() {
				function setupFileField(container) {
					var last_value = [],
						fileselector = container.find('.no_file'),
						hidden_name = container.find('input[name*="_hidden_file"]').prop('name'),
						placeholder;

					if ( ! hidden_name) {
						return;
					}

					remove = $('<input/>', {
						'type': 'hidden',
						'value': '',
						'name': hidden_name.replace('_hidden_file', '')
					});

					container.find(".remove_file").click(function() {
						container.find("input[type=hidden][name*='hidden']").val(function(i, current_value) {
							last_value[i] = current_value;
							return '';
						});
						container.find(".file_set").hide();
						container.find('.sub_filename a').show();
						fileselector.show();
						container.append(remove);

						return false;
					});

					container.find('.undo_remove').click(function() {
						container.find("input[type=hidden]").val(function(i) {
							return last_value.length ? last_value[i] : '';
						});
						container.find(".file_set").show();
						container.find('.sub_filename a').hide();
						fileselector.hide();
						remove.remove();

						return false;
					});
				}
				// most of them
				$('.file_field').not('.grid_field .file_field').each(function() {
					setupFileField($(this));
				});

				// in grid
				Grid.bind('file', 'display', function(cell) {
					setupFileField(cell);
				})
			});
JSC;
            ee()->javascript->output($script);
        }
    }

    /**
     * Prep the publish data
     *
     * @access  public
     */
    public function pre_process($data)
    {
        return ee()->file_field->parse_field($data);
    }

    /**
     * Runs before the channel entries loop on the front end
     *
     * @param array $data   All custom field data about to be processed for the front end
     * @return void
     */
    public function pre_loop($data)
    {
        ee()->file_field->cache_data($data);
    }

    /**
     * Replace frontend tag
     *
     * @access  public
     */
    public function replace_tag($data, $params = array(), $tagdata = false)
    {
        // Make sure we have file_info to work with
        if ($tagdata !== false && $data === false) {
            $tagdata = ee()->TMPL->parse_variables($tagdata, array());
        }

        // Experimental parameter, do not use
        if (isset($params['raw_output']) && $params['raw_output'] == 'yes') {
            return $data['raw_output'];
        }

        // Let's allow our default thumbs to be used inside the tag pair
        if (isset($data['path']) && isset($data['filename']) && isset($data['extension'])) {
            $data['url:thumbs'] = $data['path'] . '_thumbs/' . $data['filename'] . '.' . $data['extension'];
        }

        if (isset($data['file_id'])) {
            $data['id_path'] = array('/' . $data['file_id'], array('path_variable' => true));
        }

        // Make sure we have file_info to work with
        if ($tagdata !== false && isset($data['file_id'])) {
            return ee()->TMPL->parse_variables($tagdata, array($data));
        }

        // first just try to parse tag data as string
        if (!empty($data['url'])) {
            ee()->load->library('file_field');
            $full_path = ee()->file_field->parse_string($data['url']);

            if (isset($params['wrap'])) {
                return $this->_wrap_it($data, $params['wrap'], $full_path);
            }

            return $full_path;
        }

        //legacy old code, probably never used
        if (! empty($data['path']) && ! empty($data['file_id']) && $data['extension'] !== false) {
            $full_path = $data['path'] . $data['filename'] . '.' . $data['extension'];

            if (isset($params['wrap'])) {
                return $this->_wrap_it($data, $params['wrap'], $full_path);
            }

            return $full_path;
        }

        return '';
    }

    /**
     * Display the field for Pro Variables
     *
     */
    public function var_replace_tag($data, $params = array(), $tagdata = false)
    {
        $data = $this->pre_process($data);
        if ($tagdata === '') {
            $tagdata = false;
        }
        $fn = 'replace_' . ee()->TMPL->fetch_param('modifier', 'tag');
        if (! method_exists($this, $fn)) {
            $fn = 'replace_tag';
        }
        return $this->$fn($data, $params, $tagdata);
    }

    /**
     * Resize an image
     *
     * Supported parameters:
     *  - width
     *  - height
     *  - quality (0-100)
     *  - maintain_ratio (y / n)
     *  - master_dim (width / height)
     */
    public function replace_resize($data, $params = array(), $tagdata = false)
    {
        //this could be the name of pre-saved manipulation, check if params are set
        if (empty($params) || (count($params) == 1 && array_keys($params)[0] == 'wrap')) {
            return $this->replace_tag_catchall($data, $params, $tagdata, 'resize');
        }

        if (empty($data) || !isset($data['model_object'])) {
            return $this->replace_tag($data, $params, $tagdata);
        }
        $data['fs_filename'] = $data['fs_filename'] ?? $data['model_object']->file_name;
        $data['filesystem'] = $data['filesystem'] ?? $data['model_object']->UploadDestination->getFilesystem();
        $data['source_image'] = $data['source_image'] ?? $data['model_object']->getAbsolutePath();

        return $this->process_image('resize', $data, $params, $tagdata);
    }

    /**
     * Crop an image
     *
     * Supported parameters:
     *  - width
     *  - height
     *  - quality (0-100)
     *  - maintain_ratio (y / n)
     *  - x
     *  - y
     */
    public function replace_crop($data, $params = array(), $tagdata = false)
    {
        //this could be the name of pre-saved manipulation, check if params are set
        if (empty($params) || (count($params) == 1 && array_keys($params)[0] == 'wrap')) {
            return $this->replace_tag_catchall($data, $params, $tagdata, 'crop');
        }

        if (empty($data) || !isset($data['model_object'])) {
            return $this->replace_tag($data, $params, $tagdata);
        }
        $data['fs_filename'] = $data['fs_filename'] ?? $data['model_object']->file_name;
        $data['filesystem'] = $data['filesystem'] ?? $data['model_object']->UploadDestination->getFilesystem();
        $data['source_image'] = $data['source_image'] ?? $data['model_object']->getAbsolutePath();

        return $this->process_image('crop', $data, $params, $tagdata);
    }

    /**
     * Resize and then crop
     *
     * Supported parameters:
     *  - resize:width
     *  - resize:height
     *  - resize:quality (0-100)
     *  - resize:maintain_ratio (y / n)
     *  - resize:master_dim (width / height)
     *  - crop:width
     *  - crop:height
     *  - crop:quality (0-100)
     *  - crop:maintain_ratio (y / n)
     *  - crop:x
     *  - crop:y
     */
    public function replace_resize_crop($data, $params = array(), $tagdata = false)
    {
        if (empty($data) || !isset($data['model_object'])) {
            return $this->replace_tag($data, $params, $tagdata);
        }
        $params['function'] = 'resize_crop';

        $data['fs_filename'] = $data['fs_filename'] ?? $data['model_object']->file_name;
        $data['filesystem'] = $data['filesystem'] ?? $data['model_object']->UploadDestination->getFilesystem();
        $data['source_image'] = $data['source_image'] ?? $data['model_object']->getAbsolutePath();

        $params_copy = $params;
        foreach ($params as $key => $val) {
            $clean_key = explode(':', $key);
            if ($clean_key[0] == 'resize' && isset($clean_key[1])) {
                $params[$clean_key[1]] = $val;
            }
        }

        unset($params['wrap']);

        $data['source_image'] = $resized = $this->process_image('resize', $data, $params, false, true);

        $params = $params_copy;
        foreach ($params as $key => $val) {
            $clean_key = explode(':', $key);
            if ($clean_key[0] == 'crop' && isset($clean_key[1])) {
                $params[$clean_key[1]] = $val;
            }
        }

        $out = $this->process_image('crop', $data, $params, $tagdata);

        @unlink($resized);

        return $out;
    }

    /**
     * Rotate an image
     *
     * Supported parameters:
     *  - angle (90, 180, 270, vrt, hor)
     */
    public function replace_rotate($data, $params = array(), $tagdata = false)
    {
        if (empty($data) || !isset($data['model_object'])) {
            return $this->replace_tag($data, $params, $tagdata);
        }
        $data['fs_filename'] = $data['fs_filename'] ?? $data['model_object']->file_name;
        $data['filesystem'] = $data['filesystem'] ?? $data['model_object']->UploadDestination->getFilesystem();
        $data['source_image'] = $data['source_image'] ?? $data['model_object']->getAbsolutePath();

        return $this->process_image('rotate', $data, $params, $tagdata);
    }

    /**
     * Convert to webp
     *
     * Supported parameters same as for resize
     */
    public function replace_webp($data, $params = array(), $tagdata = false)
    {
        if (empty($data) || !isset($data['model_object'])) {
            return $this->replace_tag($data, $params, $tagdata);
        }
        $data['fs_filename'] = $data['fs_filename'] ?? $data['model_object']->file_name;
        $data['filesystem'] = $data['filesystem'] ?? $data['model_object']->UploadDestination->getFilesystem();
        $data['source_image'] = $data['source_image'] ?? $data['model_object']->getAbsolutePath();

        return $this->process_image('webp', $data, $params, $tagdata);
    }

    /**
     * Generic image processing
     */
    private function process_image($function = 'resize', $data = [], $params = array(), $tagdata = false, $return_as_path = false)
    {
        if (!in_array($function, ['resize', 'crop', 'rotate', 'webp'])) {
            return false;
        }

        if (!$data['model_object']->isImage()) {
            return ee()->TMPL->no_results();
        }

        ee()->load->library('image_lib');
        $filename = ee()->image_lib->explode_name($data['fs_filename']);
        if ($function == 'webp') {
            $filename['ext'] = '.webp';
        }
        $new_image = $filename['name'] . '_' . $function . '_' . md5(serialize($params)) . $filename['ext'];
        $data['fs_filename'] = $filename['name'] . '_' . $function . $filename['ext'];

        $new_image_dir = rtrim($data['model_object']->getBaseServerPath() . $data['model_object']->getSubfoldersPath(), '/') . '/_' . $function . DIRECTORY_SEPARATOR;
        if (! $data['filesystem']->isDir($new_image_dir)) {
            $data['filesystem']->mkdir($new_image_dir);
            $data['filesystem']->addIndexHtml($new_image_dir);
        } elseif (!$data['filesystem']->isWritable($new_image_dir)) {
            return false;
        }

        $destination_path = $new_image_dir . $new_image;
        $props = null;

        if (!$data['filesystem']->exists($destination_path)) {
            // We need to get a temporary local copy of the file in case it's stored
            // on another filesystem.
            $source = $data['filesystem']->copyToTempFile($data['source_image']);
            $new = $data['filesystem']->createTempFile();

            $imageLibConfig = array(
                'image_library' => ee()->config->item('image_resize_protocol'),
                'library_path' => ee()->config->item('image_library_path'),
                'source_image' => $source['path'],
                'new_image' => $new['path'],
                'maintain_ratio' => isset($params['maintain_ratio']) ? get_bool_from_string($params['maintain_ratio']) : true,
                'master_dim' => (isset($params['master_dim']) && in_array($params['master_dim'], ['auto', 'width', 'height'])) ? $params['master_dim'] : 'auto',

                'quality' => isset($params['quality']) ? (int) $params['quality'] : 75,
                'x_axis' => isset($params['x']) ? (int) $params['x'] : 0,
                'y_axis' => isset($params['y']) ? (int) $params['y'] : 0,
                'rotation_angle' => (isset($params['angle']) && in_array($params['angle'], ['90', '180', '270', 'vrt', 'hor'])) ? $params['angle'] : null,
            );
            //technically, both dimensions are always required, so we'll set defaults
            if ($imageLibConfig['master_dim'] != 'auto') {
                $imageLibConfig['width'] = 100;
                $imageLibConfig['height'] = 100;
            }
            if (isset($params['width'])) {
                $imageLibConfig['width'] = (int) $params['width'];
                if ($imageLibConfig['master_dim'] == 'auto' && !isset($params['height'])) {
                    $imageLibConfig['master_dim'] = 'width';
                    $imageLibConfig['height'] = 100;
                }
            }
            if (isset($params['height'])) {
                $imageLibConfig['height'] = (int) $params['height'];
                if ($imageLibConfig['master_dim'] == 'auto' && !isset($params['width'])) {
                    $imageLibConfig['master_dim'] = 'height';
                    $imageLibConfig['width'] = 100;
                }
            }

            ee()->image_lib->clear();
            if (!isset($imageLibConfig['width'])) {
                ee()->image_lib->width = '';
            }
            if (!isset($imageLibConfig['height'])) {
                ee()->image_lib->height = '';
            }
            ee()->image_lib->initialize($imageLibConfig);

            if (!ee()->image_lib->$function()) {
                if (ee()->config->item('debug') == 2 or (ee()->config->item('debug') == 1 and ee('Permission')->isSuperAdmin())) {
                    return ee()->image_lib->display_errors();
                }

                return ee()->TMPL->no_results();
            }

            // Write transformed file into correct location
            $data['filesystem']->writeStream($destination_path, fopen($new['path'], 'r+'));
            $data['filesystem']->ensureCorrectAccessMode($destination_path);

            // Get image properties before we destroy local file
            $props = ee()->image_lib->get_image_properties($new['path'], true);

            // Clean up temporary files
            fclose($new['file']);
            fclose($source['file']);
        }

        $fileNameOnModel = $data['model_object']->file_name;
        $data['model_object']->file_name = $new_image;
        $destination_url = $data['model_object']->getAbsoluteManipulationURL($function);
        $data['model_object']->file_name = $fileNameOnModel;

        if (!$props) {
            $tmp = $data['filesystem']->copyToTempFile($destination_path);
            $props = ee()->image_lib->get_image_properties($tmp['path'], true);
            fclose($tmp['file']);
        }

        if ($tagdata === null) {
            // called when chaining modifiers
            $vars = array_merge($data, [
                'url' => $destination_url,
                'source_image' => $destination_path,
                'width' => $props['width'],
                'height' => $props['height']
            ]);

            return $vars;
        } elseif ($tagdata === false) {
            // single tag or call from resize_crop
            if (isset($params['wrap'])) {
                return $this->_wrap_it($data, $params['wrap'], $destination_url);
            }

            return ($return_as_path ? $destination_path : $destination_url);
        } else {
            // tag pair
            $vars = [
                'url' => $destination_url,
                'width' => $props['width'],
                'height' => $props['height']
            ];

            return ee()->TMPL->parse_variables($tagdata, [$vars]);
        }
    }

    /**
     * :length modifier
     */
    public function replace_length($data, $params = array(), $tagdata = false)
    {
        return $data['file_size'];
    }

    /**
     * :raw_content modifier
     */
    public function replace_raw_content($data, $params = array(), $tagdata = false)
    {
        return parent::replace_raw_content($data['raw_output'], $params, $tagdata);
    }

    /**
     * :attr_safe modifier
     */
    public function replace_attr_safe($data, $params = array(), $tagdata = false)
    {
        return parent::replace_attr_safe($data['url'], $params, $tagdata);
    }

    /**
     * :limit modifier
     */
    public function replace_limit($data, $params = array(), $tagdata = false)
    {
        return parent::replace_limit($data['url'], $params, $tagdata);
    }

    /**
     * :form_prep modifier
     */
    public function replace_form_prep($data, $params = array(), $tagdata = false)
    {
        return parent::replace_form_prep($data['url'], $params, $tagdata);
    }

    /**
     * :rot13 modifier (for Seth)
     */
    public function replace_rot13($data, $params = array(), $tagdata = false)
    {
        return str_rot13($data['url']);
    }

    /**
     * :encrypt modifier
     */
    public function replace_encrypt($data, $params = array(), $tagdata = false)
    {
        return parent::replace_encrypt($data['url'], $params, $tagdata);
    }

    /**
     * :url_slug modifier
     */
    public function replace_url_slug($data, $params = array(), $tagdata = false)
    {
        return parent::replace_url_slug($data['filename'], $params, $tagdata);
    }

    /**
     * :censor modifier
     */
    public function replace_censor($data, $params = array(), $tagdata = false)
    {
        return parent::replace_censor($data['title'], $params, $tagdata);
    }

    /**
     * :json modifier
     */
    public function replace_json($data, $params = array(), $tagdata = false)
    {
        return parent::replace_json($data['url'], $params, $tagdata);
    }

    /**
     * :replace modifier
     */
    public function replace_replace($data, $params = array(), $tagdata = false)
    {
        $url = parent::replace_replace($data['url'], $params, $tagdata);
        if (isset($params['wrap'])) {
            return $this->_wrap_it($data, $params['wrap'], $url);
        }
        return $url;
    }

    /**
     * :url_encode modifier
     */
    public function replace_url_encode($data, $params = array(), $tagdata = false)
    {
        return parent::replace_url_encode($data['url'], $params, $tagdata);
    }

    /**
     * :url_decode modifier
     */
    public function replace_url_decode($data, $params = array(), $tagdata = false)
    {
        return parent::replace_url_decode($data['url'], $params, $tagdata);
    }

    /**
     * Replace frontend tag (with a modifier catchall)
     *
     * Here, the modifier is the short name of the image manipulation,
     * e.g. "small" in {about_image:small}
     *
     * @access  public
     */
    public function replace_tag_catchall($data = [], $params = array(), $tagdata = false, $modifier = '')
    {
        // These are single variable tags only, so no need for replace_tag
        $full_path = isset($data['url']) ? $data['url'] : '';
        if ($modifier) {
            if ($modifier == 'frontedit') {
                return $tagdata;
            }

            $key = 'url:' . $modifier;

            if ($modifier == 'thumbs') {
                if (isset($data['path']) && isset($data['filename']) && isset($data['extension'])) {
                    $full_path = $data['path'] . '_thumbs/' . $data['filename'] . '.' . $data['extension'];
                }
            } elseif (isset($data[$key])) {
                $full_path = $data[$key];
            }

            if (is_null($tagdata)) {
                // null means we're chaning modifier to pre-defined manipulation
                // need to set some data and return array instead of string
                if (array_key_exists('path:' . $modifier, $data)) {
                    $data['fs_filename'] = $modifier . '_' . ($data['fs_filename'] ?? $data['model_object']->file_name);
                    $data['source_image'] = $data['path:' . $modifier];
                    $data['url'] = $full_path;
                }
                return $data;
            }

            if (empty($data)) {
                return $tagdata;
            }

            if (isset($params['wrap'])) {
                return $this->_wrap_it($data, $params['wrap'], $full_path);
            }

            return $full_path;
        }
    }

    /**
     * Wrap it helper function
     *
     * @access  private
     */
    public function _wrap_it($data, $type, $full_path)
    {
        if ($type == 'link') {
            ee()->load->helper('url_helper');

            return $data['file_pre_format']
                . anchor($full_path, $data['filename'], $data['file_properties'])
                . $data['file_post_format'];
        } elseif ($type == 'image') {
            $properties = (! empty($data['image_properties'])) ? ' ' . $data['image_properties'] : '';

            return $data['image_pre_format']
                . '<img src="' . $full_path . '"' . $properties . ' alt="' . $data['filename'] . '" />'
                . $data['image_post_format'];
        }

        return $full_path;
    }

    /**
     * Display settings screen
     *
     * @return array
     * @access  public
     */
    public function display_settings($data)
    {
        ee()->lang->loadfile('fieldtypes');
        ee()->load->model('file_upload_preferences_model');

        // And now the directory
        $allowed_directories = (! isset($data['allowed_directories'])) ? 'all' : $data['allowed_directories'];

        // Show existing files? checkbox, default to yes
        $show_existing = (! isset($data['show_existing'])) ? 'y' : $data['show_existing'];

        // Number of existing files to show? 0 means all
        $num_existing = (! isset($data['num_existing'])) ? 50 : $data['num_existing'];

        $directory_choices = array('all' => lang('all'));

        $directory_choices += ee('Model')->get('UploadDestination')
            ->fields('id', 'name')
            ->filter('site_id', 'IN', [0, ee()->config->item('site_id')])
            ->filter('module_id', 0)
            ->order('name', 'asc')
            ->all(true)
            ->getDictionary('id', 'name');

        $settings = array(
            'field_options_file' => array(
                'label' => 'field_options',
                'group' => 'file',
                'settings' => array(
                    array(
                        'title' => 'file_ft_content_type',
                        'desc' => 'file_ft_content_type_desc',
                        'fields' => array(
                            'field_content_type' => array(
                                'type' => 'radio',
                                'choices' => $this->_field_content_options(),
                                'value' => isset($data['field_content_type']) ? $data['field_content_type'] : 'all'
                            )
                        )
                    ),
                    array(
                        'title' => 'file_ft_allowed_dirs',
                        'desc' => 'file_ft_allowed_dirs_desc',
                        'fields' => array(
                            'allowed_directories' => array(
                                'type' => 'radio',
                                'choices' => $directory_choices,
                                'value' => $allowed_directories,
                                'no_results' => [
                                    'text' => sprintf(lang('no_found'), lang('file_ft_upload_directories')),
                                    'link_text' => 'add_new',
                                    'link_href' => ee('CP/URL')->make('files/uploads/create')
                                ]
                            )
                        )
                    )
                )
            ),
            'channel_form_settings_file' => array(
                'label' => 'channel_form_settings',
                'group' => 'file',
                'settings' => array(
                    array(
                        'title' => 'file_ft_show_files',
                        'desc' => 'file_ft_show_files_desc',
                        'fields' => array(
                            'show_existing' => array(
                                'type' => 'yes_no',
                                'value' => $show_existing
                            )
                        )
                    ),
                    array(
                        'title' => 'file_ft_limit',
                        'desc' => 'file_ft_limit_desc',
                        'fields' => array(
                            'num_existing' => array(
                                'type' => 'text',
                                'value' => $num_existing
                            )
                        )
                    )
                )
            )
        );

        if (!array_key_exists($allowed_directories, $directory_choices)) {
            $selectedDir = ee('Model')->get('UploadDestination', $allowed_directories)->with('Site')->first();
            if (!is_null($selectedDir)) {
                $settings['field_options_file']['settings'][1]['fields']['file_field_msm_warning'] = array(
                    'type' => 'html',
                    'content' => ee('CP/Alert')->makeInline('file_field_msm_warning')
                        ->asImportant()
                        ->addToBody(sprintf(lang('file_field_msm_warning'), $selectedDir->name, $selectedDir->Site->site_label))
                        ->cannotClose()
                        ->render()
                );
            }
        }

        return $settings;
    }

    public function grid_display_settings($data)
    {
        $settings = $this->display_settings($data);

        $grid_settings = array();

        foreach ($settings as $value) {
            $grid_settings[$value['label']] = $value['settings'];
        }

        return $grid_settings;
    }

    /**
     * Returns cached dropdown-ready array of upload directories
     */
    private function getDirectories()
    {
        static $directories;

        if (empty($directories)) {
            $directories = ee('Model')->get('UploadDestination')
                ->fields('id', 'name')
                ->filter('site_id', 'IN', [0, ee()->config->item('site_id')])
                ->filter('module_id', 0)
                ->all(true)
                ->getDictionary('id', 'name');
        }

        return $directories;
    }

    /**
     * Returns dropdown-ready array of allowed file types for upload
     */
    private function _field_content_options()
    {
        return array('all' => lang('all'), 'image' => lang('type_image'));
    }

    /**
     * Table row helper
     *
     * Help simplify the form building and enforces a strict layout. If
     * you think this table needs to look different, go bug James.
     *
     * @param string  left cell content
     * @param string  right cell content
     * @param string  vertical alignment of left column
     *
     * @return  void - adds a row to the EE table class
     */
    protected function _row($cell1, $cell2 = '', $valign = 'center')
    {
        if (! $cell2) {
            ee()->table->add_row(
                array('data' => $cell1, 'colspan' => 2)
            );
        } else {
            ee()->table->add_row(
                array('data' => '<strong>' . $cell1 . '</strong>', 'width' => '170px', 'valign' => $valign),
                array('data' => $cell2, 'class' => 'id')
            );
        }
    }

    public function validate_settings($settings)
    {
        $validator = ee('Validation')->make(array(
            'allowed_directories' => 'required|allowedDirectories'
        ));

        $validator->defineRule('allowedDirectories', array($this, '_validate_file_settings'));

        return $validator->validate($settings);
    }

    public function save_settings($data)
    {
        $defaults = array(
            'field_content_type' => 'all',
            'allowed_directories' => '',
            'show_existing' => '',
            'num_existing' => 0,
            'field_fmt' => 'none'
        );

        if (empty($data)) {
            // for Pro vars, go directly into POST
            $data = array(
                'field_content_type' => ee('Request')->post('field_content_type', 'all'),
                'allowed_directories' => ee('Request')->post('allowed_directories', ''),
                'show_existing' => ee('Request')->post('show_existing', ''),
                'num_existing' => ee('Request')->post('num_existing', 0),
                'field_fmt' => ee('Request')->post('field_fmt', 'none')
            );
        }

        $all = array_merge($defaults, $data);

        return array_intersect_key($all, $defaults);
    }

    /**
     * Form Validation callback
     *
     * @return  boolean Whether or not to pass validation
     */
    public function _validate_file_settings($key, $value, $params, $rule)
    {
        return true;
    }

    /**
     * Accept all content types.
     *
     * @param string  The name of the content type
     * @return bool   Accepts all content types
     */
    public function accepts_content_type($name)
    {
        return true;
    }

    /**
     * Update the fieldtype
     *
     * @param string $version The version being updated to
     * @return boolean TRUE if successful, FALSE otherwise
     */
    public function update($version)
    {
        return true;
    }

    public function getTableColumnConfig()
    {
        return [
            'encode' => false
        ];
    }

    public function renderTableCell($data, $field_id, $entry)
    {
        if (empty($data)) {
            return '';
        }
        $field_data = $this->pre_process($data);
        if (!isset($field_data['title'])) {
            return '';
        }
        $out = '<a href="' . $this->replace_tag($field_data) . '" target="_blank">' . $field_data['title'] . '</a>';

        return $out;
    }

    /**
     * Some modifiers that are set on the field, require array of data on input,
     * while the modifiers in ModifiableTrait require string
     * The modifiers always return string, so this created problems when chaining modifiers
     * By adding this param we ask the code to return array (not string) from previous modifier when chaining
     *
     * @var array
     */
    public function getChainableModifiersThatRequireArray($data = [])
    {

        $modifiers = ['resize', 'crop', 'rotate', 'webp', 'resize_crop', 'length', 'raw_content', 'attr_safe', 'limit', 'form_prep', 'rot13', 'encrypt', 'url_slug', 'censor', 'json', 'replace', 'url_encode', 'url_decode'];
        return $modifiers;
    }
}

// END File_ft class

// EOF

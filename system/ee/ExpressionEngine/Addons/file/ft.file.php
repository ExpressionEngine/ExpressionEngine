<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2021, Packet Tide, LLC (https://www.packettide.com)
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

    public $_dirs = array();

    /**
     * Constructor
     *
     * @access	public
     */
    public function __construct()
    {
        parent::__construct();
        ee()->load->library('file_field');
    }

    /**
     * Validate the upload
     *
     * @access	public
     */
    public function validate($data)
    {
        // Is it required but empty?
        if (($this->settings['field_required'] === true
            || $this->settings['field_required'] == 'y')
                && empty($data)) {
            return array('value' => '', 'error' => lang('required'));
        }

        // Is it optional and empty?
        if (($this->settings['field_required'] === false
            || $this->settings['field_required'] == 'n')
                && empty($data)) {
            return array('value' => '');
        }

        // Does it look like '{filedir_n}file_name.ext'?
        if (preg_match('/^{filedir_(\d+)}/', $data, $matches)) {
            $upload_location_id = $matches[1];
            $file_name = str_replace($matches[0], '', $data);

            $file = ee('Model')->get('File')
                ->filter('site_id', ee()->config->item('site_id'))
                ->filter('upload_location_id', $upload_location_id)
                ->filter('file_name', $file_name)
                ->first();

            if ($file) {
                $check_permissions = false;

                // Is this an edit?
                if ($this->content_id) {
                    // Are we validating on grid data?
                    if (isset($this->settings['grid_row_id'])) {
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
                        if ($rows[$this->content_id][$this->settings['grid_row_id']] != $data) {
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
        }

        return array('value' => '', 'error' => lang('invalid_selection'));
    }

    /**
     * Save the correct value {fieldir_\d}filename.ext
     *
     * @access	public
     */
    public function save($data)
    {
        // validate does all of the work.
        return $data;
    }

    /**
     * Show the publish field
     *
     * @access	public
     */
    public function display_field($data)
    {
        $allowed_file_dirs = (isset($this->settings['allowed_directories']) && $this->settings['allowed_directories'] != 'all')
            ? $this->settings['allowed_directories']
            : '';
        $content_type = (isset($this->settings['field_content_type'])) ? $this->settings['field_content_type'] : 'all';
        $existing_limit = (isset($this->settings['num_existing'])) ? $this->settings['num_existing'] : 0;
        $show_existing = (isset($this->settings['show_existing'])) ? $this->settings['show_existing'] : 'n';
        $filebrowser = (REQ == 'CP');

        if (REQ == 'CP') {
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
     * Return a status of "warning" if the file is missing, otherwise "ok"
     *
     * @return string "warning" if the file is missing, "ok" otherwise
     */
    public function get_field_status($data)
    {
        $status = 'ok';

        $file = $this->_parse_field($data);

        if ($file && ! $file->exists()) {
            $status = 'warning';
        }

        return $status;
    }

    private function _parse_field($data)
    {
        $file = null;

        // If the file field is in the "{filedir_n}image.jpg" format
        if (preg_match('/^{filedir_(\d+)}/', $data, $matches)) {
            // Set upload directory ID and file name
            $dir_id = $matches[1];
            $file_name = str_replace($matches[0], '', $data);

            $file = ee('Model')->get('File')
                ->filter('file_name', $file_name)
                ->filter('upload_location_id', $dir_id)
                ->filter('site_id', ee()->config->item('site_id'))
                ->first();
        }
        // If file field is just a file ID
        elseif (! empty($data) && is_numeric($data)) {
            $file = ee('Model')->get('File', $data)->first();
        }

        return $file;
    }

    /**
     * Basic javascript interaction on the frontend
     *
     * @access	public
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
     * @access	public
     */
    public function pre_process($data)
    {
        return ee()->file_field->parse_field($data);
    }

    /**
     * Runs before the channel entries loop on the front end
     *
     * @param array $data	All custom field data about to be processed for the front end
     * @return void
     */
    public function pre_loop($data)
    {
        ee()->file_field->cache_data($data);
    }

    /**
     * Replace frontend tag
     *
     * @access	public
     */
    public function replace_tag($file_info, $params = array(), $tagdata = false)
    {
        // Make sure we have file_info to work with
        if ($tagdata !== false && $file_info === false) {
            $tagdata = ee()->TMPL->parse_variables($tagdata, array());
        }

        // Experimental parameter, do not use
        if (isset($params['raw_output']) && $params['raw_output'] == 'yes') {
            return $file_info['raw_output'];
        }

        // Let's allow our default thumbs to be used inside the tag pair
        if (isset($file_info['path']) && isset($file_info['filename']) && isset($file_info['extension'])) {
            $file_info['url:thumbs'] = $file_info['path'] . '_thumbs/' . $file_info['filename'] . '.' . $file_info['extension'];
        }

        if (isset($file_info['file_id'])) {
            $file_info['id_path'] = array('/' . $file_info['file_id'], array('path_variable' => true));
        }

        // Make sure we have file_info to work with
        if ($tagdata !== false && isset($file_info['file_id'])) {
            return ee()->TMPL->parse_variables($tagdata, array($file_info));
        }

        if (! empty($file_info['path'])
            && ! empty($file_info['filename'])
            && $file_info['extension'] !== false) {
            $full_path = $file_info['path'] . $file_info['filename'] . '.' . $file_info['extension'];

            if (isset($params['wrap'])) {
                return $this->_wrap_it($file_info, $params['wrap'], $full_path);
            }

            return $full_path;
        }
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
        $data['filename'] = $data['model_object']->file_name;
        $data['directory_path'] = $data['model_object']->UploadDestination->server_path;
        $data['directory_url'] = $data['model_object']->UploadDestination->url;
        $data['source_image'] = $data['model_object']->getAbsolutePath();

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
        $data['filename'] = $data['model_object']->file_name;
        $data['directory_path'] = $data['model_object']->UploadDestination->server_path;
        $data['directory_url'] = $data['model_object']->UploadDestination->url;
        $data['source_image'] = $data['model_object']->getAbsolutePath();

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

        $data['filename'] = $data['model_object']->file_name;
        $data['directory_path'] = $data['model_object']->UploadDestination->server_path;
        $data['directory_url'] = $data['model_object']->UploadDestination->url;
        $data['source_image'] = $data['model_object']->getAbsolutePath();

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
        $data['filename'] = $data['model_object']->file_name;
        $data['directory_path'] = $data['model_object']->UploadDestination->server_path;
        $data['directory_url'] = $data['model_object']->UploadDestination->url;
        $data['source_image'] = $data['model_object']->getAbsolutePath();

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
        $data['filename'] = $data['model_object']->file_name;
        $data['directory_path'] = $data['model_object']->UploadDestination->server_path;
        $data['directory_url'] = $data['model_object']->UploadDestination->url;
        $data['source_image'] = $data['model_object']->getAbsolutePath();

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
        $filename = ee()->image_lib->explode_name($data['filename']);
        if ($function == 'webp') {
            $filename['ext'] = '.webp';
        }
        $new_image = $filename['name'] . '_' . $function . '_' . md5(serialize($params)) . $filename['ext'];
        $new_image_dir = rtrim($data['directory_path'], '/') . '/_' . $function . DIRECTORY_SEPARATOR;
        if (!is_dir($new_image_dir)) {
            mkdir($new_image_dir);
            if (!file_exists($new_image_dir . 'index.html')) {
                $f = fopen($new_image_dir . 'index.html', FOPEN_READ_WRITE_CREATE_DESTRUCTIVE);
                fwrite($f, 'Directory access is forbidden.');
                fclose($f);
            }
        } elseif (!is_really_writable($new_image_dir)) {
            return false;
        }

        $new_image_path = $new_image_dir . $new_image;
        $new_image_url = rtrim($data['directory_url'], '/') . '/_' . $function . '/' . rawurlencode($new_image);
        if (!file_exists($new_image_path)) {
            $imageLibConfig = array(
                'image_library' => ee()->config->item('image_resize_protocol'),
                'library_path' => ee()->config->item('image_library_path'),
                'source_image' => $data['source_image'],
                'new_image' => $new_image_path,
                'maintain_ratio' => isset($params['maintain_ratio']) ? get_bool_from_string($params['maintain_ratio']) : true,
                'master_dim' => (isset($params['master_dim']) && in_array($params['master_dim'], ['auto', 'width', 'height'])) ? $params['master_dim'] : 'auto',

                'quality' => isset($params['quality']) ? (int) $params['quality'] : 75,
                'x_axis' => isset($params['x']) ? (int) $params['x'] : 0,
                'y_axis' => isset($params['y']) ? (int) $params['y'] : 0,
                'rotation_angle' => (isset($params['angle']) && in_array($params['angle'], ['90', '180', '270', 'vrt', 'hor'])) ? $params['angle'] : null,
            );
            //techically, both dimentions are always required, so we'll set defaults
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
        }

        if (!$tagdata) {
            if (isset($params['wrap'])) {
                return $this->_wrap_it($data, $params['wrap'], $new_image_url);
            }

            return ($return_as_path ? $new_image_path : $new_image_url);
        } else {
            $props = ee()->image_lib->get_image_properties($new_image_path, true);
            $vars = [
                'url' => $new_image_url,
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
        return parent::replace_replace($data['url'], $params, $tagdata);
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
     * @access	public
     */
    public function replace_tag_catchall($file_info = [], $params = array(), $tagdata = false, $modifier = '')
    {
        // These are single variable tags only, so no need for replace_tag
        if ($modifier) {
            $key = 'url:' . $modifier;

            if ($modifier == 'thumbs') {
                if (isset($file_info['path']) && isset($file_info['filename']) && isset($file_info['extension'])) {
                    $data = $file_info['path'] . '_thumbs/' . $file_info['filename'] . '.' . $file_info['extension'];
                }
            } elseif (isset($file_info[$key])) {
                $data = $file_info[$key];
            }

            if (empty($data)) {
                return $tagdata;
            }

            if (isset($params['wrap'])) {
                return $this->_wrap_it($file_info, $params['wrap'], $data);
            }

            return $data;
        }
    }

    /**
     * Wrap it helper function
     *
     * @access	private
     */
    public function _wrap_it($file_info, $type, $full_path)
    {
        if ($type == 'link') {
            ee()->load->helper('url_helper');

            return $file_info['file_pre_format']
                . anchor($full_path, $file_info['filename'], $file_info['file_properties'])
                . $file_info['file_post_format'];
        } elseif ($type == 'image') {
            $properties = (! empty($file_info['image_properties'])) ? ' ' . $file_info['image_properties'] : '';

            return $file_info['image_pre_format']
                . '<img src="' . $full_path . '"' . $properties . ' alt="' . $file_info['filename'] . '" />'
                . $file_info['image_post_format'];
        }

        return $full_path;
    }

    /**
     * Display settings screen
     *
     * @access	public
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
            ->filter('site_id', ee()->config->item('site_id'))
            ->filter('module_id', 0)
            ->order('name', 'asc')
            ->all()
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
                ->filter('site_id', ee()->config->item('site_id'))
                ->filter('module_id', 0)
                ->all()
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
     * @param	left cell content
     * @param	right cell content
     * @param	vertical alignment of left column
     *
     * @return	void - adds a row to the EE table class
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

        $all = array_merge($defaults, $data);

        return array_intersect_key($all, $defaults);
    }

    /**
     * Form Validation callback
     *
     * @return	boolean	Whether or not to pass validation
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
        $out = '<a href="' . $this->replace_tag($field_data) . '" target="_blank">' . $field_data['title'] . '</a>';

        return $out;
    }
}

// END File_ft class

// EOF

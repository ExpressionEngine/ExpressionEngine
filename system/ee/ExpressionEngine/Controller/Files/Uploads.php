<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Controller\Files;

use CP_Controller;

use ExpressionEngine\Library\CP;
use ExpressionEngine\Service\Model\Collection;
use ExpressionEngine\Controller\Files\AbstractFiles as AbstractFilesController;

/**
 * Uploads Directories Settings Controller
 */
class Uploads extends AbstractFilesController
{
    private $upload_errors = array();
    private $_upload_dirs = array();

    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__construct();

        ee()->load->library('form_validation');
    }

    /**
     * New upload destination
     */
    public function create()
    {
        if (! ee('Permission')->can('create_upload_directories')) {
            show_error(lang('unauthorized_access'), 403);
        }

        if (! AJAX_REQUEST) {
            $this->stdHeader();
            $this->generateSidebar(null);
        }

        ee()->view->cp_breadcrumbs = array(
            ee('CP/URL')->make('files')->compile() => lang('files'),
            '' => lang('new_directory')
        );

        return $this->form();
    }

    /**
     * Edit upload destination
     *
     * @param int	$upload_id	Table name, used when coming from SQL Manager
     *                      	for proper page-naming and breadcrumb-setting
     */
    public function edit($upload_id)
    {
        if (! ee('Permission')->can('edit_upload_directories')) {
            show_error(lang('unauthorized_access'), 403);
        }

        if (! AJAX_REQUEST) {
            $this->stdHeader();
            $this->generateSidebar($upload_id);
        }

        ee()->view->cp_breadcrumbs = array(
            ee('CP/URL')->make('files')->compile() => lang('files'),
            ee('CP/URL')->make('files/directory/' . $upload_id)->compile() => ee('Model')->get('UploadDestination', $upload_id)->fields('name')->first()->name,
            '' => lang('edit_upload_directory')
        );

        return $this->form($upload_id);
    }

    /**
     * Edit upload destination
     *
     * @param int	$upload_id	ID of upload destination to edit
     */
    private function form($upload_id = null)
    {
        // Get the upload directory
        if (is_null($upload_id)) {
            ee()->view->cp_page_title = lang('create_upload_directory');
            ee()->view->base_url = ee('CP/URL')->make('files/uploads/create');
            $upload_destination = ee('Model')->make('UploadDestination');
            $upload_destination->site_id = ee()->config->item('site_id');
        } else {
            $upload_destination = ee('Model')->get('UploadDestination', $upload_id)->first();

            if (! $upload_destination) {
                show_error(lang('unauthorized_access'), 403);
            }

            ee()->view->cp_page_title = lang('edit_upload_directory');
            ee()->view->base_url = ee('CP/URL')->make('files/uploads/edit/' . $upload_id);
        }

        if (! empty($_POST)) {
            $validate = $this->validateUploadPreferences($upload_destination);

            if (AJAX_REQUEST && !empty(ee()->input->post('ee_fv_field'))) {
                $field = ee()->input->post('ee_fv_field');

                // We may be validating a field in a Grid
                preg_match("/\[rows\]\[(\w+)\]\[(\w+)\]/", $field, $matches);

                // Error is present for the validating field, send it back
                if (! empty($matches) && isset($this->upload_errors['image_sizes'][$matches[1]][$matches[2]])) {
                    ee()->output->send_ajax_response(array('error' => $this->upload_errors['image_sizes'][$matches[1]][$matches[2]]));
                } elseif (isset($this->upload_errors[$field])) {
                    ee()->output->send_ajax_response(array('error' => $this->upload_errors[$field]));
                } else {
                    ee()->output->send_ajax_response(['success']);
                }
                exit;
            }

            if ($validate) {
                $new_upload_id = $upload_destination->save()->getId();

                ee('CP/Alert')->makeInline('shared-form')
                    ->asSuccess()
                    ->withTitle(lang('directory_saved'))
                    ->addToBody(lang('directory_saved_desc'))
                    ->defer();

                if (ee('Request')->post('submit') == 'save_and_new') {
                    ee()->functions->redirect(ee('CP/URL')->make('files/uploads/create'));
                } elseif (ee()->input->post('submit') == 'save_and_close') {
                    ee()->functions->redirect(ee('CP/URL')->make('files'));
                } else {
                    ee()->functions->redirect(ee('CP/URL')->make('files/uploads/edit/' . $new_upload_id));
                }
            } else {
                ee()->load->library('form_validation');
                ee()->form_validation->_error_array = $this->upload_errors;

                // Do some fenagling to fit our errors into Form Validation
                if (isset(ee()->form_validation->_error_array['image_sizes'])) {
                    // This is an array, Form Validation expects strings
                    unset(ee()->form_validation->_error_array['image_sizes']);

                    // We need a dummy error here to set the invalid class on the parent fieldset
                    ee()->form_validation->_error_array['image_manipulations'] = 'asdf';
                }

                ee('CP/Alert')->makeInline('shared-form')
                    ->asIssue()
                    ->withTitle(lang('directory_not_saved'))
                    ->addToBody(lang('directory_not_saved_desc'))
                    ->now();
            }
        }

        $fileTypes = array_filter(array_keys(ee()->config->loadFile('mimes')), 'is_string');
        $allowed_types = [];
        foreach ($fileTypes as $type) {
            $allowed_types[$type] = lang('type_' . $type);
        }

        if (! bool_config_item('file_manager_compatibility_mode')) {
            $adapters = ee('Filesystem/Adapter')->all();
        } else {
            $adapters = ['local' => ee('Filesystem/Adapter')->get('local')];
        }
        $settingsValues = array_merge([
            'url' => $upload_destination->getConfigOverriddenProperty('url'),
            'server_path' => $upload_destination->getConfigOverriddenProperty('server_path'),
        ], $upload_destination->adapter_settings ?? []);

        $adapter_groups = [];
        $adapter_choices = [];
        $adapter_settings = [];
        foreach ($adapters as $key => $adapter) {
            $adapter_choices[$key] = lang('adapter_' . $key);
            $adapter_groups[$key] = "adapter_{$key}";
            $adapterFields = ee('Filesystem/Adapter')->createSettingsFields($key, ($upload_destination->adapter == $key) ? $settingsValues : []);
            if (!empty($adapterFields)) {
                foreach ($adapterFields as $field) {
                    // Prefix all field names for the adapter
                    foreach ($field['fields'] as $input_name => $input) {
                        $prefixed_name = implode('', [
                            "_for_adapter[{$key}]",
                            (strpos($input_name, '[') !== false) ? '['. str_replace('[', '][', $input_name) : "[{$input_name}]"
                        ]);
                        $field['fields'][$prefixed_name] = $input;
                        unset($field['fields'][$input_name]);
                    }

                    $adapter_settings[] = array_merge($field, ['group' => "adapter_{$key}"]);
                }
            }
        }

        $vars['sections'] = array(
            array(
                array(
                    'title' => 'name',
                    'fields' => array(
                        'name' => array(
                            'type' => 'text',
                            'value' => $upload_destination->name,
                            'required' => true
                        )
                    )
                ),
                array(
                    'title' => 'filesystem_adapter',
                    'desc' => '',
                    'fields' => array(
                        'adapter' => array(
                            'type' => 'dropdown',
                            'required' => true,
                            'choices' => $adapter_choices,
                            'group_toggle' => $adapter_groups,
                            'value' => $upload_destination->adapter
                        )
                    )
                ),
            )
        );
        $vars['sections'][0] = array_merge($vars['sections'][0], $adapter_settings);
        $vars['sections'][0] = array_merge($vars['sections'][0], array(
                array(
                    'title' => 'upload_allowed_types',
                    'desc' => '',
                    'fields' => array(
                        'allowed_types' => array(
                            'type' => 'checkbox',
                            'nested' => true,
                            'attrs' => 'data-any="y"',
                            'choices' => [
                                '--' => [
                                    'name' => lang('type_all'),
                                    'children' => $allowed_types,
                                ]
                            ],
                            'value' => $upload_destination->allowed_types ? (in_array('all', $upload_destination->allowed_types) ? ['--'] : $upload_destination->allowed_types) : ['img'],
                            'toggle_all' => false,
                        ),
                    )
                ),
            )
        );

        if (bool_config_item('multiple_sites_enabled')) {
            $vars['sections'][0][] = array(
                'title' => 'share_directory_on_all_sites',
                'desc' => 'share_directory_on_all_sites_desc',
                'fields' => array(
                    'share_directory' => array(
                        'type' => 'yes_no',
                        'value' => $upload_destination->site_id === 0,
                        'disabled' => ! $upload_destination->isNew()
                    )
                )
            );
        }

        $vars['sections'] = array_merge($vars['sections'], array(
            'browser_behavior' => array(
                array(
                    'title' => 'default_modal_view',
                    'desc' => 'default_modal_view_desc',
                    'fields' => array(
                        'default_modal_view' => array(
                            'type' => 'inline_radio',
                            'choices' => array(
                                'list' => lang('default_modal_view_list'),
                                'thumb' => lang('default_modal_view_thumbnails')
                            ),
                            'value' => $upload_destination->default_modal_view ?: 'list'
                        )
                    )
                ),
            ),
            'file_limits' => array(
                array(
                    'title' => 'upload_file_size',
                    'desc' => 'upload_file_size_desc',
                    'fields' => array(
                        'max_size' => array(
                            'type' => 'text',
                            'value' => $upload_destination->max_size
                        )
                    )
                ),
                array(
                    'title' => 'upload_image_width',
                    'desc' => 'upload_image_width_desc',
                    'fields' => array(
                        'max_width' => array(
                            'type' => 'text',
                            'value' => $upload_destination->max_width
                        )
                    )
                ),
                array(
                    'title' => 'upload_image_height',
                    'desc' => 'upload_image_height_desc',
                    'fields' => array(
                        'max_height' => array(
                            'type' => 'text',
                            'value' => $upload_destination->max_height
                        )
                    )
                )
            )
        ));

        if (!bool_config_item('file_manager_compatibility_mode')) {
            $vars['sections']['browser_behavior'] = array_merge($vars['sections']['browser_behavior'], array(
                array(
                    'title' => 'allow_subfolders',
                    'desc' => 'allow_subfolders_desc',
                    'fields' => array(
                        'allow_subfolders' => array(
                            'type' => 'yes_no',
                            'group_toggle' => array(
                                'y' => 'rel_subfolder',
                            ),
                            'value' => $upload_destination->allow_subfolders
                        )
                    )
                ),
                array(
                    'title' => 'keep_subfolders_top',
                    'desc' => 'keep_subfolders_top_desc',
                    'group' => 'rel_subfolder',
                    'fields' => array(
                        'subfolders_on_top' => array(
                            'type' => 'yes_no',
                            'value' => $upload_destination->subfolders_on_top ?: false
                        )
                    )
                ),
            ));
        }

        // Image manipulations Grid
        $grid = $this->getImageSizesGrid($upload_destination);

        $vars['sections']['upload_image_manipulations'] = array(
            array(
                'title' => 'constrain_or_crop',
                'desc' => 'constrain_or_crop_desc',
                'wide' => true,
                'grid' => true,
                'fields' => array(
                    'image_manipulations' => array(
                        'type' => 'html',
                        'content' => ee()->load->view('_shared/table', $grid->viewData(), true)
                    )
                )
            )
        );

        $roles = ee('Model')->get('Role')
            ->filter('role_id', 'NOT IN', array(1,2,3,4))
            ->order('name')
            ->all()
            ->getDictionary('role_id', 'name');

        $vars['sections']['upload_privileges'] = array(
            array(
                'title' => 'upload_roles',
                'desc' => 'upload_roles_desc',
                'fields' => array(
                    'upload_roles' => array(
                        'type' => 'checkbox',
                        'choices' => $roles,
                        'value' => $upload_destination->Roles->pluck('role_id'),
                        'no_results' => [
                            'text' => sprintf(lang('no_found'), lang('roles'))
                        ]
                    )
                )
            )
        );

        // Grid validation results
        ee()->view->image_sizes_errors = isset($this->upload_errors['image_sizes'])
            ? $this->upload_errors['image_sizes'] : array();

        // Category group assignment
        $cat_group_options = ee('Model')
            ->get('CategoryGroup')
            ->filter('site_id', ee()->config->item('site_id'))
            ->filter('exclude_group', '!=', '2')
            ->all()
            ->getDictionary('group_id', 'group_name');

        $vars['sections']['upload_privileges'][] = array(
            'title' => 'upload_category_groups',
            'desc' => 'upload_category_groups_desc',
            'fields' => array(
                'cat_group' => array(
                    'type' => 'checkbox',
                    'choices' => $cat_group_options,
                    'value' => ($upload_destination) ? $upload_destination->CategoryGroups->pluck('group_id') : array(),
                    'no_results' => [
                        'text' => sprintf(lang('no_found'), lang('category_groups'))
                    ]
                )
            )
        );

        ee()->view->ajax_validate = true;
        ee()->view->buttons = [
            [
                'name' => 'submit',
                'type' => 'submit',
                'value' => 'save',
                'text' => 'save',
                'working' => 'btn_saving'
            ],
            [
                'name' => 'submit',
                'type' => 'submit',
                'value' => 'save_and_new',
                'text' => 'save_and_new',
                'working' => 'btn_saving'
            ],
            [
                'name' => 'submit',
                'type' => 'submit',
                'value' => 'save_and_close',
                'text' => 'save_and_close',
                'working' => 'btn_saving'
            ]
        ];

        if (AJAX_REQUEST) {
            return ee()->cp->render('_shared/form', $vars);
        }

        ee()->cp->render('settings/form', $vars);
    }

    /**
     * Sets up a GridInput object populated with image manipulation data
     *
     * @param	int	$upload_id		ID of upload destination to get image sizes for
     * @return	GridInput object
     */
    private function getImageSizesGrid($upload_destination = null)
    {
        // Image manipulations Grid
        $grid = ee('CP/GridInput', array(
            'field_name' => 'image_manipulations',
            'reorder' => false, // Order doesn't matter here
        ));
        $grid->loadAssets();
        $grid->setColumns(
            array(
                'image_manip_name' => array(
                    'desc' => 'image_manip_name_desc'
                ),
                'image_manip_type' => array(
                    'desc' => 'image_manip_type_desc'
                ),
                'image_manip_width' => array(
                    'desc' => 'image_manip_width_desc'
                ),
                'image_manip_height' => array(
                    'desc' => 'image_manip_height_desc'
                ),
                'image_manip_quality' => array(
                    'desc' => 'image_manip_quality_desc'
                ),
                'image_manip_watermark' => array(
                    'desc' => 'image_manip_watermark_desc'
                )
            )
        );
        $grid->setNoResultsText('no_manipulations', 'add_manipulation');

        $watermarks_choices = array(0 => lang('no_watermark'));
        $watermarks_choices += ee('Model')->get('Watermark')
            ->order('wm_name')
            ->all()
            ->getDictionary('wm_id', 'wm_name');

        $grid->setBlankRow($this->getGridRow($watermarks_choices));

        $validation_data = ee()->input->post('image_manipulations');
        $image_sizes = array();

        // If we're coming back on a validation error, load the Grid from
        // the POST data
        if (! empty($validation_data)) {
            foreach ($validation_data['rows'] as $row_id => $columns) {
                $image_sizes[$row_id] = array(
                    // Fix this, multiple new rows won't namespace right
                    'id' => str_replace('row_id_', '', $row_id),
                    'short_name' => $columns['short_name'],
                    'resize_type' => $columns['resize_type'],
                    'width' => $columns['width'],
                    'height' => $columns['height'],
                    'quality' => $columns['quality'],
                    'watermark_id' => $columns['watermark_id'],
                );
            }

            if (isset($this->upload_errors['image_sizes'])) {
                foreach ($this->upload_errors['image_sizes'] as $row_id => $columns) {
                    $image_sizes[$row_id]['errors'] = array_map('strip_tags', $columns);
                }
            }
        }
        // Otherwise, pull from the database if we're editing
        elseif ($upload_destination !== null) {
            $sizes = $upload_destination->getFileDimensions()->sortBy('id');

            if ($sizes->count() != 0) {
                $image_sizes = $sizes->toArray();
            }
        }

        // Populate image manipulations Grid
        if (! empty($image_sizes)) {
            $data = array();

            foreach ($image_sizes as $size) {
                $data[] = array(
                    'attrs' => array('row_id' => $size['id']),
                    'columns' => $this->getGridRow($watermarks_choices, $size),
                );
            }

            $grid->setData($data);
        }

        return $grid;
    }

    /**
     * Returns an array of HTML representing a single Grid row, populated by data
     * passed in the $size array: ('short_name', 'resize_type', 'width', 'height')
     *
     * @param	array	$size	Array of image size information to populate Grid row
     * @return	array	Array of HTML representing a single Grid row
     */
    private function getGridRow($watermarks_choices, $size = array())
    {
        $defaults = array(
            'short_name' => '',
            'resize_type' => '',
            'width' => '',
            'height' => '',
            'quality' => 90,
            'watermark_id' => ''
        );

        $size = array_merge($defaults, $size);
        $size = array_map('form_prep', $size);

        return array(
            array(
                'html' => form_input('short_name', $size['short_name']),
                'error' => $this->getGridFieldError($size, 'short_name')
            ),
            array(
                'html' => form_dropdown(
                    'resize_type',
                    array(
                        'constrain' => lang('image_manip_type_opt_constrain'),
                        'crop' => lang('image_manip_type_opt_crop'),
                    ),
                    $size['resize_type']
                ),
                'error' => $this->getGridFieldError($size, 'resize_type')
            ),
            array(
                'html' => form_input('width', $size['width']),
                'error' => $this->getGridFieldError($size, 'width')
            ),
            array(
                'html' => form_input('height', $size['height']),
                'error' => $this->getGridFieldError($size, 'height')
            ),
            array(
                'html' => form_input('quality', $size['quality']),
                'error' => $this->getGridFieldError($size, 'quality')
            ),
            array(
                'html' => form_dropdown(
                    'watermark_id',
                    $watermarks_choices,
                    $size['watermark_id']
                ),
                'error' => $this->getGridFieldError($size, 'watermark_id')
            )
        );
    }

    /**
     * Returns the validation error for a specific Grid cell
     *
     * @param	array	$size	Array of image size information to populate Grid row
     * @param	string	$column	Name of column to get an error for
     * @return	array	Array of HTML representing a single Grid row
     */
    private function getGridFieldError($size, $column)
    {
        if (isset($size['errors'][$column])) {
            return $size['errors'][$column];
        }

        return null;
    }

    /**
     * Sets information on the UploadDestination object and its children and
     * validates them all
     *
     * @param	model	$upload_destination		Model object for upload destination
     * @return	boolean	Success or failure of validation
     */
    private function validateUploadPreferences($upload_destination)
    {
        $upload_destination->set($_POST);

        if ($upload_destination->isNew() && ee('Request')->post('share_directory') == 'y') {
            $upload_destination->site_id = 0;
        }

        // Pull adapter specific configuration
        if (isset($_POST['_for_adapter']) && isset($_POST['_for_adapter'][$_POST['adapter']])) {
            $adapterSettings = $_POST['_for_adapter'][$_POST['adapter']];
            $upload_destination->set($adapterSettings);
        }

        $cat_group = ee()->input->post('cat_group');

        if (! empty($cat_group)) {
            if ($_POST['cat_group'][0] == 0) {
                unset($_POST['cat_group'][0]);
            }

            $upload_destination->CategoryGroups = ee('Model')->get('CategoryGroup', ee('Request')->post('cat_group'))->all();
        } else {
            $upload_destination->CategoryGroups = null;
        }

        $access = ee()->input->post('upload_roles') ?: array();

        $roles = ee('Model')->get('Role', $access)
            ->filter('role_id', 'NOT IN', [1,2,3,4])
            ->all();

        if ($roles->count() > 0) {
            $upload_destination->Roles = $roles;
        } else {
            // Remove all roles from this upload destination
            $upload_destination->Roles = null;
        }

        $result = $upload_destination->validate();

        if (!empty($upload_destination->adapter)) {
            //validate adapter settings
            $adapter = $upload_destination->getFilesystemAdapter(['allow_missing' => true]);
            $adapterValidation = ee('Validation')->make()->validate($adapter);

            foreach ($adapterValidation->getFailed() as $field_name => $rules) {
                if (property_exists($upload_destination, $field_name)) {
                    $field = '_for_adapter[' . $upload_destination->adapter . '][' . $field_name . ']';
                } else {
                    $field = '_for_adapter[' . $upload_destination->adapter . '][adapter_settings][' . $field_name . ']';
                }
                $result->addFailed($field, $rules[0]);
            }

            //for the local adapter, try to create directory, if missing
            //and then do extra validation
            //but only on form submission
            if (! AJAX_REQUEST && ee('Request')->post('adapter') == 'local' && !empty($adapterSettings['server_path'])) {
                $parsedServerPath = rtrim(parse_config_variables($adapterSettings['server_path']), '\\/') . DIRECTORY_SEPARATOR;
                if ((DIRECTORY_SEPARATOR == '/' && strpos($parsedServerPath, '/') === 0) || (DIRECTORY_SEPARATOR == '\\' && strpos($parsedServerPath, ':') === 1)) {
                    ee('Filesystem')->mkDir($parsedServerPath);
                }
                $localAdapterValidation = ee('Validation')->make(['server_path' => 'required|fileExists|writable'])->validate(['server_path' => $parsedServerPath]);
                foreach ($localAdapterValidation->getFailed() as $field_name => $rules) {
                    if (property_exists($upload_destination, $field_name)) {
                        $field = '_for_adapter[' . $upload_destination->adapter . '][' . $field_name . ']';
                    } else {
                        $field = '_for_adapter[' . $upload_destination->adapter . '][adapter_settings][' . $field_name . ']';
                    }
                    $result->addFailed($field, $rules[0]);
                }
            }
        }

        if (! $result->isValid()) {
            $this->upload_errors = $result->renderErrors();
        }

        $image_sizes = ee()->input->post('image_manipulations');

        $existing_ids = array();
        $new_sizes = array();

        // collect existing to keep, and new ones to add
        if (isset($image_sizes['rows'])) {
            foreach ($image_sizes['rows'] as $row_id => $columns) {
                if (strpos($row_id, 'row_id_') !== false) {
                    $existing_ids[] = str_replace('row_id_', '', $row_id);
                } else {
                    $new_sizes[$row_id] = $columns;
                }
            }
        }

        if (empty($existing_ids)) {
            $upload_destination->FileDimensions = new Collection();
        } else {
            $upload_destination->FileDimensions = ee('Model')->get('FileDimension', $existing_ids)->all();
        }

        $validate = array();

        if (! empty($image_sizes)) {
            foreach ($upload_destination->FileDimensions as $model) {
                $row_id = 'row_id_' . $model->getId();
                $model->set($image_sizes['rows'][$row_id]);

                $validate[$row_id] = $model;
            }
        }

        foreach ($new_sizes as $row_id => $columns) {
            $model = ee('Model')->make('FileDimension', $columns);
            $model->site_id = $upload_destination->site_id;
            $upload_destination->FileDimensions[] = $model;

            $validate[$row_id] = $model;
        }

        foreach ($validate as $row_id => $model) {
            if ($model->height === '') {
                $model->height = 0;
            }

            if ($model->width === '') {
                $model->width = 0;
            }

            if ($model->quality === '') {
                $model->quality = 90;
            }

            $result = $model->validate();

            if (! $result->isValid()) {
                $this->upload_errors['image_sizes'][$row_id] = $result->renderErrors();
            }
        }

        return empty($this->upload_errors);
    }

    /**
     * Sync upload directory
     *
     * @param	int		$id	ID of upload destination to sync
     */
    public function sync($upload_id = null)
    {
        if (! ee('Permission')->can('upload_new_files')) {
            show_error(lang('unauthorized_access'), 403);
        }

        if (empty($upload_id)) {
            ee()->functions->redirect(ee('CP/URL')->make('files/uploads'));
        }

        $this->stdHeader($upload_id);
        $this->generateSidebar($upload_id);
        $upload_destination = null;

        if (ee('Permission')->isSuperAdmin()) {
            $upload_destination = ee('Model')->get('UploadDestination', $upload_id)
                ->filter('site_id', 'IN', [0, ee()->config->item('site_id')])
                ->first();
        } else {
            $member = ee()->session->getMember();
            $assigned_upload_destinations = $member->getAssignedUploadDestinations()->indexBy('id');
            if (isset($assigned_upload_destinations[$upload_id])
                && $assigned_upload_destinations[$upload_id]->site_id == ee()->config->item('site_id')) {
                $upload_destination = $assigned_upload_destinations[$upload_id];
            }
        }

        // Get a listing of raw files in the directory
        $files = $upload_destination->getAllFileNames();

        $files_count = count($files, COUNT_RECURSIVE);

        $vars['sections'] = array(
            array(
                array(
                    'title' => 'file_sync',
                    'desc' => sprintf(lang('file_sync_desc'), $files_count),
                    'fields' => array(
                        'progress' => array(
                            'type' => 'html',
                            'content' => ee()->load->view('_shared/progress_bar', array('percent' => 0), true)
                        )
                    )
                )
            )
        );

        $sizes = ee('Model')->get('FileDimension')
            ->filter('upload_location_id', $upload_id)->all();

        $size_choices = array();
        $js_size = array();
        foreach ($sizes as $size) {
            // For checkboxes
            $size_choices[$size->id] = [
                'label' => $size->short_name,
                'instructions' => lang($size->resize_type) . ', ' . $size->width . 'px ' . lang('by') . ' ' . $size->height . 'px'
            ];

            // For JS sync script
            $js_size[$size->upload_location_id][$size->id] = array(
                'short_name' => $size->short_name,
                'resize_type' => $size->resize_type,
                'width' => $size->width,
                'height' => $size->height,
                'quality' => $size->quality,
                'watermark_id' => $size->watermark_id
            );
        }

        // Only show the manipulations section if there are manipulations
        if (! empty($size_choices)) {
            $vars['sections'][0][] = array(
                'title' => 'apply_manipulations',
                'desc' => 'apply_manipulations_desc',
                'fields' => array(
                    'sizes' => array(
                        'type' => 'checkbox',
                        'choices' => $size_choices,
                        'no_results' => [
                            'text' => sprintf(lang('no_found'), lang('image_manipulations'))
                        ]
                    )
                )
            );
        }

        $base_url = ee('CP/URL')->make('files/uploads/sync/' . $upload_id);

        ee()->cp->add_js_script('file', 'cp/files/synchronize');

        // Globals needed for JS script
        ee()->javascript->set_global(array(
            'file_manager' => array(
                'sync_id' => $upload_id,
                'sync_files' => $files,
                'sync_file_count' => $files_count,
                'sync_sizes' => $js_size,
                'sync_baseurl' => $base_url->compile(),
                'sync_endpoint' => ee('CP/URL')->make('files/uploads/do_sync_files')->compile(),
                'sync_dir_name' => $upload_destination->name,
            )
        ));

        ee()->view->base_url = $base_url;
        ee()->view->cp_page_title = lang('sync_title');
        ee()->view->cp_page_title_alt = sprintf(lang('sync_alt_title'), $upload_destination->name);
        ee()->view->save_btn_text = 'btn_sync_directory';
        ee()->view->save_btn_text_working = 'btn_sync_directory_working';

        ee()->view->cp_breadcrumbs = array(
            ee('CP/URL')->make('files')->compile() => lang('files'),
            ee('CP/URL')->make('files/directory/' . $upload_id)->compile() => ee('Model')->get('UploadDestination', $upload_id)->fields('name')->first()->name,
            '' => lang('sync_title')
        );

        // Errors are given through a POST to this same page
        $errors = ee()->input->post('errors');
        if (! empty($errors)) {
            ee()->view->set_message('warn', lang('directory_sync_warning'), json_decode($errors));
        }

        ee()->cp->render('settings/form', $vars);
    }

    /**
     * Sync process, largely copied from old content_files controller
     */
    public function doSyncFiles()
    {
        $type = 'insert';
        $errors = array();
        $file_data = array();
        $replace_sizes = array();
        $db_sync = (ee()->input->post('db_sync') == 'y') ? 'y' : 'n';
        $id = ee()->input->post('upload_directory_id');
        $sizes = ee()->input->post('sizes') ?: array($id => '');

        if (! ee('Permission')->can('upload_new_files') or empty($id)) {
            return ee()->output->send_ajax_response([
                'message_type' => 'failure',
                'errors' => lang('unauthorized_access'),
            ]);
        }

        // If file exists- make sure it exists in db - otherwise add it to db and generate all child sizes
        // If db record exists- make sure file exists -  otherwise delete from db - ?? check for child sizes??

        if (
            ($current_files = ee()->input->post('files')) === false
            && $db_sync != 'y'
        ) {
            return false;
        }

        $uploadDestination = ee('Model')->get('UploadDestination', $id)->first();

        if (empty($uploadDestination)) {
            return ee()->output->send_ajax_response([
                'message_type' => 'failure',
                'errors' => lang('unauthorized_access'),
            ]);
        }

        // Final run through, it syncs the db, removing stray records and thumbs
        if ($db_sync == 'y') {
            if (AJAX_REQUEST) {
                $errors = ee()->input->post('errors');
                if (empty($errors)) {
                    ee()->view->set_message('success', lang('directory_synced'), lang('directory_synced_desc'), true);
                }

                return ee()->output->send_ajax_response(array(
                    'message_type' => 'success'
                ));
            }

            return;
        }

        $resize_ids = ee()->input->post('resize_ids');
        $synced = $uploadDestination->syncFiles($current_files, $sizes, $resize_ids);

        if (AJAX_REQUEST) {
            if ($synced === true) {
                return ee()->output->send_ajax_response(array(
                    'message_type' => 'success'
                ));
            } else {
                return ee()->output->send_ajax_response(array(
                    'message_type' => 'failure',
                    'errors' => $synced
                ));
            }

            
        }
    }
}

// EOF

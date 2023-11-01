<?php
/**
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license
 */
 require_once PATH_ADDONS . 'grid/ft.grid.php';

/**
 * File Grid Fieldtype
 */
class file_grid_ft extends Grid_ft
{
    public $info = [
        'name' => 'File Grid',
        'version' => '1.0.0'
    ];

    public $size = 'large';

    public $supportedEvaluationRules = null;

    public $settings_form_field_name = 'file_grid';

    public function display_field($data)
    {
        $grid_markup = parent::display_field($data);

        // Just use regular Grid if in Channel Form
        if (REQ != 'CP') {
            return $grid_markup;
        }

        ee()->load->library('file_field');
        ee()->file_field->loadDragAndDropAssets();

        ee()->cp->add_js_script('file', 'fields/grid/file_grid');

        ee()->javascript->set_global([
            'lang.file_grid_maximum_rows_hit' => lang('file_grid_maximum_rows_hit'),
        ]);

        return ee('View')->make('grid:file_grid')->render([
            'grid_markup' => $grid_markup,
            'allowed_directory' => $this->get_setting('allowed_directories', 'all'),
            'content_type' => $this->get_setting('field_content_type', 'all'),
            'grid_max_rows' => $this->get_setting('grid_max_rows'),
            'vertical_layout' => isset($this->settings['vertical_layout'])
                ? ($this->settings['vertical_layout'] == 'horizontal_layout' ? 'horizontal' : $this->settings['vertical_layout'])
                : 'n',
        ]);
    }

    public function display_settings($data)
    {
        $directory_choices = ['all' => lang('all')] + ee('Model')->get('UploadDestination')
            ->fields('id', 'name')
            ->filter('site_id', 'IN', [0, ee()->config->item('site_id')])
            ->filter('module_id', 0)
            ->order('name', 'asc')
            ->all(true)
            ->getDictionary('id', 'name');

        $vars = $this->getSettingsVars();
        $vars['group'] = $this->settings_form_field_name;
        $allowed_directories = isset($data['allowed_directories']) ? $data['allowed_directories'] : 'all';

        $settings = [
            'field_options_file_grid' => [
                'label' => 'field_options',
                'group' => $vars['group'],
                'settings' => [
                    [
                        'title' => 'grid_min_rows',
                        'desc' => 'grid_min_rows_desc',
                        'fields' => [
                            'grid_min_rows' => [
                                'type' => 'text',
                                'value' => isset($data['grid_min_rows']) ? $data['grid_min_rows'] : 0
                            ]
                        ]
                    ],
                    [
                        'title' => 'grid_max_rows',
                        'desc' => 'grid_max_rows_desc',
                        'fields' => [
                            'grid_max_rows' => [
                                'type' => 'text',
                                'value' => isset($data['grid_max_rows']) ? $data['grid_max_rows'] : ''
                            ]
                        ]
                    ],
                    [
                        'title' => 'grid_allow_reorder',
                        'fields' => [
                            'allow_reorder' => [
                                'type' => 'yes_no',
                                'value' => isset($data['allow_reorder']) ? $data['allow_reorder'] : 'y'
                            ]
                        ]
                    ],
                    [
                        'title' => 'file_ft_content_type',
                        'desc' => 'file_ft_content_type_desc',
                        'fields' => [
                            'field_content_type' => [
                                'type' => 'radio',
                                'choices' => [
                                    'all' => lang('all'),
                                    'image' => lang('file_ft_images_only')
                                ],
                                'value' => isset($data['field_content_type']) ? $data['field_content_type'] : 'image'
                            ]
                        ]
                    ],
                    [
                        'title' => 'file_ft_allowed_dirs',
                        'desc' => 'file_ft_allowed_dirs_desc',
                        'fields' => [
                            'allowed_directories' => [
                                'type' => 'radio',
                                'choices' => $directory_choices,
                                'value' => $allowed_directories,
                                'no_results' => [
                                    'text' => sprintf(lang('no_found'), lang('file_ft_upload_directories')),
                                    'link_text' => 'add_new',
                                    'link_href' => ee('CP/URL')->make('files/uploads/create')
                                ]
                            ]
                        ]
                    ],
                    [
                        'title' => 'file_grid_vertical_layout_title',
                        'desc' => 'grid_vertical_layout_desc',
                        'fields' => [
                            'vertical_layout' => [
                                'type' => 'radio',
                                'choices' => [
                                    'n' => lang('grid_auto'),
                                    'y' => lang('grid_vertical_layout'),
                                    'horizontal' => lang('grid_horizontal_layout'),
                                ],
                                'value' => isset($data['vertical_layout']) ? ($data['vertical_layout'] == 'horizontal_layout' ? 'horizontal' : $data['vertical_layout']) : 'n'
                            ]
                        ]
                    ]
                ]
            ],
            'field_options_file_grid_fields' => [
                'label' => 'file_grid_setup',
                'group' => $vars['group'],
                'settings' => [$vars['grid_alert'], ee('View')->make('grid:settings')->render($vars)]
            ]
        ];

        if (!array_key_exists($allowed_directories, $directory_choices)) {
            $selectedDir = ee('Model')->get('UploadDestination', $allowed_directories)->with('Site')->first();
            if (!is_null($selectedDir)) {
                $settings['field_options_file_grid']['settings'][4]['fields']['file_field_msm_warning'] = array(
                    'type' => 'html',
                    'content' => ee('CP/Alert')->makeInline('file_field_msm_warning')
                        ->asImportant()
                        ->addToBody(sprintf(lang('file_field_msm_warning'), $selectedDir->name, $selectedDir->Site->site_label))
                        ->cannotClose()
                        ->render()
                );
            }
        }

        $this->loadGridSettingsAssets();

        $settings_json = '{ minColumns: 0, fieldName: "file_grid" }';

        ee()->javascript->output('EE.grid_settings($(".fields-grid-setup[data-group=file_grid]"), ' . $settings_json . ');');
        ee()->javascript->output('FieldManager.on("fieldModalDisplay", function(modal) {
            EE.grid_settings($(".fields-grid-setup[data-group=file_grid]", modal), ' . $settings_json . ');
        });');

        return $settings;
    }

    /**
     * Override parent to insert/hide our phantom File column
     */
    public function getColumnsForSettingsView()
    {
        $columns = parent::getColumnsForSettingsView();

        if ($this->id()) {
            foreach ($columns as &$column) {
                $column['col_hidden'] = true;

                break;
            }
        } else {
            array_unshift($columns, [
                'col_id' => 'new_0',
                'col_type' => 'file',
                'col_label' => 'File',
                'col_name' => 'file',
                'col_instructions' => '',
                'col_required' => 'n',
                'col_search' => 'y',
                'col_width' => '',
                'col_settings' => [
                    'field_content_type' => 'image',
                    'allowed_directories' => 'all'
                ],
                'col_hidden' => true
            ]);
        }

        return $columns;
    }

    public function save_settings($data)
    {
        $settings = parent::save_settings($data);

        $settings['field_content_type'] = $data['field_content_type'];
        $settings['allowed_directories'] = $data['allowed_directories'];
        $settings['vertical_layout'] = empty($data['vertical_layout']) ? 'n' : $data['vertical_layout'];

        return $settings;
    }

    /**
     * Override parent apply File Grid upload preference settings to phantom file column
     */
    public function post_save_settings($data)
    {
        if (isset($_POST[$this->settings_form_field_name])) {
            foreach ($_POST[$this->settings_form_field_name]['cols'] as $col_field => &$column) {
                if ($column['col_name'] == 'file') {
                    $column['col_settings'] = [
                        'field_content_type' => ee('Request')->post('field_content_type'),
                        'allowed_directories' => ee('Request')->post('allowed_directories')
                    ];
                }
            }
        }

        parent::post_save_settings($data);
    }

    // for File Grid, we need to validate grid_min_rows
    public function validate($data)
    {
        if (!ee('Request')->isAjax() && !empty($this->settings['grid_min_rows']) && (empty($data) || !isset($data['rows']) || count($data['rows']) < $this->settings['grid_min_rows'])) {
            return sprintf(lang('grid_min_rows_required'), $this->settings['grid_min_rows']);
        }

        return parent::validate($data);
    }
}

// EOF

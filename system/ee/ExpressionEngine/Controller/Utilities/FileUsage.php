<?php
/**
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2022, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license
 */

namespace ExpressionEngine\Controller\Utilities;

use ExpressionEngine\Service\Model\Collection;

/**
 * File Usage Update Controller
 */
class FileUsage extends Utilities
{
    const CACHE_KEY = '/search/file-usage';

    protected $fieldsAndTables = [];
    protected $updating = false;
    //the numbers of rows to handle on each run
    protected $entriesLimit = 10;
    protected $offset = 0;

    public function __construct()
    {
        parent::__construct();

        $data = ee()->cache->get(self::CACHE_KEY);
        if ($data === false || $data['updating'] === false) {
            $gridFields = [];

            foreach (ee('Model')->get('ChannelField')->all() as $field)
            {
                $this->fieldsAndTables[] = [
                    $field->getDataStorageTable() => ['field_id_' . $field->getId()]
                ];
                if (in_array($field->field_type, ['grid', 'file_grid'])) {
                    $gridFields[] = $field;
                }
            }
            $this->fieldsAndTables[] = [
                'categories' => ['cat_image']
            ];
            ee()->load->model('grid_model');
            foreach ($gridFields as $field) {
                $columnIds =  ee()->grid_model->get_columns_for_field($field->getId(), 'channel');
                $columns = [];
                foreach ($columnIds as $col) {
                    $columns[] = 'col_id_' . $col['col_id'];
                }
                $this->fieldsAndTables[] = [
                    'channel_grid_field_' . $field->getId() => $columns
                ];
            }
            $this->cache();
        } else {
            $this->fieldsAndTables = $data['fieldsAndTables'];
            $this->updating = $data['updating'];
            $this->offset = $data['offset'];
        }
    }

    /**
     * Save the field_ids, entry_ids, and updating status to cache
     *
     * @return bool TRUE if it saved; FALSE if not
     */
    protected function cache()
    {
        $data = [
            'fieldsAndTables' => $this->fieldsAndTables,
            'updating' => $this->updating,
            'offset' => $this->offset
        ];

        return ee()->cache->save(self::CACHE_KEY, $data);
    }

    /**
     * Update File Usage utility
     *
     * @access	public
     * @return	void
     */
    public function index()
    {
        if (! ee('Permission')->has('can_access_data')) {
            show_error(lang('unauthorized_access'), 403);
        }

        ee()->cp->add_js_script('file', 'cp/utilities/file-usage');

        ee()->javascript->set_global([
            'update_file_usage' => [
                'endpoint' => ee('CP/URL')->make('utilities/file-usage/process')->compile(),
                'fieldsAndTables' => $this->fieldsAndTables,
                'desc' => lang('update_file_usage_desc'),
                'base_url' => ee('CP/URL')->make('utilities/file-usage')->compile(),
                'ajax_fail_banner' => ee('CP/Alert')->makeInline('update_file_usage-fail')
                    ->asIssue()
                    ->withTitle(lang('update_file_usage_fail'))
                    ->addToBody('%body%')
                    ->render()
            ]
        ]);

        $vars = [
            'base_url' => ee('CP/URL')->make('utilities/file-usage/process')->compile(),
            'hide_top_buttons' => true,
            'save_btn_text' => 'update',
            'save_btn_text_working' => 'updating',
            'sections' => [
                [
                    [
                        'title' => 'update_file_usage',
                        'desc' => sprintf(lang('update_file_usage_desc'), number_format(count($this->fieldsAndTables))),
                        'fields' => [
                            'progress' => [
                                'type' => 'html',
                                'content' => ee()->load->view('_shared/progress_bar', array('percent' => 0), true)
                            ]
                        ]
                    ]
                ]
            ],
        ];

        ee('CP/Alert')->makeInline('update_file_usage_explained')
            ->asTip()
            ->cannotClose()
            ->addToBody(sprintf(
                lang('update_file_usage_explained_desc'),
                DOC_URL . 'control-panel/file-manager/file-manager.html#compatibility-mode',
                ee('CP/URL')->make('utilities/db-backup')->compile(),
                ee('CP/URL')->make('settings/content-design')->compile() . '#fieldset-file_manager_compatibility_mode')
            )
            ->now();

        ee()->view->extra_alerts = ['update_file_usage_explained'];

        ee()->view->cp_page_title = lang('update_file_usage');

        ee()->view->cp_breadcrumbs = array(
            '' => lang('update_file_usage')
        );

        ee()->cp->render('settings/form', $vars);
    }

    /**
     * Process the updating
     *
     * @access	public
     * @return	void
     */
    public function process()
    {
        // Only accept POST requests
        if (is_null(ee('Request')->post('progress'))) {
            show_404();
        }

        if (! $this->updating) {
            ee()->logger->log_action(lang('update_file_usage_started'));
            $this->updating = true;
            $this->cache();
            ee('db')->where('entry_id != 0')->delete('file_usage');
        }

        $progress = (int) ee('Request')->post('progress');

        if (isset($this->fieldsAndTables[$progress])) {
            foreach ($this->fieldsAndTables[$progress] as $table => $fields) {
                if (empty($table) || empty($fields)) {
                    continue;
                }
                $idField = ($table == 'categories') ? 'cat_id' : 'entry_id';
                $fieldsList = $idField . ', ' . implode(', ', $fields);
                if (strpos($table, 'channel_grid_field') === 0) {
                    $fieldsList .= ', row_id';
                }
                $query = ee('db')->select($fieldsList)->from($table)->limit($this->entriesLimit)->offset($this->offset)->get();
                // if we got less entries then expected, we come to end of DB table - shift the pointers
                if ($query->num_rows() < $this->entriesLimit) {
                    $this->offset = 0;
                    $progress++;
                } else {
                    $this->offset += $this->entriesLimit;
                }
                $this->cache();
                //extra check if we got no rows
                if ($query->num_rows() == 0) {
                    continue;
                }
                $replacement = [];
                foreach ($query->result_array() as $row) {
                    $update = [];
                    foreach ($fields as $fieldName) {
                        $data = $row[$fieldName];
                        if (strpos((string) $data, '{filedir_') !== false || strpos((string) $data, '{file:') !== false) {
                            $dirsAndFiles = [];
                            $currentReplacement = [];
                            //grab the files in old format
                            if (preg_match_all('/{filedir_(\d+)}([^\"\'\s]*)/', $data, $matches, PREG_SET_ORDER)) {
                                foreach ($matches as $match) {
                                    //set the data for files to be fetched - or use what we have
                                    if (!isset($replacement[$match[0]])) {
                                        $dirsAndFiles[$match[1]][] = $match[2];
                                    } else {
                                        $currentReplacement[$match[0]] = $replacement[$match[0]];
                                    }
                                }
                            }
                            //and make sure the new format is still not lost
                            if (preg_match_all('/{file\:(\d+)\:url}/', $data, $matches, PREG_SET_ORDER)) {
                                foreach ($matches as $match) {
                                    $currentReplacement[$match[0]] = $replacement[$match[0]] = [
                                        'file_id' => $match[1],
                                        'tag' => $match[0],
                                    ];
                                }
                            }
                            //only fetch the files data if we don't have those set to variable from previous loops
                            if (!empty($dirsAndFiles)) {
                                $files = ee('Model')
                                    ->get('File')
                                    ->fields('file_id', 'upload_location_id', 'file_name');
                                $files->filterGroup();
                                foreach ($dirsAndFiles as $dir_id => $file_names) {
                                    $files->orFilterGroup()
                                        ->filter('upload_location_id', $dir_id)
                                        ->filter('file_name', 'IN', $file_names)
                                        ->endFilterGroup();
                                }
                                $files->endFilterGroup();
                                foreach ($files->all() as $file) {
                                    //set the data to variables to use later
                                    $fileTag = '{filedir_' . $file->upload_location_id . '}' . $file->file_name;
                                    $currentReplacement[$fileTag] = $replacement[$fileTag] = [
                                        'file_id' => $file->file_id,
                                        'tag' => '{file:' . $file->file_id . ':url}',
                                    ];
                                }
                            }
                            //replace, actually
                            if (!empty($currentReplacement)) {
                                $customFieldId = str_replace(['field_id_', 'col_id_', 'category_image'], ['', '', ''], $fieldName);
                                $countFilesUsed = [];
                                foreach ($currentReplacement as $oldTag => $replacementData) {
                                    //the replacements are being counted
                                    $numberOfReplacements = 0;
                                    $data = str_replace($oldTag, $replacementData['tag'], $data, $numberOfReplacements);
                                    $countFilesUsed[(int) $customFieldId][$replacementData['file_id']] = $numberOfReplacements;
                                }
                                $update[$fieldName] = $data;
                            }
                        }
                    }
                    if (! empty($update)) {
                        //update the data table
                        if (strpos($table, 'channel_grid_field') === 0) {
                            ee('db')->where('row_id', $row['row_id'])->update($table, $update);
                        } else {
                            ee('db')->where($idField, $row[$idField])->update($table, $update);
                        }
                        //add as many records for file usage as needed
                        foreach ($countFilesUsed as $customFieldId => $fieldFileUsageData) {
                            foreach ($fieldFileUsageData as $fileId => $numberOfReplacements) {
                                for ($i = 0; $i < $numberOfReplacements; $i++) {
                                    ee('db')->insert('file_usage', [
                                        $idField => $row[$idField],
                                        //'field_id' => $customFieldId,
                                        'file_id' => $fileId
                                    ]);
                                }
                                //update the file usage counter on file
                                ee('db')->set('total_records', 'total_records + ' . $numberOfReplacements, false)->where('file_id', $fileId)->update('files');
                            }
                        }
                    }
                }
            }
        }

        if ($progress >= count($this->fieldsAndTables)) {
            ee('CP/Alert')->makeInline('shared-form')
                ->asSuccess()
                ->withTitle(lang('update_file_usage_success'))
                ->addToBody(lang('update_file_usage_success_desc'))
                ->defer();

            ee()->logger->log_action(sprintf(lang('update_file_usage_completed'), number_format(count($this->fieldsAndTables))));

            $this->updating = false; // For symmetry and "futureproofing"
            ee()->cache->delete(self::CACHE_KEY); // All done!
            ee()->output->send_ajax_response(['status' => 'finished']);
        }

        ee()->output->send_ajax_response([
            'status' => 'in_progress',
            'progress' => $progress
        ]);
    }
}
// END CLASS

// EOF

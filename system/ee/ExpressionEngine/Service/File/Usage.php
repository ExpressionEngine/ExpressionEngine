<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Service\File;

/**
 * File Usage Service
 */
class Usage
{
    const CACHE_KEY = '/search/file-usage';

    protected $fieldsAndTables = [];
    protected $updating = false;
    //the numbers of rows to handle on each run
    protected $entriesLimit = 50;
    protected $offset = 0;

    public function __construct()
    {
        // Load the logger
        if (! isset(ee()->logger)) {
            ee()->load->library('logger');
        }

        // If this is a CLI request, we will set the entries limit to practically infinite
        if (defined('REQ') && REQ === 'CLI') {
            $this->entriesLimit = PHP_INT_MAX;
        }

        $this->initializeFieldsAndTables();
    }

    public function getFieldsAndTables()
    {
        return $this->fieldsAndTables;
    }

    public function inProgress()
    {
        return $this->updating;
    }

    public function getProgressSteps()
    {
        return array_keys($this->fieldsAndTables);
    }

    protected function initializeFieldsAndTables()
    {
        $data = ee()->cache->get(self::CACHE_KEY);
        if ($data === false || $data['updating'] === false) {
            $this->buildFieldsAndTables();
            $this->cache();
        } else {
            $this->fieldsAndTables = $data['fieldsAndTables'];
            $this->updating = $data['updating'];
            $this->offset = $data['offset'];
        }
    }

    protected function buildFieldsAndTables()
    {
        $gridFields = [];

        foreach (ee('Model')->get('ChannelField')->all() as $field) {
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
            $columnIds = ee()->grid_model->get_columns_for_field($field->getId(), 'channel');
            $columns = [];
            foreach ($columnIds as $col) {
                $columns[] = 'col_id_' . $col['col_id'];
            }
            $this->fieldsAndTables[] = [
                'channel_grid_field_' . $field->getId() => $columns
            ];
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
     * Process the updating
     *
     * @access  public
     * @return  void
     */
    public function process(int $progress)
    {
        if (! $this->updating) {
            ee()->logger->log_action(lang('update_file_usage_started'));
            $this->updating = true;
            $this->cache();
            ee('db')->where('entry_id != 0')->delete('file_usage');
            ee('db')->update('files', ['total_records' => 0]);
        }

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
                if (strpos($table, 'channel_data_field') === 0) {
                    $fieldsList .= ', id';
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
                        if (strpos($table, 'channel_data_field') === 0) {
                            ee('db')->where('id', $row['id'])->update($table, $update);
                        } elseif (strpos($table, 'channel_grid_field') === 0) {
                            ee('db')->where('row_id', $row['row_id'])->update($table, $update);
                        } else {
                            ee('db')->where($idField, $row[$idField])->update($table, $update);
                        }
                        //add file usage record once per entry/category, as this is pivot table for models
                        foreach ($countFilesUsed as $customFieldId => $fieldFileUsageData) {
                            foreach ($fieldFileUsageData as $fileId => $numberOfReplacements) {
                                $pivotRecord = [
                                    $idField => $row[$idField],
                                    'file_id' => $fileId
                                ];
                                $pivotExists = ee('db')->where($pivotRecord)->count_all_results('file_usage');
                                if ($pivotExists == 0) {
                                    ee('db')->insert('file_usage', $pivotRecord);
                                    //update the file usage counter on file
                                    ee('db')->set('total_records', 'total_records + 1', false)->where('file_id', $fileId)->update('files');
                                }
                            }
                        }
                    }
                }
            }
        }

        if ($progress >= count($this->fieldsAndTables)) {
            ee()->logger->log_action(sprintf(lang('update_file_usage_completed'), number_format(count($this->fieldsAndTables))));

            $this->updating = false; // For symmetry and "futureproofing"
            ee()->cache->delete(self::CACHE_KEY); // All done!
        }

        return $progress;
    }
}

// EOF

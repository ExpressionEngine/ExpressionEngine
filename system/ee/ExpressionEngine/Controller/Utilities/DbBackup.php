<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2026, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Controller\Utilities;

use ExpressionEngine\Library\CP\Table;
use ExpressionEngine\Library\CP\Form;

/**
 * Database Backup Utility Controller
 */
class DbBackup extends Utilities
{
    protected $base_url = 'utilities/db-backup';

    public function __construct()
    {
        parent::__construct();
        if (! ee('Permission')->can('access_sql_manager')) {
            show_error(lang('unauthorized_access'), 403);
        }

    }

    public function index()
    {
        $sort_col = ee('Request')->get('sort_col') ?: 'bm.id';
        $sort_dir = ee('Request')->get('sort_dir') ?: 'desc';

        $base_url = ee('CP/URL')->make($this->base_url . '/index');
        $table = ee('CP/Table', [
            'lang_cols' => true,
            'sort_col' => $sort_col,
            'sort_dir' => $sort_dir,
            'class' => 'backup_manager'
        ]);

        $vars['cp_page_title'] = lang('backups');
        $table->setColumns([
            'file_name' => ['sort' => false],
            'date' => ['sort' => false],
            'size' => ['sort' => false, 'encode' => false],
            'manage' => [
                'type' => Table::COL_TOOLBAR,
            ],
        ]);

        $table->setNoResultsText(sprintf(lang('no_found'), lang('backups')));

        $backups = ee('Database/Backup', PATH_CACHE)->getBackups();

        $totalBackups = 0;
        $data = [];
        foreach ($backups as $backup) {
            $data[] = [
                $backup['filename'],
                $backup['date'],
                $backup['size'],
                ['toolbar_items' => [
                    'download' => [
                        'href' => ee('CP/URL')->make( 'utilities/db-backup/download', ['id' => $backup['hash']]),
                        'title' => lang('download'),
                    ],
                    'remove' => [
                        'href' => ee('CP/URL')->make('utilities/db-backup/remove', ['id' => $backup['hash']])->compile(),
                        'title' => lang('remove'),
                    ],
                ]],
            ];
        }

        ee()->view->cp_breadcrumbs = array(
            '' => lang('backups')
        );

        $table->setData($data);
        $vars['table'] = $table->viewData($base_url);
        $vars['base_url'] = $base_url;

        ee()->cp->render('utilities/backups/index', $vars);
        //return $this;
    }

    public function download()
    {
        $path = ee('Database/Backup', PATH_CACHE)->getBackup(ee()->input->get('id'));
        if($path) {
            ee('CP/Alert')->makeInline('shared-form')
                ->asSuccess()
                ->withTitle(lang('backup_not_found'))
                ->defer();

            ee()->functions->redirect(ee('CP/URL')->make($this->base_url));
        }

        header('Content-Type: application/octet-stream');
        header("Content-Transfer-Encoding: Binary");
        header("Content-disposition: attachment; filename=\"" . basename($path) . "\"");
        ob_clean(); flush();
        readfile($path);
        exit;
    }

    public function remove($id = false)
    {
        $id = ee()->input->get('id');
        $path = ee('Database/Backup', PATH_CACHE)->getBackup($id);
        if (is_null($path)) {
            ee()->functions->redirect(ee('CP/URL')->make($this->base_url));
        }

        $form = new Form;
        $field_group = $form->getGroup('verify_remove_backup');
        $field_set = $field_group->getFieldSet('confirm_remove_backup');
        $field_set->setDesc('confirm_delete_desc');
        $field_set->getField('confirm', 'yes_no');

        $form = $form->toArray();

        if (!empty($_POST)) {
            if(ee()->input->post('confirm') == 'y') {
                ee('Database/Backup', PATH_CACHE)->deleteBackup($path);
                ee('CP/Alert')->makeInline('shared-form')
                    ->asSuccess()
                    ->withTitle(lang('backup_deleted'))
                    ->defer();
                ee()->functions->redirect(ee('CP/URL')->make($this->base_url));
            } else {
                ee('CP/Alert')->makeInline('shared-form')
                    ->asWarning()
                    ->withTitle(lang('must_confirm_removal'))
                    ->now();
            }

        }

        $vars = [
            'cp_page_title' => lang('remove_backup'),
            'base_url' => ee('CP/URL')->make('utilities/db-backup/remove', ['id' => $id])->compile(),
            'save_btn_text' => lang('remove'),
            'save_btn_text_working' => lang('removing'),
        ];

        $vars += $form;

        ee()->view->cp_breadcrumbs = array(
            ee('CP/URL')->make('utilities/db-backup')->compile() => lang('backups'),
            '' => lang('remove_backup')
        );

        ee()->cp->render('settings/form', $vars);
    }

    public function backup()
    {
        $tables = ee('Database/Backup/Query')->getTables();

        $vars = [
            'cp_page_title' => lang('backup_database'),
            'save_btn_text' => 'backup_database',
            'save_btn_text_working' => 'backing_up',
            'hide_top_buttons' => true,
            'base_url' => '#',
            'sections' => [
                [
                    [
                        'title' => 'backup_tables',
                        'desc' => sprintf(lang('table_count'), count($tables)),
                        'fields' => [
                            'progress' => [
                                'type' => 'html',
                                'content' => ee('View')
                                    ->make('_shared/progress_bar')
                                    ->render(['percent' => 0])
                            ]
                        ]
                    ]
                ]
            ]
        ];

        // Create an error template for us to manipulate and display
        // in the event of AJAX errors
        $backup_ajax_fail_banner = ee('CP/Alert')->makeInline('backup-ajax-fail')
            ->asIssue()
            ->withTitle(lang('backup_error'))
            ->addToBody('%body%');

        $table_counts = [];
        $total_size = 0;
        foreach ($tables as $table => $specs) {
            $table_counts[$table] = $specs['rows'];
            $total_size += $specs['size'];
        }

        ee()->cp->add_js_script('file', 'cp/db_backup');
        ee()->javascript->set_global([
            'db_backup' => [
                'endpoint' => ee('CP/URL')->make('utilities/db-backup/do-backup')->compile(),
                'tables' => array_keys($tables),
                'table_counts' => $table_counts,
                'total_rows' => array_sum($table_counts),
                'backup_ajax_fail_banner' => $backup_ajax_fail_banner->render(),
                'base_url' => ee('CP/URL')->make('utilities/db-backup')->compile(),
                'out_of_memory_lang' => sprintf(lang('backup_out_of_memory'), ee()->cp->masked_url(DOC_URL . 'general/system-configuration-overrides.html#db_backup_row_limit'))
            ]
        ]);

        ee()->view->cp_breadcrumbs = array(
            ee('CP/URL')->make('utilities/db-backup')->compile() => lang('backups'),
            '' => lang('backup_database')
        );

        ee()->cp->render('settings/form', $vars);
    }

    /**
     * AJAX endpoint for backup requests
     */
    public function doBackup()
    {
        if (! ee('Filesystem')->isWritable(PATH_CACHE)) {
            return $this->sendError(lang('cache_path_not_writable'));
        }

        $table_name = ee('Request')->post('table_name');
        $offset = ee('Request')->post('offset');
        $file_path = ee('Request')->post('file_path');

        // Create a filename with the database name and timestamp
        if (empty($file_path)) {
            $date = ee()->localize->format_date('%Y-%m-%d_%Hh%im%ss%T');
            $file_path = PATH_CACHE . ee()->db->database . '_' . $date . '.sql';
        } else {
            // The path we get from POST will be truncated for security,
            // so we need to prepend it back
            $file_path = SYSPATH . $file_path;
        }

        // Some tables might be resource-intensive, do what we can
        @set_time_limit(0);
        @ini_set('memory_limit', '512M');

        $backup = ee('Database/Backup', $file_path);

        // Beginning a new backup
        if (empty($table_name)) {
            try {
                $backup->startFile();
                $backup->writeDropAndCreateStatements();
            } catch (\Exception $e) {
                return $this->sendError($e->getMessage());
            }
        }

        try {
            $returned = $backup->writeTableInsertsConservatively($table_name, $offset);
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage());
        }

        // Hide the absolute server path to our backup so that it's not exposed
        // in the request and in the front-end success message
        $safe_file_path = str_replace(SYSPATH, '', $file_path);

        // There are more tables to do, let our JavaScript know that we need
        // another request to this method
        if ($returned !== false) {
            return [
                'status' => 'in_progress',
                'table_name' => $returned['table_name'],
                'offset' => $returned['offset'],
                'file_path' => $safe_file_path
            ];
        }

        $backup->endFile();

        ee('CP/Alert')->makeInline('shared-form')
            ->asSuccess()
            ->canClose()
            ->withTitle(lang('backup_success'))
            ->addToBody(sprintf(lang('backup_success_desc'), $safe_file_path))
            ->defer();

        // All finished!
        return [
            'status' => 'finished',
            'file_path' => $safe_file_path
        ];
    }

    private function sendError($error)
    {
        return [
            'status' => 'error',
            'message' => $error
        ];
    }
}

// EOF

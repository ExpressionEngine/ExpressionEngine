<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2021, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Controller\Utilities;

use ExpressionEngine\Library\CP;

/**
 * SQL Manager Controller
 */
class Sql extends Utilities
{
    /**
     * SQL Manager
     */
    public function index()
    {
        if (! ee('Permission')->can('access_sql_manager')) {
            show_error(lang('unauthorized_access'), 403);
        }

        if (ee()->input->post('bulk_action_submit')) {
            $action = ee()->input->post('bulk_action');
            $tables = ee()->input->post('table');

            // Must select an action
            if ($action == 'none') {
                ee()->view->set_message('issue', lang('cp_message_issue'), lang('no_action_selected'));
            }
            // Must be either OPTIMIZE or REPAIR
            elseif (! in_array($action, array('OPTIMIZE', 'REPAIR'))) {
                show_error(lang('unauthorized_access'), 403);
            }
            // Must have selected tables
            elseif (empty($tables)) {
                ee()->view->set_message('issue', lang('cp_message_issue'), lang('no_tables_selected'));
            } else {
                return $this->opResults();
            }
        }

        ee()->load->model('tools_model');
        $vars = ee()->tools_model->get_sql_info();
        $vars += ee()->tools_model->get_table_status();

        foreach ($vars['status'] as $table) {
            $data[] = array(
                $table['name'],
                $table['rows'],
                $table['size'],
                array('toolbar_items' => array(
                    'view' => array(
                        'href' => ee('CP/URL')->make(
                            'utilities/query/run-query/' . $table['name'],
                            array(
                                'thequery' => rawurlencode(base64_encode('SELECT * FROM ' . $table['name'])),
                                'signature' => ee('Encrypt')->sign('SELECT * FROM ' . $table['name'])
                            )
                        ),
                        'title' => lang('view')
                    )
                )),
                array(
                    'name' => 'table[]',
                    'value' => $table['name']
                )
            );
        }

        $table = ee('CP/Table', array('autosort' => true, 'autosearch' => true, 'limit' => 0));
        $table->setColumns(
            array(
                'table_name',
                'records',
                'size' => array(
                    'encode' => false
                ),
                'manage' => array(
                    'type' => CP\Table::COL_TOOLBAR
                ),
                array(
                    'type' => CP\Table::COL_CHECKBOX
                )
            )
        );
        $table->setNoResultsText('no_tables_match');
        $table->setData($data);

        $vars['table'] = $table->viewData(ee('CP/URL')->make('utilities/sql'));

        ee()->view->cp_page_title = lang('sql_manager');
        ee()->view->table_heading = lang('database_tables');

        // Set search results heading
        if (! empty($vars['table']['search'])) {
            ee()->view->table_heading = sprintf(
                lang('search_results_heading'),
                $vars['table']['total_rows'],
                htmlspecialchars($vars['table']['search'], ENT_QUOTES, 'UTF-8')
            );
        }

        ee()->view->cp_breadcrumbs = array(
            '' => lang('sql_manager')
        );

        ee()->cp->render('utilities/sql/manager', $vars);
    }

    /**
     * Results of table operation
     */
    public function opResults()
    {
        $action = ee()->input->post('bulk_action');
        $tables = ee()->input->post('table');

        // This page can be invoked from a GET request due various ways to
        // sort and filter the table, so we need to check for cached data
        // from the original request
        if ($action == false && $tables == false) {
            $cache = ee()->cache->get('sql-op-results', \Cache::GLOBAL_SCOPE);

            if (empty($cache)) {
                return $this->index();
            } else {
                $action = $cache['action'];
                $data = $cache['data'];
            }
        } else {
            // Perform the action on each selected table and store the results
            foreach ($tables as $table) {
                $query = ee()->db->query("{$action} TABLE " . ee()->db->escape_str($table));

                foreach ($query->result_array() as $row) {
                    $row = array_values($row);
                    $row[0] = $table;
                    $data[] = array(
                        $row[0],
                        $row[2],
                        $row[3]
                    );
                }
            }

            $cache = array(
                'action' => $action,
                'data' => $data
            );

            // Cache it so we can access it on subsequent page requests due
            // to sorting and searching of the table
            ee()->cache->save('sql-op-results', $cache, 3600, \Cache::GLOBAL_SCOPE);
        }

        // Set up our table with automatic sorting and search capability
        $table = ee('CP/Table', array('autosort' => true, 'autosearch' => true, 'limit' => 0));
        $table->setColumns(array(
            'table',
            'status' => array(
                'type' => CP\Table::COL_STATUS
            ),
            'message'
        ));
        $table->setData($data);
        $table->setNoResultsText('no_tables_match');
        $vars['table'] = $table->viewData(ee('CP/URL')->make('utilities/sql/op-results'));

        ee()->view->cp_page_title = lang(strtolower($action) . '_tables_results');
        ee()->cp->set_breadcrumb(ee('CP/URL')->make('utilities/sql'), lang('sql_manager'));

        return ee()->cp->render('utilities/sql/ops', $vars);
    }
}
// END CLASS

// EOF

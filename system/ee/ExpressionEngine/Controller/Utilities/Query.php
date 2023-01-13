<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Controller\Utilities;

use ExpressionEngine\Library\CP;

/**
 * Query Controller
 */
class Query extends Utilities
{
    /**
     * Query form
     */
    public function index($show_validation = true)
    {
        // Super Admins only, please
        if (! ee('Permission')->isSuperAdmin()) {
            show_error(lang('unauthorized_access'), 403);
        }

        ee()->load->library('form_validation');
        ee()->form_validation->set_rules(array(
            array(
                'field' => 'thequery',
                'label' => 'lang:sql_query_to_run',
                'rules' => 'required'
            )
        ));

        if (AJAX_REQUEST) {
            ee()->form_validation->run_ajax();
            exit;
        } elseif (ee()->form_validation->run() !== false && $show_validation) {
            return $this->runQuery();
        } elseif (ee()->form_validation->errors_exist() && $show_validation) {
            ee()->view->set_message('issue', lang('query_form_error'), lang('query_form_error_desc'));
        }

        ee()->view->cp_page_title = lang('sql_query_form');

        ee()->cp->add_js_script([
            'plugin' => 'ee_codemirror',
            'ui' => 'resizable',
            'file' => array(
                'cp/utilities/sql-query-form',
                'vendor/codemirror/codemirror',
                'vendor/codemirror/closebrackets',
                'vendor/codemirror/comment',
                'vendor/codemirror/lint',
                'vendor/codemirror/active-line',
                'vendor/codemirror/overlay',
                'vendor/codemirror/xml',
                'vendor/codemirror/css',
                'vendor/codemirror/javascript',
                'vendor/codemirror/htmlmixed',
                'ee-codemirror-mode',
                'vendor/codemirror/dialog',
                'vendor/codemirror/searchcursor',
                'vendor/codemirror/search',
                'vendor/codemirror/sql',
            )
        ]);

        $fontSize = ee()->config->item('codemirror_fontsize');
        if ($fontSize !== false) {
            ee()->cp->add_to_head('<style type="text/css">.CodeMirror-scroll {font-size: ' . $fontSize . '}</style>');
        }

        ee()->view->cp_breadcrumbs = array(
            '' => lang('sql_query_form')
        );

        return ee()->cp->render('utilities/query/index');
    }

    /**
     * Query handler
     *
     * @param string	$table	Table name, used when coming from SQL Manager
     *                      	for proper page-naming and breadcrumb-setting
     */
    public function runQuery($table_name = '')
    {
        $row_limit = 25;
        $title = lang('query_result');
        $vars['write'] = false;

        $page = ee()->input->get('page') ? ee()->input->get('page') : 1;
        $page = ($page > 0) ? $page : 1;

        // Fetch the query.  It can either come from a
        // POST request or a url encoded GET request
        if (! $sql = ee()->input->post('thequery')) {
            if (! $sql = ee()->input->get('thequery')) {
                return $this->index();
            } else {
                $sql = trim(base64_decode(rawurldecode($sql)));

                if (! $signature = ee('Request')->get('signature')) {
                    return $this->index(false);
                }

                if (! ee('Encrypt')->verifySignature($sql, $signature)) {
                    return $this->index(false);
                }

                if (strncasecmp($sql, 'SELECT ', 7) !== 0 &&
                    strncasecmp($sql, 'SHOW', 4) !== 0) {
                    return $this->index(false);
                }
            }
        }

        $sql = trim(str_replace(";", "", $sql));

        // Determine if the query is one of the non-allowed types
        $qtypes = array('FLUSH', 'REPLACE', 'GRANT', 'REVOKE', 'LOCK', 'UNLOCK');

        if (preg_match("/(^|\s)(" . implode('|', $qtypes) . ")\s/si", $sql)) {
            ee()->view->set_message('issue', lang('sql_not_allowed'), lang('sql_not_allowed_desc'), true);

            return ee()->functions->redirect(ee('CP/URL')->make('utilities/query'));
        }

        // If it's a DELETE query, require that a Super Admin be the one submitting it
        if (! ee('Permission')->isSuperAdmin()) {
            if (strpos(strtoupper($sql), 'DELETE') !== false or strpos(strtoupper($sql), 'ALTER') !== false or strpos(strtoupper($sql), 'TRUNCATE') !== false or strpos(strtoupper($sql), 'DROP') !== false) {
                show_error(lang('unauthorized_access'), 403);
            }
        }

        ee()->db->db_exception = true;

        try {
            $query = ee()->db->query($sql);
        } catch (\Exception $e) {
            ee()->view->invalid_query = $e->getMessage();

            return $this->index(false);
        }

        ee()->db->db_exception = false;

        $qtypes = array('INSERT', 'UPDATE', 'DELETE', 'ALTER', 'CREATE', 'DROP', 'TRUNCATE');

        foreach ($qtypes as $type) {
            if (strncasecmp($sql, $type, strlen($type)) == 0) {
                $vars['affected'] = ee()->db->affected_rows();
                $vars['write'] = true;

                break;
            }
        }

        // Don't run column names though lang()
        $table_config = array('lang_cols' => false);

        // SHOW queries don't handle limiting and sorting, let the
        // Table library handle it
        if ($show_query = (strncasecmp($sql, 'SHOW', 4) === 0)) {
            $table_config['autosort'] = true;
            $table_config['autosearch'] = true;
        }

        $columns = array();
        if ($query && $vars['write'] == false) {
            $columns = array_keys($query->row_array());
        }

        $table = ee('CP/Table', $table_config);
        $table->setColumns($columns);

        $search = $table->search; // PHP 5.3
        if (! empty($search) && $query && ! $show_query) {
            if ($query->num_rows() > 0) {
                $data = $query->result_array();
                $keys = array_keys($data[0]);

                $new_sql = 'SELECT * FROM (' . $sql . ') AS search WHERE';
                foreach ($keys as $index => $key) {
                    if ($index > 0) {
                        $new_sql .= ' OR';
                    }
                    $new_sql .= ' ' . $key . ' LIKE \'%' . ee()->db->escape_like_str($table->search) . '%\'';
                }
            }
        }

        // Get the total results on the orignal query before we paginate it
        $query = (isset($new_sql)) ? ee()->db->query($new_sql) : $query;
        $total_results = (is_object($query)) ? $query->num_rows() : 0;

        //set up pagination filter
        $filters = ee('CP/Filter')
            ->add('Perpage', $total_results);
        $row_limit = $filters->values()['perpage'];

        // Does this query already have a limit?
        $limited_query = (preg_match("/LIMIT\s+[0-9]/i", $sql));

        // If it's a SELECT query we'll see if we need to limit
        // the result total and add pagination links
        if (strpos(strtoupper(trim($sql)), 'SELECT') === 0) {
            $sort_col = $table->sort_col; // PHP 5.3
            if (! empty($sort_col)) {
                $new_sql = (! isset($new_sql)) ? '(' . $sql . ')' : '(' . $new_sql . ')';

                // Wrap query in parenthesis in case query already has a
                // limit on it, we can't put an ORDER BY after a LIMIT
                $new_sql .= ' ORDER BY ' . $table->sort_col . ' ' . $table->sort_dir;
            }

            if (! $limited_query) {
                // Modify the query so we get the total sans LIMIT
                $row = ($page - 1) * $row_limit; // Offset is 0 indexed

                if (! isset($new_sql)) {
                    $new_sql = $sql;
                }

                $new_sql .= " LIMIT " . $row . ", " . $row_limit;
            }

            if (isset($new_sql)) {
                $query = ee()->db->query($new_sql);
            }
        }

        $data = (is_object($query)) ? $query->result_array() : array();

        $table->setData($data);

        $base_url = ee('CP/URL')->make(
            'utilities/query/run-query/' . $table_name,
            array(
                'thequery' => rawurlencode(base64_encode($sql)),
                'signature' => ee('Encrypt')->sign($sql)
            )
        );
        $view_data = $table->viewData($base_url);
        $data = $view_data['data'];
        $vars['table'] = $view_data;

        $vars['thequery'] = $sql;
        $vars['total_results'] = (isset($total_results)) ? $total_results : 0;
        $vars['total_results'] = ($show_query) ? $vars['table']['total_rows'] : $vars['total_results'];

        // Set search results heading
        if (! empty($search)) {
            ee()->view->table_heading = sprintf(
                lang('search_results_heading'),
                $vars['total_results'],
                $search
            );
        }

        $row_limit = ($limited_query) ? $vars['total_results'] : $row_limit;

        $vars['pagination'] = ee('CP/Pagination', $vars['total_results'])
            ->perPage($row_limit)
            ->currentPage($page)
            ->render($view_data['base_url']);

        // If no table, keep query form labeling
        if (empty($table_name)) {
            ee()->cp->set_breadcrumb(ee('CP/URL')->make('utilities/query'), lang('query_form'));
            ee()->view->cp_page_title = lang('query_results');
        }
        // Otherwise, we're coming from the SQL Manager
        else {
            ee()->cp->set_breadcrumb(ee('CP/URL')->make('utilities/sql'), lang('sql_manager_abbr'));
            ee()->view->cp_page_title = $table_name . ' ' . lang('table');
        }

        ee()->cp->render('utilities/query/results', $vars);
    }
}
// END CLASS

// EOF

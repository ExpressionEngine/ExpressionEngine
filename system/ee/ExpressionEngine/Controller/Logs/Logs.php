<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Controller\Logs;

use CP_Controller;
use ExpressionEngine\Library\CP;
use ExpressionEngine\Library\CP\LogManager\ColumnFactory;
use ExpressionEngine\Library\CP\LogManager\ColumnRenderer;
use ExpressionEngine\Dependency\Monolog;

/**
 * Logs Controller
 */
class Logs extends CP_Controller
{
    public $perpage = 25;
    public $params = array();
    public $base_url;
    protected $search_installed = false;

    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__construct();

        ee()->lang->loadfile('logs');

        if (! ee('Permission')->can('access_logs')) {
            show_error(lang('unauthorized_access'), 403);
        }

        $this->base_url = ee('CP/URL')->make('logs');

        $this->search_installed = ee('Model')->get('Module')
            ->filter('module_name', 'Search')
            ->first();

        $this->search_installed = ! is_null($this->search_installed);

        $this->params['perpage'] = $this->perpage; // Set a default

        $this->generateSidebar();
    }

    protected function generateSidebar()
    {
        $sidebar = ee('CP/Sidebar')->make();
        $logs = $sidebar->addHeader(lang('logs'))
            ->addBasicList();

        $item = $logs->addItem(lang('system_log'), ee('CP/URL')->make('logs'));

        if (ee('Permission')->can('manage_consents')) {
            $item = $logs->addItem(lang('consent_log'), ee('CP/URL')->make('logs/consent'));
        }

        $item = $logs->addItem(lang('throttle_log'), ee('CP/URL')->make('logs/throttle'));
        $item = $logs->addItem(lang('email_log'), ee('CP/URL')->make('logs/email'));

        if ($this->search_installed) {
            $item = $logs->addItem(lang('search_log'), ee('CP/URL')->make('logs/search'));
        }
    }

    /**
     * Index function
     *
     * @access public
     * @return string
     */
    public function index()
    {
        $action = ee()->input->get_post('bulk_action');

        if ($action) {
            $ids = ee()->input->get_post('selection');
            switch ($action) {
                case 'remove':
                    $this->removeLogs($ids);
                    break;
            }
            ee()->functions->redirect($this->base_url);
        }

        $vars = $this->listingsPage();

        ee()->javascript->set_global('lang.remove_confirm', lang('logs') . ': <b>### ' . lang('logs') . '</b>');
        ee()->cp->add_js_script(array(
            'file' => array('cp/confirm_remove'),
        ));

        $vars['cp_heading'] = empty(ee()->input->get('channel')) ? lang('system_log') : ucfirst(lang(ee()->input->get('channel'))) . ' ' . lang('logs');

        $vars['toolbar_items'] = [];
        if (ee('Permission')->can('access_sys_prefs')) {
            $vars['toolbar_items']['settings'] = [
                'href' => ee('CP/URL')->make('settings/logging'),
                'class' => 'button--secondary icon--settings',
                'title' => lang('logging_settings')
            ];
        }
        $vars['toolbar_items']['remove'] = [
            'href' => ee('CP/URL')->make('logs', ['bulk_action' => 'remove', 'selection' => ee()->input->get('channel') ?: '_all_']),
            'class' => 'button--danger fal fa-trash',
            'title' => lang('clear_logs'),
            'data-warning' => sprintf(lang('confirm_remove_logs'), $vars['cp_heading'])
        ];

        ee()->view->base_url = $this->base_url;
        ee()->view->ajax_validate = true;
        ee()->view->cp_page_title = ee()->view->cp_page_title ?: lang('system_log');

        ee()->view->cp_breadcrumbs = array(
            '' => lang('logs')
        );

        if (AJAX_REQUEST) {
            return array(
                'html' => ee('View')->make('logs/index')->render($vars),
                'url' => $vars['form_url']->compile(),
                'viewManager_saveDefaultUrl' => ee('CP/URL')->make('logs/views/save-default', ['channel' => $vars['channel']])->compile()
            );
        }

        ee()->cp->render('logs/index', $vars);
    }

    protected function listingsPage()
    {
        $vars = array(
            'channel' => null
        );

        $base_url = ee('CP/URL')->make('logs');

        $logs = ee('Model')->get('Log');
        // developer logs only available for superadmins
        if (!ee('Permission')->isSuperAdmin()) {
            $logs->filter('channel', '!=', 'developer');
        }

        $filters = ee('CP/Filter');
        $channelFilter = $this->createChannelFilter();
        if ($channelFilter->value()) {
            $vars['channel'] = $channelFilter->value();
            $logs->filter('channel', $channelFilter->value());
        }
        $levelFilter = $this->createLevelFilter();
        if ($levelFilter->value()) {
            $logs->filter('level', '>=', $levelFilter->value());
        }
        $filters->add('EntryKeyword')
            ->add($channelFilter)
            ->add($levelFilter)
            ->add('Date');

        $filters->add('LogManagerColumns', $this->createColumnFilter());

        $filter_values = $filters->values();
        if (! empty($filter_values['filter_by_date'])) {
            if (is_array($filter_values['filter_by_date'])) {
                $logs->filter('log_date', '>=', $filter_values['filter_by_date'][0]);
                $logs->filter('log_date', '<', $filter_values['filter_by_date'][1]);
            } else {
                $logs->filter('log_date', '>=', ee()->localize->now - $filter_values['filter_by_date']);
            }
        }

        $search_terms = ee()->input->get_post('filter_by_keyword');
        if ($search_terms) {
            $vars['search_terms'] = htmlentities($search_terms, ENT_QUOTES, 'UTF-8');
            $logs->search(['message', 'context', 'extra', 'ip_address'], $search_terms);
        }

        $total = $logs->count();

        $filters->add('Perpage', $total, 'show_all_logs');

        $filter_values = $filters->values();

        $perpage = $filter_values['perpage'];
        $page = ((int) ee()->input->get('page')) ?: 1;
        $offset = ($page - 1) * $perpage;

        $base_url->addQueryStringVariables(
            array_filter(
                $filter_values,
                function ($key) {
                    return (!in_array($key, ['columns', 'sort']));
                },
                ARRAY_FILTER_USE_KEY
            )
        );

        // Get order by and sort preferences for our initial state
        $sort_col = 'log_id';
        $sort_dir = 'desc';
        $table = ee('CP/Table', array(
            'sort_col' => $sort_col,
            'sort_dir' => $sort_dir,
            'class' => 'tbl-fixed'
        ));

        //which columns should we show
        $columns = [];
        array_unshift($filter_values['columns'], 'checkbox');
        foreach ($filter_values['columns'] as $column) {
            $columns[$column] = ColumnFactory::getColumn($column);
        }
        $columns = array_filter($columns);

        foreach ($columns as $column) {
            if (!empty($column)) {
                if (!empty($column->getEntryManagerColumnModels())) {
                    foreach ($column->getEntryManagerColumnModels() as $with) {
                        if (!empty($with)) {
                            $logs->with($with);
                        }
                    }
                }
            }
        }

        $column_renderer = new ColumnRenderer($columns);
        $table_columns = $column_renderer->getTableColumnsConfig();
        $table->setColumns($table_columns);

        $table->setNoResultsText('no_logs_found');

        $vars['bulk_options'] = [];
        if (ee('Permission')->has('can_access_logs')) {
            $vars['bulk_options'][] = [
                'value' => "remove",
                'text' => lang('delete'),
                'attrs' => ' data-confirm-trigger="selected" rel="modal-confirm-delete"'
            ];
        }

        foreach ($table_columns as $table_column) {
            if ($table_column['label'] == $table->sort_col) {
                $sort_col = $table_column['name'];

                break;
            }
        }

        if (! ($table->sort_dir == $sort_dir && $table->sort_col == $sort_col)) {
            $base_url->addQueryStringVariables(
                array(
                    'sort_dir' => $table->sort_dir,
                    'sort_col' => $table->sort_col
                )
            );
        }

        $vars['pagination'] = ee('CP/Pagination', $total)
            ->perPage($perpage)
            ->currentPage($page)
            ->render($base_url);
        $sort_field = $columns[$sort_col]->getEntryManagerColumnSortField();
        $logs = $logs->order($sort_field, $table->sort_dir)
            ->limit($perpage)
            ->offset($offset)
            ->all();

        $data = array();

        foreach ($logs as $log) {
            $data[] = array(
                'attrs' => [],
                'columns' => $column_renderer->getRenderedTableRowForEntry($log)
            );
        }

        $table->setData($data);

        $vars['table'] = $table->viewData($base_url);
        $vars['form_url'] = $vars['table']['base_url'];

        $vars['filters'] = $filters->renderEntryFilters($base_url);
        $vars['filters_search'] = $filters->renderSearch($base_url, true);
        $vars['search_value'] = htmlentities(ee()->input->get_post('filter_by_keyword'), ENT_QUOTES, 'UTF-8');

        ee()->javascript->set_global([
            'viewManager.saveDefaultUrl' => ee('CP/URL')->make('logs/views/save-default', ['channel' => $vars['channel']])->compile()
        ]);

        ee()->cp->add_js_script(array(
            'file' => array(
                'cp/confirm_remove',
                'cp/files/manager',
                'cp/publish/entry-list',
            ),
        ));
        return $vars;
    }

    /**
     * Creates log channel filter
     */
    private function createChannelFilter()
    {
        $builtinChannels = [
            'cp' => 'cp',
            'developer' => 'developer',
        ];
        $channelsQuery = ee()->db->select('channel')
            ->distinct()
            ->from('logs')
            ->order_by('channel', 'asc')
            ->get();
        $channels = array_map(function ($row) {
            return $row['channel'];
        }, $channelsQuery->result_array());
        $channels = array_combine($channels, $channels);
        $channels = array_merge($channels, $builtinChannels);

        $filter = ee('CP/Filter')->make('channel', lang('log_channel'), $channels);
        $filter->useListFilter();

        return $filter;
    }

    /**
     * Creates log level filter
     */
    private function createLevelFilter()
    {
        $levels = array_flip(Monolog\Logger::getLevels());

        $filter = ee('CP/Filter')->make('level', lang('level'), $levels);
        $filter->useListFilter();

        return $filter;
    }

        /**
     * Creates a column filter
     */
    private function createColumnFilter()
    {
        $column_choices = [];

        $columns = ColumnFactory::getAvailableColumns();

        foreach ($columns as $column) {
            $identifier = $column->getTableColumnIdentifier();

            // This column is mandatory, not optional
            if (in_array($identifier, ['checkbox'])) {
                continue;
            }

            $column_choices[$identifier] = strip_tags(lang($column->getTableColumnLabel()));
        }

        return $column_choices;
    }

    /**
     * Deletes log entries, either all at once, or one at a time
     *
     * @param string	$model		The name of the model to pass to
     *								ee('Model')->get()
     * @param string	$log_type	The text used in the delete message
     *								describing the type of log deleted
     */
    protected function delete($model, $log_type)
    {
        if (! ee('Permission')->can('access_logs')) {
            show_error(lang('unauthorized_access'), 403);
        }

        $id = ee()->input->post('delete');

        $flashdata = false;
        if (strtolower($id) == 'all') {
            $id = null;
            $flashdata = true;
        }

        $query = ee('Model')->get($model, $id);

        $count = $query->count();
        $query->delete();

        $message = sprintf(lang('logs_deleted_desc'), $count, lang($log_type));

        ee()->view->set_message('success', lang('logs_deleted'), $message, $flashdata);
    }

    /**
     * Remove log records
     *
     * @param array $ids
     * @return void
     */
    private function removeLogs($ids)
    {
        if (empty($ids)) {
            return;
        }

        if (is_array($ids)) {
            $count = count($ids);
            $type = lang('all');
            ee('Model')->get('Log', $ids)->delete();
        } elseif ($ids === '_all_') {
            ee('Model')->get('Log')->delete();
            $count = lang('all');
            $type = '';
        } else {
            ee('Model')->get('Log')->filter('channel', (string) $ids)->delete();
            $count = lang('all');
            $type = lang($ids);
        }

        if (isset($count)) {
            ee('CP/Alert')->makeInline('shared-form')
                ->asSuccess()
                ->withTitle(lang('logs_deleted'))
                ->addToBody(sprintf(lang('logs_deleted_desc'), $count, lang($type)))
                ->defer();
        }
    }
}
// END CLASS

// EOF

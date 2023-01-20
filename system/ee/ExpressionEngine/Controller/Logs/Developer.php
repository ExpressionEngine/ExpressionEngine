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

use ExpressionEngine\Service\CP\Filter\FilterFactory;
use ExpressionEngine\Service\CP\Filter\FilterRunner;

/**
 * Logs\Developer Controller
 */
class Developer extends Logs
{
    /**
     * Shows Developer Log page
     *
     * @access public
     * @return void
     */
    public function index()
    {
        if (! ee('Permission')->isSuperAdmin()) {
            show_error(lang('unauthorized_access'), 403);
        }

        if (ee()->input->post('delete')) {
            $this->delete('DeveloperLog', lang('developer_log'));
            if (strtolower(ee()->input->post('delete')) == 'all') {
                return ee()->functions->redirect(ee('CP/URL')->make('logs/developer'));
            }
        }

        ee('Model')->get('DeveloperLog')->set('viewed', 'y')->update();

        $this->base_url->path = 'logs/developer';
        ee()->view->cp_page_title = lang('view_developer_log');

        $logs = ee('Model')->get('DeveloperLog');

        if ($search = ee()->input->get_post('filter_by_keyword')) {
            /* The following SQL is an example of how to build the localized
             * deprecation log for searching.
             *
             *  SELECT * FROM exp_developer_log
             *  WHERE IFNULL(description,
             *    CONCAT_WS(' ',
             *          CONCAT('Deprecated function ', function, ' called'),
             *          CONCAT(' in ', file, ' on line ', line, '.'),
             *          CONCAT('From template tag exp:', addon_module, ':', addon_method, ' in ', template_group, '/', template_name, '.'),
             *          CONCAT('This tag may have been parsed from one of these snippets: ', snippets),
             *          CONCAT('Deprecated since ', deprecated_since, '.'),
             *          CONCAT('Use ', use_instead, ' instead.')
             *      )
             *  ) LIKE '%exp:foo:bar%';
             */

            $deprecated_function = str_replace('%s', "', exp_developer_log.function, '", lang('deprecated_function'));
            $deprecated_on_line = str_replace('%s', "', file, '", lang('deprecated_on_line'));
            $deprecated_on_line = str_replace('%d', "', line, '", $deprecated_on_line);

            $deprecated_template = str_replace(' %s ', " exp:', addon_module, ':', addon_method, ' ", lang('deprecated_template'));
            $deprecated_template = str_replace(' %s.', " ', template_group, '/', template_name, '.", $deprecated_template);

            $deprecated_snippets = str_replace('%s', "', snippets, '", lang('deprecated_snippets'));
            $deprecated_since = str_replace('%s', "', deprecated_since, '", lang('deprecated_since'));
            $deprecated_use_instead = str_replace('%s', "', use_instead, '", lang('deprecated_use_instead'));

            $localized_description = "IFNULL(description,\n";
            $localized_description .= "CONCAT_WS(' ',\n";
            $localized_description .= "CONCAT('" . $deprecated_function . "'),\n";
            $localized_description .= "CONCAT('" . $deprecated_on_line . "'),\n";
            $localized_description .= "CONCAT('" . $deprecated_template . "'),\n";
            $localized_description .= "CONCAT('" . $deprecated_snippets . "'),\n";
            $localized_description .= "CONCAT('" . $deprecated_since . "'),\n";
            $localized_description .= "CONCAT('" . $deprecated_use_instead . "')\n";
            $localized_description .= ")\n";
            $localized_description .= ")\n";

            // @TODO refactor to eliminate this query
            ee()->load->dbforge();
            $results = ee()->db->select('log_id')
                ->where($localized_description . " LIKE '%" . ee()->db->escape_like_str($search) . "%'")
                ->get('developer_log')
                ->result_array();

            $ids = array();
            foreach ($results as $row) {
                $ids[] = $row['log_id'];
            }

            if (empty($ids)) {
                $ids[] = -1; // log_id is usually positive, thus this triggers a no_results response
            }

            $logs = $logs->filter('log_id', 'IN', $ids);
        }

        $filters = ee('CP/Filter')
            ->add('Date')
            ->add('Keyword')
            ->add('Perpage', $logs->count(), 'all_developer_logs');
        ee()->view->filters = $filters->render($this->base_url);
        $this->params = $filters->values();
        $this->base_url->addQueryStringVariables($this->params);

        $page = ((int) ee()->input->get('page')) ?: 1;
        $offset = ($page - 1) * $this->params['perpage']; // Offset is 0 indexed

        if (! empty($this->params['filter_by_date'])) {
            if (is_array($this->params['filter_by_date'])) {
                $logs = $logs->filter('timestamp', '>=', $this->params['filter_by_date'][0]);
                $logs = $logs->filter('timestamp', '<', $this->params['filter_by_date'][1]);
            } else {
                $logs = $logs->filter('timestamp', '>=', ee()->localize->now - $this->params['filter_by_date']);
            }
        }

        $count = $logs->count();

        // Set the page heading
        if (! empty($search)) {
            ee()->view->cp_heading = sprintf(
                lang('search_results_heading'),
                $count,
                ee('Format')->make('Text', $search)->convertToEntities()
            );
        }

        ee()->view->header = array(
            'title' => lang('system_logs'),
            'form_url' => $this->base_url->compile(),
            'search_button_value' => lang('search_logs_button')
        );

        $logs = $logs->order('timestamp', 'desc')
            ->order('log_id', 'desc')
            ->limit($this->params['perpage'])
            ->offset($offset)
            ->all();

        $rows = array();

        foreach ($logs as $log) {
            if (! $log->function) {
                $description = '<p>' . $log->description . '</p>';
            } else {
                $description = '<p>';

                // "Deprecated function %s called"
                $description .= sprintf(lang('deprecated_function'), $log->function);

                // "in %s on line %d."
                if ($log->file && $log->line) {
                    $description .= NBS . sprintf(lang('deprecated_on_line'), '<code>' . $log->file . '</code>', $log->line);
                }

                $description .= '</p>';

                // "from template tag: %s in template %s"
                if ($log->addon_module && $log->addon_method) {
                    $description .= '<p>';
                    $description .= sprintf(
                        lang('deprecated_template'),
                        '<code>exp:' . strtolower($log->addon_module) . ':' . $log->addon_method . '</code>',
                        '<a href="' . ee('CP/URL')->make('design/template/edit/' . $log->template_id) . '">' . $log->template_group . '/' . $log->template_name . '</a>'
                    );

                    if ($log->snippets) {
                        $snippets = explode('|', $log->snippets);

                        foreach ($snippets as &$snip) {
                            $snip = '<a href="' . ee('CP/URL')->make('design/snippets_edit', array('snippet' => $snip)) . '">{' . $snip . '}</a>';
                        }

                        $description .= '<br>';
                        $description .= sprintf(lang('deprecated_snippets'), implode(', ', $snippets));
                    }
                    $description .= '</p>';
                }

                if ($log->deprecated_since || $log->use_instead) {
                    // Add a line break if there is additional information
                    $description .= '<p>';

                    // "Deprecated since %s."
                    if ($log->deprecated_since) {
                        $description .= sprintf(lang('deprecated_since'), $log->deprecated_since);
                    }

                    // "Use %s instead."
                    if ($log->use_instead) {
                        $description .= NBS . sprintf(lang('deprecated_use_instead'), $log->use_instead);
                    }
                    $description .= '</p>';
                }
            }

            $rows[] = array(
                'log_id' => $log->log_id,
                'timestamp' => ee()->localize->human_time($log->timestamp),
                'description' => $description
            );
        }

        $pagination = ee('CP/Pagination', $count)
            ->perPage($this->params['perpage'])
            ->currentPage($page)
            ->render($this->base_url);

        $vars = array(
            'rows' => $rows,
            'pagination' => $pagination,
            'form_url' => $this->base_url->compile(),
        );

        ee()->view->cp_breadcrumbs = array(
            '' => lang('view_developer_log')
        );

        ee()->cp->render('logs/developer', $vars);
    }
}
// END CLASS

// EOF

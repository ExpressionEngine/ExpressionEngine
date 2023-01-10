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
 * Logs\Throttle Controller
 */
class Throttle extends Logs
{
    /**
     * View Throttle Log
     *
     * Shows a list of ips that are currently throttled
     *
     * @access	public
     * @return	mixed
     */
    public function index()
    {
        if (! ee('Permission')->can('access_logs')) {
            show_error(lang('unauthorized_access'), 403);
        }

        ee('CP/Alert')->makeDeprecationNotice()->now();

        if (ee()->input->post('delete')) {
            $this->delete('Throttle', lang('throttle_log'));
            if (strtolower(ee()->input->post('delete')) == 'all') {
                return ee()->functions->redirect(ee('CP/URL')->make('logs/throttle'));
            }
        }

        $this->base_url->path = 'logs/throttle';
        ee()->view->cp_page_title = lang('view_throttle_log');

        $logs = array();
        $pagination = '';
        $throttling_disabled = true;

        if (ee()->config->item('enable_throttling') == 'y') {
            $throttling_disabled = false;
            $max_page_loads = 10;
            $lockout_time = 30;

            if (is_numeric(ee()->config->item('max_page_loads'))) {
                $max_page_loads = ee()->config->item('max_page_loads');
            }

            if (is_numeric(ee()->config->item('lockout_time'))) {
                $lockout_time = ee()->config->item('lockout_time');
            }

            $logs = ee('Model')->get('Throttle')
                ->filterGroup()
                ->filter('hits', '>=', $max_page_loads)
                ->orFilterGroup()
                ->filter('locked_out', 'y')
                ->filter('last_activity', '>', $lockout_time)
                ->endFilterGroup()
                ->endFilterGroup();

            if ($search = ee()->input->get_post('filter_by_keyword')) {
                $logs->search(['ip_address', 'hits'], $search);
            }

            $filters = ee('CP/Filter')
                ->add('Date')
                ->add('Keyword')
                ->add('Perpage', $logs->count(), 'all_throttle_logs');
            ee()->view->filters = $filters->render($this->base_url);
            $this->params = $filters->values();
            $this->base_url->addQueryStringVariables($this->params);

            $page = ((int) ee()->input->get('page')) ?: 1;
            $offset = ($page - 1) * $this->params['perpage']; // Offset is 0 indexed

            if (! empty($this->params['filter_by_date'])) {
                if (is_array($this->params['filter_by_date'])) {
                    $logs = $logs->filter('last_activity', '>=', $this->params['filter_by_date'][0]);
                    $logs = $logs->filter('last_activity', '<', $this->params['filter_by_date'][1]);
                } else {
                    $logs = $logs->filter('last_activity', '>=', ee()->localize->now - $this->params['filter_by_date']);
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

            $logs = $logs->order('last_activity', 'desc')
                ->limit($this->params['perpage'])
                ->offset($offset)
                ->all();

            $pagination = ee('CP/Pagination', $count)
                ->perPage($this->params['perpage'])
                ->currentPage($page)
                ->render($this->base_url);
        }

        ee()->view->header = array(
            'title' => lang('system_logs'),
            'form_url' => $this->base_url->compile(),
            'search_button_value' => lang('search_logs_button')
        );

        $vars = array(
            'logs' => $logs,
            'pagination' => $pagination,
            'disabled' => $throttling_disabled,
            'form_url' => $this->base_url->compile(),
        );

        ee()->view->cp_breadcrumbs = array(
            '' => lang('view_throttle_log')
        );

        ee()->cp->render('logs/throttle', $vars);
    }
}
// END CLASS

// EOF

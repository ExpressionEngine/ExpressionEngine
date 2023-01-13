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
 * Logs\Email Controller
 */
class Email extends Logs
{
    /**
     * View Email Log
     *
     * Displays emails logged
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
            $this->delete('EmailConsoleCache', lang('email_log'));
            if (strtolower(ee()->input->post('delete')) == 'all') {
                return ee()->functions->redirect(ee('CP/URL')->make('logs/email'));
            }
        }

        $this->base_url->path = 'logs/email';
        ee()->view->cp_page_title = lang('view_email_logs');

        $logs = ee('Model')->get('EmailConsoleCache');

        if ($search = ee()->input->get_post('filter_by_keyword')) {
            $logs->search(['member_name', 'ip_address', 'recipient', 'recipient_name', 'subject', 'message'], $search);
        }

        $filters = ee('CP/Filter')
            ->add('Username')
            ->add('Date')
            ->add('Keyword')
            ->add('Perpage', $logs->count(), 'all_email_logs');
        ee()->view->filters = $filters->render($this->base_url);
        $this->params = $filters->values();
        $this->base_url->addQueryStringVariables($this->params);

        $page = ((int) ee()->input->get('page')) ?: 1;
        $offset = ($page - 1) * $this->params['perpage']; // Offset is 0 indexed

        if (! empty($this->params['filter_by_username'])) {
            $logs = $logs->filter('member_id', 'IN', $this->params['filter_by_username']);
        }

        if (! empty($this->params['filter_by_date'])) {
            if (is_array($this->params['filter_by_date'])) {
                $logs = $logs->filter('cache_date', '>=', $this->params['filter_by_date'][0]);
                $logs = $logs->filter('cache_date', '<', $this->params['filter_by_date'][1]);
            } else {
                $logs = $logs->filter('cache_date', '>=', ee()->localize->now - $this->params['filter_by_date']);
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

        $logs = $logs->order('cache_date', 'desc')
            ->limit($this->params['perpage'])
            ->offset($offset)
            ->all();

        $pagination = ee('CP/Pagination', $count)
            ->perPage($this->params['perpage'])
            ->currentPage($page)
            ->render($this->base_url);

        $vars = array(
            'logs' => $logs,
            'pagination' => $pagination,
            'form_url' => $this->base_url->compile(),
        );

        ee()->view->cp_breadcrumbs = array(
            '' => lang('view_email_logs')
        );

        ee()->cp->render('logs/email/list', $vars);
    }

    /**
     * View Single Email
     *
     * @access	public
     * @return	mixed
     */
    public function view($id)
    {
        if (! ee('Permission')->can('access_logs')) {
            show_error(lang('unauthorized_access'), 403);
        }

        $email = ee('Model')->get('EmailConsoleCache', $id)->first();

        if (is_null($email)) {
            ee()->lang->load('communicate');
            ee()->view->set_message('issue', lang('no_cached_email'), '', true);
            ee()->functions->redirect(ee('CP/URL')->make('logs/email'));
        }

        ee()->view->cp_page_title = lang('email_log') . ': ' . $email->subject;
        ee()->view->cp_breadcrumbs = array(
            ee('CP/URL')->make('logs/email')->compile() => lang('view_email_logs'),
            '' => lang('view')
        );
        ee()->view->email = $email;
        ee()->cp->render('logs/email/detail');
    }
}
// END CLASS

// EOF

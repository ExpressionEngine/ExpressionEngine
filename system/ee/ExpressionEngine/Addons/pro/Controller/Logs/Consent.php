<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Addons\Pro\Controller\Logs;

use ExpressionEngine\Controller\Logs;

/**
 * Logs\CP Controller
 */
class Consent extends Logs\Consent
{
    public function __construct()
    {
        if (!ee('pro:Access')->hasRequiredLicense()) {
            show_error(lang('unauthorized_access'), 403);
        }
        ee()->lang->load('pro');
    }

    /**
     * Export Consent Audit Logs
     *
     */
    public function export()
    {
        $csv = ee('CSV');

        $logs = $this->_get_logs()->order('log_date', 'desc')->all();

        foreach ($logs as $log) {
            $datum = [
                lang('date_logged') => ee()->localize->human_time($log->log_date->getTimestamp()),
                lang('username') => !empty($log->member_id) ? $log->Member->username : lang('anonymous'),
                lang('ip_address') => $log->ip_address,
                lang('user_agent') => $log->user_agent,
                lang('consent_title') => $log->ConsentRequest->title,
                lang('action')  => $log->action
            ];
            $csv->addRow($datum);
        }

        ee()->logger->log_action(lang('exported_consent_log'));

        ee()->load->helper('download');
        force_download('consent-audit-log.csv', (string) $csv);
    }
}
// END CLASS

// EOF

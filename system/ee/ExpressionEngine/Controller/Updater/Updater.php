<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Controller\Updater;

use CP_Controller;
use ExpressionEngine\Service;

/**
 * Updater controller, funnels update commands to the updater runner
 */
class Updater extends CP_Controller
{
    /**
     * Early permissions checks
     */
    public function __construct()
    {
        parent::__construct();

        if (! ee('Permission')->isSuperAdmin() or
            ee('Request')->method() != 'POST') {
            show_error(lang('unauthorized_access'), 403);
        }
    }

    /**
     * Request end-point for updater tasks
     */
    public function index()
    {
        ee()->lang->loadfile('updater');
        ee()->load->library('el_pings');
        $version_file = ee()->el_pings->get_version_info(true);
        $current_version = ee()->config->item('app_version');
        $to_version = $version_file['latest_version'];

        $newer_version_available = version_compare($current_version, $to_version, '<');

        if (! $newer_version_available) {
            return ee()->functions->redirect(ee('CP/URL', 'homepage'));
        }

        $preflight_error = null;
        $runner = ee('Updater/Runner');

        try {
            // Run preflight first and go ahead and show those errors
            $runner->runStep($runner->getFirstStep());
        } catch (\Exception $e) {
            $preflight_error = str_replace("\n", '<br>', $e->getMessage());
        }

        ee()->load->helper('text');

        $next_step = $runner->getNextStep();
        $vars = [
            'cp_page_title' => lang('updating'),
            'site_name' => ee()->config->item('site_name'),
            'current_version' => formatted_version($current_version),
            'to_version' => formatted_version($to_version),
            'warn_message' => $preflight_error,
            'first_step' => $runner->getLanguageForStep($next_step),
            'next_step' => $next_step
        ];

        ee()->javascript->set_global([
            'lang.fatal_error_caught' => lang('fatal_error_caught'),
            'lang.we_stopped_on' => lang('we_stopped_on')
        ]);

        return ee('View')->make('updater/index')->render($vars);
    }

    /**
     * AJAX endpoint for the updater
     */
    public function run()
    {
        $step = ee()->input->get('step');

        if ($step === false or $step == 'undefined') {
            return;
        }

        // This step should not have hit this controller
        if ($step == 'updateFiles') {
            ee()->lang->loadfile('updater');

            throw new \Exception(lang('out_of_date_admin_php'));
        }

        $runner = ee('Updater/Runner');
        $runner->runStep($step);

        // If there is no next step and we're not rolling back, 'updateFiles'
        // should be next in the micro app
        $next_step = $runner->getNextStep();
        if ($next_step === false && $step !== 'rollback') {
            $next_step = 'updateFiles';
        }

        return [
            'messageType' => 'success',
            'message' => $runner->getLanguageForStep($next_step),
            'nextStep' => $next_step
        ];
    }

    public function authenticate()
    {
        ee()->load->library('auth');

        // Run through basic verifications: authenticate, username and
        // password both exist, not banned, IP checking is okay, run hook
        if (! ($verify_result = ee()->auth->verify())) {
            if (AJAX_REQUEST) {
                ee()->output->send_ajax_response(array(
                    'messageType' => 'failure',
                    'message' => $this->auth->errors
                ));
            }
        }

        if (AJAX_REQUEST) {
            ee()->output->send_ajax_response(array(
                'messageType' => 'success',
                'message' => ''
            ));
        }
    }

    public function subscribe()
    {
        ee()->lang->loadfile('updater');

        // Validate the email before any outbound request.
        $email = trim(ee()->input->post('email'));
        $validator = ee('Validation')->make(array(
            'email' => 'required|email'
        ));
        $result = $validator->validate(array(
            'email' => $email
        ));

        if ($result->isNotValid()) {
            return ee()->output->send_ajax_response(array(
                'messageType' => 'error',
                'message' => lang('updater_subscribe_invalid_email')
            ));
        }

        if (! function_exists('curl_init')) {
            return ee()->output->send_ajax_response(array(
                'messageType' => 'error',
                'message' => lang('updater_subscribe_submit_error')
            ));
        }

        // Build payload for the notifications service.
        $payload = array(
            'email' => $email,
            'notifications_opt_in' => 'y',
            'marketing_opt_in' => (ee()->input->post('marketing_opt_in') === 'y') ? 'y' : 'n',
            'site_url' => ee()->config->item('site_url'),
            'app_version' => ee()->config->item('app_version'),
            'source' => 'one_click_updater'
        );

        $payload_json = json_encode($payload);
        if ($payload_json === false) {
            return ee()->output->send_ajax_response(array(
                'messageType' => 'error',
                'message' => lang('updater_subscribe_submit_error')
            ));
        }

        // Send JSON to the notifications endpoint.
        $endpoint = 'https://update.expressionengine.com/notifications/subscribe';
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $endpoint);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $payload_json);
        curl_setopt($curl, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json',
            'Content-Length: ' . strlen($payload_json)
        ));
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($curl, CURLOPT_TIMEOUT, 10);

        $response = curl_exec($curl);
        $http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);

        // Treat non-2xx responses as a failure to subscribe.
        if ($response === false || $http_code < 200 || $http_code >= 300) {
            return ee()->output->send_ajax_response(array(
                'messageType' => 'error',
                'message' => lang('updater_subscribe_submit_error')
            ));
        }

        return ee()->output->send_ajax_response(array(
            'messageType' => 'success',
            'message' => ''
        ));
    }
}
// EOF

<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Controller\License;

use CP_Controller;

/**
 * License controller
 */
class License extends CP_Controller
{
    /**
     * Early permissions checks
     */
    public function __construct()
    {
        parent::__construct();

        if (! AJAX_REQUEST or
            ee('Request')->method() != 'POST') {
            show_error(lang('unauthorized_access'), 403);
        }
    }

    /**
     * Handles the license check from the EE main server.
     *
     * @return string success or error
     */
    public function handleAccessResponse()
    {
        $licenseResponse = ee()->input->post('licenseStatus');

        // Combine our info into a file we can cache.
        $data = [
            'appVer' => preg_replace('/[^\da-z\.-]/i', '', ee()->input->post('appVer')),
            'license' => preg_replace('/[^a-z0-9]/i', '', ee()->input->post('license')),
            'validLicense' => ($licenseResponse['messageType'] === 'success'),
            'licenseStatus' => preg_replace('/[^a-z0-9_]/i', '', $licenseResponse['messageType']),
            'site_id' => filter_var(ee()->input->post('site_id'), FILTER_VALIDATE_INT),
            'site_url' => filter_var(ee()->input->post('site_url'), FILTER_VALIDATE_URL),
            'addons' => []
        ];

        if (!empty(ee()->input->post('addons')) && is_array(ee()->input->post('addons'))) {
            foreach (ee()->input->post('addons') as $addon) {
                $cleanSlug = preg_replace('/[^\da-z\.-_]/i', '', $addon['slug']);
                $data['addons'][$cleanSlug] = [
                    'slug' => $cleanSlug,
                    'version' => isset($addon['version']) ? preg_replace('/[^\da-z\.-]/i', '', $addon['version']) : '',
                    'status' => $addon['status'],
                    'update' => $addon['update']
                ];
            }
        }

        $data['sha'] = hash('sha256', json_encode($data));

        $encrypted = ee('Encrypt')->encode(json_encode($data), ee()->config->item('session_crypt_key'));
        ee()->cache->file->save('/addons-status', $encrypted . '||s=' . hash('sha256', $encrypted), 0);

        return ee()->output->send_ajax_response(array(
            'messageType' => 'success',
            'message' => ''
        ));
    }

}
// EOF

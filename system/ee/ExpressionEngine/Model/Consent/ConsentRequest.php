<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Model\Consent;

use ExpressionEngine\Service\Model\Model;

/**
 * Consent Request Model
 */
class ConsentRequest extends Model
{
    protected static $_primary_key = 'consent_request_id';
    protected static $_table_name = 'consent_requests';

    protected static $_typed_columns = [
        'consent_request_id' => 'int',
        'consent_request_version_id' => 'int',
        'double_opt_in' => 'boolString',
        'user_created' => 'boolString',
    ];

    protected static $_relationships = [
        'CurrentVersion' => [
            'type' => 'belongsTo',
            'model' => 'ConsentRequestVersion',
            'from_key' => 'consent_request_version_id'
        ],
        'Versions' => [
            'type' => 'hasMany',
            'model' => 'ConsentRequestVersion'
        ],
        'Consents' => [
            'type' => 'hasMany',
            'model' => 'Consent'
        ],
        'Logs' => [
            'type' => 'hasMany',
            'model' => 'ConsentAuditLog'
        ],
    ];

    protected static $_validation_rules = [
        'user_created' => 'enum[y,n]',
        'title' => 'required|maxLength[200]|limitHtml[b,cite,code,del,em,i,ins,markspan,strong,sub,sup]',
        'consent_name' => 'required|unique|maxLength[50]|validateName[user_created]',
        'double_opt_in' => 'enum[y,n]',
    ];

    // protected static $_events = [];

    // Properties
    protected $consent_request_id;
    protected $consent_request_version_id;
    protected $user_created;
    protected $title;
    protected $consent_name;
    protected $double_opt_in;
    protected $retention_period;

    public function validateName($name, $value, $params, $object)
    {
        $user_created = $params[0];

        $pattern = "-a-z0-9_-";

        if ($user_created == 'n') {
            $pattern .= ':';
        }

        if (preg_match("/^([" . $pattern . "])+$/i", (string) $value)) {
            return true;
        }

        return 'alpha_dash';
    }

    public function render()
    {
        if (! $this->CurrentVersion) {
            return '';
        }

        return $this->CurrentVersion->render();
    }

    /**
     * Adds a record to the Consent Audit Log
     *
     * @param string $action The action/log message
     * @return NULL
     */
    public function log($action)
    {
        $log = $this->getModelFacade()->make('ConsentAuditLog');
        $log->ConsentRequest = $this;
        $log->ConsentRequestVersion = $this->CurrentVersion;
        $log->member_id = ee()->session->userdata('member_id');
        $log->ip_address = ee()->input->ip_address();
        $log->user_agent = substr(ee()->input->user_agent(), 0, 120);
        $log->action = $action;
        $log->log_date = ee()->localize->now;
        $log->save();
    }
}

// EOF

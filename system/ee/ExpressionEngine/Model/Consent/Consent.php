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
 * Consent Model
 */
class Consent extends Model
{
    protected static $_primary_key = 'consent_id';
    protected static $_table_name = 'consents';

    protected static $_typed_columns = [
        'consent_id' => 'int',
        'consent_request_id' => 'int',
        'consent_request_version_id' => 'int',
        'member_id' => 'int',
        'consent_given' => 'boolString',
        'expiration_date' => 'timestamp',
        'response_date' => 'timestamp',
    ];

    protected static $_relationships = [
        'ConsentRequest' => [
            'type' => 'belongsTo'
        ],
        'ConsentRequestVersion' => [
            'type' => 'belongsTo'
        ],
        'Member' => [
            'type' => 'belongsTo'
        ]
    ];

    protected static $_events = [
        'afterSave',
    ];

    protected static $_validation_rules = [
        'consent_id' => 'required',
        'consent_request_id' => 'required',
        'consent_request_version_id' => 'required',
        'member_id' => 'required',
        'consent_given' => 'enum[y,n]',
    ];

    // protected static $_events = [];

    // Properties
    protected $consent_id;
    protected $consent_request_id;
    protected $consent_request_version_id;
    protected $member_id;
    protected $request_copy;
    protected $request_format;
    protected $consent_given;
    protected $consent_given_via;
    protected $expiration_date;
    protected $response_date;

    /**
     * Is this consent expired?
     *
     * @return bool TRUE if it is, FALSE otherwise.
     */
    public function isExpired()
    {
        $now = ee()->localize->now;

        if ($this->expiration_date && $this->expiration_date > $now) {
            return true;
        }

        return false;
    }

    /**
     * Checks to see if the request version matches, and that it hasn't been edited
     * since the member responded, and that the consent isn't expired, and that
     * consent was granted.
     *
     * @return bool TRUE if it is, FALSE otherwise.
     */
    public function isGranted()
    {
        $request = $this->ConsentRequest->CurrentVersion;

        if (! $request->getId()) {
            return false;
        }

        // If the consent is not for the current version of the request, then the consent
        // is void. The request has changed.
        if ($this->ConsentRequestVersion->getId() != $request->getId()) {
            return false;
        }

        // If the current request version was edited after the consent was granted,
        // then the consent is void. The request has changed.
        if ($request->create_date > $this->response_date) {
            return false;
        }

        // If the consent has expired it's no longer granted
        if ($this->isExpired()) {
            return false;
        }

        return $this->getProperty('consent_given');
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
        $log->ConsentRequest = $this->ConsentRequest;
        $log->ConsentRequestVersion = $this->ConsentRequestVersion;
        $log->Member = $this->Member;
        $anonymize = explode('|', ee()->config->item('anonymize_consent_logs'));
        if (!empty($anonymize) && in_array('ip_address', $anonymize)) {
            $log->ip_address = ee('IpAddress')->anonymize(ee()->input->ip_address());
        } else {
            $log->ip_address = ee()->input->ip_address();
        }
        $log->user_agent = substr(ee()->input->user_agent(), 0, 120);
        $log->action = $action;
        $log->log_date = ee()->localize->now;
        $log->save();
    }

    public function onAfterSave()
    {
        // make sure date fields are objects, or we'll get fatal errors
        // when isGranted() is called on the same request after a save()
        if (is_int($this->response_date)) {
            $this->set(['response_date' => new \DateTime("@{$this->response_date}")]);
        }
    }
}

// EOF

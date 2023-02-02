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
 * Consent Audit Log Model
 */
class ConsentAuditLog extends Model
{
    protected static $_primary_key = 'consent_audit_id';
    protected static $_table_name = 'consent_audit_log';

    protected static $_typed_columns = [
        'consent_audit_id' => 'int',
        'consent_request_id' => 'int',
        'consent_request_version_id' => 'int',
        'member_id' => 'int',
        'log_date' => 'timestamp',
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

    protected static $_validation_rules = [
        'consent_audit_id' => 'required',
        'consent_request_id' => 'required',
        'consent_request_version_id' => 'required',
        'member_id' => 'required',
        'action' => 'required',
        'log_date' => 'required',
    ];

    // protected static $_events = [];

    // Properties
    protected $consent_audit_id;
    protected $consent_request_id;
    protected $consent_request_version_id;
    protected $member_id;
    protected $ip_address;
    protected $user_agent;
    protected $action;
    protected $log_date;
}

// EOF

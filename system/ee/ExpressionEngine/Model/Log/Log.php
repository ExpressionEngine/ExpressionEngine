<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Model\Log;

use ExpressionEngine\Service\Model\Model;

/**
 * Log Model
 */
class Log extends Model
{
    protected static $_primary_key = 'log_id';
    protected static $_table_name = 'logs';

    protected static $_relationships = array(
        'Site' => array(
            'type' => 'belongsTo'
        ),
        'Member' => array(
            'type' => 'belongsTo'
        )
    );

    protected static $_typed_columns = array(
        'context' => 'json',
        'extra' => 'json',
        'viewed' => 'boolString'
    );

    protected static $_events = array(
        'beforeInsert'
    );

    protected static $_validation_rules = array(
        'level' => 'required|int',
        'channel' => 'required',
        'message' => 'required'
    );

    protected $log_id;
    protected $site_id;
    protected $member_id;
    protected $log_date;
    protected $level;
    protected $channel;
    protected $message;
    protected $context;
    protected $extra;
    protected $ip_address;
    protected $viewed;

    public function onBeforeInsert()
    {
        $this->setRawProperty('site_id', ee()->config->item('site_id'));
        if (isset(ee()->session)) {
            $this->setRawProperty('member_id', ee()->session->userdata('member_id'));
        }
        $this->setRawProperty('ip_address', ee()->input->ip_address());
    }
}

// EOF

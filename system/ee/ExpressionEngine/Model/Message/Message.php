<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Model\Message;

use ExpressionEngine\Service\Model\Model;

/**
 * Private message
 */
class Message extends Model
{
    protected static $_primary_key = 'message_id';
    protected static $_table_name = 'message_data';

    protected static $_relationships = [
        'Member' => [
            'type' => 'belongsTo',
            'from_key' => 'sender_id'
        ],
        'Recipients' => [
            'type' => 'hasAndBelongsToMany',
            'model' => 'Member',
            'pivot' => [
                'table' => 'message_copies',
                'left' => 'message_id',
                'right' => 'recipient_id'
            ]
        ]
    ];

    protected static $_typed_columns = [
        'message_id' => 'int',
        'sender_id' => 'int',
        'message_date' => 'timestamp',
        'message_subject' => 'string',
        'message_body' => 'string',
        'message_tracking' => 'boolString',
        'message_attachments' => 'boolString',
        'message_recipients' => 'string',
        'message_cc' => 'string',
        'message_hide_cc' => 'boolString',
        'message_sent_copy' => 'boolString',
        'total_recipients' => 'int',
        'message_status' => 'string'
    ];

    protected $message_id;
    protected $sender_id;
    protected $message_date;
    protected $message_subject;
    protected $message_body;
    protected $message_tracking;
    protected $message_attachments;
    protected $message_recipients;
    protected $message_cc;
    protected $message_hide_cc;
    protected $message_sent_copy;
    protected $total_recipients;
    protected $message_status;
}
// END CLASS

// EOF

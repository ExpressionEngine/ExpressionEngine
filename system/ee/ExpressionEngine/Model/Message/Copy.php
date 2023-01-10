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
 * Message Copy
 *
 * Represents delivery of a message to a single member
 */
class Copy extends Model
{
    protected static $_primary_key = 'copy_id';
    protected static $_table_name = 'message_copies';

    protected static $_relationships = [
        'Message' => [
            'type' => 'belongsTo',
            'model' => 'Message'
        ],
        'Sender' => [
            'type' => 'belongsTo',
            'model' => 'Member',
            'from_key' => 'sender_id'
        ],
        'Recipient' => [
            'type' => 'belongsTo',
            'model' => 'Member',
            'from_key' => 'recipient_id'
        ]
    ];

    protected static $_typed_columns = [
        'copy_id' => 'int',
        'message_id' => 'int',
        'sender_id' => 'int',
        'recipient_id' => 'int',
        'message_received' => 'boolString',
        'message_read' => 'boolString',
        'message_time_read' => 'timestamp',
        'attachment_downloaded' => 'boolString',
        'message_authcode' => 'string',
        'message_deleted' => 'boolString',
        'message_status' => 'string'
    ];

    protected $copy_id;
    protected $message_id;
    protected $sender_id;
    protected $recipient_id;
    protected $message_received;
    protected $message_read;
    protected $message_time_read;
    protected $attachment_downloaded;
    protected $message_folder;
    protected $message_authcode;
    protected $message_deleted;
    protected $message_status;
}
// END CLASS

// EOF

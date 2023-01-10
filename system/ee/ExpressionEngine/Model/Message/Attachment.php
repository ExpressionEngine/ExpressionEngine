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
 * Attachment
 *
 * Attachments sent via private messages
 */
class Attachment extends Model
{
    protected static $_primary_key = 'attachment_id';
    protected static $_table_name = 'message_attachments';

    protected static $_relationships = [
        'Member' => [
            'type' => 'belongsTo',
            'from_key' => 'sender_id'
        ],
        'Message' => [
            'type' => 'belongsTo'
        ]
    ];

    protected static $_typed_columns = [
        'attachment_id' => 'int',
        'sender_id' => 'int',
        'message_id' => 'int',
        'attachment_name' => 'string',
        'attachment_hash' => 'string',
        'attachment_extension' => 'string',
        'attachment_location' => 'string',
        'attachment_date' => 'timestamp',
        'attachment_size' => 'int',
        'is_temp' => 'boolString'
    ];

    protected $attachment_id;
    protected $sender_id;
    protected $message_id;
    protected $attachment_name;
    protected $attachment_hash;
    protected $attachment_extension;
    protected $attachment_location;
    protected $attachment_date;
    protected $attachment_size;
    protected $is_temp;
}
// END CLASS

// EOF

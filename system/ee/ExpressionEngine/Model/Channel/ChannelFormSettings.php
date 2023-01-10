<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Model\Channel;

use ExpressionEngine\Service\Model\Model;

/**
 * Channel Form Settings Model
 */
class ChannelFormSettings extends Model
{
    protected static $_primary_key = 'channel_form_settings_id';
    protected static $_table_name = 'channel_form_settings';

    protected static $_hook_id = 'channel_form_settings';

    protected static $_relationships = array(
        'Channel' => array(
            'type' => 'belongsTo'
        )
    );

    protected $channel_form_settings_id;
    protected $site_id;
    protected $channel_id;
    protected $default_status;
    protected $allow_guest_posts;
    protected $default_author;
}

// EOF

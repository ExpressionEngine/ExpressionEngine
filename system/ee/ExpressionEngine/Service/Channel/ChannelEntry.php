<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Service\Channel;

/**
 * ChannelEntry Service
 */
class ChannelEntry
{
    /**
     * Enables or disabled entry vesioning for all entries in channel 
     *
     * @param Int $channelId
     * @param enum[y, n] $enabled
     * @return void
     */
    public function updateVersioning($channelId = null, $enabled = 'y')
    {
        if (empty($channelId)) {
            return false;
        }
        if (!in_array($enabled, ['y', 'n'])) {
            return false;
        }
        ee('db')->where('channel_id', $channelId)->update('channel_titles', ['versioning_enabled' => $enabled]);
    }
}
// EOF

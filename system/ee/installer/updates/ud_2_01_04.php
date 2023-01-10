<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Updater\Version_2_1_4;

/**
 * Update
 */
class Updater
{
    public $version_suffix = '';

    public function do_update()
    {
        ee()->smartforge->drop_key('channel_data', 'weblog_id');

        ee()->smartforge->add_key('channel_data', 'channel_id');

        ee()->smartforge->drop_key('channel_titles', 'weblog_id');

        ee()->smartforge->add_key('channel_titles', 'channel_id');

        return true;
    }
}
/* END CLASS */

// EOF

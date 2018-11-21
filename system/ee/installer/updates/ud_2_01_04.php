<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2018, EllisLab, Inc. (https://ellislab.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

/**
 * Update
 */
class Updater {

	var $version_suffix = '';

	function do_update()
	{

		ee()->smartforge->drop_key('channel_data', 'weblog_id');

		ee()->smartforge->add_key('channel_data', 'channel_id');

		ee()->smartforge->drop_key('channel_titles', 'weblog_id');

		ee()->smartforge->add_key('channel_titles', 'channel_id');

		return TRUE;
	}
}
/* END CLASS */

// EOF

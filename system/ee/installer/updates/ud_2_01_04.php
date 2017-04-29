<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2017, EllisLab, Inc. (https://ellislab.com)
 * @license   https://expressionengine.com/license
 */

/**
 * ExpressionEngine Update Class
 *
 * @package     ExpressionEngine
 * @subpackage  Core
 * @category    Core
 * @author      EllisLab Dev Team
 * @link        https://ellislab.com
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

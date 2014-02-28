<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * ExpressionEngine - by EllisLab
 *
 * @package     ExpressionEngine
 * @author      EllisLab Dev Team
 * @copyright   Copyright (c) 2003 - 2014, EllisLab, Inc.
 * @license     http://ellislab.com/expressionengine/user-guide/license.html
 * @link        http://ellislab.com
 * @since       Version 2.0
 * @filesource
 */

// ------------------------------------------------------------------------

/**
 * ExpressionEngine Update Class
 *
 * @package     ExpressionEngine
 * @subpackage  Core
 * @category    Core
 * @author      EllisLab Dev Team
 * @link        http://ellislab.com
 */
class Updater {

	var $version_suffix = '';

	function Updater()
	{
		$this->EE =& get_instance();
	}

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

/* End of file ud_214.php */
/* Location: ./system/expressionengine/installer/updates/ud_214.php */
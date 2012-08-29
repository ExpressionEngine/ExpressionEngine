<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * ExpressionEngine - by EllisLab
 *
 * @package     ExpressionEngine
 * @author      EllisLab Dev Team
 * @copyright   Copyright (c) 2003 - 2012, EllisLab, Inc.
 * @license     http://expressionengine.com/user_guide/license.html
 * @link        http://expressionengine.com
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
 * @link        http://expressionengine.com
 */
class Updater {

	var $version_suffix = '';

	function Updater()
	{
		$this->EE =& get_instance();
	}

	function do_update() 
	{ 

		$this->EE->migrate->drop_index('channel_data', 'weblog_id');

		$this->EE->migrate->create_index('channel_data', 'channel_id');

		$this->EE->migrate->drop_index('channel_titles', 'weblog_id');

		$this->EE->migrate->create_index('channel_titles', 'channel_id');
		
		return TRUE;
	}
}   
/* END CLASS */

/* End of file ud_214.php */
/* Location: ./system/expressionengine/installer/updates/ud_214.php */
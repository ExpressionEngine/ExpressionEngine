<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2016, EllisLab, Inc.
 * @license		https://expressionengine.com/license
 * @link		https://ellislab.com
 * @since		Version 3.4.5
 * @filesource
 */

// ------------------------------------------------------------------------

/**
 * ExpressionEngine Mock Session Class
 *
 * @package		ExpressionEngine
 * @subpackage	Installer
 * @category	Session
 * @author		EllisLab Dev Team
 * @link		https://ellislab.com
 */

class Installer_Session {

	public function cache($class, $key, $default = FALSE)
	{
		return FALSE;
	}

	public function set_cache($class, $key, $val)
	{
		return $this;
	}

	public function userdata($which, $default = FALSE)
	{
		return FALSE;
	}
}
// END CLASS

// EOF

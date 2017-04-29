<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2017, EllisLab, Inc. (https://ellislab.com)
 * @license   https://expressionengine.com/license
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

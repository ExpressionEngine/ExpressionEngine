<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2016, EllisLab, Inc.
 * @license		https://expressionengine.com/license
 * @link		https://ellislab.com
 * @since		Version 2.0
 * @filesource
 */

// ------------------------------------------------------------------------

/**
 * ExpressionEngine Layout Class
 *
 * @package		ExpressionEngine
 * @subpackage	Control Panel
 * @category	Control Panel
 * @author		EllisLab Dev Team
 * @link		https://ellislab.com
 */

require_once(EE_APPPATH.'/libraries/Extensions.php');

class Installer_Extensions extends EE_Extensions {

	/**
	 * Installer doesn't allow any extensions to run, to
	 * avoid running third-party code in this context
	 **/
	public function call($which)
	{
		return;
	}
}
// END CLASS

// EOF

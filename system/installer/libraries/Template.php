<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2013, EllisLab, Inc.
 * @license		http://ellislab.com/expressionengine/user-guide/license.html
 * @link		http://ellislab.com
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
 * @link		http://ellislab.com
 */
define('RD', '}');
define('LD', '{');

require_once(EE_APPPATH.'/libraries/Template'.EXT);

class Installer_Template extends EE_Template {
	// Nothing to see here.

	public function __construct()
	{
		// Make a local reference to the ExpressionEngine super object
		$this->EE =& get_instance();

		$this->debugging = TRUE;
		$this->start_microtime = microtime(TRUE);
	}
}
// END CLASS

/* End of file Template.php */
/* Location: ./system/expressionengine/installer/libraries/Template.php */

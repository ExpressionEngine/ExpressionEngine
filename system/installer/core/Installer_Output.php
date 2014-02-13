<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2014, EllisLab, Inc.
 * @license		http://ellislab.com/expressionengine/user-guide/license.html
 * @link		http://ellislab.com
 * @since		Version 2.0
 * @filesource
 */

// ------------------------------------------------------------------------


// We've already extended this library in the other app,
// so instead of maintaining the code in both, we'll just
// do an include and create a small meta class that
// CI can instantiate using the proper prefix.

require_once(EE_APPPATH.'core/EE_Output'.EXT);


// ------------------------------------------------------------------------

/**
 * ExpressionEngine Exceptions Class
 *
 * @package		ExpressionEngine
 * @subpackage	Core
 * @category	Core
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */

class Installer_Output Extends EE_Output {
	// Yes, it's empty!
}

/* End of file Installer_Output.php */
/* Location: ./system/expressionengine/installer/libraries/Installer_Output.php */
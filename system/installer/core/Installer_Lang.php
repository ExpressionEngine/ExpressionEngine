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

require_once(EE_APPPATH.'core/EE_Lang'.EXT);


// ------------------------------------------------------------------------

/**
 * ExpressionEngine Language Class
 *
 * @package		ExpressionEngine
 * @subpackage	Core
 * @category	Core
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */

class Installer_Lang Extends EE_Lang {

	/**
	 *   Fetch a specific line of text
	 *
	 * @access	public
	 * @param	string
	 * @param	string
	 * @return	string
	 */
	public function line($which = '', $label = '')
	{
		$line = parent::line($which, $label);

		if (IS_CORE)
		{
			$line = str_replace('ExpressionEngine', 'ExpressionEngine Core', $line);
		}

		return $line;
	}
}


/* End of file Installer_Lang.php */
/* Location: ./system/expressionengine/installer/libraries/Installer_Lang.php */
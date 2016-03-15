<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * ExpressionEngine - by EllisLab
 *
 * @package     ExpressionEngine
 * @author      EllisLab Dev Team
 * @copyright   Copyright (c) 2003 - 2016, EllisLab, Inc.
 * @license     https://expressionengine.com/license
 * @link        https://ellislab.com
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
 * @link        https://ellislab.com
 */
class Updater {

	var $version_suffix = '';

	/**
	 * Constructor
	 */
	public function __construct()
	{

	}

	// --------------------------------------------------------------------

	/**
	 * Do Update
	 *
	 * @return TRUE
	 */
	public function do_update()
    {
		return TRUE;
    }
}
/* END CLASS */

// EOF

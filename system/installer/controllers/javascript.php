<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
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

/**
 * ExpressionEngine Installation and Update Javascript Class
 *
 * @package		ExpressionEngine
 * @subpackage	Core
 * @category	Core
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */
class Javascript {

	/**
	 * Constructor
	 */
	function __construct()
	{
		$file = EE_APPPATH.'javascript/compressed/jquery/jquery.js';

		$contents = file_get_contents($file);

		header('Content-Length: '.strlen($contents));
		header("Content-type: text/javascript");
		exit($contents);
	}

	// --------------------------------------------------------------------

}

// END Javascript class


/* End of file javascript.php */
/* Location: ./system/expressionengine/installer/controllers/javascript.php */
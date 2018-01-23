<?php
/**
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2018, EllisLab, Inc. (https://ellislab.com)
 * @license   https://expressionengine.com/license
 */

/**
 * Installation and Update Javascript
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

}
// END CLASS

// EOF

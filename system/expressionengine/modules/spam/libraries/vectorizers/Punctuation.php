<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2014, EllisLab, Inc.
 * @license		http://ellislab.com/expressionengine/user-guide/license.html
 * @link		http://ellislab.com
 * @since		Version 3.0
 * @filesource
 */

// ------------------------------------------------------------------------

/**
 * ExpressionEngine Spam Module
 *
 * @package		ExpressionEngine
 * @subpackage	Modules
 * @category	Modules
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */

class Punctuation {

	/**
	 * Calculates the ratio of punctuation to non-punctuation 
	 * 
	 * @param string $source The source text 
	 * @access public
	 * @return float The calculated ratio
	 */
	public static function vectorize($source)
	{
		$punctuation = preg_match_all('/[!-~]/u', $source);
		$characters  = preg_match_all('/[^!-~]/u', $source);
		if ($characters !== 0)
		{
			$ratio = $whitespace / $characters;
		}
		else
		{
			$ratio = 1;
		}
    	return $ratio;
	}

}

?>
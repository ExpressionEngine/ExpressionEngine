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

class Entropy {

	/**
	 * Estimates the entropy of a string by calculating the compression ratio.
	 * 
	 * @param string $source The source text 
	 * @access public
	 * @return float estimated entropy
	 */
	public static function vectorize($source)
	{
    	$length = mb_strlen($source);
    	$compressed = gzcompress($source);
    	$compressed_length = mb_strlen($compressed) - 8; // 8 bytes of gzip overhead
    	$ratio = $compressed_length / $length;
    	return $ratio;
	}

}

?>
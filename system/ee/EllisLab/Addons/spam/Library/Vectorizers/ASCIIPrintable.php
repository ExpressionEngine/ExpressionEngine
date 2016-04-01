<?php

namespace EllisLab\Addons\Spam\Library\Vectorizers;

use \EllisLab\Addons\spam\Library\Vectorizer;

/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2016, EllisLab, Inc.
 * @license		https://expressionengine.com/license
 * @link		https://ellislab.com
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
 * @link		https://ellislab.com
 */

class ASCIIPrintable implements Vectorizer {

	/**
	 * Calculates the ratio of non-ASCII printable characters
	 *
	 * @param string $source The source text
	 * @access public
	 * @return float The calculated ratio
	 */
	public function vectorize($source)
	{
		$non_ascii  = preg_match_all('/[^\x20-\x7E]/u', $source);
		$length = mb_strlen($source);
		if ($length !== 0)
		{
			$ratio = $non_ascii / $length;
		}
		else
		{
			$ratio = 1;
		}
		return $ratio;
	}

}

// EOF

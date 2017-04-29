<?php

namespace EllisLab\Addons\Spam\Library\Vectorizers;

use \EllisLab\Addons\spam\Library\Vectorizer;

/**
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2017, EllisLab, Inc. (https://ellislab.com)
 * @license   https://expressionengine.com/license
 */

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

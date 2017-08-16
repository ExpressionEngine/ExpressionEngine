<?php
/**
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2017, EllisLab, Inc. (https://ellislab.com)
 * @license   https://expressionengine.com/license
 */

namespace EllisLab\Addons\Spam\Library\Vectorizers;

use \EllisLab\Addons\spam\Library\Vectorizer;

/**
 * Spam Module ASCIIPrintable Vectorizer
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
		$non_ascii  = preg_match_all('/[^\x20-\x7E]/u', $source, $matches);
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

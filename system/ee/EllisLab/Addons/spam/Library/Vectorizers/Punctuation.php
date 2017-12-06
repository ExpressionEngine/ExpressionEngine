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
 * Spam Module Punctuation Vectorizer
 */
class Punctuation implements Vectorizer {

	/**
	 * Calculates the ratio of punctuation to non-punctuation
	 *
	 * @param string $source The source text
	 * @access public
	 * @return float The calculated ratio
	 */
	public function vectorize($source)
	{
		$punctuation = preg_match_all('/[!-~]/u', $source, $matches);
		$characters  = mb_strlen($source);
		if ($characters !== 0)
		{
			$ratio = $punctuation / $characters;
		}
		else
		{
			$ratio = 1;
		}
		return $ratio;
	}

}

// EOF

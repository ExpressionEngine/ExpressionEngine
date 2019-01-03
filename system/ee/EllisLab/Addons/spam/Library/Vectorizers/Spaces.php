<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2019, EllisLab Corp. (https://ellislab.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace EllisLab\Addons\Spam\Library\Vectorizers;

use \EllisLab\Addons\spam\Library\Vectorizer;

/**
 * Spam Module Spaces Vectorizer
 */
class Spaces implements Vectorizer {

	/**
	 * Calculates the ratio of whitespace to non-whitespace
	 *
	 * @param string $source The source text
	 * @access public
	 * @return float The calculated ratio
	 */
	public function vectorize($source)
	{
		$whitespace = preg_match_all('/\s/u', $source, $matches);
		$characters  = mb_strlen($source);
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

// EOF

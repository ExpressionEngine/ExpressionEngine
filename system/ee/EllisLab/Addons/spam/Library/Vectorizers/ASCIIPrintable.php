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

		ee()->load->helper('multibyte');

		$non_ascii  = preg_match_all('/[^\x20-\x7E]/u', $source, $matches);

		$length = ee_mb_strlen($source);

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

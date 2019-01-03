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
 * Spam Module Entropy Vectorizer
 */
class Entropy implements Vectorizer {

	/**
	 * Estimates the entropy of a string by calculating the compression ratio.
	 *
	 * @param string $source The source text
	 * @access public
	 * @return float estimated entropy
	 */
	public function vectorize($source)
	{
		$length = mb_strlen($source);

		if ($length > 0)
		{
			$compressed = gzcompress($source);
			$compressed_length = mb_strlen($compressed) - 8; // 8 bytes of gzip overhead
			$ratio = $compressed_length / $length;
		}
		else
		{
			$ratio = 0;
		}

		return $ratio;
	}

}

// EOF

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

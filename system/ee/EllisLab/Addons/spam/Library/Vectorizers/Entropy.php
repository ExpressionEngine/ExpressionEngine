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

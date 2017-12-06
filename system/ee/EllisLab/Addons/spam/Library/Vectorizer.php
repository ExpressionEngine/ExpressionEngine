<?php
/**
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2017, EllisLab, Inc. (https://ellislab.com)
 * @license   https://expressionengine.com/license
 */

namespace EllisLab\Addons\Spam\Library;

/**
 * Spam Document Vectorizer Interface
 */
interface Vectorizer {

	/**
	 * Return an array of floats computed from the source string
	 *
	 * @param string $source
	 * @return float
	 */
	public function vectorize($source);

}

// EOF

<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2019, EllisLab Corp. (https://ellislab.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
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

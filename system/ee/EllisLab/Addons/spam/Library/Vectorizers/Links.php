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

class Links implements Vectorizer {

	/**
	 * Calculates the amount of links in the source
	 *
	 * @param string $source The source text
	 * @access public
	 * @return float The calculated ratio
	 */
	public function vectorize($source)
	{
		$pattern = '#[-a-zA-Z0-9@:%_\+.~\#?&//=]{2,256}\.[a-z]{2,4}\b(\/[-a-zA-Z0-9@:%_\+.~\#?&//=]*)?#si';
		return preg_match_all($pattern, $source);
	}

}

// EOF

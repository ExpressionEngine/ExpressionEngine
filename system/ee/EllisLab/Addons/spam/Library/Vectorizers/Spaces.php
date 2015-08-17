<?php

namespace EllisLab\Addons\Spam\Library\Vectorizers;

use \EllisLab\Addons\spam\Library\Vectorizer;

/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2014, EllisLab, Inc.
 * @license		http://ellislab.com/expressionengine/user-guide/license.html
 * @link		http://ellislab.com
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
 * @link		http://ellislab.com
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
		$whitespace = preg_match_all('/\s/u', $source);
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

/* End of file Spaces.php */
/* Location: ./system/expressionengine/modules/spam/libraries/vectorizers/Spaces.php */

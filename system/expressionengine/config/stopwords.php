<?php
/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		ExpressionEngine Dev Team
 * @copyright	Copyright (c) 2003 - 2011, EllisLab, Inc.
 * @license		http://expressionengine.com/user_guide/license.html
 * @link		http://expressionengine.com
 * @since		Version 2.0
 * @filesource
 */
 
// ------------------------------------------------------------------------

/**
 * ExpressionEngine Stop Words
 *
 * This file contains an array of words that the search functions in EE will
 * ignore in order to a) reduce load, and b) generate better results.
 *
 * @package		ExpressionEngine
 * @subpackage	Config
 * @category	Config
 * @author		ExpressionEngine Dev Team
 * @link		http://expressionengine.com
 */

$ignore = array(
					'a',
					'about',
					'an',
					'and',
					'are',
					'as',
					'at',
					'be',
					'by',
					'but',
					'from',
					'how',
					'i',
					'in',
					'is',
					'it',
					'of',
					'on',
					'or',
					'that',
					'the',
					'this',
					'to',
					'was',
					'we',
					'what',
					'when',
					'where',
					'which',
					'with'	// no comma after last item
			);

/* End of file stopwords.php */
/* Location: ./system/expressionengine/config/stopwords.php */
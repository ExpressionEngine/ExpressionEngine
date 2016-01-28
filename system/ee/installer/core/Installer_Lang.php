<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2016, EllisLab, Inc.
 * @license		https://expressionengine.com/license
 * @link		https://ellislab.com
 * @since		Version 2.0
 * @filesource
 */

// ------------------------------------------------------------------------

/**
 * ExpressionEngine Language Class
 *
 * @package		ExpressionEngine
 * @subpackage	Core
 * @category	Core
 * @author		EllisLab Dev Team
 * @link		https://ellislab.com
 */

class Installer_Lang Extends EE_Lang {

	/**
	 *   Fetch a specific line of text
	 *
	 * @access	public
	 * @param	string
	 * @param	string
	 * @return	string
	 */
	public function line($which = '', $label = '')
	{
		$line = parent::line($which, $label);

		if (IS_CORE)
		{
			$line = str_replace('ExpressionEngine', 'ExpressionEngine Core', $line);
		}

		return $line;
	}

	/**
	 * Forces the current language to English
	 * @return string The idiom to load
	 */
	protected function getIdiom()
	{
		return 'english';
	}
}

// EOF

<?php
/**
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2018, EllisLab, Inc. (https://ellislab.com)
 * @license   https://expressionengine.com/license
 */

/**
 * Installer Language
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

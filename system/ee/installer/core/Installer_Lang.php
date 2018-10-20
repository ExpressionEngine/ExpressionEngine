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
	 * Forces the current language to English
	 * @return string The idiom to load
	 */
	protected function getIdiom()
	{
		return 'english';
	}
}

// EOF

<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2019, EllisLab Corp. (https://ellislab.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
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

<?php

namespace EllisLab\ExpressionEngine\Service\Formatter\Formats;

use EllisLab\ExpressionEngine\Service\Formatter\Formatter;

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
 * ExpressionEngine Formatter\Text Class
 *
 * @package		ExpressionEngine
 * @category	Service
 * @author		EllisLab Dev Team
 * @link		https://ellislab.com
 */
class Text extends Formatter {

	/**
	 * Escapes a string for use in an HTML attribute
	 *
	 * @return self This returns a reference to itself
	 */
	public function attributeEscape()
	{
		$this->content = htmlspecialchars($this->content, ENT_QUOTES, 'UTF-8');
		return $this;
	}
}

// EOF

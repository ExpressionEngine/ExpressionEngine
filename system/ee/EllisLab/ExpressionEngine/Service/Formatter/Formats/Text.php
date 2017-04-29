<?php
/**
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2017, EllisLab, Inc. (https://ellislab.com)
 * @license   https://expressionengine.com/license
 */

namespace EllisLab\ExpressionEngine\Service\Formatter\Formats;

use EllisLab\ExpressionEngine\Service\Formatter\Formatter;

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

	/**
	 * Converts all applicable characters to HTML entities
	 *
	 * @return self This returns a reference to itself
	 */
	public function convertToEntities()
	{
		$this->content = htmlentities($this->content, ENT_QUOTES, 'UTF-8');
		return $this;
	}
}

// EOF

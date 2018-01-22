<?php
/**
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2018, EllisLab, Inc. (https://ellislab.com)
 * @license   https://expressionengine.com/license
 */

namespace EllisLab\ExpressionEngine\FreeVersion\Spam;

use EllisLab\ExpressionEngine\Protocol\Spam\Spam as SpamProtocol;

/**
 * Free Version Spam Class
 *
 * Prevents errors in the free version, which doesn't come with the Spam module
 */
class Spam implements SpamProtocol {

	/**
	 * Returns true if the string is classified as spam
	 *
	 * @param string $source Text to classify
	 * @return bool Is Spam?
	 */
	public function isSpam($source)
	{
		return FALSE;
	}

	/**
	 * Moderate Spam
	 *
	 * @see EllisLab\ExpressionEngine\Protocol\Spam\Spam
	 */
	public function moderate($content_type, $entity, $document, $optional_data)
	{
		// void
	}
}

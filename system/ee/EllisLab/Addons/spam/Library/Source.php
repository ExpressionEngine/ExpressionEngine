<?php
/**
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2017, EllisLab, Inc. (https://ellislab.com)
 * @license   https://expressionengine.com/license
 */

namespace EllisLab\Addons\Spam\Library;

/**
 * ExpressionEngine Spam Module Source class. We use the Source class
 * instead of plain text in the Spam Module for future proofing. This allows us
 * to attach extra information to each piece of text which can be used for
 * Naive Bayes.
 */
class Source {

	private $text;

	public function __construct($text)
	{
		$this->text = $text;
	}

	public function __toString()
	{
		return $this->text;
	}

}

// EOF

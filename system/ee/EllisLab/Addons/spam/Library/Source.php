<?php

namespace EllisLab\Addons\Spam\Library;

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
 * ExpressionEngine Spam Module Source class. We use the Source class
 * instead of plain text in the Spam Module for future proofing. This allows us
 * to attach extra information to each piece of text which can be used for
 * Naive Bayes.
 *
 * @package		ExpressionEngine
 * @subpackage	Modules
 * @category	Modules
 * @author		EllisLab Dev Team
 * @link		https://ellislab.com
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

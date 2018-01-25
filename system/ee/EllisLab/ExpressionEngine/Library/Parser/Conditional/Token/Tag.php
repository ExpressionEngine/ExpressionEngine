<?php
/**
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2018, EllisLab, Inc. (https://ellislab.com)
 * @license   https://expressionengine.com/license
 */

namespace EllisLab\ExpressionEngine\Library\Parser\Conditional\Token;

/**
 * Tag Token
 */
class Tag extends Token {

	public function __construct($lexeme)
	{
		parent::__construct('TAG', $lexeme);
	}

	public function canEvaluate()
	{
		return FALSE;
	}
}

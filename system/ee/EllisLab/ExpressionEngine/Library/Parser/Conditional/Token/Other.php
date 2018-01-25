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
 * Other Token
 */
class Other extends Token {

	public function __construct($lexeme)
	{
		parent::__construct('MISC', $lexeme);

		// always encode misc
		$this->value = str_replace(
			array('{', '}',),
			array('&#123;', '&#125;'),
			$lexeme
		);
	}

	public function canEvaluate()
	{
		return FALSE;
	}
}

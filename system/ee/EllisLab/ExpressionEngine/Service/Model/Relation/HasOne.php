<?php
/**
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2018, EllisLab, Inc. (https://ellislab.com)
 * @license   https://expressionengine.com/license
 */

namespace EllisLab\ExpressionEngine\Service\Model\Relation;

use EllisLab\ExpressionEngine\Service\Model\Model;
use EllisLab\ExpressionEngine\Service\Model\Association\ToOne;

/**
 * HasOne Relation
 */
class HasOne extends HasOneOrMany {

	/**
	 *
	 */
	public function createAssociation()
	{
		return new ToOne($this);
	}
}

// EOF

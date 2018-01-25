<?php
/**
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2018, EllisLab, Inc. (https://ellislab.com)
 * @license   https://expressionengine.com/license
 */

namespace EllisLab\ExpressionEngine\Service\Model\Column\Serialized;

use EllisLab\ExpressionEngine\Service\Model\Column\SerializedType;

/**
 * Model Service Base64 Encoded Typed Column that defaults to an
 * empty array.
 */
class Base64Array extends Base64 {

	/**
	 * Same as base64, but with an array as the default data
	 */
	protected $data = array();

}

// EOF

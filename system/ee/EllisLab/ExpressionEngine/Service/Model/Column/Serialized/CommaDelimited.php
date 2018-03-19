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
 * Model Service Comma-Delimited Typed Column
 */
class CommaDelimited extends SerializedType {

	protected $data = array();

	/**
	 * Called when the column is fetched from db
	 */
	public static function unserialize($db_data)
	{
		return array_filter(explode(',', $db_data));
	}

	/**
	 * Called before the column is written to the db
	 */
	public static function serialize($data)
	{
		return implode(',', $data);
	}

}

// EOF

<?php

namespace EllisLab\ExpressionEngine\Service\Model\Column\Serialized;

use EllisLab\ExpressionEngine\Service\Model\Column\SerializedType;

/**
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2017, EllisLab, Inc. (https://ellislab.com)
 * @license   https://expressionengine.com/license
 */

// ------------------------------------------------------------------------

/**
 * ExpressionEngine Model Base64 Encoded and Serialized Typed Column
 *
 * @package		ExpressionEngine
 * @subpackage	Model
 * @category	Service
 * @author		EllisLab Dev Team
 * @link		https://ellislab.com
 */
class Base64Native extends SerializedType {

	protected $data = array();

	/**
	 * Called when the column is fetched from db
	 */
	public static function unserialize($db_data)
	{
		return strlen($db_data) ? unserialize(base64_decode($db_data)) : array();
	}

	/**
	 * Called before the column is written to the db
	 */
	public static function serialize($data)
	{
		return base64_encode(serialize($data));
	}

}

// EOF

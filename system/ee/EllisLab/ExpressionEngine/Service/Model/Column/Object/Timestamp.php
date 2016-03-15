<?php

namespace EllisLab\ExpressionEngine\Service\Model\Column\Object;

use DateTime;
use EllisLab\ExpressionEngine\Service\Model\Column\SerializedType;

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
 * ExpressionEngine Model Base64 Encoded Typed Column
 *
 * @package		ExpressionEngine
 * @subpackage	Model
 * @category	Service
 * @author		EllisLab Dev Team
 * @link		https://ellislab.com
 */
class Timestamp extends SerializedType {

	/**
	 * Called when the column is fetched from db
	 */
	public static function unserialize($db_data)
	{
		if ($db_data !== NULL)
		{
			return new DateTime("@{$db_data}");
		}
	}

	/**
	 * Called before the column is written to the db
	 */
	public static function serialize($data)
	{
		return is_object($data) ? $data->getTimestamp() : intval($data);
	}
}

// EOF

<?php

namespace EllisLab\ExpressionEngine\Service\Model\Column\Serialized;

use EllisLab\ExpressionEngine\Service\Model\Column\SerializedType;

/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2015, EllisLab, Inc.
 * @license		https://ellislab.com/expressionengine/user-guide/license.html
 * @link		http://ellislab.com
 * @since		Version 3.0
 * @filesource
 */

// ------------------------------------------------------------------------

/**
 * ExpressionEngine Model Pipe-Delimited Typed Column
 *
 * @package		ExpressionEngine
 * @subpackage	Model
 * @category	Service
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */
class PipeDelimited extends SerializedType {

	protected $data = array();

	/**
	 * Called when the column is fetched from db
	 */
	public static function unserialize($db_data)
	{
		return array_filter(explode('|', $db_data));
	}

	/**
	 * Called before the column is written to the db
	 */
	public static function serialize($data)
	{
		return implode('|', $data);
	}

}

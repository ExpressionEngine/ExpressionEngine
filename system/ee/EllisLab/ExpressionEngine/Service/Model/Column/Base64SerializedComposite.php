<?php

namespace EllisLab\ExpressionEngine\Service\Model\Column;

/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2014, EllisLab, Inc.
 * @license		http://ellislab.com/expressionengine/user-guide/license.html
 * @link		http://ellislab.com
 * @since		Version 3.0
 * @filesource
 */

// ------------------------------------------------------------------------

/**
 * ExpressionEngine PHP Serialized & Base64 Encoded Composite Column
 *
 * @package		ExpressionEngine
 * @subpackage	Model
 * @category	Service
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */
abstract class Base64SerializedComposite extends Composite {

	/**
	 * @param $data Unserialized column data
	 * @return Serialized column data
	 */
	protected function serialize($data)
	{
		return base64_encode(serialize($data));
	}

	/**
	 * @param $data Serialized column data
	 * @return Unserialized data
	 */
	protected function unserialize($data)
	{
		return unserialize(base64_decode($data));
	}

}
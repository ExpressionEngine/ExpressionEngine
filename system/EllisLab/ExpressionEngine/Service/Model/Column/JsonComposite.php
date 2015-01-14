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
 * ExpressionEngine JSON Encoded Composite Column
 *
 * @package		ExpressionEngine
 * @subpackage	Model
 * @category	Service
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */
abstract class JsonComposite extends Composite {

	protected function serialize($data)
	{
		return json_encode($data);
	}

	protected function unserialize($data)
	{
		return json_decode($data);
	}

}
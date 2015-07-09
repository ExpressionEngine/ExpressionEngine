<?php

namespace EllisLab\ExpressionEngine\Service\Model\Column;

use InvalidArgumentException;

use EllisLab\ExpressionEngine\Library\Data\Entity;

/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2014, EllisLab, Inc.
 * @license		https://ellislab.com/expressionengine/user-guide/license.html
 * @link		http://ellislab.com
 * @since		Version 3.0
 * @filesource
 */

// ------------------------------------------------------------------------

/**
 * ExpressionEngine Composite Column
 *
 * @package		ExpressionEngine
 * @subpackage	Model
 * @category	Service
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */
abstract class Composite extends Entity implements Column {

	abstract protected function serialize($data);

	abstract protected function unserialize($data);


	public function fill($db_data)
	{
		$data = $this->unserialize($db_data);

		if ( ! empty($data))
		{
			foreach ($data as $key => $value)
			{
				$this->setRawProperty($key, $value);
			}
		}
	}

	public function getValue()
	{
		return $this->serialize($this->getRawValues());
	}
}
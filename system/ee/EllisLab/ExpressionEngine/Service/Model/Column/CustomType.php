<?php

namespace EllisLab\ExpressionEngine\Service\Model\Column;

use EllisLab\ExpressionEngine\Library\Data\Entity;

/**
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2017, EllisLab, Inc. (https://ellislab.com)
 * @license   https://expressionengine.com/license
 */

// ------------------------------------------------------------------------

/**
 * ExpressionEngine Model Custom Typed Column
 *
 * @package		ExpressionEngine
 * @subpackage	Model
 * @category	Service
 * @author		EllisLab Dev Team
 * @link		https://ellislab.com
 */
abstract class CustomType extends Entity implements Type {

	public static function create()
	{
		return new static;
	}

	abstract public function unserialize($db_data);

	abstract public function serialize($data);

	public function load($db_data)
	{
		$data = $this->unserialize($db_data);

		$this->fill($data);

		return $db_data;
	}

	public function store($data)
	{
		return $this->serialize($this->getValues());
	}

	public function set(array $data = array())
	{
		return $data;
	}

	public function get()
	{
		return $this;
	}

}

// EOF

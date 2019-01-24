<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2019, EllisLab Corp. (https://ellislab.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace EllisLab\ExpressionEngine\Service\Model\Column;

use EllisLab\ExpressionEngine\Library\Data\Entity;

/**
 * Model Service Custom Typed Column
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

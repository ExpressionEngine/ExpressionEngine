<?php
/**
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2018, EllisLab, Inc. (https://ellislab.com)
 * @license   https://expressionengine.com/license
 */

namespace EllisLab\ExpressionEngine\Service\Model\Column;

use EllisLab\ExpressionEngine\Library\Data\Entity;

/**
 * Model Service Serialized Typed Column
 */
abstract class SerializedType implements Type {

	protected $data = '';

	public static function create()
	{
		return new static;
	}

	public function load($db_data)
	{
		$data = $this->unserialize($db_data);
		$this->data = $data;

		return $data;
	}

	public function store($data)
	{
		return $this->serialize($this->data);
	}

	public function set($data)
	{
		return $this->data = $data;
	}

	public function get()
	{
		return $this->data;
	}

}

// EOF

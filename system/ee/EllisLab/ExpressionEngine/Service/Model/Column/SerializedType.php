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
 * ExpressionEngine Model Serialized Typed Column
 *
 * @package		ExpressionEngine
 * @subpackage	Model
 * @category	Service
 * @author		EllisLab Dev Team
 * @link		https://ellislab.com
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

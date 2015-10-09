<?php

namespace EllisLab\ExpressionEngine\Service\Model\Column;

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
 * ExpressionEngine Model Serialized Typed Column
 *
 * @package		ExpressionEngine
 * @subpackage	Model
 * @category	Service
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
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

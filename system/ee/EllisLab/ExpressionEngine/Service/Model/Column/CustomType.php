<?php

namespace EllisLab\ExpressionEngine\Service\Model\Column;

use EllisLab\ExpressionEngine\Library\Data\Entity;

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

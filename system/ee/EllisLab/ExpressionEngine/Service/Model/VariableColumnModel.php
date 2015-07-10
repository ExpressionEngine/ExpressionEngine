<?php

namespace EllisLab\ExpressionEngine\Service\Model;

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
 * ExpressionEngine VariableColumn Model
 *
 * @package		ExpressionEngine
 * @subpackage	Model
 * @category	Service
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */
class VariableColumnModel extends Model {

	protected $_variable_values = array();

	public function hasProperty($name)
	{
		return ($name !== '' && $name[0] != '_');
	}

	public function getRawProperty($name)
	{
		if (parent::hasProperty($name))
		{
			return parent::getRawProperty($name);
		}

		return $this->getVariableValue($name);
	}

	public function setRawProperty($name, $value)
	{
		if (parent::hasProperty($name))
		{
			return parent::setRawProperty($name, $value);
		}

		$this->backupIfChanging($name, $this->getVariableValue($name), $value);

		$this->_variable_values[$name] = $value;
	}

	public function getValues()
	{
		return array_merge(parent::getValues(), $this->_variable_values);
	}

	protected function getVariableValue($name)
	{
		if ( ! array_key_exists($name, $this->_variable_values))
		{
			return NULL;
		}

		return $this->_variable_values[$name];
	}
}

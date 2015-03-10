<?php

namespace EllisLab\ExpressionEngine\Service\Model\Mixin;

use DateTime;
use EllisLab\ExpressionEngine\Library\Mixin\Mixin;

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
 * ExpressionEngine Model Typed Column Mixin
 *
 * @package		ExpressionEngine
 * @subpackage	Model
 * @category	Service
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */
class TypedColumn implements Mixin {

	/**
	 * @var The parent scope
	 */
	protected $scope;

	/**
	 * @var Array of [columnname => type]
	 */
	protected $columns;

	/**
	 * @var Booleans that PHP won't cast
	 */
	protected $booleans = array(
		'y' => TRUE,
		'n' => FALSE,
	);

	/**
	 * @param Object $scope Parent scope
	 */
	public function __construct($scope)
	{
		$this->scope = $scope;
		$this->columns = $this->scope->getTypedColumns();
		$this->scope->addFilter('get', array($this, 'typedGetter'));
		$this->scope->addFilter('set', array($this, 'typedSetter'));
	}

	/**
	 * Get the mixin name
	 */
	public function getName()
	{
		return 'Model:TypedColumn';
	}

	/**
	 * Call the getter on a typed column if it exists.
	 *
	 * @param String $column Column name
	 * @return Mixed The cast value [or the original if not typed]
	 */
	public function typedGetter($value, $column)
	{
		$scope = $this->scope;

		if ( ! array_key_exists($column, $this->columns))
		{
			return $value;
		}

		switch ($this->columns[$column])
		{
			case 'string':
				$value = (string) $value;
				break;
			case 'int':
				$value = (int) $value;
				break;
			case 'float':
				$value = (float) $value;
				break;
			case 'bool':
				$value = $this->getBool((bool) $value);
				break;
			case 'boolString':
				$value = $this->getBool($value, 'y', 'n');
				break;
			case 'boolInt':
				$value = $this->getBool($value, 1, 0);
				break;
			case 'timestamp':
				if ($value !== NULL)
				{
					$value = new DateTime("@{$value}");
				}
				break;
			default:
				throw new \Exception("Invalid column type `{$type}`");
		}

		return $value;
	}

	/**
	 * Call the setter on a typed column if it exists.
	 *
	 * @param String $column Column name
	 * @param Mixed $value The value they're attempting to set
	 * @return Mixed The cast value [or the original if not typed]
	 */
	public function typedSetter($value, $column)
	{
		if ( ! array_key_exists($column, $this->columns))
		{
			return $value;
		}

		switch ($this->columns[$column])
		{
			case 'string':
				$value = (string) $value;
				break;
			case 'int':
				$value = (int) $value;
				break;
			case 'float':
				$value = (float) $value;
				break;
			case 'bool':
				$value = $this->setBool($value);
				break;
			case 'boolString':
				$value = $this->setBool($value, 'y', 'n');
				break;
			case 'boolInt':
				$value = $this->setBool($value, 1, 0);
				break;
			case 'timestamp':
				$value = $this->setTimestamp($value);
				break;
			default:
				throw new \Exception("Invalid column type `{$type}`");
		}

		return $value;
	}

	/**
	 * Mutate a timestamp on set.
	 *
	 * TODO could support string inputs here, ala entry_date
	 *
	 * @param Mixed $value A DateTime object or timestamp
	 * @return int a timestamp
	 */
	protected function setTimestamp($value)
	{
		if ($value instanceOf DateTime)
		{
			return $value->getTimestamp();
		}

		return (int) $value;
	}

	/**
	 * Mutate a boolean on set
	 *
	 * @param Mixed $value The input value
	 * @param Mixed $truthy The value to set for truthy booleans
	 * @param Mixed $falsey The value to set for falsey booleans
	 * @return Mixed The new value
	 */
	protected function setBool($value, $truthy = TRUE, $falsey = FALSE)
	{
		if (is_bool($value))
		{
			return $value ? $truthy : $falsey;
		}

		if ($value === $truthy || $value === $falsey)
		{
			return $value;
		}

		if (array_key_exists($value, $this->booleans))
		{
			$value = $this->booleans[$value];
		}

		$value = (bool) $value;

		if ($value == TRUE)
		{
			return $truthy;
		}

		return $falsey;
	}

	/**
	 * Mutate a boolean on get
	 *
	 * @param Mixed $value The input value
	 * @param Mixed $truthy The input value to treat as truthy
	 * @param Mixed $falsey The input value to treat as falsey
	 * @return bool The boolean equivalent
	 */
	protected function getBool($value, $truthy = TRUE, $falsey = FALSE)
	{
		if ($value === $truthy)
		{
			return TRUE;
		}

		return FALSE;
	}
}
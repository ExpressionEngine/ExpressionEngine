<?php

namespace EllisLab\ExpressionEngine\Service\Model\Mixin;

use DateTime;
use EllisLab\ExpressionEngine\Library\Mixin\Mixin;

class TypedColumn implements Mixin {

	protected $scope;
	protected $columns;

	public function __construct($scope, $manager)
	{
		$this->scope = $scope;
		$this->columns = $this->scope->getTypedColumns();

		if (count($this->columns))
		{
			$this->observePropertyEvents();
		}
	}

	/**
	 *
	 */
	protected function observePropertyEvents()
	{
		$that = $this;

		$this->scope->on('afterSet', function($model, $property, $value) use ($that)
		{
			$that->setTypedColumn($property, $value);
		});

		$this->scope->on('beforeGet', function($model, $property) use ($that)
		{
			$that->getTypedColumn($property);
		});
	}

	public function getTypedColumn($column)
	{
		if ( ! array_key_exists($column, $this->columns))
		{
			return;
		}

		$scope = $this->scope;
		$type = $this->columns[$column];
		$value = $scope->getRawProperty($column);

		switch ($type)
		{
			case 'string':
				$get_value = (string) $value;
				break;
			case 'int':
				$get_value = (int) $value;
				break;
			case 'float':
				$get_value = (float) $value;
				break;
			case 'bool':
				$get_value = (bool) $value;
				break;
			case 'boolString':
				$get_value = $this->getBool($value, 'y', 'n');
				break;
			case 'boolInt':
				$get_value = $this->getBool($value, 1, 0);
				break;
			case 'timestamp':
				$get_value = new DateTime("@{$value}");
				break;
			default:
				throw new \Exception("Invalid column type `{$type}`");
		}

		// fake the property set and then immediately undo it again
		// in the after event

		$scope->setRawProperty($column, $get_value);

		$scope->getEventEmitter()->once('afterGet', function($model, $property) use ($scope, $column, $value)
		{
			var_dump('reset');
			$scope->setRawProperty($column, $value);
		});
	}

	/**
	 *
	 */
	public function setTypedColumn($column, $value)
	{
		if ( ! array_key_exists($column, $this->columns))
		{
			return;
		}

		$type = $this->columns[$column];

		switch ($type)
		{
			case 'string':
				$new_value = (string) $value;
				break;
			case 'int':
				$new_value = (int) $value;
				break;
			case 'float':
				$new_value = (float) $value;
				break;
			case 'bool':
				$new_value = (bool) $value;
				break;
			case 'boolString':
				$new_value = $this->setBool($value, 'y', 'n');
				break;
			case 'boolInt':
				$new_value = $this->setBool($value, 1, 0);
				break;
			case 'timestamp':
				$new_value = $this->setTimestamp($value);
				break;
			default:
				throw new \Exception("Invalid column type `{$type}`");
		}

		if ($new_value !== $value)
		{
			$this->scope->setRawProperty($column, $new_value);
		}
	}

	/**
	 * Set a timestamp, by passing a datetime object or a timestamp
	 *
	 * TODO could support string inputs here, ala entry_date
	 */
	public function setTimestamp($value)
	{
		if ($value instanceOf DateTime)
		{
			return $value->getTimestamp();
		}

		return (int) $value;
	}

	/**
	 * Set a boolean
	 */
	protected function setBool($value, $truthy, $falsey)
	{
		if ($value === $truthy || $value === $falsey)
		{
			return $value;
		}

		$value = (bool) $value;

		if ($value == TRUE)
		{
			return $truthy;
		}

		return $falsey;
	}

	/**
	 * Get a boolean
	 */
	protected function getBool($value, $truthy, $falsey)
	{
		if ($value === $truthy)
		{
			return TRUE;
		}

		return FALSE;
	}
}
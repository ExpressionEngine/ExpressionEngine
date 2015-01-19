<?php

namespace EllisLab\ExpressionEngine\Service\Model\Mixin;

use DateTime;
use EllisLab\ExpressionEngine\Library\Mixin\Mixin;

class TypedColumn implements Mixin {

	protected $scope;
	protected $columns;

	protected $booleans = array(
		'y' => TRUE,
		'n' => FALSE,
	);

	public function __construct($scope)
	{
		$this->scope = $scope;
		$this->columns = $this->scope->getTypedColumns();
	}

	/**
	 * Get the mixin name
	 */
	public function getName()
	{
		return 'Model:TypedColumn';
	}

	/**
	 *
	 */
	public function typedColumnGetter($column)
	{
		$scope = $this->scope;
		$value = $scope->getRawProperty($column);

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
				$value = new DateTime("@{$value}");
				break;
			default:
				throw new \Exception("Invalid column type `{$type}`");
		}

		return $value;
	}

	/**
	 *
	 */
	public function typedColumnSetter($column, $value)
	{
		if ( ! array_key_exists($column, $this->columns))
		{
			return $value;
		}

		$type = $this->columns[$column];

		switch ($type)
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
	 * You can either pass a datetime object or a timestamp
	 *
	 * TODO could support string inputs here, ala entry_date
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
	 */
	protected function setBool($value, $truthy = TRUE, $falsey = FALSE)
	{
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
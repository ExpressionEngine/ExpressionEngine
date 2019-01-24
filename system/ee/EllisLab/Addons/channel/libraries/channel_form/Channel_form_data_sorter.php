<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2019, EllisLab Corp. (https://ellislab.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

/**
 * Channel Form Data Sorter
 */
class Channel_form_data_sorter
{
	private $column;
	private $direction;
	private $value;
	private $operator;
	private $valid_operators = array('==', '!=', '===', '!==', '>', '<', '>=', '<=', '<>', 'in_array');

	public function sort(array &$array, $column, $direction = 'asc')
	{
		$this->set_column($column);
		$this->set_direction($direction);

		usort($array, array($this, 'compare'));
	}

	public function filter(array &$array, $column, $value, $operator = '==')
	{
		$this->set_column($column);
		$this->set_operator($operator);
		$this->set_value($value);

		$array = array_filter($array, array($this, 'match'));
	}

	private function set_column($column)
	{
		$this->column = $column;
	}

	private function set_operator($operator)
	{
		if ( ! in_array($operator, $this->valid_operators))
		{
			$operator = $this->valid_operators[0];
		}

		$this->operator = $operator;
	}

	private function set_direction($direction)
	{
		$this->direction = $direction;
	}

	private function set_value($value)
	{
		$this->value = $value;
	}

	private function match($row)
	{
		$a = (isset($row[$this->column])) ? $row[$this->column] : NULL;
		$b = $this->value;

		switch($this->operator)
		{
			case '==':
				return $a == $b;
			case '!=':
				return $a != $b;
			case '===':
				return $a === $b;
			case '!==':
				return $a !== $b;
			case '>':
				return $a > $b;
			case '<':
				return $a < $b;
			case '>=':
				return $a >= $b;
			case '<=':
				return $a <= $b;
			case '<>':
				return $a <> $b;
			case 'in_array':
				return in_array($a, is_array($b) ? $b : explode('|', $b));
		}
	}

	private function compare($a, $b)
	{
		$a = (isset($a[$this->column])) ? $a[$this->column] : NULL;
		$b = (isset($b[$this->column])) ? $b[$this->column] : NULL;

		if ($a == $b)
		{
			return 0;
		}

		$compare = (strtolower($this->direction) == 'desc') ? ($a < $b) : ($a > $b);

		return ($compare) ? 1 : -1;
	}
}

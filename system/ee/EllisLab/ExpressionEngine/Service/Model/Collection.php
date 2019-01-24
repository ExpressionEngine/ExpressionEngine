<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2019, EllisLab Corp. (https://ellislab.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace EllisLab\ExpressionEngine\Service\Model;

use Closure;
use InvalidArgumentException;

use EllisLab\ExpressionEngine\Service\Model\Association\Association;
use EllisLab\ExpressionEngine\Library\Data\Collection as CoreCollection;

/**
 * Model Service Collection
 */
class Collection extends CoreCollection {

	private $association;

	/**
	 * Shortcut ->Relationship to a pluck that returns a collection
	 */
	public function __get($key)
	{
		if (ucfirst($key) != $key)
		{
			throw new InvalidArgumentException('Trying to get a non-relationship property on a collection. Did you mean `pluck()`?');
		}

		return new static($this->pluck($key));
	}

	/**
	 *
	 */
	public function add(Model $model, $propagate = TRUE)
	{
		$this->elements[] = $model;

		if (isset($this->association) && $propagate)
		{
			$this->association->add($model);
		}

		return $this;
	}

	/**
	 *
	 */
	public function getIds()
	{
		return $this->collect(function($model)
		{
			return $model->getId();
		});
	}

	/**
	 *
	 */
	public function indexByIds()
	{
		return array_combine(
			$this->getIds(),
			$this->elements
		);
	}

	/**
	 *
	 */
	public function filter($key, $operator = '', $value = NULL)
	{
		if ( ! ($key instanceOf Closure))
		{
			$key = $this->getOperatorCallback($key, $value, $operator);
		}

		return parent::filter($key);
	}

	/**
	 * Called on a Collection of Collections, returns a Collection containing
	 * the models that are present in all the Collections
	 *
	 * @return Collection
	 */
	public function intersect()
	{
		// Only 1 or none? Nothing to intersect!
		if ($this->count() < 2)
		{
			return $this;
		}

		// Flat collection of models? Return a unique set
		if ($this->first() instanceOf Model)
		{
			return new static($this->indexByIds());
		}

		$elements = $this->map(function($collection)
		{
			return $collection->indexByIds();
		});

		return new static(call_user_func_array('array_intersect_key', $elements));
	}

	/**
	 *
	 */
	public function with($with)
	{
		// todo
	}

	/**
	 *
	 */
	public function remove($which)
	{
		if ($this->count() == 0)
		{
			return $this;
		}

		if ($which instanceOf Model)
		{
			$remove = array($which);
		}
		elseif ($which instanceOf CoreCollection)
		{
			$remove = $which->asArray();
		}
		elseif ($which instanceOf Closure)
		{
			$remove = $this->filter($which)->asArray();
		}
		else
		{
			$pk = $this->first()->getPrimaryKey();
			$remove = $this->filter($pk, $which)->asArray();
		}

		foreach ($remove as $model)
		{
			$this->association->remove($model);
		}

		return $this;
	}

	public function removeElement($model)
	{
		$this->elements = array_diff($this->elements, array($model));
		return $this;
	}

	public function getAssociation()
	{
		return $this->association;
	}

	public function setAssociation(Association $association)
	{
		$this->association = $association;
	}

	public function offsetSet($offset, $value)
	{
		parent::offsetSet($offset, $value);

		if (isset($this->association))
		{
			$this->association->add($value);
		}
	}

	/**
	 *
	 */
	protected function getOperatorCallback($k, $v, $operator)
	{
		if (is_null($v))
		{
			$v = $operator;
			$operator = '==';
		}

		switch ($operator)
		{
			case '<':
				return function($m) use($k, $v) { return $m->$k < $v; };
			case '>':
				return function($m) use($k, $v) { return $m->$k > $v; };
			case '<=':
				return function($m) use($k, $v) { return $m->$k <= $v; };
			case '>=':
				return function($m) use($k, $v) { return $m->$k >= $v; };
			case '==':
				return function($m) use($k, $v) { return $m->$k == $v; };
			case '!=':
				return function($m) use($k, $v) { return $m->$k != $v; };
			case 'IN':
				return function($m) use($k, $v) { return in_array($m->$k, $v); };
			case 'NOT IN':
				return function($m) use($k, $v) { return ! in_array($m->$k, $v); };
			default:
				throw new InvalidArgumentException('Not a valid operator: '.htmlentities($operator));
		}
	}

	public function __toString()
	{
		return spl_object_hash($this);
	}
}

// EOF

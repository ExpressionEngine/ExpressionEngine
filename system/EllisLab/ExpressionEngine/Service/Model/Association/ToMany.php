<?php

namespace EllisLab\ExpressionEngine\Service\Model\Association;

use LogicException;
use InvalidArgumentException;

use EllisLab\ExpressionEngine\Service\Model\Model;
use EllisLab\ExpressionEngine\Service\Model\Collection;

/**
 * Association that points to a collection
 */
abstract class ToMany extends Association {

	protected $related = array();
	protected $collection = NULL;

	/**
	 *
	 */
	public function get()
	{
		$items = parent::get();

		return $this->getCollection();
	}

	/**
	 *
	 */
	public function fill($related)
	{
		$this->related = array();
		$this->collection = NULL;

		$this->markAsLoaded();

		if ( ! isset($related))
		{
			return;
		}

		if ( ! (is_array($related) || $related instanceOf Collection))
		{
			throw new InvalidArgumentException('Invalid fill(), must be collection or array.');
		}

		foreach ($related as $model)
		{
			$this->related[spl_object_hash($model)] = $model;
		}
	}

	/**
	 *
	 */
	protected function hasRelated(Model $model)
	{
		$hash = spl_object_hash($model);

		return array_key_exists($hash, $this->related);
	}

	/**
	 *
	 */
	protected function addToRelated(Model $model)
	{
		parent::addToRelated($model);

		if ( ! $this->hasRelated($model))
		{
			$collection = $this->getCollection();
			$collection[] = $model;

			$this->related[spl_object_hash($model)] = $model;
		}
	}

	/**
	 *
	 */
	protected function removeFromRelated(Model $model)
	{
		parent::removeFromRelated($model);

		$this->collection = NULL;
		unset($this->related[spl_object_hash($model)]);
	}

	/**
	 *
	 */
	protected function saveAllRelated()
	{
		$this->getCollection()->save();
	}

	/**
	 *
	 */
	protected function getCollection()
	{
		if ( ! isset($this->collection))
		{
			$this->collection = new Collection(array_values($this->related));
		}

		return $this->collection;
	}
}
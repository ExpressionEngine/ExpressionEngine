<?php

namespace EllisLab\ExpressionEngine\Service\Model\Association;

use LogicException;
use InvalidArgumentException;

use EllisLab\ExpressionEngine\Service\Model\Model;

/**
 * Association that points to a collection
 */
abstract class ToMany extends Association {

	protected $related = array();

	/**
	 *
	 */
	public function get()
	{
		$items = parent::get();

		return array_values($items);
	}

	/**
	 *
	 */
	public function fill($related)
	{
		$this->related = array();
		$this->markAsLoaded();

		if ( ! isset($related))
		{
			return;
		}

		if ( ! is_array($related))
		{
			throw new InvalidArgumentException('Invalid fill(), must be array.');
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

		$this->related[spl_object_hash($model)] = $model;
	}

	/**
	 *
	 */
	protected function removeFromRelated(Model $model)
	{
		parent::removeFromRelated($model);

		unset($this->related[spl_object_hash($model)]);
	}

	/**
	 *
	 */
	protected function saveAllRelated()
	{
		foreach ($this->related as $related)
		{
			$related->save();
		}
	}
}
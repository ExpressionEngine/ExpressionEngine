<?php

namespace EllisLab\ExpressionEngine\Service\Model\Association;

use LogicException;

use EllisLab\ExpressionEngine\Service\Model\Model;

/**
 * Association that points directly to another model
 */
abstract class ToOne extends Association {

	/**
	 * Overriden to enforce type. The parent accepts
	 * arrays/collections. We don't allow that here.
	 */
	public function set($model)
	{
		if (isset($model) && ! ($model instanceOf Model))
		{
			throw new \InvalidArgumentException('Cannot set(), must be a Model');
		}

		parent::set($model);
	}

	/**
	 * Overriden to enforce type. The parent accepts
	 * arrays/collections. We don't allow that here.
	 *
	 * Typically add() should never be used on a *toOne relationship, but
	 * we'll allow it if nothing is set. This makes the parent implementation
	 * of set() a lot easier and we can avoid reimplementing it here.
	 */
	public function add($model)
	{
		if (isset($model) && ! ($model instanceOf Model))
		{
			throw new \InvalidArgumentException('Cannot set(), must be a Model');
		}

		if (isset($this->relation))
		{
			throw new LogicException('Cannot add(), did you mean set()?');
		}

		parent::add($model);
	}

	/**
	 *
	 */
	protected function hasRelated(Model $model)
	{
		return $this->related === $model;
	}

	/**
	 *
	 */
	protected function addToRelated(Model $model)
	{
		parent::addToRelated($model);

		$this->related = $model;
	}

	/**
	 *
	 */
	protected function removeFromRelated(Model $model)
	{
		parent::addToRelated($model);

		$this->related = NULL;
	}

	/**
	 *
	 */
	protected function saveAllRelated()
	{
		$this->related->save();
	}
}
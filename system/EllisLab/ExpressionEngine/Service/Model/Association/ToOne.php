<?php

namespace EllisLab\ExpressionEngine\Service\Model\Association;

use LogicException;

use EllisLab\ExpressionEngine\Service\Model\Model;
use EllisLab\ExpressionEngine\Library\Data\Collection;

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
 * ExpressionEngine ToOne Association
 *
 * Associations that point directly to a single model instance.
 *
 * @package		ExpressionEngine
 * @subpackage	Model
 * @category	Service
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */
abstract class ToOne extends Association {

	/**
	 *
	 */
	public function fill($models)
	{
		if (is_array($models) || $models instanceOf Collection)
		{
			if (count($models))
			{
				parent::fill($models[0]);
			}
			else
			{
				parent::fill(NULL);
			}
		}
		else
		{
			parent::fill($models);
		}
	}

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

		if (isset($this->related))
		{
			throw new LogicException('Cannot add(), did you mean set()?');
		}

		parent::add($model);
	}

	/**
	 *
	 */
	public function clear()
	{
		parent::clear();
		$this->related = NULL;
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
		parent::removeFromRelated($model);

		$this->related = NULL;
	}

	/**
	 *
	 */
	protected function saveAllRelated()
	{
		if (isset($this->related))
		{
			$this->related->save();
		}
	}
}
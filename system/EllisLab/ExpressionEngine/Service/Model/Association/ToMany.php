<?php

namespace EllisLab\ExpressionEngine\Service\Model\Association;

use LogicException;
use InvalidArgumentException;

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
 * ExpressionEngine ToMany Association
 *
 * Associations that point to a collection of models.
 *
 * @package		ExpressionEngine
 * @subpackage	Model
 * @category	Service
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
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
	public function clear()
	{
		parent::clear();
		$this->related = array();
		$this->collection = NULL;
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
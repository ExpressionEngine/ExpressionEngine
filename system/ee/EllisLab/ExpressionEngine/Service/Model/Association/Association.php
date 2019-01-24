<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2019, EllisLab Corp. (https://ellislab.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace EllisLab\ExpressionEngine\Service\Model\Association;

use EllisLab\ExpressionEngine\Service\Model\Collection;
use EllisLab\ExpressionEngine\Service\Model\Model;
use EllisLab\ExpressionEngine\Service\Model\Relation\Relation;

/**
 * Model Service: Assocation
 */
class Association {

	private $booted = FALSE;
	private $loaded = FALSE;
	private $saving = FALSE;

	private $inverse_name;

	protected $diff;
	protected $model;
	protected $facade;
	protected $related;
	protected $relation;
	protected $foreign_key;

	public function __construct(Relation $relation)
	{
		$this->relation = $relation;
		list($this->foreign_key, $_) = $this->relation->getKeys();
	}

	/**
	 * Fill item(s) for this association. Will not mark changes.
	 *
	 * @param Mixed $related Model(s)|Collection
	 * @param Bool $_skip_inverse
	 * @return void
	 */
	public function fill($related, $_skip_inverse = FALSE)
	{
		$this->related = $related;

		if ( ! $_skip_inverse)
		{
			$related = $this->toModelArray($related);

			foreach ($related as $to)
			{
				$this->relation->fillLinkIds($this->model, $to);

				if ($this->relation instanceOf HasOneOrMany)
				{
					$this->getInverse($to)->fill($this->model, TRUE);
				}
			}
		}

		$this->markAsLoaded();
	}

	/**
	 * set item(s) for this association. Will remove all existing ones and
	 * mark as changed.
	 *
	 * @param Mixed $item Model(s)|Collection
	 * @return void
	 */
	public function set($item)
	{
		$this->diff->reset();

		$this->remove();
		$items = $this->toModelArray($item);

		foreach ($items as $model)
		{
			$inverse = $this->getInverse($model);

			if ($inverse instanceOf ToOne)
			{
				$inverse->remove();
			}

			$this->addToRelated($model);

			if ($inverse instanceOf ToOne)
			{
				$inverse->markAsLoaded();
			}
		}

		$this->markAsLoaded();
		$this->diff->wasSet();
	}

	/**
	 * Get inverse association name
	 *
	 * @return String Name of inverse association
	 */
	public function getInverseName()
	{
		if ( ! isset($this->inverse_name))
		{
			$inverse = $this->relation->getInverse();
			$this->inverse_name = $inverse->getName();
		}

		return $this->inverse_name;
	}

	/**
	 * Get inverse association on a given model
	 *
	 * @param Model $model Model whose association to get
	 * @return Association that reverses this one
	 */
	public function getInverse(Model $model)
	{
		$inverse_name = $this->getInverseName();
		return $model->getAssociation($inverse_name);
	}

	/**
	 * Get association items. Will lazy load if necessary
	 *
	 * @return Model|Collection
	 */
	public function get()
	{
		if ( ! $this->isLoaded())
		{
			$this->reload();
		}

		return $this->related;
	}

	/**
	 * Add an item to this association
	 *
	 * @param Mixed $item Model(s)|Collection
	 * @return void
	 */
	public function add($item)
	{
		$items = $this->toModelArray($item);

		foreach ($items as $model)
		{
			$this->addToRelated($model);

			$inverse = $this->getInverse($model);

			if ($inverse instanceOf ToOne)
			{
				$inverse->markAsLoaded();
			}
		}
	}

	/**
	 * Remove an item from this association
	 *
	 * @param Mixed $items Model(s)|Collection (if not passed, remove all)
	 * @return void
	 */
	public function remove($items = NULL)
	{
		$items = $items ?: $this->related;
		$items = $this->toModelArray($items);

		foreach ($items as $model)
		{
			if ($model instanceOf Model)
			{
				$this->removeFromRelated($model);
			}
		}
	}

	/**
	 * Utility method to handle a primary key change. Public due to PHP 5.3's callbacks
	 * being wonky. Don't call externally, all other methods in this class will
	 * do the right thing automtically.
	 *
	 * @return void
	 */
	public function idHasChanged()
	{
		$new_id = $this->model->getId();
		$items = $this->toModelArray($this->related);

		foreach ($items as $to)
		{
			$this->relation->linkIds($this->model, $to);
		}
	}

	/**
	 * Save any unsaved relations and then the related models.
	 *
	 * @return void
	 */
	public function save()
	{
		$this->diff->commit();

		if ( ! $this->saving && $this->relation->canSaveAcross())
		{
			$this->saving = TRUE;

			if (isset($this->related))
			{
				$this->related->save();
			}

			$this->saving = FALSE;
		}
	}

	/**
	 * Utility method to mark data as loaded. Public due to PHP 5.3's callbacks
	 * being wonky. Don't call externally, all other methods in this class will
	 * do the right thing automtically.
	 *
	 * @return void
	 */
	public function markAsLoaded()
	{
		$this->loaded = TRUE;
	}

	/**
	 * Utility method to check if data has been loaded. Public due to PHP 5.3's
	 * callbacks being wonky. Don't call externally, all other methods in this
	 * class will do the right thing automtically.
	 *
	 * @return bool Association data is loaded?
	 */
	public function isLoaded()
	{
		return $this->loaded;
	}

	/**
	 * (Re)load the association
	 *
	 * This runs a query to pull in related data and links up all the object
	 * references.
	 */
	public function reload()
	{
		$query = $this->facade->get($this->relation->getTargetModel());
		$query->setLazyConstraint($this->relation, $this->model);

		// Hack for lazy loaded relationships to ensure that they're matched
		// with a site_id if we know of one. Can't fall back on ee()->config
		// here without breaking the fixtures :(
		if ($this->relation->getTargetModel() == 'ee:MemberGroup')
		{
			if ($this->model->hasProperty('site_id') && $this->model->site_id)
			{
				$query->filter('site_id', $this->model->site_id);
			}
		}

		$result = $query->all();

		if ($result instanceOf Collection)
		{
			$result->setAssociation($this);
		}

		$this->fill($result);

		$this->diff->reset();
		$this->markAsLoaded();
	}

	public function setFacade($facade)
	{
		$this->facade = $facade;
	}

	protected function addToRelated(Model $model)
	{
		$this->ensureExists($model);
		$this->ensureInverseExists($model);
	}

	protected function removeFromRelated(Model $model)
	{
		$this->ensureDoesNotExist($model);
		$this->ensureInverseDoesNotExist($model);
	}

	protected function ensureExists($model)
	{
		$this->diff->add($model);
		$this->relation->linkIds($this->model, $model);
	}

	protected function ensureDoesNotExist($model)
	{
		$this->diff->remove($model);
		$this->relation->unlinkIds($this->model, $model);
	}

	protected function ensureInverseExists($model)
	{
		$assoc = $this->getInverse($model);
		$assoc->ensureExists($this->model);
	}

	protected function ensureInverseDoesNotExist($model)
	{
		$assoc = $this->getInverse($model);
		$assoc->ensureDoesNotExist($this->model);
	}

	/**
	 * Utility method to turn a model, model collection, or other model
	 * datastructure into a plain array.
	 *
	 * @param Mixed Model(s)
	 * @return array of Models
	 */
	protected function toModelArray($item)
	{
		if (is_null($item))
		{
			return array();
		}

		if (is_array($item))
		{
			return $item;
		}

		if ($item instanceOf Model)
		{
			return array($item);
		}

		if ($item instanceOf Collection)
		{
			return $item->asArray();
		}

		throw new \InvalidArgumentException('Must be a model, collection, or array of models');
	}

	/**
	 * Force a reload next time this relationship is accessed.
	 *
	 * @return void
	 */
	public function markForReload()
	{
		if ($this->isLoaded())
		{
			$related = $this->toModelArray($this->related);

			$this->related = NULL;
			$this->loaded = FALSE;
		}
	}

	/**
	 * Accessor for the foreign key name
	 *
	 * @return String foreign key name
	 */
	public function getForeignKey()
	{
		return $this->foreign_key;
	}

	/**
	 * Handle a foreign key change
	 *
	 * This gets called when the potential foreign key changes. Currently our
	 * response it to play it safe and always reload the relationship.
	 *
	 * @param Mixed $value New foreign key value
	 * @return void
	 */
	public function foreignKeyChanged($value)
	{
		if ($value == NULL)
		{
			return $this->remove();
		}

		$this->markForReload();
	}

	/**
	 * Check if the association has been booted
	 *
	 * @return bool Booted?
	 */
	public function isBooted()
	{
		return $this->booted;
	}

	/**
	 * Spin up the association
	 *
	 * This creates an object diff tracker to ensure we save object changes. It
	 * also sets up listeners on the current id and the potential foreign key
	 * to track changes to those and trigger reloads.
	 *
	 * @return void
	 */
	public function boot($model)
	{
		$this->booted = TRUE;
		$this->model = $model;
		$this->diff = new Diff($this->model, $this->relation);
	}
}

// EOF

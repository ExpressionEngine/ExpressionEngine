<?php

namespace EllisLab\ExpressionEngine\Service\Model\Association;

use EllisLab\ExpressionEngine\Library\Data\Collection;
use EllisLab\ExpressionEngine\Service\Model\Model;
use EllisLab\ExpressionEngine\Service\Model\Relation;

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
 * ExpressionEngine Association
 *
 * Associations describe how two model instances are connected. For general
 * relationships between models, @see Relations.
 *
 * @package		ExpressionEngine
 * @subpackage	Model
 * @category	Service
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */
abstract class Association {

	protected $source = NULL;
	protected $related = NULL;
	protected $tracker = NULL;

	protected $name = '';
	protected $loaded = FALSE;

	protected $relation = NULL;
	protected $frontend = NULL;

	public function __construct(Model $source, $name = '')
	{
		$this->name = $name;
		$this->source = $source;
		$this->bootAssociation();
	}

	/**
	 *
	 */
	abstract protected function isStrongAssociation();

	/**
	 *
	 */
	abstract protected function canSaveAcross();

	/**
	 *
	 */
	abstract protected function hasRelated(Model $model);

	/**
	 *
	 */
	abstract protected function saveAllRelated();

	/**
	 *
	 */
	public function clear()
	{
		$this->loaded = FALSE;
	}

	/**
	 *
	 */
	public function isLoaded()
	{
		return $this->loaded;
	}

	/**
	 *
	 */
	public function markAsLoaded()
	{
		$this->loaded = TRUE;
	}

	/**
	 *
	 */
	public function get()
	{
		if ( ! $this->isLoaded() && ! $this->source->isNew())
		{
			$this->reload();
		}

		return $this->related;
	}

	/**
	 *
	 */
	public function all()
	{
		$related = $this->get();

		if ( ! isset($related))
		{
			return new Collection();
		}

		if ($related instanceOf Collection)
		{
			return $related;
		}

		if ( ! is_array($related))
		{
			$related = array($related);
		}

		return new Collection($related);
	}

	/**
	 *
	 */
	public function count()
	{
		$related = $this->get();

		if ( ! isset($related))
		{
			return 0;
		}

		return is_array($related) ? count($related) : 1;
	}

	/**
	 *
	 */
	public function first()
	{
		return $this->all()->first();
	}

	/**
	 *
	 */
	public function fill($related)
	{
		$this->related = $related;
		$this->markAsLoaded();
	}

	/**
	 *
	 */
	public function set($item)
	{
		$this->remove();
		$this->add($item);
	}

	/**
	 *
	 */
	public function add($item)
	{
		if ($item instanceOf Collection || is_array($item))
		{
			foreach ($item as $model)
			{
				$this->add($model);
			}

			return;
		}

		$this->addToRelated($item);
	}

	/**
	 *
	 */
	public function remove($item = NULL)
	{
		if ( ! isset($item))
		{
			if ( ! $this->isLoaded())
			{
				return $this->clear();
			}

			$item = $this->related;

			if ( ! isset($item))
			{
				return;
			}
		}

		if ($item instanceOf Collection || is_array($item))
		{
			foreach ($item as $model)
			{
				$this->remove($model);
			}

			return;
		}

		$this->removeFromRelated($item);
	}

	/**
	 *
	 */
	public function create($item)
	{
		if (is_array($item))
		{
			$item = $this->frontend->make(
				$this->relation->getTargetModel(),
				$item
			);
		}

		$this->addToRelated($item);

		return $item;
	}

	/**
	 *
	 */
	public function delete($item)
	{
		$this->removeFromRelated($item);
	}

	/**
	 * Save any unsaved relations and then the related models.
	 */
	public function save()
	{
		foreach ($this->tracker->getRemoved() as $model)
		{
			$this->dropRelationship($this->source, $model);
		}

		foreach ($this->tracker->getAdded() as $model)
		{
			$this->insertRelationship($this->source, $model);
		}

		$this->tracker->reset();

		if ($this->canSaveAcross())
		{
			$this->saveAllRelated();
		}
	}

	/**
	 *
	 */
	public function reload()
	{
		$query = $this->frontend->get($this->relation->getTargetModel());
		$query->setLazyConstraint($this->relation, $this->source);

		$result = $query->all();
		$this->fill($result);

		// If we're a single owner, then we fill the inverse
		// relationship, in essence caching the parent relation
		$inverse = $this->relation->getInverse();

		if ($inverse instanceOf Relation\BelongsTo)
		{
			$inverse_name = $inverse->getName();
			$result->$inverse_name->fill($this->source);
		}

		$this->markAsLoaded();
	}

	/**
	 *
	 */
	public function setFrontend($frontend)
	{
		$this->frontend = $frontend;
	}

	/**
	 *
	 */
	public function setRelation($relation)
	{
		$this->relation = $relation;
	}

	/**
	 * Persist the relation. Only many-to-many implements this
	 * all others are stored directly on one of the models.
	 */
	protected function insertRelationship($source, $model)
	{
		// only exists on many-to-many
	}

	/**
	 * Drop the relation. Only many-to-many implements this
	 * all others are stored directly on one of the models.
	 */
	protected function dropRelationship($source, $model)
	{
		// only exists on many-to-many
		// todo if not many to many, but still weak, then this
		// is where we can zero out the field!
	}

	/**
	 *
	 */
	protected function addToRelated(Model $model)
	{
		$this->relation->linkIds($this->source, $model);
		$this->tracker->add($model);
	}

	/**
	 *
	 */
	protected function removeFromRelated(Model $model)
	{
		if ($this->hasRelated($model))
		{
			$this->tracker->remove($model);
			$this->relation->unlinkIds($this->source, $model);
		}
	}

	/**
	 *
	 */
	protected function bootAssociation()
	{
		if ( ! $this->isStrongAssociation())
		{
			$this->tracker = new Tracker\Staged();
		}
		else
		{
			$this->tracker = new Tracker\Immediate();
		}
	}
}

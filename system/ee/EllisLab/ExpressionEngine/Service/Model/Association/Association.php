<?php

namespace EllisLab\ExpressionEngine\Service\Model\Association;

use EllisLab\ExpressionEngine\Service\Model\Collection;
use EllisLab\ExpressionEngine\Service\Model\Model;
use EllisLab\ExpressionEngine\Service\Model\Relation\Relation;

class Association {

    private $loaded = FALSE;
    private $saving = FALSE;

    private $inverse_name;

    protected $diff;
    protected $facade;
    protected $related;
    protected $relation;
    protected $foreign_key;

    public function __construct(Model $model, Relation $relation)
    {
        $this->relation = $relation;
        list($this->foreign_key, $_) = $this->relation->getKeys();

        $this->bootAssociation($model);
    }

    /**
     * Fill item(s) for this association. Will not mark changes.
     *
     * @param Mixed $related Model(s)|Collection
     * @param Bool $_skip_inverse
     * @return void
     */
    public function fill($model, $related, $_skip_inverse = FALSE)
    {
        $this->related = $related;

        if ( ! $_skip_inverse)
        {
            $related = $this->toModelArray($related);

            foreach ($related as $to)
            {
                $this->relation->fillLinkIds($model, $to);
                $this->getInverse($to)->fill($to, $model, TRUE);
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
    public function set($parent, $item)
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

            $this->addToRelated($parent, $model);
        }

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
    public function get($parent)
    {
        if ( ! $this->isLoaded())
        {
            $this->reload($parent);
        }

        return $this->related;
    }

    /**
     * Add an item to this association
     *
     * @param Mixed $item Model(s)|Collection
     * @return void
     */
    public function add($parent, $item)
    {
        $items = $this->toModelArray($item);

        foreach ($items as $model)
        {
            $this->addToRelated($parent, $model);
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
            $this->removeFromRelated($model);
        }
    }

    /**
     * Utility method to handle a primary key change. Public due to PHP 5.3's callbacks
     * being wonky. Don't call externally, all other methods in this class will
     * do the right thing automtically.
     *
     * @return void
     */
    public function idHasChanged($parent)
    {
        $new_id = $parent->getId();
        $items = $this->toModelArray($this->related);

        foreach ($items as $to)
        {
            $this->relation->linkIds($parent, $to);
        }
    }

    /**
     * Save any unsaved relations and then the related models.
     *
     * @return void
     */
    public function save()
    {
        $this->diff->commit($parent);

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
	public function reload($parent)
	{
		$query = $this->facade->get($this->relation->getTargetModel());
		$query->setLazyConstraint($this->relation, $parent);

		$result = $query->all();

        if ($result instanceOf Collection)
        {
            $result->setAssociation($this);
        }

		$this->fill($parent, $result);

        $this->diff->reset();
		$this->markAsLoaded();
	}

    public function setFacade($facade)
    {
        $this->facade = $facade;
    }

    protected function addToRelated(Model $parent, Model $model)
    {
        $this->ensureExists($parent, $model);
        $this->ensureInverseExists($parent, $model);
    }

    protected function removeFromRelated(Model $parent, Model $model)
    {
        $this->ensureDoesNotExist($parent, $model);
        $this->ensureInverseDoesNotExist($parent, $model);
    }

    protected function ensureExists($parent, $model)
    {
        $this->diff->add($model);
        $this->relation->linkIds($parent, $model);
    }

    protected function ensureDoesNotExist($parent, $model)
    {
        $this->diff->remove($model);
        $this->relation->unlinkIds($parent, $model);
    }

    protected function ensureInverseExists($parent, $model)
    {
        $assoc = $this->getInverse($model);
        $assoc->ensureExists($model, $parent);
    }

    protected function ensureInverseDoesNotExist($parent, $model)
    {
        $assoc = $this->getInverse($model);
        $assoc->ensureDoesNotExist($model, $parent);
    }

    /**
     * Utility method to turn a model, model collection, or other model
     * datastructure into a plain array.
     *
     * @param Mixed Model(s)
     * @return Array of Models
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

            foreach ($related as $model)
            {
                $inverse = $this->getInverse($model);
                $inverse->markForReload();
            }
        }
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

        if ($this->isLoaded())
        {
            $this->markForReload();
        }
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
    protected function bootAssociation($model)
    {
        $this->diff = new Diff($this->relation);

        if ($this->foreign_key != $model->getPrimaryKey())
        {
            $model->addForeignKey($this->foreign_key, $this);
        }
    }
}

// EOF

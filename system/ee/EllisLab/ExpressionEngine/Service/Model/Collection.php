<?php

namespace EllisLab\ExpressionEngine\Service\Model;

use Closure;
use InvalidArgumentException;

use EllisLab\ExpressionEngine\Service\Model\Association\Association;
use EllisLab\ExpressionEngine\Library\Data\Collection as CoreCollection;

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
    public function add(Model $model)
    {
        $this->elements[] = $model;

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

        $this->elements = array_diff($this->elements, $remove);

		foreach ($remove as $model)
		{
			$this->association->remove($model);
		}

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

	public function offsetSet($offset, $value = NULL)
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
}

// EOF

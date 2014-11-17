<?php namespace EllisLab\ExpressionEngine\Service\Model\Query;

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
 * ExpressionEngine Model Query Class
 *
 * Used to query the model for instances of models that have been persisted
 * in some way, usually in the database.
 *
 * @package		ExpressionEngine
 * @subpackage	Model
 * @category	Service
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */
class Builder {

	protected $root = NULL;
	protected $model_object = NULL;

	protected $fields = array();
	protected $filters = array();
	protected $orders = array();
	protected $set = array();

	protected $limit = '18446744073709551615'; // 2^64
	protected $offset = 0;

	private $query;
	private $references;
	private $filter_stack = array();

	/**
	 * Constructor
	 */
	public function __construct(Query $query, ReferenceChain $references, $model_name)
	{
		$this->query = $query;
		$this->references = $references;

		list($model, $alias) = $this->splitAlias($model_name);

		$this->root = new Reference($alias);
		$this->root->model = $model;

		$this->references->add($this->root);
	}

	/**
	 *
	 */
	public function setModelObject($modelOrCollection)
	{
		$this->model_object = $modelOrCollection;
		$this->root->setObject($modelOrCollection);
	}

	/**
	 * Run the query and return a collection.
	 *
	 * @return Collection
	 */
	public function all()
	{
		if ( ! $this->filterStackIsEmpty())
		{
			throw new \Exception('Unclosed filter group.');
		}

		$result = $this->query->executeFetch($this);

		return $result->collection();
	}

	/**
	 * Run the query and get the results, but only return the first.
	 *
	 * @return Model Instance
	 */
	public function first()
	{
		if ( ! $this->filterStackIsEmpty())
		{
			throw new \Exception('Unclosed filter group.');
		}

		$this->limit(1);

		$result = $this->query->executeFetch($this);

		return $result->first();
	}

	/**
	 *
	 */
	public function delete()
	{
		return $this->query->executeDelete($this);
	}

	/**
	 *
	 */
	public function update()
	{
		return $this->query->executeUpdate($this);
	}

	/**
	 *
	 */
	public function create()
	{
		return $this->query->executeCreate($this);
	}

	/**
	 * Count the number of objects that would be returned by this query if it
	 * was run right now.
	 *
	 * @return int Row count
	 */
	public function count()
	{
		return $this->query->executeCount($this);
	}


	/**
	 *
	 */
	public function set($key, $value = NULL)
	{
		if ( ! is_array($key))
		{
			$key = array($key => $value);
		}

		$this->set = array_merge($this->set, $key);

		return $this;
	}

	/**
	 * Apply a filter
	 *
	 * @param String $key		Relationship.columnname to filter on
	 * @param String $operator	Comparison to perform [==, !=, <, >, <=, >=, IN]
	 * @param Mixed  $value		Value to compare to
	 * @return Query $this
	 *
	 * The third parameter is optional. If it is not given, the == operator is
	 * assumed and the second parameter becomes the value.
	 */
	public function filter($property, $operator = NULL, $value = NULL)
	{
		if ( ! isset($value))
		{
			$value = $operator;
			$operator = '==';
		}

		$this->filters[] = array($property, $operator, $value, FALSE);
		return $this;
	}

	/**
	 * Same as `filter()`, but creates an OR statement.
	 *
	 * @param String $key		Relationship.columnname to filter on
	 * @param String $operator	Comparison to perform [==, !=, <, >, <=, >=, IN]
	 * @param Mixed  $value		Value to compare to [optional]
	 * @return Query $this
	 *
	 * The third parameter is optional. If it is not given, the == operator is
	 * assumed and the second parameter becomes the value.
	 */
	public function orFilter($property, $operator = NULL, $value = NULL)
	{
		if ( ! isset($value))
		{
			$value = $operator;
			$operator = '==';
		}

		$this->filters[] = array($property, $operator, $value, TRUE);

		return $this;
	}

	/**
	 * Open a filter group
	 */
	public function filterGroup()
	{
		// open group
		$this->filter_stack[] = $this->filters;
		$this->filter_stack[] = 'and'; // nesting type

		$this->filters = array();
		return $this;
	}

	/**
	 * Open a filter group that will be OR'd on the query
	 */
	public function orFilterGroup()
	{
		$this->filter_stack[] = $this->filters;
		$this->filter_stack[] = 'or'; // nesting type

		$this->filters = array();
		return $this;
	}

	/**
	 * Close a (or)filterGroup
	 */
	public function endFilterGroup()
	{
		// end group
		$nested = $this->filters;
		$prefix = array_pop($this->filter_stack);

		$this->filters = array_pop($this->filter_stack);
		$this->filters[] = array($prefix, $nested);

		return $this;
	}

	/**
	 * Check if the filter groups have been open and closed correctly
	 */
	protected function filterStackIsEmpty()
	{
		return count($this->filter_stack) == 0;
	}

	/**
	 * Add ordering to the query
	 */
	public function order($property, $direction = '')
	{
		$this->orders[] = array($property, $direction);
		return $this;
	}


	/**
	 * Limit the result set.
	 *
	 * @param int Number of elements to limit to
	 * @return $this
	 */
	public function limit($n = NULL)
	{
		$this->limit = $n;
		return $this;
	}

	/**
	 * Offset the result set.
	 *
	 * @param int Number of elements to offset to
	 * @return $this
	 */
	public function offset($n)
	{
		$this->offset = $n;
		return $this;
	}

	/**
	 * Only select and return a subset of fields.
	 */
	public function fields()
	{
		$this->fields = array_merge($this->fields, func_get_args());

		return $this;
	}

	/**
	 * Add relationships to the query
	 */
	public function with()
	{
		$this->addToWith($this->root, func_get_args());

		return $this;
	}

	/**
	 * Add the new `with()` relationships in a normalized format.
	 */
	private function addToWith($parent, $relateds)
	{
		foreach ($relateds as $child => $grandchildren)
		{
			if (is_numeric($child))
			{
				if (is_array($grandchildren))
				{
					// array(parent => array(...))
					$this->addToWith($parent, $grandchildren);
				}
				else
				{
					// array(parent => grandchild)
					$this->relate($parent, $grandchildren);
				}
			}
			else
			{
				$child = $this->relate($parent, $child);

				if (is_array($grandchildren))
				{
					// child => array(parent => array(...))
					$this->addToWith($child, $grandchildren);
				}
				else
				{
					// child => array(parent => grandchild)
					$this->relate($child, $grandchildren);
				}
			}
		}
	}

	/**
	 *
	 */
	private function relate($parent, $to_string)
	{
		list($name, $alias) = $this->splitAlias($to_string);

		$child = new Reference($alias);
		$child->connecting_name = $name;

		$this->references->add($child);
		$this->references->connect($parent, $child, $name);

		return $child;
	}

	/**
	 * Get the root model name
	 */
	public function getRootModel()
	{
		return $this->root->model;
	}

	/**
	 * Get the root model name
	 */
	public function getRootAlias()
	{
		return $this->root->alias;
	}

	/**
	 * Get the root model if one was set. Used for lazy- and sub-queries.
	 */
	public function getModelObject()
	{
		return $this->model_object;
	}

	/**
	 * Get the query LIMIT
	 */
	public function getLimit()
	{
		return $this->limit;
	}

	/**
	 * Get the query OFFSET
	 */
	public function getOffset()
	{
		return $this->offset;
	}

	/**
	 * Get the query ORDER
	 */
	public function getOrders()
	{
		return $this->orders;
	}

	/**
	 * Get the query WHERE's
	 */
	public function getFilters()
	{
		return $this->filters;
	}

	/**
	 *
	 */
	public function getSet()
	{
		return $this->set;
	}

	/**
	 *
	 */
	public function getAliasReference($name)
	{
		return $this->references->get($name);
	}

	/**
	 * Get all references
	 */
	public function getReferences()
	{
		return $this->references->all();
	}

	/**
	 * Split up an alias.
	 */
	public function splitAlias($string)
	{
		$string = trim($string);
		$parts = preg_split('/\s+AS\s+/i', $string);

		if ( ! isset($parts[1]))
		{
			return array($string, $string);
		}

		return $parts;
	}

	/**
	 * TODO
	 */
	public function debugQuery()
	{
		return $this->buildSelect()->_compile_select();
	}
}
<?php namespace EllisLab\ExpressionEngine\Service\Model\Query;

use EllisLab\ExpressionEngine\Service\AliasServiceInterface;
use EllisLab\ExpressionEngine\Service\Model\Factory;
use EllisLab\ExpressionEngine\Service\Model\Relationship\RelationshipMeta;

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
class Query {

	const DELETE_BATCH_SIZE = 100;

	private $factory;
	private $alias_service;

	private $db;
	private $graph;

	private $limit = '18446744073709551615'; // 2^64
	private $offset = 0;

	private $model = '';
	private $filters = array();
	private $orders = array();
	private $withs = array();
	private $only_fields = array();

	private $aliases = array();
	private $subqueries = array();
	private $filter_stack = array();
	private $cached_metadata = array();

	/**
	 * Constructor
	 */
	public function __construct(ModelFactory $factory, AliasServiceInterface $alias_service, $model_name)
	{
		$this->model = $model_name;
		$this->factory = $factory;
		$this->alias_service = $alias_service;

		if (function_exists('ee'))
		{
			$this->setConnection(ee()->db); // TODO reset?
		}

		// TODO move this, don't ask for things!
		$this->graph = $factory->getRelationshipGraph();
	}

	/**
	 * Run the query and return a collection.
	 *
	 * @return Collection
	 */
	public function all()
	{
		return $this->getResult()->collection();
	}

	/**
	 * Run the query and get the results, but only return the first.
	 *
	 * @return Model Instance
	 */
	public function first()
	{
		$this->limit(1);
		return $this->getResult()->first();
	}

	/**
	 * Get the query result. This de-aliases the fields, hydrates the models
	 * and hooks up all of the relationships. That's a lot of work, so it's
	 * done in a separate class.
	 */
	protected function getResult()
	{
		$this->buildSelect();

		// Run the query and return
		return new QueryResult(
			$this->factory,
			$this->alias_service,
			$this->db->get()->result_array()
		);
	}

	/**
	 * Build the select query with all of the filters, relationships,
	 * as well as the standard limit, offset, and sort.
	 *
	 * Should be called right before getting the result.
	 */
	private function buildSelect()
	{
		// reset our aliasing
		$this->sql_alias_id = 1;
		$this->sql_alias_ids = array();

		// retrieve root model metadata and relationship info
		$meta = $this->getMeta($this->model);
		$node = $this->graph->getNode($meta->getClass());

		// store the mapping information so we can associate filters
		// on the root model with the correct tables
		$this->storeSqlId($this->model);

		// FROM the root table
		$root_table = $meta->getPrimaryTable();
		$this->db->from("{$root_table} AS {$root_table}_{$this->sql_alias_id}");

		// SELECT all root model fields on all gateways
		$this->selectFields($meta, 'root');
		$this->joinSecondaryTables($meta);

		// JOIN related models that were in "with"
		$node->setModelName($this->model);
		$this->joinGraph($node, $this->withs, $this->sql_alias_id);

		// WHERE filters
		$this->applyFilters($this->filters);

		// ORDER BY something
		$this->applyOrders();

		// LIMIT and OFFSET
		$this->db->limit($this->limit, $this->offset);
	}

	public function delete()
	{
		$parent_meta = $this->getMeta($this->model);

		// Get the parent ids for quick batching
		// queries on all the little children.
		// @todo TODO batch this as well? if no filters?

		$parent_ids = $this
			->onlyFields($this->model.'.'.$parent_meta->getPrimaryKey())
			->all()
			->pluck($parent_meta->getPrimaryKey());

		if (empty($parent_ids))
		{
			return;
		}

		// find the order to delete in so we don't end up with
		// orphans
		list($deleteOrder, $deletePaths) = $this->getDeleteGraph($parent_meta);

		// go through them in the correct order and process our delete
		foreach ($deleteOrder as $name)
		{
			$edges = $deletePaths[$name];
			$from_meta = $this->getMeta($name);

			// recreate the nested with() from our deletePath. Ugh.
			$with = array();
			$with_pointer =& $with;

			while ($edge = array_pop($edges))
			{
				$with_pointer[$edge->name] = array();
				$with_pointer =& $with_pointer[$edge->name];
			}

			$offset = 0;
			$batch_size = self::DELETE_BATCH_SIZE; // TODO change depending on model

			do {
				// grab the delete ids and process in batches
				$delete_ids = $this->factory->get($name)
					->with($with)
					->onlyFields($name.'.'.$from_meta->getPrimaryKey())
					->filter($name.'.'.$parent_meta->getPrimaryKey(), 'IN', $parent_ids)
					->offset($offset)
					->limit($batch_size)
					->all()
					->pluck($from_meta->getPrimaryKey());

				$offset += $batch_size;

				if ( ! count($delete_ids))
				{
					continue;
				}

				/* @pk TODO put this back
				$collection = $this->factory->get($name)
					->filter($name.'.'.$from_meta->getPrimaryKey(), 'IN', $delete_ids)
					->all();

				$collection->triggerEvent('delete');
				*/

				$this->deleteAsLeaf($from_meta, $delete_ids);
			}
			while (count($delete_ids) == $batch_size);

		}

		$this->deleteAsLeaf($parent_meta, $parent_ids);
	}

	/**
	 * Delete the model and its tables, ignoring any relationships
	 * that might exist. This is a utility function for the main
	 * delete which *is* aware of relationships.
	 *
	 * @param MetaCache $meta Metadata cache for the model to delete
	 * @param Int[] $delete_ids Array of ids to remove
	 * @param void
	 */
	private function deleteAsLeaf($meta, $delete_ids)
	{
		$tables = $meta->getTables();

		$this->db
			->where_in($meta->getPrimaryKey(), $delete_ids)
			->delete($tables);
	}

	/**
	 * Given a node to delete, returns all of the other
	 * nodes that need to be deleted in reverse topological
	 * order. This means leaves first and then upwards from
	 * there. Basically a topsort with the results reversed
	 * and edges returned instead of nodes
	 *
	 * For many-to-many, the pivot table entries are removed
	 * before the nodes are. This helps prevent new conflicts
	 * from being created during the batching process.
	 */
	private function getDeleteGraph($meta)
	{
		// get all the edges that need to be removed,
		// where we first remove the child and then the
		// parent. Except many to many where the pivot
		// is cleared.
		$node = $this->graph->getNode($meta->getClass());

		$delete_order = array();
		$roots = array($node);
		$edges_visited = array();
		$paths = array(
			$meta->getName() => array()
		);

		$node->setModelName($meta->getName());

		while ($node = array_shift($roots))
		{
			foreach ($node->getAllOutgoingEdges() as $e)
			{
				$reverse = $e->getInverseOn($this->factory->make($e->model));

				if ( ! isset($reverse))
				{
					throw new \Exception('Could not reverse relationship ' . $e->model.' for '.$e->from.'.');
				}

				$delete_order[] = $e->model;

				$paths[$e->model] = $paths[$node->model];
				$paths[$e->model][] = $reverse;

				$to_node = $this->graph->getNode($e->to_class);
				$to_node->setModelName($e->model);

				$to_node_key = spl_object_hash($to_node);

				if ( ! isset($edges_visited[$to_node_key]))
				{
					$edges_visited[$to_node_key] = 0;
				}

				$edges_visited[$to_node_key]++;

				if ($edges_visited[$to_node_key] == count($to_node->getAllIncomingEdges()))
				{
					$roots[] = $to_node;
				}
			}
		}

		return array(
			array_reverse($delete_order),
			$paths
		);
	}

	private function joinGraph($node, $related, $path)
	{
		$parent_id = trim(strrchr($path, '_'), '_') ?: $path;

		foreach ($related as $name => $children)
		{
			// todo: skip subqueries
			$this->sql_alias_id++;

			// get('ChannelEntries')->with("Authors as Peeps")
			// Peeps is the alias
			// Authors is the relationship name
			// Members is the model
			list($name, $alias) = $this->splitAtAlias($name);

			$edge = $node->getEdgeByName($name);
			$meta = $this->getMeta($edge->model);

			$this->storeSqlId($alias, $name);
			$this->selectFields($meta, $name, $path.'_');

			$from_table = $this->getTable($node->model, $edge->key);
			$to_table = $this->getTable($edge->model, $edge->to_key);

			$to_table_alias = $to_table . '_' . $this->sql_alias_id;
			$from_table_alias = $from_table . '_' . $parent_id;

			// ALL EXCEPT MANY TO MANY
			$this->db->join(
				"{$to_table} AS {$to_table_alias}",
				"{$from_table_alias}.{$edge->key} = {$to_table_alias}.{$edge->to_key}",
				"LEFT OUTER"
			);

			$this->joinSecondaryTables($meta, $to_table);

/*
$this->db->join($relationship_meta->pivot_table . ' AS ' . $relationship_meta->pivot_table . '_' . $node->getId(),
	$relationship_meta->from_table . '_' . $from_id . '.' . $relationship_meta->from_key .
	'=' .
	$relationship_meta->pivot_table . '_' . $node->getId() . '.' . $relationship_meta->pivot_from_key,
	'LEFT OUTER');
$this->db->join($relationship_meta->to_table . ' AS ' . $relationship_meta->to_table . '_' . $node->getId(),
	$relationship_meta->pivot_table . '_' . $node->getId() . '.' . $relationship_meta->pivot_to_key .
	'=' .
	$relationship_meta->to_table . '_' . $node->getId() . '.' . $relationship_meta->to_key,
	'LEFT OUTER');
*/

			// recurse
			if (count($children))
			{
				$child_node = $this->graph->getNode($edge->to_class);
				$child_node->setModelName($edge->model);

				$this->joinGraph($child_node, $children, $path.'_'.$this->sql_alias_id);
			}
		}
	}

	private function selectFields($meta, $relationship_name, $id_path = '')
	{
		// if check if we only need to select a subset
		$this->translateOnlyFields();

		$all_fields = empty($this->only_fields);

		$uniqid = $this->sql_alias_id;

		foreach ($meta->getFields() as $table => $fields)
		{
			$table_alias = $table . '_' . $uniqid;
			$table_path = $id_path . $uniqid . '__' . $relationship_name . '__' . $meta->getName();

			foreach ($fields as $field)
			{
				if ( ! isset($field) || $field == 'fields')
				{
					continue; // TODO channel is weird, the field list is all messed up, returns NULLs
				}

				$select = "{$table_alias}.{$field}";

				if ( ! $all_fields && ! in_array($select, $this->only_fields))
				{
					continue;
				}

				$this->db->select("{$select} AS {$table_path}__{$field}");
			}
		}
	}

	/**
	 * Join the other gateways related to the model represented
	 * by the cached `$meta`.
	 *
	 * @param MetaCache $meta Model meta to delete from
	 * @param String $known_table The table to join on. We expect this
	 *			to be in the query already. Defaults to the first gateway.
	 * @return void
	 */
	private function joinSecondaryTables($meta, $known_table = NULL)
	{
		$join_type = '';

		$tables = $meta->getTables();
		$primary_key = $meta->getPrimaryKey();

		// If they specified a known table then this is a joined model
		// where the to gateway was known from the relationship. In those
		// cases we want the join to be outer left. Otherwise it's the
		// main table of this model, which is the first gateway.
		if (isset($known_table))
		{
			$join_type = 'LEFT OUTER';
		}
		else
		{
			$known_table = current($tables);
		}

		foreach ($tables as $table)
		{
			if ($table != $known_table)
			{
				$table_alias = $table . '_' . $this->sql_alias_id;
				$known_table_alias = $known_table.'_'.$this->sql_alias_id;

				$this->db->join(
					"{$table} AS {$table_alias}",
					"{$known_table_alias}.{$primary_key} = {$table_alias}.{$primary_key}",
					$join_type
				);
			}
		}
	}


	private function getTable($model, $field)
	{
		return $this->getMeta($model)->findTable($field);
	}

	/**
	 * Take Relationship.property and translate it to table.property
	 */
	protected function translateProperty($property)
	{
		// set model-less filters on the main model
		if ( ! strpos($property, '.'))
		{
			$property = $this->model.'.'.$property;
		}

		list($name, $column) = explode('.', $property);
		list($model, $sql_id) = $this->getSqlId($name);

		if ( ! isset($sql_id))
		{
			// table doesn't exist. This usually means the model was not selected
			return NULL;
		}

		$table = $this->getTable($model, $column);

		if ( ! isset($table))
		{
			throw new \Exception("Property {$column} was not found on model {$model}.");
		}

		return "{$table}_{$sql_id}.{$column}";
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
	public function filter($property, $operator, $value = NULL)
	{
		$this->filters[] = array($property, $operator, $value, FALSE);
		return $this;
	}

	public function orFilter($property, $operator, $value = NULL)
	{
		$this->filters[] = array($property, $operator, $value, TRUE);
		return $this;
	}

	public function filterGroup()
	{
		// open group
		$filter_stack[] = $this->filters;
		$filter_stack[] = 'and'; // nesting type

		$this->filters = array();
		return $this;
	}

	public function orFilterGroup()
	{
		$filter_stack[] = $this->filters;
		$filter_stack[] = 'or'; // nesting type

		$this->filters = array();
		return $this;
	}

	public function endFilterGroup()
	{
		// end group
		$nested = $this->filters;
		$prefix = array_pop($filter_stack);

		$this->filters = array_pop($filter_stack);
		$this->filters[] = array($prefix, $nested);

		return $this;
	}

	protected function applyFilters($filters)
	{
		foreach ($filters as $filter_data)
		{
			// it's nested!
			if (count($filter_data) == 2)
			{
				list($prefix, $nested) = $filter_data;

				if ($prefix == 'and')
				{
					ee()->db->start_group();
				}
				else
				{
					ee()->db->or_start_group();
				}

				$this->applyFilters($nested);
				ee()->db->end_group();
			}
			else
			{
				$this->applyFilter(
					$filter_data[0],
					$filter_data[1],
					$filter_data[2],
					$filter_data[3]
				);
			}
		}
	}

	protected function applyFilter($relationship_property, $operator, $value, $or = FALSE)
	{
		if ( ! isset($value))
		{
			$value = $operator;
			$operator = '';
		}

		if ($operator == '==')
		{
			$operator = ''; // CI's query builder defaults to equals
		}

		$table_property = $this->translateProperty($relationship_property);

		if ( ! isset($table_property))
		{
			return;
		}

		if ($or)
		{
			if (strtolower($operator) == 'in')
			{
				$this->db->or_where_in($table_property, (array) $value);
			}
			else
			{
				$this->db->or_where($table_property.' '.$operator, $value);
			}
		}
		else
		{
			if (strtolower($operator) == 'in')
			{
				$this->db->where_in($table_property, (array) $value);
			}
			else
			{
				$this->db->where($table_property.' '.$operator, $value);
			}
		}
	}


	public function order($property, $direction = '')
	{
		$this->orders[] = array($property, $direction);
		return $this;
	}

	protected function applyOrders()
	{
		foreach ($this->orders as $info)
		{
			list($property, $direction) = $info;

			$table_column = $this->translateProperty($property);

			if ( ! isset($table_column))
			{
				return;
			}

			$this->db->order_by($table_column, $direction);
		}
	}

	public function with()
	{
		$relateds = func_get_args();

		$this->withs = $this->addToWith($this->withs, $relateds);

		return $this;
	}

	private function addToWith($withs, $relateds)
	{
		foreach ($relateds as $parent => $children)
		{
			if (is_numeric($parent) && ! is_array($children))
			{
				$withs[$children] = array();
			}
			elseif (is_numeric($parent) && is_array($children) && count($children))
			{
				$withs = $this->addToWith($withs, $children);
			}
			else
			{
				if ( ! isset($withs[$parent]))
				{
					$withs[$parent] = array();
				}

				$withs[$parent] = $this->addToWith($withs[$parent], $children);
			}
		}

		return $withs;
	}

	/**
	 * Count the number of objects that would be returned by this query if it
	 * was run right now.
	 *
	 * @return int Row count
	 */
	public function count()
	{
		// count_all_results() is destructive (it calls _reset_select())
		// so we're going to clone the db before calling it so that
		// we can continue building on the query.  Or, you know, actually
		// get the query's results.
		$db = clone $this->db;
		return $db->count_all_results();
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
	protected function onlyFields()
	{
		$this->only_fields = array_merge(
			$this->only_fields,
			func_get_args()
		);

		return $this;
	}

	/**
	 * Translate limited fields. This is similar to `translateProperty`,
	 * but keeps the old property name in case it's found in a later
	 * gateway.
	 */
	private function translateOnlyFields()
	{
		foreach ($this->only_fields as &$field)
		{
			$result = $this->translateProperty($field);

			if (isset($result))
			{
				$field = $result;
			}
		}
	}


	protected function splitAtAlias($str)
	{
		$str = trim($str);

		if ( ! strpos($str, 'AS'))
		{
			return array($str, $str);
		}

		$parts = preg_split('\s+AS\s+', $str);

		$this->aliases[$parts[1]] = $parts[0];

		return $parts;
	}

	protected function getAlias($aliased)
	{
		if (isset($this->aliases[$aliased]))
		{
			return $this->aliases[$aliased];
		}

		return $aliased;
	}

	/**
	 * We store based on name, because that is what we get
	 * in the filter function.
	 */
	protected function storeSqlId($name, $model = NULL)
	{
		if ( ! isset($model))
		{
			$model = $name;
		}

		$this->sql_alias_ids[$name] = array($model, $this->sql_alias_id);
	}

	protected function getSqlId($name)
	{
		if ( ! array_key_exists($name, $this->sql_alias_ids))
		{
			return NULL;
		}

		return $this->sql_alias_ids[$name];
	}


	private function getMeta($model)
	{
		if ( ! isset($this->cached_metadata[$model]))
		{
			$this->cached_metadata[$model] = new MetaCache(
				$this->alias_service,
				$model
			);
		}

		return $this->cached_metadata[$model];
	}

	public function debugQuery()
	{
		$this->buildSelect();
		return $this->db->_compile_select();
	}

	public function setConnection($db)
	{
		$this->db = $db;
	}

}

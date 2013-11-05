<?php namespace EllisLab\ExpressionEngine\Model\Query;

use EllisLab\ExpressionEngine\Core\Dependencies;
use EllisLab\ExpressionEngine\Model\DataStructure\Tree\QueryTreeNode;

class Query {
	private $di = NULL;

	private $db;
	private $model_name;

	private $limit = '18446744073709551615'; // 2^64
	private $offset = 0;

	private $tables = array();
	private $filters = array();
	private $selected = array();
	private $subqueries = array();

	/**
	 * @var	QueryTreeNode $root	The root of this query's tree of model
	 * 			relationships.  The model we initiated the query against.
	 */
	private $root = NULL;

	private $data = array();

	public function __construct(Dependencies $di, $model)
	{
		$this->di = $di;
		$this->model_name = $model;
		$this->root = new QueryTreeNode($model);

		// @todo Need to be able to inject this for testing.  -Bingham
		$this->db = clone ee()->db; // TODO reset?

		$this->selectFields($this->root);
		$this->addTable($this->getTableName($model));
	}

	/**
	 * Apply a filter
	 *
	 * @param String $key		Modelname.columnname to filter on
	 * @param String $operator	Comparison to perform [==, !=, <, >, <=, >=, IN]
	 * @param Mixed  $value		Value to compare to
	 * @return Query $this
	 *
	 * The third parameter is optional. If it is not given, the == operator is
	 * assumed and the second parameter becomes the value.
	 */
	public function filter($key, $operator, $value = NULL)
	{
		$model_name = strtok($key, '.');

		if ($model_name == $this->model_name)
		{
			$this->applyFilter($key, $operator, $value);
		}
		else
		{
			$this->filters[] = array($key, $operator, $value);
		}

		return $this;
	}

	private function applyFilter($key, $operator, $value)
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

		list($table, $key) = $this->resolveAlias($key);

		if ($operator == 'IN')
		{
			$this->db->where_in($table.'.'.$key, (array) $value);
		}
		else
		{
			$this->db->where($table.'.'.$key.' '.$operator, $value);
		}
	}

	/**
	 * Eager load a relationship
	 *
	 * @param Mixed Any combination of either a direct relationship name or
	 * an array of (parent > child).
	 *
	 * For example:
	 *
	 * get('ChannelEntry')
	 * 	->with(
	 * 		'Channel',
	 * 		array(
	 * 			'Member' => array(
	 * 				array('MemberGroup'=>'Member'),
	 * 				'MemberCustomFields')),
	 * 		array('Categories' => 'CategoryGroup')) 
	 * OR
	 *
	 * get('ChannelEntry')
	 * 	->with(
	 * 		array(
	 * 			'to' => 'Channel',
	 * 			'method' => 'join'
	 * 		)
	 * 		array(
	 * 			'to' => 'Member',
	 * 			'method' => 'subquery',
	 * 			'with'  => array(
	 * 				'to' => array('MemberGroup', 'MemberCustomFields')
	 * 				'method' => 'join'),
	 * 		array('Categories' => array(
	 * 			'CategoryCustomFields', 
	 * 			'CategoryGroup')) 
	 */
	public function with()
	{
		$relationships = func_get_args();

		foreach ($relationships as $relationship)
		{
			$this->buildRelationshipTree($this->root, $relationship);
		}

		foreach($root->getBreadthFirstIterator() as $node)
		{
			$this->buildRelationship($node);
		}

		return $this;
	}

	/**
	 *
	 * @param string	$from_model_name	Must be the name of the *Model* not
	 * 			the relationship.  Relationship names must be parsed into model
	 * 			names before being sent to walkRelationshipTree()	
	 */
	private function buildRelationshipTree(QueryTreeNode $parent, $relationship)
	{
		// An array could be one or two things:
		// 	- We're specifying meta data for this node of the tree
		// 		where meta data could be things like join vs subquery
		// 	- The relationship tree has another level below this one 
		if (is_array($relationship))
		{
			// If the 'to' key exists, then we're specifying meta data, but
			// we could still have another level below this one in the 
			// relationship tree.  So we need to check for a 'with' key.
			if ( isset($relationship['to']))
			{
				// Build the relationship, pass the meta data through.
				$to = $relationship['to'];
				unset($relationship['to']);
				if ( isset ($relationship['with']))
				{
					$with = $relationship['with'];
					unset($relationship['with'];
				}
		
				$parent->add($this->createNode($parent, $to, $relationship));

				// If we have a with key, then recurse.
				if ( isset ($with))
				{
					$this->buildRelationshipTree($to_node, $with);
				}
			}
			// If we don't have a 'to' key, then each element of the
			// array is just a model from => to.
			else
			{
				foreach ($relationship as $from => $to)
				{
					// If the key is numeric, then we have the case of
					// 	'Member' => array(
					// 		'MemberCustomField',
					// 		'MemberGroup
					// 	)
					//
					// 	Where a related model needs multiple child models
					// 	eager loaded.  In that case, the "from" model is
					// 	not the key (which is numeric), but rather the
					// 	from model we were called with last time.  We'll
					// 	recurse and pass the from model with each to
					// 	model.
					if (is_numeric($from))
					{
						$parent->add($this->createNode($parent, $to));
					}
					// Otherwise, if the key is not numeric, then
					// we have the case of 
					// 	'Member' => array(
					// 		'MemberGroup' => array('Member')
					// 	)
					// 	We'll need to build the 'Member' => 'MemberGroup'
					// 	relationship and then recurse into the 
					// 	'MemberGroup' => 'Member' relationship. 
					else
					{
						// 'Member' => 'MemberGroup'	
						$parent->add($this->createNode($parent, $from));
						// 'MemberGroup' => array('Member')
						// where $to might be an array and could result
						// in more recursing.  The call will deal
						// with that.
						$this->buildRelationshipTree($to_node, $to);
					}
				}
			}
			// If we had an array, then we definitely recursed, and the 
			// recursive calls will eventually boil down to a single from
			// and to.  The call with a non-array to will handle the building
			// and that is not this call.
			return;
		}

		// If there's no more tree walking to do, then we have bubbled
		// down to a single edge in the tree (From_Model -> To_Model), build it.
		$parent->add($this->createNode($parent, $relationship));
	}

	/**
	 * Create Node in the Relationship Tree
	 *
	 * Create a node in our relationship tree with the given parent.  In our
	 * relationship tree, nodes are actually the edges in the Relationship graph.
	 * They are named for the relationship, the name of the method used to
	 * retrieve the relationship on the "from" object, and carry a "from" and
	 * "to". 
	 *
	 * @param	QueryTreeNode	$parent	The parent node to this one, represents the edge
	 * 		leading to the attached vertex from which this node (representing an edge)
	 * 		spawns.
	 */
	private function createNode(QueryTreeNode $parent, $relationship_name, array $meta=array())
	{
		$node = new QueryTreeNode($relationship_name);

		if ($parent->isRoot())
		{
			$from_model_name = $parent->getName();
		}
		else
		{
			$from_model_name = $parent->meta->to_model_name;
		}

		$from_model_class = QueryBuilder::getQualifiedClassName($from_model_name);

		$relationship_method = 'get' . $relationship_name;

		if ( ! method_exists($from_model_class, $relationship_method))
		{
			throw new Exception('Undefined relationship from ' . $from_model_name . ' to ' . $relationship_name);
		}

		$from_model = new $from_model_class();
		$relationship_meta = $from_model->$relationship_method();
		$relationship_meta->override($meta);
		$relationship_meta->from_model_name = $from_model_name;

		$node->meta = $relationship_meta;
		return $node;
	}

	private function buildRelationship(QueryTreeNode $node)
	{
		if ($node->meta->method == ModelRelationshipMeta::METHOD_JOIN 
			&& ! $this->hasParentSubquery($node))
		{
			$this->buildJoinRelationship($node);
		}
		elseif ($node->meta->method == ModelRelationshipMeta::METHOD_SUBQUERY)
		{
			$this->buildSubqueryRelationship($node);
		}
	}

	private function buildJoinRelationship(QueryTreeNode $node)
	{
		$relationship_meta = $node->meta;
		$this->selectFields($relationship_meta->to_model_name);
		
		switch ($relationship_meta->type)
		{
			case ModelRelationshipMeta::TYPE_ONE_TO_ONE:
			case ModelRelationshipMeta::TYPE_ONE_TO_MANY:
			case ModelRelationshipMeta::TYPE_MANY_TO_ONE:
				$this->db->join($relationship_meta->getToTable(),
					$relationship_meta->getFromTable() . '.' . $relationship_meta->from_key 
					. '=' . 
					$relationship_meta->getToTable() . '.' . $relationship_meta->to_key);
				break;

			case ModelRelationshipMeta::TYPE_MANY_TO_MANY:
				$this->db->join($relationship_meta->getPivotTable(),
					$relationship_meta->getFromTable() . '.' . $relationship_meta->from_key 
					. '=' . 
					$relationship_meta->getPivotTable() . '.' . $relationship_meta->pivot_from_key);
				$this->db->join($to_table,
					$pivot_table . '.' . $pivot_to_key . '=' . $to_table . '.' . $pivot_to_key);
				break;
		}
	}

	private function buildSubqueryRelationship(QueryTreeNode $node)
	{
		$subquery = new Query($node->meta->to_model_name);
		$subquery->withSubtree($node->getSubtree());

		$this->subqueries[] = array('node' => $node, 'subquery' => $subquery);
	}

	private function hasParentSubquery(QueryTreeNode $node)
	{
		foreach($n = $node; $n !== NULL; $n = $n->getParent())
		{
			// If we encounter a subquery parent with no parent, then that subquery
			// node is the root and we're in a subquery!
			if ($n->meta->method == ModelRelationshipMeta::METHOD_SUBQUERY
				&& $n->getParent() !== NULL)
			{
				return TRUE;
			}
		}

		return FALSE;
	}
	
	/**
	 * Run the query, hydrate the models, and reassemble the relationships
	 *
	 * @return Collection
	 */
	public function all()
	{
		// Run the query
		$result = $this->db->get()->result_array();

		// Build a collection
		$collection = new Collection();
		$model_class = QueryBuilder::getQualifiedClassName($this->model_name);

		// Fill it while also populating the main model
		foreach ($result as $i => $row)
		{
			$collection[] = new $model_class($this->di, $row);
		}

		// And resolve any eager loaded relationships
		foreach ($this->relationships as $resolver)
		{
			$resolver($collection, $result);
		}

		return $collection;
	}

	/**
	 * Need to take aliased results in the form of joined query rows and
	 * build the model tree out of them.
	 */
	private function dealiasResults($result)
	{
		foreach($result as $row)
		{

			// Each row holds field=>value data for the full joined query's
			// tree.  In order to take this flat row data and reconstruct into
			// a tree, the field names of each field=>value pair have been
			// aliased with the path to the correct node (in ids), and the
			// model to which the field belongs.  Each field name looks like
			// this: path__model__fieldName
			//
			// Where path, model and fieldName may have single underscores in
			// them.  The path is a series of underscore separated integers
			// corresponding to the unique ids of nodes in this query's
			// relationship tree.  The tree might look something like this:
			//
			// 				ChannelEntry(1)
			// 			/			|			\
			// 	Channel (2)		Author (3)		Categories (4)
			//						|				|
			// 					MemberGroup (5)	CategoryGroup (6)
			//
			// 	So a field in the CategoryGroup model would be aliased like so:
			//
			// 	1_4_6__CategoryGroup__group_id
			//
			// 	A field in the Author relationship (Member model) might be
			// 	aliased:
			//
			// 	1_3__Member__member_id
			//
			// 	This will allow us to create the models we've pulled and
			// 	correctly reconstruct the tree.
			$row_data = array();
			foreach($row as $name=>$value)
			{
				list($path, $model_name, $field_name) = explode('__', $name);
				if ( ! isset($row_data[$path]))
				{
					$row_data[$path] = array();
				}
				if ( ! isset($row_data[$path]['__model_name']))
				{
					$row_data[$path]['__model_name'] = $model_name;
				}
				$row_data[$path][$field_name] = $value;
			}

			foreach ($row_data as $path=>$model_data)
			{
				if ($this->isModelRoot($path))
				{
					if ($this->modelExists($model_data))
					{
						continue;
					}
					else
					{
						$model_class = QueryBuilder::getQualifiedClassName($model_data['__model_name']);
						$primary_key = $model_class::getMetaData('primary_key');
						$model = new $model_class($model_data);
						$this->results[$model_data[$primary_key]] = $model;
					}
				}
				else
				{
					$parent_model = $this->findModelParent($row_data, $path);
					if ($this->modelExists($model_data, $parent_model))
					{
						continue;
					}
					else
					{
						$model = new $model_class($model_data);
						$parent_model->addRelated($relationship_name, $model);
					}
				}
			}
		}

	}

	private function isModelRoot($path, $model_data)
	{
		if (int($path) === 1)
		{
			return true;
		}

		return false;
	}

	private function modelExists($model_data, $parent=NULL)
	{
		$model_class = QueryBuilder::getQualifiedClassName($model_data['__model_name']);
		$primary_key = $model_class::getMetaData('primary_key');
		if ($parent == NULL)
		{
			if (isset($this->results[$model_data[$primary_key]]))
			{
				return TRUE;
			}
			else
			{
				return FALSE;
			}
		}
	}

	private function getModelNode($path)
	{
		$node_ids = explode('_', $path);
		$id = array_shift($node_ids);
		$node = $this->root;
		while( ! empty($node_ids))
		{
			$id = array_shift($node_ids);
			$node = $node->getChildById($id);
		}

		return $node;
	}

	private function findModelParent($path_data, $child_path)
	{
		$path = substr($child_path, 0, strrpos('_', $child_path));

		$model_data = $path_data[$path];

		$model_name = $model_data['__model_name'];
		$model_class = QueryBuilder::getQualifiedClassName($model_name);
		$primary_key_name = $model_class::getMetaData('primary_key');
		$primary_key = $model_data[$primary_key_name];
		
		if (isset($this->results[$model_name][$primary_key]))
		{

		}


	}

	/**
	 * Run the query, hydrate the models, and reassemble the relationships, but
	 * limit it to just one.
	 *
	 * @return Model Instance
	 */
	public function first()
	{
		$this->limit(1);
		$collection = $this->all();

		if (count($collection))
		{
			return $collection->first();
		}

		return NULL;
	}

	/**
	 * Count the number of objects that would be returned by this query if it
	 * was run right now.
	 *
	 * @return int Row count
	 */
	public function count()
	{
		return $this->db->count_all_results();
	}

	/**
	 * Limit the result set.
	 *
	 * @param int Number of elements to limit to
	 * @return $this
	 */
	public function limit($n = NULL)
	{
		$this->db->limit($n, $this->offset);
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
		$this->db->limit($this->limit, $n);
		$this->offset = $n;
		return $this;
	}

	/**
	 * Add selects for all fields to the query.
	 *
	 * @param String $model Model name to select.
	 */
	private function selectFields(QueryTreeNode $node)
	{
		if ($node->isRoot())
		{
			$relationship_name = 'root';
			$model_name = $node->getName();
		}
		else
		{
			$relationship_name = $node->getName();
			$model_name = $node->to_model_name;
		}

		if (in_array($model_name, $this->selected))
		{
			return;
		}

		$this->selected[] = $model_name;

		$model_class_name = QueryBuilder::getQualifiedClassName($model_name);

		foreach ($model_class_name::getMetaData('entity_names') as $entity_name)
		{
			$entity_class_name = QueryBuilder::getQualifiedClassName($entity_name);

			$table = $entity_class_name::getMetaData('table_name');
			$properties = get_object_vars($entity_class_name);
			foreach ($properties as $property)
			{
				$this->db->select($table . '.' . $property . ' AS ' . $node->getPath() . '__' . $model_name . '__' . $property);
			}
		}
	}

	/**
	 * Add a table to the FROM statement
	 *
	 * @param String $table_name Name of the table to add.
	 */
	private function addTable($table_name)
	{
		if ( ! in_array($table_name, $this->tables))
		{
			$this->db->from($table_name);
			$this->tables[] = $table_name;
		}
	}

	/**
	 * Take a string such as ModelName.field and resolve it to its
	 * proper tablename and key for where queries.
	 *
	 * @param String $name Model specific accessor
	 * @return array [table, key]
	 */
	private function resolveAlias($alias)
	{
		$model_name = strtok($alias, '.');
		$key		= strtok('.');

		if ($key == FALSE)
		{
			$key = $this->getPrimaryKey($model_name);
		}

		$table = $this->getTableName($model_name, $key);

		if ($table == '')
		{
			throw new QueryException('Unknown field name in filter: '. $key);
		}

		return array($table, $key);
	}

	/**
	 * Retreive the primary key for a given model.
	 *
	 * @param String $model_name The name of the model
	 * @return array [table, key_name]
	 */
	private function getPrimaryKey($model_name)
	{
		$model = QueryBuilder::getQualifiedClassName($model_name);
		return $model::getMetaData('primary_key');
	}

	/**
	 * Retrieve the table name for a given Model and key. If more than one entity
	 * has the key, it will return the first.
	 *
	 * @param String $model_name The name of the model
	 * @param String $key The name of the property [optional, defaults to primary key]
	 * @return String Table name
	 */
	private function getTableName($model_name, $key = NULL)
	{
		if ( ! isset($key))
		{
			$key = $this->getPrimaryKey($model_name);
		}

		$model = QueryBuilder::getQualifiedClassName($model_name);

		$known_keys = $model::getMetaData('key_map');

		if (isset($known_keys[$key]))
		{
			$key_entity = QueryBuilder::getQualifiedClassName($known_keys[$key]);
			return $key_entity::getMetaData('table_name');
		}

		// If it's not a key, we need to loop. Technically it could be on more
		// than one entity - we only return the first one.
		$entity_names = $model::getMetadata('entity_names');

		foreach ($entity_names as $entity)
		{
			$entity = QueryBuilder::getQualifiedClassName($entity);
			if (property_exists($entity, $key))
			{
				return $entity::getMetaData('table_name');
			}
		}

		return NULL;
	}
}

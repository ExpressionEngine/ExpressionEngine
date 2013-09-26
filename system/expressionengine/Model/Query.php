<?php namespace EllisLab\ExpressionEngine\Model;

class Query {

	private $db;
	private $model_name;
	private $tables = array();
	private $selected = array();
	private $relationships = array();

	public function __construct($model)
	{
		$this->model_name = $model;

		$this->db = ee()->db; // TODO clone and reset?
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

		$this->addTable($table);
		return $this;
	}

	/**
	 * Apply a relation
	 *
	 * @param Mixed
	 * @param String $operator	Comparison to perform [==, !=, <, >, <=, >=, IN]
	 * @param Mixed  $value		Value to compare to
	 *
	 * The third parameter is optional. If it is not given, the == operator is
	 * assumed and the second parameter becomes the value.
	 */
	// qb->with('Channel', array('Member' => ))
	public function with()
	{
		$related_models = func_get_args();

		foreach ($related_models as $to_model)
		{
			$this->queryRelation($this->model_name, $to_model);
		}

		return $this;
	}


	/**
	 * Run the query, hydrate the models, and reassemble the relationships
	 *
	 * @return Collection
	 */
	public function all()
	{
		$model_name = $this->model_name;

		$this->selectFields($model_name);

		$result = $this->db->get()->result_array();
		$collection = new Collection;

		foreach ($result as $row)
		{
			$collection[] = new $model_name($row);
		}

		return $result;
	}

	public function first()
	{
		// @todo add limit
	}


	/**
	 *
	 */
	private function queryRelation($from_model, $to_relation_name)
	{
		// recurse for arrays
		if (is_array($to_relation_name))
		{
			foreach ($to_relation_name as $from => $to)
			{
				return $this->queryRelation($from, $to);
			}
		}

		// TODO select the values on each table
		$this->selectFields($from_model);

		// find a path to the model
		$relationships = $this->getRelationships($from_model, $to_relation_name);

		// Add a join to the query
		foreach ($relationships as $resolve_relationship)
		{
			$resolve_relationship();
		}
	}

	/**
	 * Find a path from one model to another model through their entity
	 * relationships.
	 *
	 * @param String $from_model Model name of the model that defines the relationship
	 * @param String $to_model   Model name of the model to relate to
	 * @return Array [
	 *     from_table: Table name to join on
	 *     from_key:   Key name to join on
	 *     to_table:   Table name to join to
	 *     to_key:     Key name to join to
	 * ]
	 */
	private function getRelationships($from_model_name, $to_model_name)
	{
		$relationship_method = 'get'.$to_model_name;

		$from_model = QueryBuilder::getQualifiedClassName($from_model_name);

		if ( ! method_exists($from_model, $relationship_method))
		{
			throw new UndefinedRelationshipException($to_model);
		}

		$from_model_obj = new $from_model();
		$from_relation = $from_model_obj->$relationship_method();

		$from_entities = $from_model::getMetaData('key_map');

		$from_entity = $from_entities[$from_relation['key']];
		$from_entity = QueryBuilder::getQualifiedClassName($from_entity);

		$from_entity_relations = $from_entity::getMetaData('related_entities');
		$to_entity_relations = $from_entity_relations[$from_relation['key']];

		if ( ! is_array(current($to_entity_relations)))
		{
			$to_entity_relations = array($to_entity_relations);
		}

		$relationships = array();

		foreach ($to_entity_relations as $to_relation)
		{
			$to_entity = $to_relation['entity'];
			$to_entity = QueryBuilder::getQualifiedClassName($to_entity);
			$type = $from_relation['type'];


			$relationships[] = $this->manyToOneRelationship(array(
				'to_model'	 => $from_relation['model_name'],
				'from_table' => $from_entity::getMetaData('table_name'),
				'from_key'	 => $from_relation['key'],
				'to_table'	 => $to_entity::getMetaData('table_name'),
				'to_key'	 => $to_relation['key'],
			));
		}

		return $relationships;
	}

	/**
	 * Adds the proper query and then returns a function that can resolve the
	 * relationship.
	 */
	private function manyToOneRelationship($info)
	{
		$to_model = $info['to_model'];
		$this->selectFields($to_model);

		$this->db->join(
			$info['to_table'],
			$info['from_table'].'.'.$info['from_key'].'='.$info['to_table'].'.'.$info['to_key']
		);

		return function($result_model, $query_result_row) use ($to_model) {
			return new $to_model($query_result);
		};
	}



	/**
	 * Add selects for all fields to the query.
	 *
	 * @param String $model Model name to select.
	 */
	private function selectFields($model_name)
	{
		if (in_array($model_name, $this->selected))
		{
			return;
		}

		$this->selected[] = $model_name;

		$model = QueryBuilder::getQualifiedClassName($model_name);

		foreach ($model::getMetaData('entity_names') as $entity)
		{
			$entity = QueryBuilder::getQualifiedClassName($entity);
			$table = $entity::getMetaData('table_name');

			foreach (array_keys(get_class_vars($entity)) as $property)
			{
				$this->db->select($table.'.'.$property.' AS '.$this->prefixField($property, $model_name));
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
	 * Prefix a column name if it belongs to another model. This lets us
	 * correclty populate everything at the end.
	 *
	 * @param String $key   Name of the column
	 * @param String $model Name of the model that owns the key
	 * @return String Prefixed key
	 */
	private function prefixField($key, $model)
	{
		return $model.'__'.$key;
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
		// If we only have a model name, then we use the primary key
		if (strpos($alias, '.') === FALSE)
		{
			$model_name = $alias;
			$key = $this->getPrimaryKey($model_name);
		}
		else
		{
			list($model_name, $key) = explode('.', $alias);
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
	 * Retreive the table name for a given Model and key. If more than one entity
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

		$table = '';
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
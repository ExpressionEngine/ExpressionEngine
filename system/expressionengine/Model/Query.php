<?php namespace EllisLab\ExpressionEngine\Model;

class Query {

	private $db;
	private $model_name;
	private $selected = array();

	public function __construct($model)
	{
		$this->model_name = $model;

		$this->db = ee()->db; // TODO clone and reset?
	}

	/**
	 * Apply a filter
	 *
	 * @param String $key		Modelname.columnname to filter on
	 * @param String $operator	Comparison to perform [==, !=, <, >, <=, >=, IN]
	 * @param Mixed  $value		Value to compare to
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

		list($table, $key) = $this->resolveAlias($key);

		if ($operator == 'IN')
		{
			$this->db->where_in($table.'.'.$key, (array) $value);
		}
		else
		{
			$this->db->where($table.'.'.$key.' '.$operator, $value);
		}

		$this->db->from($table);
		return $this;
	}

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

	public function run()
	{
		$this->selectFields($this->model_name);

		$result = $this->db->get()->result_array();



		var_dump($result);
	}

	/**
	 *
	 */
	private function queryRelation($from_model, $to_model)
	{
		// recurse for arrays
		if (is_array($to_model))
		{
			foreach ($to_model as $from => $to)
			{
				return $this->queryRelation($from, $to);
			}
		}

		// find a path to the model
		$paths = $this->findRelatedPath($from_model, $to_model);

		// TODO select the values on each table
		$this->selectFields($from_model);
		$this->selectFields($to_model);

		// Add a join to the query
		foreach ($paths as $path)
		{
			$this->db->join(
				$path['to_table'],
				$path['from_table'].'.'.$path['from_key'].'='.$path['to_table'].'.'.$path['to_key']
			);
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
	private function findRelatedPath($from_model_name, $to_model_name)
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

		$related_paths = array();

		foreach ($to_entity_relations as $to_relation)
		{
			$to_entity = $to_relation['entity'];
			$to_entity = QueryBuilder::getQualifiedClassName($to_entity);

			$related_paths[] = array(
				'from_table' => $from_entity::getMetaData('table_name'),
				'from_key' => $from_relation['key'],
				'to_table' => $to_entity::getMetaData('table_name'),
				'to_key' => $to_relation['key'],
			);
		}

		return $related_paths;
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
	private function resolveAlias($name)
	{
		if (strpos($name, '.') !== FALSE)
		{
			list($model_name, $key) = explode('.', $name);
			$model = QueryBuilder::getQualifiedClassName($model);
		}
		else
		{
			$model_name = $name;
			$model = QueryBuilder::getQualifiedClassName($name);
			$key = $model::getMetaData('primary_key');
		}

		$table = '';
		$known_keys = $model::getMetaData('key_map');

		if (isset($known_keys[$key]))
		{
			$key_entity = QueryBuilder::getQualifiedClassName($known_keys[$key]);
			$table = $key_entity::getMetaData('table_name');
		}
		else
		{
			$entities = $model::getEntities();

			// find the right table
			foreach ($entities as $entity)
			{
				if (property_exists($entity, $key))
				{
					$table = $entity::getMetaData('table_name');
				}
			}
		}

		if ($table == '')
		{
			throw new QueryException('Unknown field name in filter: '. $key);
		}

		return array($table, $key);
	}
}
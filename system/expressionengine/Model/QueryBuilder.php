<?php



class QueryBuilder {

	public function __construct($model_name, $ids = NULL)
	{
		$query = new Query($model_name);

		if (isset($ids))
		{
			if (is_array($ids))
			{
				$query->filter($model_name, 'IN', $ids);
			}
			else
			{
				$query->filter($model_name, $ids);
			}
		}

		return $query;
	}
}



class Query {

	private $db;
	private $model;

	public function __construct($model)
	{
		$this->db = ee()->db; // TODO clone and reset?
		$this->model = $model;
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
			$operator = '==';
		}

		list($table, $key) = $this->resolve_alias($key);

		if ($operator == 'IN')
		{
			$this->db->where_in($table.'.'.$key, (array) $value);
		}
		else
		{
			$this->db->where($table.'.'.$key.' '.$operator, $value);
		}

	}

	// qb->with('Channel', array('Member' => ))
	public function with()
	{
		$related_models = func_get_args();

		foreach ($related_models as $to_model)
		{
			$this->query_relation($this->model, $to_model);
		}
	}

	/**
	 *
	 */
	private function query_relation($from_model, $to_model)
	{
		// recurse for arrays
		if (is_array($to_model))
		{
			foreach ($to_model as $from => $to)
			{
				return $this->query_relation($from, $to);
			}
		}

		// find a path to the model
		$paths = $this->find_related_path($from_model, $to_model);

		// add it to the query
		// TODO select the values on

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
	private function find_related_path($from_model, $to_model)
	{
		$from_related = $from_model::getRelationshipInfo();
		$from_relation = $from_related[$to_model];

		$from_entities = $from_model::getEntities();
		$to_entities = $to_model::getEntities();

		$from_entity = $from_entities[$from_relation['entity']];
		$from_entity_relations = $from_entity::getRelationshipInfo();

		// [key, entity]
		$to_entity_relations = $to_entities[$from_entity_relations[$from_relation[$key]]];

		if ( ! is_array($to_entity_relations))
		{
			$to_entity_relations = array($to_entity_relations);
		}

		$related_paths = array();

		foreach ($to_entity_relations as $to_relation)
		{
			$to_entity = $to_relation['entity'];

			$related_paths[] = array(
				'from_table' => $from_entity::getTableName(),
				'from_key' => $this->prefix_field($from_relation['key'], $from_model),
				'to_table' => $to_entity::getTableName(),
				'to_key' => $this->prefix_field($to_relation['key'], $to_model)
			);
		}

		return $related_paths;
	}

	/**
	 * Add selects for all fields to the query.
	 *
	 * @param String $model Model name to select.
	 */
	private function select_fields($model)
	{
		foreach ($model::getEntities() as $entity)
		{
			$table = $entity::getTableName();

			if ($model == $this->model)
			{
				$this->db->select($table.'.*');
			}
			else
			{
				foreach (array_keys(get_class_vars($entity)) as $property)
				{
					$this->db->select($table.'.'.$this->prefix_field($property));
				}
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
	private function prefix_field($key, $model)
	{
		if ($model == $this->model)
		{
			return $key;
		}

		return $model.'__'.$key;
	}

	/**
	 * Take a string such as ModelName.field and resolve it to its
	 * proper tablename and key.
	 *
	 * @param String $name Model specific accessor
	 * @return array [table, key]
	 */
	private function resolve_alias($name)
	{
		if (strpos($name, '.') === FALSE)
		{
			$model = $name;
			$key = $model::getIdName(); // TODO need this friend access in model :(
		}
		else
		{
			list($model, $key) = explode('.', $name);
		}

		$table = '';
		$entities = $model::getEntities();

		// find the right table
		foreach ($entities as $entity)
		{
			if (property_exists($entity, $key))
			{
				$table = $entity::getTableName();
			}
		}

		if ($table == '')
		{
			throw new QueryException('Unknown field name in filter: '. $key);
		}

		// prefix the key so we can separate the data later.
		$key = $this->prefix_field($key);

		return array($table, $key);
	}

}

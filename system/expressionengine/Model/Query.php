<?php namespace EllisLab\ExpressionEngine\Model;

class Query {

	private $db;
	private $model_name;
	private $tables = array();
	private $filters = array();
	private $selected = array();
	private $relationships = array();

	public function __construct($model)
	{
		$this->model_name = $model;

		$this->db = clone ee()->db; // TODO reset?

		$this->selectFields($model);
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

	/**
	 * Eager load a relationship
	 *
	 * @param Mixed Any combination of either a direct relationship name or
	 * an array of (parent > child).
	 *
	 * For example:
	 * get('ChannelEntry')->with('Categories', array('Member' => 'MemberGroup'))
	 *
	 */
	public function with()
	{
		$related_models = func_get_args();

		foreach ($related_models as $to_model)
		{
			$this->buildRelated($this->model_name, $to_model);
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
		// Run the query
		$result = $this->db->get()->result_array();

		// Build a collection
		$collection = new Collection();
		$model_class = QueryBuilder::getQualifiedClassName($this->model_name);

		// Fill it while also populating the main model
		foreach ($result as $i => $row)
		{
			$collection[] = new $model_class($row);
		}

		// And resolve any eager loaded relationships
		foreach ($this->relationships as $resolver)
		{
			$resolver($collection, $result);
		}

		return $collection;
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

	public function limit($n)
	{
		$this->db->limit($n);
	}

	public function offset($n)
	{
		$this->db->offset($n);
	}

	public function getFilters()
	{
		return $this->filters;
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
	 *
	 */
	private function buildRelated($from_model, $related_name)
	{
		// recurse for arrays
		if (is_array($related_name))
		{
			foreach ($related_name as $from => $to)
			{
				return $this->buildRelated($from, $to);
			}
		}

		// Select the fields from all associated entities
		$this->selectFields($from_model);

		// Set up the relationships
		$this->populateRelationships($from_model, $related_name);
	}

	/**
	 * Force an eager load on all of the relationships that were specified
	 * in the with clause.
	 *
	 * @param String $from_model Model name of the model that defines the relationship
	 * @param String $to_model   Model name of the model to relate to
	 * @return void
	 */
	private function populateRelationships($from_model_name, $related_name)
	{
		$from_model = QueryBuilder::getQualifiedClassName($from_model_name);

		$relationship_method = 'get'.$related_name;

		if ( ! method_exists($from_model, $relationship_method))
		{
			throw new UndefinedRelationshipException($to_model);
		}

		$from_model_obj = new $from_model();
		$relationship = $from_model_obj->$relationship_method();

		foreach ($relationship->buildRelationships($this, $this->db) as $relationship_resolver)
		{
			$this->relationships[] = $relationship_resolver;
		}
	}

	/**
	 * Add selects for all fields to the query.
	 *
	 * @param String $model Model name to select.
	 *
	 * FRIEND of Relationships. Do not use!
	 */
	public function selectFields($model_name)
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
			$this->db->select($table.'.*');
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
<?php
namespace EllisLab\ExpressionEngine\Model;


class Relationship {

	private $type;
	private $from_model;
	private $to_model_name;
	private $to_model_class;

	private $link = array();

	public function __construct($from_model, $to_model_name, $type)
	{
		$this->from_model = $from_model;
		$this->to_model_name = $to_model_name;
		$this->to_model_class = QueryBuilder::getQualifiedClassName($to_model_name);

		// dash-words to CamelCase
		$this->type = str_replace(' ', '', ucwords(str_replace('-', ' ', $type)));
	}

	/**
	 * Set up an eager loading relationship
	 *
	 * @return $this
	 */
	public function eagerLoad($from_key, $to_key)
	{
		$from_entity = $this->findEntityForFromKey($from_key);

		$this->link = array(
			'from_key' => $from_key,
			'to_key'   => $to_key,
			'from_entity' => $from_entity
		);

		return $this;
	}

	/**
	 * Setup and run a lazy loading relationship
	 *
	 * @return Model|Collection Related data
	 */
	public function lazyLoad($from_key, $to_key)
	{
		$to_name = $this->to_model_name;

		$query = new Query($to_name);
		$query->filter($to_name.'.'.$to_key, $this->from_model->$from_key);

		if (substr($this->type, -3) == 'One')
		{
			$result = $query->first();
		}
		else
		{
			$result = $query->all();
		}

		// Remember it for future access
		$this->from_model->setRelated($to_name, $result);
		return $result;
	}

	/**
	 * Build the relationship resolution methods
	 *
	 * @param Query $query query object
	 * @param DB $db database object
	 * @return Array<Closure> List of closures that will each resolve a relationship
	 */
	public function buildRelationships($query, $db)
	{
		if (empty($this->link))
		{
			throw new Exception('No Linked Relationships');
		}

		$resolvers = array();

		foreach ($this->findRelatedEntities() as $relation)
		{
			$buildRelationship = 'build'.$this->type;

			$resolvers[] = $this->$buildRelationship($relation, $query, $db);
		}

		return $resolvers;
	}

	/**
	 * Join an given entity onto the main entity for this model.
	 *
	 * @param String $to_entity Fully qualified name of the entity to join
	 * @param DB $db database object
	 * @return void
	 */
	private function joinEntity($to_entity, $db)
	{
		$from_entity = $this->link['from_entity'];

		$to_table = $to_entity::getMetaData('table_name');
		$from_table = $from_entity::getMetaData('table_name');

		$db->join(
			$to_table,
			$from_table.'.'.$this->link['from_key'].'='.$to_table.'.'.$this->link['to_key']
		);
	}

	/**
	 * Create a one-to-one relationship
	 *
	 * Sets up the relationship in the context of the current query and then
	 * returns a resolving function that will be called after the query has
	 * been run.
	 *
	 * @param Array $relation Related entity information [entity => ..., key => ...]
	 * @param Query $query query object
	 * @param DB $db database object
	 * @return Closure
	 */
	private function buildOneToOne($relation, $query, $db)
	{
		return $this->buildManyToOne($relation, $query, $db);
	}

	/**
	 * Create a many-to-one relationship
	 *
	 * Sets up the relationship in the context of the current query and then
	 * returns a resolving function that will be called after the query has
	 * been run.
	 *
	 * @param Array $relation Related entity information [entity => ..., key => ...]
	 * @param Query $query query object
	 * @param DB $db database object
	 * @return Closure
	 */
	private function buildManyToOne($relation, $query, $db)
	{
		$to_model_name = $this->to_model_name;
		$to_model_class = $this->to_model_class;

		$query->selectFields($to_model_name);
		$this->joinEntity($relation['entity'], $db);

		// Return a function that resolves the relationship
		return function($collection, $query_result) use ($to_model_name, $to_model_class)
		{
			foreach ($collection as $i => $model)
			{
				$model->setRelated($to_model_name, new $to_model_class($query_result[$i]));
			}
		};
	}

	/**
	 * Create a one-to-many relationship
	 *
	 * Sets up the relationship in the context of the current query and then
	 * returns a resolving function that will be called after the query has
	 * been run.
	 *
	 * @param Array $relation Related entity information [entity => ..., key => ...]
	 * @param Query $query query object
	 * @param DB $db database object
	 * @return Closure
	 */
	private function buildOneToMany($relation, $query, $db)
	{
		$link = $this->link;
		$to_model = $this->to_model_name;

		// Return a function that resolves the relationship
		return function($collection, $query_result) use ($query, $link, $to_model)
		{
			$from_key = $link['from_key'];
			$to_key = $link['to_key'];

			// run a subquery on the ids we've received
			$new_query = new Query($to_model);
			$new_query->filter($to_model.'.'.$to_key, 'IN', $collection->getIds());

			foreach ($query->getFilters() as $filter)
			{
				call_user_func_array(array($new_query, 'filter'), $filter);
			}

			$related_models = $new_query->all();

			// Create a map of the foreign key id => related objects
			$result_map = array();

			foreach ($related_models as $model)
			{
				if ( ! array_key_exists($model->$to_key, $result_map))
				{
					$result_map[$model->$to_key] = new Collection();
				}

				$result_map[$model->$to_key][] = $model;
			}

			// Add the relationships to the result collection
			foreach ($collection as $i => $model)
			{
				// If our result map does not include this collection element,
				// it means that they applied a restricting filter onto the
				// children, so we will remove empty parents.
				// There is room for optimization here, but it depends on the
				// specific filters they are applying.

				if (array_key_exists($model->$from_key, $result_map))
				{
					$model->setRelated($to_model, $result_map[$model->$from_key]);
				}
				else
				{
					unset($collection[$i]);
				}
			}
		};
	}

	/**
	 * Find the entity that holds this model's from key
	 *
	 * @return String Fully qualified class name of the entity
	 */
	private function findEntityForFromKey($from_key)
	{
		$from_entities = $this->from_model->getMetaData('key_map');
		$from_entity = $from_entities[$from_key];

		return QueryBuilder::getQualifiedClassName($from_entity);
	}

	/**
	 * Grab the related_entities information from the current entity
	 * and namespace the qualified class names.
	 *
	 * @return Array List of related entities [entity => '...', key => '...']
	 */
	private function findRelatedEntities()
	{
		$from_entity = $this->link['from_entity'];

		$from_related = $from_entity::getMetaData('related_entities');
		$to_related = $from_related[$this->link['from_key']];

		if ( ! is_array(current($to_related)))
		{
			$to_related = array($to_related);
		}

		foreach ($to_related as &$relation)
		{
			$relation['entity'] = QueryBuilder::getQualifiedClassName($relation['entity']);
		}

		return $to_related;
	}
}
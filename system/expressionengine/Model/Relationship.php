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

	public function buildRelationships($query, $db)
	{
		if (empty($this->link))
		{
			throw new Exception('No Linked Relationships');
		}

		$resolvers = array();
		$relations = $this->findRelatedEntities();

		foreach ($relations as $relation)
		{
			$buildRelationship = 'build'.$this->type;

			$resolvers[] = $this->$buildRelationship($relation, $query, $db);
		}

		return $resolvers;
	}

	private function buildManyToOne($relation, $query, $db)
	{
		$from_entity = $this->link['from_entity'];
		$to_entity = $relation['entity'];

		$to_table = $to_entity::getMetaData('table_name');
		$from_table = $from_entity::getMetaData('table_name');

		$to_model_name = $this->to_model_name;
		$to_model_class = $this->to_model_class;

		$query->selectFields($to_model_name);
		$db->join(
			$to_table,
			$from_table.'.'.$this->link['from_key'].'='.$to_table.'.'.$this->link['to_key']
		);

		// Return a function that resolves the relationship
		return function($collection, $query_result) use ($to_model_name, $to_model_class)
		{
			foreach ($collection as $i => $model)
			{
				$model->setRelationship($to_model_name, new $to_model_class($query_result[$i]));
			}
		};
	}

	private function buildOneToMany($relation, $query, $db)
	{
		$link = $this->link;
		$to_model = $this->to_model_name;

		// Return a function that resolves the relationship
		return function($collection, $query_result) use ($query, $link, $to_model)
		{
			$from_key = $link['from_key'];
			$to_key = $link['to_key'];

			$new_query = new Query($to_model);
			$new_query->filter($to_model.'.'.$to_key, 'IN', $collection->getIds());

			foreach ($query->getFilters($to_model) as $filter)
			{
				call_user_func_array(array($new_query, 'filter'), $filter);
			}

			// run the query and build a map to our parent foreign keys
			$related_models = $new_query->all();

			$result_map = array();

			foreach ($related_models as $model)
			{
				if ( ! array_key_exists($model->$to_key, $result_map))
				{
					$result_map[$model->$to_key] = new Collection();
				}

				$result_map[$model->$to_key][] = $model;
			}

			foreach ($collection as $i => $model)
			{
				// If our result map does not include this collection element,
				// it means that they applied a restricting filter onto the
				// children, so we will remove empty parents.
				// There is room for optimization here, but it depends on the
				// specific filters they are applying.

				if (array_key_exists($model->$from_key, $result_map))
				{
					$model->setRelationship($to_model, $result_map[$model->$from_key]);
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
	 */
	private function findEntityForFromKey($from_key)
	{
		$from_entities = $this->from_model->getMetaData('key_map');
		$from_entity = $from_entities[$from_key];

		return QueryBuilder::getQualifiedClassName($from_entity);
	}

	private function findRelatedEntities()
	{
		$from_entity = $this->link['from_entity'];

		$from_entity_relations = $from_entity::getMetaData('related_entities');
		$to_entity_relations = $from_entity_relations[$this->link['from_key']];

		if ( ! is_array(current($to_entity_relations)))
		{
			$to_entity_relations = array($to_entity_relations);
		}

		foreach ($to_entity_relations as &$relation)
		{
			$relation['entity'] = QueryBuilder::getQualifiedClassName($relation['entity']);
		}

		return $to_entity_relations;
	}
}
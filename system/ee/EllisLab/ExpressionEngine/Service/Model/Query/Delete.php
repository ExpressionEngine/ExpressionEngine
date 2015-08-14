<?php

namespace EllisLab\ExpressionEngine\Service\Model\Query;

use EllisLab\ExpressionEngine\Service\Model\Relation\BelongsTo;

/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2014, EllisLab, Inc.
 * @license		https://ellislab.com/expressionengine/user-guide/license.html
 * @link		http://ellislab.com
 * @since		Version 3.0
 * @filesource
 */

// ------------------------------------------------------------------------

/**
 * ExpressionEngine Delete Query
 *
 * @package		ExpressionEngine
 * @subpackage	Model
 * @category	Service
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */
class Delete extends Query {

	const DELETE_BATCH_SIZE = 100;

	public function run()
	{
		$builder  = $this->builder;
		$from     = $this->builder->getFrom();
		$frontend = $builder->getFrontend();
		$from_pk  = $this->store->getMetaDataReader($from)->getPrimaryKey();

		$parent_ids = $this->getParentIds($from, $from_pk);

		if ( ! count($parent_ids))
		{
			return;
		}

		$delete_list = $this->getDeleteList($from);

		foreach ($delete_list as $model => $withs)
		{
			$offset		= 0;
			$batch_size = self::DELETE_BATCH_SIZE; // TODO change depending on model?

			// TODO yuck. The relations have this info more correctly
			// in their to and from keys. store that instead.
			$to_meta = $this->store->getMetaDataReader($model);
			$to_pk = $to_meta->getPrimaryKey();
			$events = $to_meta->getEvents();

			$has_delete_event = (
				in_array('beforeDelete', $events) ||
				in_array('afterDelete', $events)
			 );

			 $basic_query = $builder
				 ->getFrontend()
				 ->get($model)
				 ->filter("{$from}.{$from_pk}", 'IN', $parent_ids);

			 // expensive recursion fallback
			 if ($withs instanceOf \Closure)
			 {
				 $delete_collection = $withs($basic_query);
				 $this->deleteCollection($delete_collection, $to_meta);
				 continue;
			 }


			// TODO optimize further for on-db deletes
			do {
				$fetch_query = clone $basic_query;

				$fetch_query
					->with($withs)
					->offset($offset)
					->limit($batch_size);

				if ($has_delete_event)
				{
					$fetch_query->fields("{$model}.*");
				}
				else
				{
					$fetch_query->fields("{$model}.{$to_pk}");
				}

				$delete_models = $fetch_query->all();
				$delete_ids = $this->deleteCollection($delete_models, $to_meta);

				$offset += $batch_size;
			}
			while (count($delete_ids) == $batch_size);
		}
	}

	/**
	 * Trigger a delete on a collection, given a collection and relevant
	 * metadata
	 */
	protected function deleteCollection($collection, $to_meta)
	{
		$delete_ids = $collection->getIds();

		$collection->emit('beforeDelete');

		if ( ! count($delete_ids))
		{
			return array();
		}

		$this->deleteAsLeaf($to_meta, $delete_ids);

		$collection->emit('afterDelete');

		return $delete_ids;
	}

	/**
	 * Delete the model and its tables, ignoring any relationships
	 * that might exist. This is a utility function for the main
	 * delete which *is* aware of relationships.
	 *
	 * @param String $model       Model name to delete from
	 * @param Int[]  $delete_ids  Array of primary key ids to remove
	 */
	protected function deleteAsLeaf($reader, $delete_ids)
	{
		$tables = array_keys($reader->getTables());
		$key = $reader->getPrimaryKey();

		$this->store->rawQuery()
			->where_in($key, $delete_ids)
			->delete($tables);
	}

	/**
	 * Fetch all ids of the parent.
	 *
	 * This way we can restrict all of our filters to just the
	 * ids instead of running a potentially expensive query a
	 * bunch of times.
	 */
	protected function getParentIds($from, $from_pk)
	{
		$builder = clone $this->builder;
		return $builder
			->fields("{$from}.{$from_pk}")
			->all()
			->pluck($from_pk);
	}

	/**
	 * Generate a list for each child model name to delete, that contains all
	 * withs() that lead back to the parent being deleted. These are returned
	 * in the reverse order of how they need to be processed. Think of it as a
	 * reversed topsort.
	 *
	 * Example:
	 * get('Site')->delete()
	 *
	 * Returns:
	 *
	 * array(
	 *    'Template'      => array('TemplateGroup' => array('Site' => array()))
	 *    'TemplateGroup' => array('Site' => array())
	 *    'Site'          => array()
	 * );
	 *
	 * @param String  $from  Model to delete from
	 * @return Array  [name => withs, ...] as described above
	 */
	protected function getDeleteList($from)
	{
		$this->delete_list = array();
		$this->deleteListRecursive($from);
		return array_reverse($this->delete_list);
	}

	/**
	 * Helper to build a delete list. See the `getDeleteList()` method
	 * for details.
	 *
	 * @param String  $parent  Model we're processing
	 * @return Array  [name => withs, ...]
	 */
	protected function deleteListRecursive($parent)
	{
		$results = array();
		$relations = $this->store->getAllRelations($parent);

		if ( ! isset($this->delete_list[$parent]))
		{
			$this->delete_list[$parent] = array();
		}

		foreach ($relations as $name => $relation)
		{
			if ($relation->isWeak())
			{
				$to_model = $relation->getSourceModel();

				$inherit = $this->delete_list[$parent];
				$this->delete_list[$to_model] = $this->weak($relation, $inherit);
				continue;
			}

			$inverse = $relation->getInverse();

			if ($inverse instanceOf BelongsTo)
			{
				$to_name = $inverse->getName();
				$to_model = $relation->getTargetModel();

				if ( ! isset($this->delete_list[$to_model]))
				{
					$this->delete_list[$to_model] = array();
				}

				$inherit = $this->delete_list[$parent];

				if (isset($this->delete_list[$to_model][$to_name]))
				{
					$this->delete_list[$to_model] = $this->recursive($relation, $inherit);
					continue;
				}

				$this->delete_list[$to_model][$to_name] = $inherit;

				$this->deleteListRecursive($to_model);
			}
		}

		return $results;
	}

	/**
	 * Creates a worker function to handle recursive deletes inline with
	 * the rest of the delete flow. Will attempt to return to a bottom-up
	 * deletion if the recursion is broken. If the recursion is not broken
	 * this ends up being a slow process. For something like categories we
	 * can detect this inability directly from the relation, so there's
	 * definitely room for improvement.
	 */
	private function recursive($relation, $withs)
	{
		return function($query) use ($relation, $withs)
		{
			$name = $relation->getName();
			$models = $query->with($withs)->all();

			// TODO ideally we would grab the $model->$name's with just ids
			// and then proceed to call delete on them after our current
			// delete process is done. Unfortunately we're way down the stack
			// at this point and inside a closure that can't see outside it's
			// own four walls in 5.3. So as per usual stupid old versions of
			// PHP just won't let us have nice things.
			foreach ($models as $model)
			{
				$model->$name->delete();
			}

			return $models;
		};
	}

	/**
	 * Creates a worker function to handle weak deletes.
	 */
	private function weak($relation, $withs)
	{
		return function($query) use ($relation, $withs)
		{
			$name = $relation->getName();
			$models = $query->with($withs)->all();

			foreach ($models as $model)
			{
				$relation->drop($model, $model->$name);
			}

			return $models;
		};
	}
}

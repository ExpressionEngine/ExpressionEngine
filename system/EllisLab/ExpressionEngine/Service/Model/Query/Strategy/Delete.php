<?php
namespace EllisLab\ExpressionEngine\Service\Model\Query\Strategy;

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
 * ExpressionEngine Model Query Delete Strategy Class
 *
 * @package		ExpressionEngine
 * @subpackage	Model
 * @category	Service
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */
class Delete extends Strategy {

	const DELETE_BATCH_SIZE = 100;

	/**
	 *
	 */
	public function run()
	{
		return $this->delete();
	}

	/**
	 *
	 */
	protected function delete()
	{
		$root_ids	= $this->getMainIds();
		$root_key	= $this->getPrimaryKey($this->builder->getRootAlias());
		$root_model	= $this->builder->getRootModel();

		if (empty($root_ids))
		{
			return;
		}

		// find the order to delete in so we don't end up with orphans
		list($order, $paths, $reverse_paths) = $this->getDeleteGraph($root_model);

		// go through them in the correct order and process our delete
		foreach ($order as $name)
		{
			$this->disconnect($paths[$name], $reverse_paths[$name][0]);

			// recreate the nested `with()` given the delete path
			// we delete bottom up so the path used are the reversed edges
			$with = $this->buildWithFromEdgeList($reverse_paths[$name]);

			$offset		= 0;
			$batch_size = self::DELETE_BATCH_SIZE; // TODO change depending on model
			$from_key	= $this->factory->getMetaData($name, 'primary_key');

			do {
				// grab the delete ids and process in batches
				$delete_ids = $this->factory
					->get($name)
					->with($with)
					->fields("{$name}.{$from_key}")
					->filter("{$name}.{$root_key}", 'IN', $root_ids)
					->offset($offset)
					->limit($batch_size)
					->all()
					->pluck($from_key);

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

				$this->deleteAsLeaf($name, $delete_ids);
			}
			while (count($delete_ids) == $batch_size);

		}

		$object = $this->builder->getModelObject();

		if (isset($object))
		{
			$object->markAsNew();
		}

		$this->deleteAsLeaf($root_model, $root_ids);
	}

	/**
	 * It's a topsort
	 */
	protected function getDeleteGraph($root_model)
	{
		$this->graph->ensureGraph($root_model);

		$roots = array($root_model);
		$edges_visited = array();

		$delete_order = array();

		$paths = array(
			$root_model => array()
		);

		$reverse_paths = array(
			$root_model => array()
		);

		while ($node = array_shift($roots))
		{
			foreach ($this->graph->getOutgoingEdges($node) as $edge)
			{
				if ( ! $edge->is_parent)
				{
					continue;
				}

				$reverse = $edge->getInverse();

				if ( ! isset($reverse))
				{
					throw new \Exception('Could not reverse relationship ' . $e->model.' for '.$e->from.'.');
				}

				$delete_order[] = $edge->to;

				$paths[$edge->to] = $paths[$node];
				$paths[$edge->to][] = $edge->name;

				$reverse_paths[$edge->to] = $reverse_paths[$node];
				$reverse_paths[$edge->to][] = $reverse;

				$to = $edge->to;

				if ( ! isset($edges_visited[$to]))
				{
					$edges_visited[$to] = 0;
				}

				$edges_visited[$to]++;

				if ($edges_visited[$to] == count($this->graph->getIncomingEdges($to)))
				{
					$roots[] = $to;
				}
			}
		}


		return array(
			array_reverse($delete_order),
			$paths,
			$reverse_paths,
		);
	}

	/**
	 *
	 */
	protected function disconnect($paths, $edge)
	{
		$object = $this->builder->getModelObject();

		if ( ! isset($object))
		{
			return;
		}

		while ($path = array_shift($paths))
		{
			$prev_object = $object;

			if ($object->hasRelated($path))
			{
				$getter = 'get'.$path;
				$object = $object->$getter($path);
			}
		}

		$edge->disconnect($object, $prev_object);
		$object->markAsNew();
	}

	/**
	 *
	 */
	protected function buildWithFromEdgeList($edges)
	{
		$with = array();
		$with_pointer =& $with;

		while ($edge = array_pop($edges))
		{
			$with_pointer[$edge->name] = array();
			$with_pointer =& $with_pointer[$edge->name];
		}

		return $with;
	}

	/**
	 * Delete the model and its tables, ignoring any relationships
	 * that might exist. This is a utility function for the main
	 * delete which *is* aware of relationships.
	 *
	 * @param String $name  Model name to delete from
	 * @param Int[]  $delete_ids  Array of primary key ids to remove
	 */
	protected function deleteAsLeaf($name, $delete_ids)
	{
		$tables = array_keys($this->getModelFields($name));

		$this->db
			->where_in($this->factory->getMetaData($name, 'primary_key'), $delete_ids)
			->delete($tables);
	}
}
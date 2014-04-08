<?php
namespace EllisLab\ExpressionEngine\Model\Relationship;

use EllisLab\ExpressionEngine\Model\Collection;

class RelationshipBag {

	private $relationships = array();

	/**
	 * Contains a relationship of a given name?
	 *
	 * @param String $name         Name of the relationship
	 * @param Int    $primary_key  Optionally look up by primary key
	 * @return Boolean
	 */
	public function has($name, $primary_key = NULL)
	{
		if ( ! array_key_exists($name, $this->relationships))
		{
			return FALSE;
		}

		if (isset($primary_key))
		{
			return in_array(
				$primary_key,
				$this->get($name)->getIds()
			);
		}

		return TRUE;
	}

	/**
	 * Get a relationship
	 *
	 * @param String  $name  Name of the relationship
	 * @return Related Model Collection
	 */
	public function get($name, $as_collection = TRUE)
	{
		if ( ! $this->has($name))
		{
			return NULL;
		}

		$collection = $this->relationships[$name];
		return $as_collection ? $collection : $collection[0];
	}

	/**
	 * Set a relationship
	 *
	 * @param String  $name  Name of the relationship
	 * @param Object  $model Model to add as a related model
	 * @return void
	 */
	public function set($name, $model)
	{
		$this->relationships[$name] = $model;
	}

	/**
	 * Add a relationship
	 *
	 * @param String  $name  Name of the relationship
	 * @param Object  $model Model to add as a related model
	 * @return void
	 */
	public function add($name, $model)
	{
		if ( ! $this->has($name))
		{
			$this->set($name, new Collection());
		}

		$this->relationships[$name][] = $model;
	}
}
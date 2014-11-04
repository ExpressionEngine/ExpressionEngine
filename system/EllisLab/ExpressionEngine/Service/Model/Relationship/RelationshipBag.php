<?php
namespace EllisLab\ExpressionEngine\Service\Model\Relationship;

use EllisLab\ExpressionEngine\Service\Model\Collection;

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
 * ExpressionEngine Relationship Bag
 *
 * Container class for all the relationships of a given model.
 *
 * @package		ExpressionEngine
 * @subpackage	Model\Relationship
 * @category	Service
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */
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
		if (is_array($model))
		{
			$this->relationships[$name] = new Collection($model);
		}
		elseif ( ! is_array($model) && ! ($model instanceof Collection))
		{
			$this->relationships[$name] = new Collection(array($model));
		}
		elseif ($model instanceof Collection)
		{
			$this->relationships[$name] = $model;
		}
		else
		{
			throw new \RuntimeException('Unrecognized type passed to RelationshipBag.');
		}
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
			$this->set($name, $model);
		}
		else
		{
			$this->relationships[$name][] = $model;
		}
	}

}

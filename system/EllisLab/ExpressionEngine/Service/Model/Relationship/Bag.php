<?php
namespace EllisLab\ExpressionEngine\Service\Model\Relationship;

use EllisLab\ExpressionEngine\Service\Model\Collection;
use EllisLab\ExpressionEngine\Service\Model\Model;

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
class Bag {

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
	 */
	public function get($name)
	{
		if ( ! $this->has($name))
		{
			return NULL;
		}

		return $this->relationships[$name];
	}

	/**
	 * Set a relationship
	 *
	 * @param String  $name       Name of the relationship
	 * @param Object  $collection Collection to relate to. Defaults to null for
	 *                            the case where there was no db result even
	 *                            though we tried. In those cases we definitely
	 *                            don't want to lazy query repeatedly.
	 * @return void
	 */
	public function setCollection($name, Collection $collection = NULL)
	{
		$this->relationships[$name] = $collection;
	}

	/**
	 * Set a relationship
	 *
	 * @param String  $name   Name of the relationship
	 * @param Object  $model  Model to relate to. Defaults to null for the case
	 *                        where there was no db result even though we tried.
	 *                        In those cases we definitely don't want to lazy
	 *                        query repeatedly.
	 * @return void
	 */
	public function setModel($name, Model $model = NULL)
	{
		$this->relationships[$name] = $model;
	}

	/**
	 * Add to a relationship
	 *
	 * @param String  $name  Name of the relationship
	 * @param Object  $model Model to add as a related model
	 * @return void
	 */
	public function add($name, $model = NULL)
	{
		if ( ! $this->has($name))
		{
			$this->setCollection($name, new Collection());
		}

		if (isset($model))
		{
			$this->relationships[$name][] = $model;
		}
	}

	/**
	 *
	 */
	public function remove($name, $value = NULL)
	{
		if ( ! array_key_exists($name, $this->relationships))
		{
			return;
		}

		$item = $this->relationships[$name];

		if ($item instanceOf Collection)
		{
			$new_items = $item->filter(function($model) use ($value)
			{
				return $value != $model;
			});

			if (count($new_items))
			{
				$this->relationships[$name] = new Collection($new_items);
				return;
			}
		}

		unset($this->relationships[$name]);
	}
}
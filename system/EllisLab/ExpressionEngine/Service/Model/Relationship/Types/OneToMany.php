<?php
namespace EllisLab\ExpressionEngine\Service\Model\Relationship\Types;

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
 * ExpressionEngine One-To-Many Relationship
 *
 * The one-to-many relationship type.
 *
 * @package		ExpressionEngine
 * @subpackage	Model\Relationship\Types
 * @category	Service
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */
class OneToMany extends Relationship {

	public $type	= 'one_to_many';
	public $inverse	= 'many_to_one';

	/**
	 * Set the related ids to correclty connect the models.
	 *
	 * For OneToMany, this means setting a field on each item in $to_collection,
	 * since the other side can't logically have a column for each of its
	 * related models.
	 *
	 * @param Model  $from_instance  Model that the data is being set on.
	 * @param Model  $to_model_or_collecion  Related data that is being set.
	 * @return void
	 */
	public function connect($from_instance, $to_collection)
	{
		foreach ($to_collection as $model)
		{
			$model->{$this->to_key} = $from_instance->{$this->key};
		}
	}

	public function disconnect($from_instance, $to_collection)
	{
		foreach ($to_collection as $model)
		{
			$model->{$this->to_key} = NULL;
		}
	}

	/**
	 * Determine whether the edge accepts a given action.
	 *
	 * For this edge that means never accepting add, and then accepting
	 * the others based on which way the edge is pointing. Weak edges
	 * are always set/remove.
	 */
	public function assertAcceptsAction($action)
	{
		$is_weak = $this->is_weak;

		// weak relationships are always set/remove
		if ($is_weak && ($action == 'create' || $action == 'delete'))
		{
			$alt = ($action == 'create') ? '(set|add)' : 'remove';
			throw new \Exception("Cannot {$action} on a weak relationship ({$this->name}), did you mean {$alt}{$this->name}?");
		}
		// this is a parent edge it, requires create/delete
		elseif ($action == 'add' || $action == 'set' || $action = 'remove')
		{
			$alt = ($action == 'remove') ? 'delete' : 'create';
			throw new \Exception("Cannot {$action}{$this->name}, did you mean {$alt}{$this->name}?");
		}
	}

	/**
	 * Figure out optional key settings as well as the parent.
	 *
	 * The parent must always be a single item, so this must always be the parent.
	 * For keys, this side can never contain a reference to each child, so the
	 * children must contain the reference to this side. By default, the key used
	 * matches the primary key it references.
	 *
	 * @return void
	 */
	protected function normalizeKeys()
	{
		$from = $this->from;

		$this->is_parent = TRUE;
		$this->key = $this->key ?: $this->factory->getMetaData($from, 'primary_key');
		$this->to_key = $this->to_key ?: $this->key;
	}
}
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
 * ExpressionEngine One-To-One Relationship
 *
 * The one-to-one relationship type.
 *
 * @package		ExpressionEngine
 * @subpackage	Model\Relationship\Types
 * @category	Service
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */
class OneToOne extends Relationship {

	public $type	= 'one_to_one';
	public $inverse	= 'one_to_one';

	/**
	 * Set the related ids to correclty connect the models.
	 *
	 * For oneToOne, either side can be the parent, or they could both have
	 * each other's keys, so we do some extra work to compare names
	 *
	 * @param Model  $from_instance  Model that the data is being set on.
	 * @param Model  $to_model_or_collecion  Related data that is being set.
	 * @return void
	 */
	public function connect($from_instance, $to_model)
	{
		if ($this->key != $from_instance->getMetaData('primary_key'))
		{
			$from_instance->{$this->key} = $to_model->{$this->to_key};
		}

		$to = $this->to;

		if ($this->to_key != $this->factory->getMetaData($to, 'primary_key'))
		{
			$to_model->{$this->to_key} = $from_instance->{$this->key};
		}
	}

	public function disconnect($from_instance, $to_model)
	{
		if ($this->key != $from_instance->getMetaData('primary_key'))
		{
			$from_instance->{$this->key} = NULL;
		}

		$to = $this->to;

		if ($this->to_key != $this->factory->getMetaData($to, 'primary_key'))
		{
			$to_model->{$this->to_key} = NULL;
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
		$is_parent = $this->is_parent;

		// weak relationships are always set/remove
		if ($is_weak && ($action == 'create' || $action == 'delete'))
		{
			$alt = ($action == 'create') ? 'set' : 'remove';
			throw new \Exception("Cannot {$action} on a weak relationship ({$this->name}), did you mean {$alt}{$this->name}?");
		}

		// if this is the parent edge it requires create/delete
		if ($is_parent && ($action == 'add' || $action == 'set' || $action = 'remove'))
		{
			$alt = ($action == 'remove') ? 'delete' : 'create';
			throw new \Exception("Cannot {$action}{$this->name}, did you mean {$alt}{$this->name}?");
		}

		// add is not ok for a *-to-one, must be a set
		if ($action == 'add')
		{
			throw new \Exception("Cannot add{$this->name}, did you mean set{$this->name}?");
		}
	}

	/**
	 * Figure out optional key settings as well as the parent.
	 *
	 * The parent must always be a single item, which is ambiguous here, so we
	 * use the key information to discern the parent. One side must have a reference
	 * to the other side. The key containing the reference is the child. If both
	 * sides contain the reference, the developer must specify is_parent on one
	 * of them. By default, the key used matches the primary key it references.
	 *
	 * @return void
	 */
	protected function normalizeKeys()
	{
		$from = $this->from;
		$to = $this->to;

		if ( ! $this->key && ! $this->to_key)
		{
			if (property_exists($to, $this->factory->getMetaData($from, 'primary_key')))
			{
				$this->key	  = $this->factory->getMetaData($from, 'primary_key');
				$this->to_key = $this->key;
				$this->is_parent = TRUE;
			}
			else if (property_exists($from, $this->factory->getMetaData($to, 'primary_key')))
			{
				$this->key	  = $this->factory->getMetaData($to, 'primary_key');
				$this->to_key = $this->key;
				$this->is_parent = FALSE;
			}
		}
		else
		{
			$this->key	  = $this->key ?: $this->factory->getMetaData($to, 'primary_key');
			$this->to_key = $this->to_key ?: $this->factory->getMetaData($from, 'primary_key');
			$this->is_parent = ($this->to_key == $this->factory->getMetaData($from, 'primary_key'));
		}
	}

}
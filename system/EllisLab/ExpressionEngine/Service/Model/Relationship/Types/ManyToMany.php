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
 * ExpressionEngine Many-To-Many Relationship
 *
 * The many-to-many relationship type.
 *
 * @package		ExpressionEngine
 * @subpackage	Model\Relationship\Types
 * @category	Service
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */
class ManyToMany extends Relationship {

	public $type	= 'many_to_many';
	public $inverse	= 'many_to_many';

	public $pivot_table;
	public $pivot_key;
	public $pivot_to_key;

	public $pivot_from_table;
	public $pivot_to_table;

	/**
	 * Set the related ids to correctly connect the models.
	 *
	 * For many to many this is handled via the pivot table, so we don't
	 * have any actual ids to set.
	 *
	 * @param Model  $from_instance  Model that the data is being set on.
	 * @param Model  $to_model_or_collecion  Related data that is being set.
	 * @return void
	 */
	public function connect($from_instance, $to_collection)
	{
		// todo verify that it exists on the pivot?
	}

	public function disconnect($from_instance, $to_collection)
	{
		// todo verify that it no longer exists on the pivot?
	}


	public function sync($from_instance, $to_collection)
	{
		$from_id = $from_instance->{$this->key};

		ee()->db->where($this->pivot_key, $from_id);
		ee()->db->delete($this->pivot_table);

		$to_ids = $to_collection->pluck($this->to_key);

		// todo batch
		foreach ($to_ids as $to_id)
		{
			ee()->db->set($this->pivot_key, $from_id);
			ee()->db->set($this->pivot_to_key, $to_id);
			ee()->db->insert($this->pivot_table);
		}
	}

	/**
	 * Determine whether the edge accepts a given action.
	 *
	 * For this edge that means only accepting add, set, and remove.
	 */
	public function assertAcceptsAction($action)
	{
		// weak relationships are always set/remove
		if ($action == 'create' || $action == 'delete')
		{
			$alt = ($action == 'create') ? '(add|set)' : 'remove';
			throw new \Exception("Cannot {$action} on a weak relationship ({$this->name}), did you mean {$alt}{$this->name}?");
		}
	}

	/**
	 *
	 */
	public function eagerQuery($query, $from_alias, $to_alias)
	{
		$joins = array();

		$joins[] = $query
			->joinTable($this->pivot_table, "{$from_alias}_{$this->pivot_table}")
			->on("{$from_alias}_{$this->pivot_from_table}")
			->where($this->key, $this->pivot_key)
			->type('LEFT OUTER');

		$joins[] = $query
			->joinTable($this->pivot_to_table, "{$to_alias}_{$this->pivot_to_table}")
			->on("{$from_alias}_{$this->pivot_table}")
			->where($this->pivot_to_key, $this->to_key)
			->type('LEFT OUTER');

		$add = $query->joinModelGateways($to_alias, $this->pivot_to_table);

		return array_merge($joins, $add);
	}

	/**
	 *
	 */
	public function lazyQuery($query, $parent_instance, $to_alias)
	{
		$from_alias = 'NoAccess_member_groups';

		$query->setFrom($to_alias);

		$from_table = key($query->getFields($to_alias));

		$query
			->joinTable($this->pivot_table, "PIVOT")
			->on($from_alias)
			->where($this->to_key, $this->pivot_to_key)
			->type('LEFT OUTER');

		$builder = $query->getBuilder();
		$builder->filter("PIVOT.{$this->key}", 'IN', $parent_instance->getId());

		//$query->applyFilter("PIVOT.{$this->key}", 'IN', $parent_instance->getId());
	}

	/**
	 * Figure out optional key settings as well as the parent.
	 *
	 * Since parents must always be single items, a ManyToMany relationship has
	 * no distinct parents or children. Instead, both model's primary keys are
	 * stored multiple times on a pivot table. Easy stuff.
	 *
	 * @return void
	 */
	// default: both primary keys on pivot
	protected function normalizeKeys()
	{
		$to = $this->to;
		$from = $this->from;

		$this->is_weak = TRUE;
		$this->is_parent = FALSE;

		$this->key = $this->key ?: $this->factory->getMetaData($from, 'primary_key');
		$this->to_key = $this->to_key ?: $this->factory->getMetaData($to, 'primary_key');

		// have to read the gateways to really figure this out
		$gateways = $this->factory->getMetaData($from, 'gateway_names');

		foreach ($gateways as $gateway)
		{
			$related = $this->factory->getMetaData($gateway, 'related_gateways');

			if (is_array($related) && isset($related[$this->key]))
			{
				$pivot = $related[$this->key];

				// more than one relatioship on this key
				if (is_array(current($pivot)))
				{
					$pivot = $pivot[$this->name];
				}

				$this->pivot_table = $pivot['pivot_table'];
				$this->pivot_key = $pivot['pivot_key'];
				$this->pivot_to_key = $pivot['pivot_foreign_key'];

				$this->pivot_from_table = $this->factory->getMetaData($gateway, 'table_name');
				$this->pivot_to_table = $this->factory->getMetaData($pivot['gateway'], 'table_name');

				break;
			}
		}

		if ( ! isset($this->pivot_table))
		{
			throw new \Exception("No pivot table found from {$this->from} to {$this->to}.");
		}
	}
}
<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2019, EllisLab Corp. (https://ellislab.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace EllisLab\ExpressionEngine\Service\Model\Relation;

use EllisLab\ExpressionEngine\Service\Model\Model;
use EllisLab\ExpressionEngine\Service\Model\Association\ToMany;

/**
 * HasAndBelongsToMany Relation
 */
class HasAndBelongsToMany extends Relation {

	protected $pivot = array();

	/**
	 *
	 */
	public function canSaveAcross()
	{
		return FALSE;
	}

	/**
	 * Get pivot data
	 */
	public function getPivot()
	{
		return $this->pivot;
	}

	public function getInverseOptions()
	{
		$options = parent::getInverseOptions();
		$options['pivot'] = [
			'table' => $this->pivot['table'],
			'left'  => $this->pivot['right'],
			'right' => $this->pivot['left'],
		];

		return $options;
	}

	/**
	 *
	 */
	public function createAssociation()
	{
		return new ToMany($this);
	}

	/**
	 *
	 */
	public function modifyEagerQuery($query, $from_alias, $to_alias)
	{
		list($from, $to) = $this->getKeys();

		$pivot_table = $this->pivot['table'];
		$pivot_as = "{$from_alias}_{$to_alias}_{$pivot_table}";

		// from -> pivot
		$query->join(
			"{$pivot_table} as {$pivot_as}",
			"{$pivot_as}.{$this->pivot['left']} = {$from_alias}_{$this->from_table}.{$from}",
			'LEFT'
		);

		// pivot -> to
		$query->join(
			"{$this->to_table} as {$to_alias}_{$this->to_table}",
			"{$to_alias}_{$this->to_table}.{$to} = {$pivot_as}.{$this->pivot['right']}",
			'LEFT'
		);
	}

	/**
	 *
	 */
	public function modifyLazyQuery($query, $source, $to_alias)
	{
		list($from, $to) = $this->getKeys();

		$from_alias = str_replace(':', '_m_', $source->getName());
		$pivot_table = $this->pivot['table'];
		$pivot_as = "{$from_alias}_{$to_alias}_{$pivot_table}";

		// pivot -> to_alias
		$query->join(
			"{$pivot_table} as {$pivot_as}",
			"{$pivot_as}.{$this->pivot['right']} = {$to_alias}_{$this->to_table}.{$to}",
			'LEFT'
		);

		$query->where("{$pivot_as}.{$this->pivot['left']}", $source->$from);
	}

	/**
	 *
	 */
	public function insert(Model $source, $targets)
	{
		if (empty($targets))
		{
			return;
		}

		foreach ($targets as $target)
		{
			$this->datastore->rawQuery()
				->set($this->pivot['left'], $source->{$this->from_key})
				->set($this->pivot['right'], $target->{$this->to_key})
				->insert($this->pivot['table']);
		}
	}

	/**
	 *
	 */
	public function drop(Model $source, $targets = NULL)
	{
		$query = $this->datastore
			->rawQuery()
			->where($this->pivot['left'], $source->{$this->from_key});

		if ( ! empty($targets))
		{
			$ids = array();

			foreach ($targets as $target)
			{
				$ids[] = $target->{$this->to_key};
			}

			if (empty($ids))
			{
				return;
			}

			$query->where_in($this->pivot['right'], $ids);
		}

		$query->delete($this->pivot['table']);
	}

	public function set(Model $source, $targets)
	{
		$this->drop($source, NULL);
		$this->insert($source, $targets);
	}

	/**
	*
	*/
	public function fillLinkIds(Model $source, Model $target)
	{
		return; // nada
	}

	/**
	 *
	 */
	public function linkIds(Model $source, Model $target)
	{
		return; // nada
	}

	/**
	 *
	 */
	public function unlinkIds(Model $source, Model $target)
	{
		return; // nada
	}

	/**
	*
	*/
	public function markLinkAsClean(Model $source, Model $target)
	{
		return; // nada
	}

	/**
	 *
	 */
	protected function deriveKeys()
	{
		$from = $this->from_key ?: $this->from_primary_key;
		$to   = $this->to_key   ?: $this->to_primary_key;

		return array($from, $to);
	}

	/**
	 * Process pivot information
	 *
	 * In a model the pivot key can either be an array or a table name.
	 * If it is a table name, then the lhs and rhs keys must equal the pk's
	 * of the two models
	 */
	protected function processOptions($options)
	{
		parent::processOptions($options);

		$pivot = $options['pivot'];

		if ( ! is_array($pivot))
		{
			$pivot = array('table' => $pivot);
		}

		$defaults = array(
			'left' => $this->from_primary_key,
			'right' => $this->to_primary_key
		);

		$this->is_weak = TRUE;
		$this->pivot = array_merge($defaults, $pivot);
	}
}

// EOF

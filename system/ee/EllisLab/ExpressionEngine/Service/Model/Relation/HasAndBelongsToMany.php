<?php

namespace EllisLab\ExpressionEngine\Service\Model\Relation;

use EllisLab\ExpressionEngine\Service\Model\Model;
use EllisLab\ExpressionEngine\Service\Model\Association\ToMany;

/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2016, EllisLab, Inc.
 * @license		https://expressionengine.com/license
 * @link		https://ellislab.com
 * @since		Version 3.0
 * @filesource
 */

// ------------------------------------------------------------------------

/**
 * ExpressionEngine HasAndBelongsToMany Relation
 *
 * @package		ExpressionEngine
 * @subpackage	Model
 * @category	Service
 * @author		EllisLab Dev Team
 * @link		https://ellislab.com
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


	/**
	 *
	 */
	public function createAssociation(Model $source)
	{
		return new ToMany($source, $this);
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
	 *
	 */
	protected function processOptions($options)
	{
		parent::processOptions($options);

		$pivot = array(
			'left' => $this->from_primary_key,
			'right' => $this->to_primary_key
		);

		$this->is_weak = TRUE;
		$this->pivot = array_merge($pivot, $options['pivot']);
	}
}

// EOF

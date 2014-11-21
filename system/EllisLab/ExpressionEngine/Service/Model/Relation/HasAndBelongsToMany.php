<?php
namespace EllisLab\ExpressionEngine\Service\Model\Relation;

use EllisLab\ExpressionEngine\Service\Model\Model;
use EllisLab\ExpressionEngine\Service\Model\Association;

class HasAndBelongsToMany extends Relation {

	protected $pivot = array();

	/**
	 *
	 */
	public function createAssociation(Model $source)
	{
		return new Association\HasAndBelongsToMany($source, $this->name);
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
		$query->from("{$pivot_table} as {$pivot_as}");

		$query->where(
			"{$pivot_as}.{$this->pivot['left']}",
			"{$from_alias}_{$this->from_table}.{$from}",
			FALSE
		);

		// pivot -> to
		$query->from("{$this->to_table} as {$to_alias}_{$this->to_table}");

		$query->where(
			"{$to_alias}_{$this->to_table}.{$to}",
			"{$pivot_as}.{$this->pivot['right']}",
			FALSE
		);
	}

	/**
	 *
	 */
	public function insertRelation($source, $target)
	{
		$this->datastore->rawQuery()
			->set($this->pivot['left'], $source->{$this->from_key})
			->set($this->pivot['right'], $target->{$this->to_key})
			->insert($this->pivot['table']);
	}

	/**
	 *
	 */
	public function dropRelation($source, $target)
	{
		$this->datastore->rawQuery()
			->where($this->pivot['left'], $source->{$this->from_key})
			->where($this->pivot['right'], $target->{$this->to_key})
			->delete($this->pivot['table']);
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

		$this->pivot = array(
			'table' => $options['pivot_table'],
			'left'  => $options['pivot_from_key'],
			'right' => $options['pivot_to_key']
		);
	}
}
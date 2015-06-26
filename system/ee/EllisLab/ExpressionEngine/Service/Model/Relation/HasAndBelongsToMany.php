<?php

namespace EllisLab\ExpressionEngine\Service\Model\Relation;

use EllisLab\ExpressionEngine\Service\Model\Model;
use EllisLab\ExpressionEngine\Service\Model\Association\ManyToMany;

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
 * ExpressionEngine HasAndBelongsToMany Relation
 *
 * @package		ExpressionEngine
 * @subpackage	Model
 * @category	Service
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
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
	 *
	 */
	public function createAssociation(Model $source)
	{
		return new ManyToMany($source, $this);
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

		$from_alias = $source->getName();
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
	public function dropRelation($source, $target = NULL)
	{
		$query = $this->datastore
			->rawQuery()
			->where($this->pivot['left'], $source->{$this->from_key});

		if (isset($target))
		{
			$query->where($this->pivot['right'], $target->{$this->to_key});
		}

		$query->delete($this->pivot['table']);
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

		$this->pivot = array_merge($pivot, $options['pivot']);
	}
}

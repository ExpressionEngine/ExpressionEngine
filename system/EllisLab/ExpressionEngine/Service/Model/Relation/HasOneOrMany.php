<?php
namespace EllisLab\ExpressionEngine\Service\Model\Relation;

use EllisLab\ExpressionEngine\Service\Model\Model;

abstract class HasOneOrMany extends Relation {

	/**
	 *
	 */
	public function linkIds(Model $source, Model $target)
	{
		list($from, $to) = $this->getKeys();

		$target->$to = $source->$from;
	}

	/**
	 *
	 */
	public function unlinkIds(Model $source, Model $target)
	{
		list($_, $to) = $this->getKeys();

		$target->$to = NULL;
	}

	/**
	 *
	 */
	protected function deriveKeys()
	{
		$from = $this->from_key ?: $this->from_primary_key;
		$to   = $this->to_key   ?: $from;

		return array($from, $to);
	}
}
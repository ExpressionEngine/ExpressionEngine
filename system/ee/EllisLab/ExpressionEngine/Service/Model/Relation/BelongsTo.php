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
use EllisLab\ExpressionEngine\Service\Model\Association\ToOne;

/**
 * BelongsTo Relation
 */
class BelongsTo extends Relation {

	/**
	 *
	 */
	public function createAssociation()
	{
		return new ToOne($this);
	}

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
	public function fillLinkIds(Model $source, Model $target)
	{
		list($from, $to) = $this->getKeys();

		$source->fill(array($from => $target->$to));
	}

	/**
	* Insert a database link between the model and targets
	*/
	public function insert(Model $source, $targets)
	{
		// nada;
	}

	/**
	* Drop the database link between the model and targets, potentially
	* triggering a soft delete.
	*/
	public function drop(Model $source, $targets = NULL)
	{
		// nada;
	}

	/**
	* Set the relationship
	*/
	public function set(Model $source, $targets)
	{
		// nada;
	}


	/**
	 *
	 */
	public function linkIds(Model $source, Model $target)
	{
		list($from, $to) = $this->getKeys();

		$source->$from = $target->$to;
	}

	/**
	 *
	 */
	public function unlinkIds(Model $source, Model $target)
	{
		list($from, $_) = $this->getKeys();

		if ($this->is_weak)
		{
			$source->$from = 0;
		}
		else
		{
			$source->$from = NULL;
		}
	}

	/**
	 *
	 */
	public function markLinkAsClean(Model $source, Model $target)
	{
		list($from, $_) = $this->getKeys();

		$source->markAsClean($from);
	}

	/**
	 *
	 */
	protected function deriveKeys()
	{
		$to   = $this->to_key   ?: $this->to_primary_key;
		$from = $this->from_key ?: $to;

		return array($from, $to);
	}
}

// EOF

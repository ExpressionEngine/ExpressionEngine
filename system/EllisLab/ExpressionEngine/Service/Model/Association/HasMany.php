<?php

namespace EllisLab\ExpressionEngine\Service\Model\Association;

use LogicException;

class HasMany extends ToMany {

	/**
	 *
	 */
	public function canSaveAcross()
	{
		return TRUE;
	}

	/**
	 *
	 */
	public function isStrongAssociation()
	{
		return TRUE;
	}

	/**
	 * Disable set
	 */
	public function set($item)
	{
		throw new LogicException('Cannot set(), did you mean create()?');
	}

	/**
	 * Disable add
	 */
	public function add($item)
	{
		throw new LogicException('Cannot add(), did you mean create()?');
	}

	/**
	 * Disable remove
	 */
	public function remove($item)
	{
		throw new LogicException('Cannot remove(), did you mean delete()?');
	}
}
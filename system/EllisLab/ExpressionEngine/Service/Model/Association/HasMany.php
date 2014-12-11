<?php

namespace EllisLab\ExpressionEngine\Service\Model\Association;

use LogicException;

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
 * ExpressionEngine HasMany Association
 *
 * @package		ExpressionEngine
 * @subpackage	Model
 * @category	Service
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */
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
	public function remove($item = NULL)
	{
		throw new LogicException('Cannot remove(), did you mean delete()?');
	}
}
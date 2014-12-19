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
 * ExpressionEngine BelongsTo Association
 *
 * @package		ExpressionEngine
 * @subpackage	Model
 * @category	Service
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */
class BelongsTo extends ToOne {

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
	public function isStrongAssociation()
	{
		return FALSE;
	}

	/**
	 * Disable save
	 */
	public function create($item)
	{
		throw new LogicException('Cannot create(), did you mean set()?');
	}

	/**
	 * Disable delete
	 */
	public function delete($item)
	{
		throw new LogicException('Cannot delete(), did you mean remove()?');
	}
}
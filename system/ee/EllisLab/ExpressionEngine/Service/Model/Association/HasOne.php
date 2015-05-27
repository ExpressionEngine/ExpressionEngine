<?php

namespace EllisLab\ExpressionEngine\Service\Model\Association;

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
 * ExpressionEngine HasOne Association
 *
 * @package		ExpressionEngine
 * @subpackage	Model
 * @category	Service
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */
class HasOne extends ToOne {

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

}
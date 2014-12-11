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
 * ExpressionEngine HasAndBelongsToMany Association
 *
 * @package		ExpressionEngine
 * @subpackage	Model
 * @category	Service
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */
class HasAndBelongsToMany extends ToMany {

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
	 *
	 */
	public function clear()
	{
		parent::clear();
		$this->relation->dropRelationship();
	}

	/**
	 *
	 */
	protected function insertRelationship($target)
	{
		$this->relation->insertAssociation($this->source, $target);
	}

	/**
	 *
	 */
	protected function dropRelationship($target = NULL)
	{
		$this->relation->dropAssociation($this->source, $target);
	}
}
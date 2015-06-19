<?php

namespace EllisLab\ExpressionEngine\Service\Model\Relation;

use EllisLab\ExpressionEngine\Service\Model\Model;
use EllisLab\ExpressionEngine\Service\Model\Association\ToOne;

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
 * ExpressionEngine BelongsTo Relation
 *
 * @package		ExpressionEngine
 * @subpackage	Model
 * @category	Service
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */
class BelongsTo extends Relation {

	/**
	 *
	 */
	public function createAssociation(Model $source)
	{
		return new ToOne($source, $this);
		//return new Association\BelongsTo($source, $this);
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

		$source->$from = NULL;
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

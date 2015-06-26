<?php

namespace EllisLab\ExpressionEngine\Service\Model\Relation;

use EllisLab\ExpressionEngine\Service\Model\Model;

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
 * ExpressionEngine HasOneOrMany Relation
 *
 * @package		ExpressionEngine
 * @subpackage	Model
 * @category	Service
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */
abstract class HasOneOrMany extends Relation {

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
	public function fillLinkIds(Model $source, Model $target)
	{
		list($from, $to) = $this->getKeys();

		$target->fill(array($to => $source->$from));
	}

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
	public function markLinkAsClean(Model $source, Model $target)
	{
		list($_, $to) = $this->getKeys();

		$target->markAsClean($to);
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

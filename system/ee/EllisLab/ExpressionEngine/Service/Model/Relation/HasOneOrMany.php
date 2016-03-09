<?php

namespace EllisLab\ExpressionEngine\Service\Model\Relation;

use EllisLab\ExpressionEngine\Service\Model\Model;
use EllisLab\ExpressionEngine\Service\Model\Collection;

/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2016, EllisLab, Inc.
 * @license		https://expressionengine.com/license
 * @link		https://ellislab.com
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
 * @link		https://ellislab.com
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

		if ($this->is_weak)
		{
			$target->$to = 0;
		}
		else
		{
			$target->$to = NULL;
		}
	}

	/**
	* Insert a database link between the model and targets
	*/
	public function insert(Model $source, $targets)
	{
		// nada
	}

	/**
	* Drop the database link between the model and targets, potentially
	* triggering a soft delete.
	*/
	public function drop(Model $source, $targets = NULL)
	{
		list($from, $to) = $this->getKeys();

		$ids = array();

		if (is_array($targets) || $targets instanceOf Collection)
		{
			foreach ($targets as $target)
			{
				$ids[] = $target->getId();
			}
		}
		elseif (isset($targets))
		{
			$ids = array($targets->getId());
		}

		$query = $this->datastore->rawQuery()
			->where($to, $source->$from);

		if ( ! empty($ids))
		{
			$query->where_in($this->to_primary_key, $ids);
		}

		if ($this->is_weak)
		{
			$query->set($to, 0)->update($this->to_table);
		}
		else
		{
			$query->delete($this->to_table);
		}
	}

	public function set(Model $source, $targets)
	{
		$this->dropComplement($source, $targets);
	}

	/**
	* Drop the set-theoretic complement, i.e. drop everything that's *not*
	* in the second parameter set
	*/
	protected function dropComplement(Model $source, $targets)
	{
		list($from, $to) = $this->getKeys();

		if (is_array($targets))
		{
			$ids = array();

			foreach ($targets as $target)
			{
				$ids[] = $target->getId();
			}
		}
		else
		{
			$ids = array($targets->getId());
		}

		$query = $this->datastore->rawQuery()
			->where($to, $source->$from);

		if ( ! empty($ids))
		{
			$query->where_not_in($this->to_primary_key, $ids);
		}

		if ($this->is_weak)
		{
			$query->set($to, 0)->update($this->to_table);
		}
		else
		{
			$query->delete($this->to_table);
		}
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

// EOF

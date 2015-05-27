<?php

namespace EllisLab\ExpressionEngine\Service\Model\Association\Tracker;

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
 * ExpressionEngine Staged Association Tracker
 *
 * Adding something to this tracker stages it to be saved at a later
 * time.
 *
 * @package		ExpressionEngine
 * @subpackage	Model
 * @category	Service
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */
class Staged implements Tracker {

	protected $added = array();
	protected $removed = array();

	/**
	 *
	 */
	public function getAdded()
	{
		return array_values($this->added);
	}

	/**
	 *
	 */
	public function getRemoved()
	{
		return array_values($this->removed);
	}

	/**
	 * Mark as added
	 */
	public function add(Model $model)
	{
		$hash = spl_object_hash($model);

		if ( ! $this->attemptFastUndoRemove($hash))
		{
			$this->added[$hash] = $model;
		}
	}

	/**
	 * Mark as removed
	 */
	public function remove(Model $model)
	{
		$hash = spl_object_hash($model);

		if ( ! $this->attemptFastUndoAdd($hash))
		{
			$this->removed[$hash] = $model;
		}
	}

	/**
	 *
	 */
	public function reset()
	{
		$this->added = array();
		$this->removed = array();
	}

	/**
	 *
	 */
	protected function attemptFastUndoRemove($hash)
	{
		if (isset($this->removed[$hash]))
		{
			unset($this->removed[$hash]);
			return TRUE;
		}

		return FALSE;
	}

	/**
	 *
	 */
	protected function attemptFastUndoAdd($hash)
	{
		if (isset($this->added[$hash]))
		{
			unset($this->added[$hash]);
			return TRUE;
		}

		return FALSE;
	}

}
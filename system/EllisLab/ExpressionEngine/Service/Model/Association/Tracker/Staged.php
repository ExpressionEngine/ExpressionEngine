<?php
namespace EllisLab\ExpressionEngine\Service\Model\Association\Tracker;

use EllisLab\ExpressionEngine\Service\Model\Model;

class Staged implements Tracker {

	protected $added = array();
	protected $removed = array();

	/**
	 *
	 */
	public function getAdded()
	{
		return $this->added;
	}

	/**
	 *
	 */
	public function getRemoved()
	{
		return $this->removed;
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
	protected function attemptFastUndoAdd($model)
	{
		if (isset($this->added[$hash]))
		{
			unset($this->added[$hash]);
			return TRUE;
		}

		return FALSE;
	}

	/**
	 *
	 */
	protected function reset()
	{
		$this->added = array();
		$this->removed = array();
	}

}
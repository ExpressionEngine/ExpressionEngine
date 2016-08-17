<?php

namespace EllisLab\ExpressionEngine\Service\Model\Association;

/**
 * Running diff of relationship changes
 */
class Diff {

	private $relation;

	protected $added = array();
	protected $removed = array();
	protected $was_set = FALSE;

	public function __construct($relation)
	{
		$this->relation = $relation;
	}

	public function reset()
	{
		$this->added = array();
		$this->removed = array();
		$this->was_set = FALSE;
	}

	public function wasSet()
	{
		$this->was_set = TRUE;
	}

	public function add($model)
	{
		$hash = spl_object_hash($model);

		if ( ! $this->attemptFastUndoRemove($hash))
		{
			$this->added[$hash] = $model;
		}
	}

	public function remove($model)
	{
		$hash = spl_object_hash($model);

		if ( ! $this->attemptFastUndoAdd($hash))
		{
			$this->removed[$hash] = $model;
		}
	}

	protected function attemptFastUndoRemove($hash)
	{
		if (array_key_exists($hash, $this->removed))
		{
			unset($this->removed[$hash]);
			return TRUE;
		}

		return FALSE;
	}

	protected function attemptFastUndoAdd($hash)
	{
		if (array_key_exists($hash, $this->added))
		{
			unset($this->added[$hash]);
			return TRUE;
		}

		return FALSE;
	}

	public function commit($parent)
	{
		// when setting, remove everything not in the new set
		if ($this->was_set)
		{
			$this->relation->set($parent, $this->added);
		}
		else
		{
			if ( ! empty($this->removed))
			{
				$this->relation->drop($parent, $this->removed);
			}

			$this->relation->insert($parent, $this->added);
		}

		$this->reset();
	}
}

// EOF

<?php

namespace EllisLab\ExpressionEngine\Service\Model\Association;

use EllisLab\ExpressionEngine\Service\Model\Model;

class ManyToMany extends ToMany {

	private $known = array();
	private $added = array();
	private $removed = array();

	public function fill($related, $_skip_inverse = FALSE)
	{
		$this->replaceKnown($related);

		return parent::fill($related, $_skip_inverse);
	}

	/**
	 * Small tweak to trigger a remove all on the db level
	 */
	public function remove($items = NULL)
	{
		if (is_null($items))
		{
			$this->removed[] = '*';
		}

		return parent::remove($items);
	}

	public function save()
	{
		foreach ($this->removed as $model)
		{
			$model = ($model == '*') ? NULL : $model;

			$this->relation->dropRelation($this->model, $model);
		}

		foreach ($this->added as $model)
		{
			$this->relation->insertRelation($this->model, $model);
		}

		$this->reset();

		return parent::save();
	}

	public function ensureExists(Model $model)
	{
		$this->addToKnown($model);
		return parent::ensureExists($model);
	}

	public function ensureDoesNotExist(Model $model)
	{
		$this->removeFromKnown($model);
		return parent::ensureDoesNotExist($model);
	}

	protected function replaceKnown($new_known)
	{
		if ($new_known instanceOf Model)
		{
			$new_known = array($new_known);
		}

		foreach ($this->known as $model)
		{
			$this->removeFromKnown($model);
		}

		foreach ($new_known as $model)
		{
			$this->addToKnown($model);
		}

		if ( ! is_array($new_known))
		{
			$new_known = $new_known->asArray();
		}

		$this->known = $new_known ?: array();
	}

	protected function removeFromKnown($model)
	{
		$hash = spl_object_hash($model);

		if (isset($this->added[$hash]))
		{
			unset($this->added[$hash]);
		}
		else
		{
			$this->removed[$hash] = $model;
		}

		unset($this->known[$hash]);
	}

	protected function addToKnown($model)
	{
		$hash = spl_object_hash($model);

		if (isset($this->removed[$hash]))
		{
			unset($this->removed[$hash]);
		}
		else
		{
			$this->added[$hash] = $model;
		}

		$this->known[$hash] = $model;
	}

	protected function reset()
	{
		$this->added = array();
		$this->removed = array();
	}
}

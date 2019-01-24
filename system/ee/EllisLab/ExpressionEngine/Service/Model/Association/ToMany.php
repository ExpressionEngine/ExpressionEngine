<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2019, EllisLab Corp. (https://ellislab.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace EllisLab\ExpressionEngine\Service\Model\Association;

use EllisLab\ExpressionEngine\Service\Model\Collection;
use EllisLab\ExpressionEngine\Service\Model\Model;

/**
 * Model Service: To Many Association
 */
class ToMany extends Association {

	public function fill($related, $_skip_inverse = FALSE)
	{
		if (is_array($related))
		{
			$related = new Collection($related);
		}

		if ($related instanceOf Model)
		{
			$related = new Collection(array($related));
		}

		if ($related instanceOf Collection)
		{
			$this->ensureAssociation($related);
		}


		return parent::fill($related, $_skip_inverse);
	}

	public function get()
	{
		$result = parent::get();

		if ( ! isset($result))
		{
			$this->ensureCollection();
			return $this->related;
		}

		return $result;
	}

	public function foreignKeyChanged($value)
	{
		// nada
	}

	protected function ensureExists($model)
	{
		$this->ensureCollection();

		if ( ! $this->has($model))
		{
			$this->related->add($model, FALSE);
			parent::ensureExists($model);
		}
	}

	protected function ensureDoesNotExist($model)
	{
		if ($this->has($model))
		{
			$this->related->removeElement($model);
			parent::ensureDoesNotExist($model);
		}
	}

	protected function has($model)
	{
		if (is_null($this->related))
		{
			return FALSE;
		}

		foreach ($this->related as $m)
		{
			if ($m === $model)
			{
				return TRUE;
			}

			// Existing models queried independently may fail the above check
			if ($m->getId() && $model->getId() &&
				$m->getId() === $model->getId() &&
				get_class($m) == get_class($model))
			{
				return TRUE;
			}
		}

		return FALSE;
	}

	protected function ensureCollection()
	{
		if (is_null($this->related))
		{
			$this->related = new Collection();
		}

		$this->ensureAssociation($this->related);
	}

	protected function ensureAssociation(Collection $related)
	{
		if ($related->getAssociation() !== $this)
		{
			$related->setAssociation($this);
		}
	}
}

// EOF

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

/**
 * Model Service: To One Association
 */
class ToOne extends Association {

	private $fk_value = NULL;

	public function foreignKeyChanged($value)
	{
		if ($value != $this->fk_value)
		{
			return parent::foreignKeyChanged($value);
		}
	}

	public function fill($related, $_skip_inverse = FALSE)
	{
		if ($related instanceOf Collection)
		{
			$related = $related->first();
		}

		if (is_array($related))
		{
			$related = array_shift($related);
		}

		$this->cacheFKValue($related);

		return parent::fill($related, $_skip_inverse);
	}

	protected function ensureExists($model)
	{
		if ($this->related !== $model)
		{
			$this->cacheFKValue($model);

			$this->related = $model;
			parent::ensureExists($model);
		}
	}

	protected function ensureDoesNotExist($model)
	{
		if ($this->related === $model)
		{
			$this->cacheFKValue(NULL);

			$this->related = NULL;
			parent::ensureDoesNotExist($model);
		}
	}

	private function cacheFKValue($model)
	{
		$fk = $this->getForeignKey();

		if ($model && $model->hasProperty($fk))
		{
			$this->fk_value = $model->$fk;
		}
		else
		{
			$this->fk_value = NULL;
		}
	}
}

// EOF

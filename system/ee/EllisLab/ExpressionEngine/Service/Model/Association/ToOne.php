<?php

namespace EllisLab\ExpressionEngine\Service\Model\Association;

use EllisLab\ExpressionEngine\Service\Model\Collection;

class ToOne extends Association {

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

		return parent::fill($related, $_skip_inverse);
	}

	protected function ensureExists($model)
	{
		if ($this->related !== $model)
		{
			$this->related = $model;
			parent::ensureExists($model);
		}
	}

	protected function ensureDoesNotExist($model)
	{
		if ($this->related === $model)
		{
			$this->related = NULL;
			parent::ensureDoesNotExist($model);
		}
	}
}

// EOF

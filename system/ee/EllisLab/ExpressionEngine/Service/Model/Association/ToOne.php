<?php

namespace EllisLab\ExpressionEngine\Service\Model\Association;

use EllisLab\ExpressionEngine\Service\Model\Collection;

class ToOne extends Association {

	public function fill($parent, $related, $_skip_inverse = FALSE)
	{
		if ($related instanceOf Collection)
		{
			$related = $related->first();
		}

		if (is_array($related))
		{
			$related = array_shift($related);
		}

		return parent::fill($parent, $related, $_skip_inverse);
	}

	protected function ensureExists($parent, $model)
	{
		if ($this->related !== $model)
		{
			$this->related = $model;
			parent::ensureExists($parent, $model);
		}
	}

	protected function ensureDoesNotExist($parent, $model)
	{
		if ($this->related === $model)
		{
			$this->related = NULL;
			parent::ensureDoesNotExist($parent, $model);
		}
	}
}

// EOF

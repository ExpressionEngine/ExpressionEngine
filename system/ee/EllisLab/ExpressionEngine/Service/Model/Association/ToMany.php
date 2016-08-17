<?php

namespace EllisLab\ExpressionEngine\Service\Model\Association;

use EllisLab\ExpressionEngine\Service\Model\Collection;
use EllisLab\ExpressionEngine\Service\Model\Model;

class ToMany extends Association {

	public function fill($parent, $related, $_skip_inverse = FALSE)
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


		return parent::fill($parent, $related, $_skip_inverse);
	}

	public function get($parent)
	{
		$result = parent::get($parent);

		if ( ! isset($result))
		{
			$this->ensureCollection();
			return $this->related;
		}

		return $result;
	}

	protected function ensureExists($parent, $model)
	{
		$this->ensureCollection();

		if ( ! $this->has($model))
		{
			$this->related->add($model);
			parent::ensureExists($parent, $model);
		}
	}

	protected function ensureDoesNotExist($parent, $model)
	{
		if ($this->has($model))
		{
			$this->related->remove($model);
			parent::ensureDoesNotExist($parent, $model);
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

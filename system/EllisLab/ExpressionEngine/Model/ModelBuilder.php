<?php
namespace EllisLab\ExpressionEngine\Model;

use EllisLab\ExpressionEngine\Core\Dependencies;
use EllisLab\ExpressionEngine\Model\Query\Query;

class ModelBuilder {

	protected $alias_service;

	public function __construct(AliasService $aliases)
	{
		$this->alias_service = $aliases;
	}

	public function get($model_name, $ids = NULL)
	{
		$query = $this->newQuery($model_name);

		if (isset($ids))
		{
			if (is_array($ids))
			{
				$query->filter($model_name, 'IN', $ids);
			}
			else
			{
				$query->filter($model_name, $ids);
			}
		}

		return $query;
	}

	public function make($model, array $data = array(), $dirty = TRUE)
	{
		$class = $this->alias_service->getRegisteredClass($model);

		if ( ! is_a($class, '\EllisLab\ExpressionEngine\Model\Model', TRUE))
		{
			throw new \InvalidArgumentException('Can only create Models.');
		}

		return new $class($data, $dirty);
	}

	protected function newQuery($model_name)
	{
		return new Query($this, $this->alias_service, $model_name);
	}
}

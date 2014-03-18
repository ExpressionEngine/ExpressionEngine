<?php
namespace EllisLab\ExpressionEngine\Model;

use EllisLab\ExpressionEngine\Core\Dependencies;
use EllisLab\ExpressionEngine\Model\Query\Query;

/**
 * The model builder is our entry point. Any external dependencies should be
 * explicitly declared here by providing getters similar to getValidation().
 */
class ModelBuilder {

	protected $alias_service;

	public function __construct(Dependencies $di, AliasService $aliases)
	{
		$this->di = $di;
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

		return new $class($this, $this->alias_service, $data, $dirty);
	}

	public function makeGateway($gateway, $data = array())
	{
		$class = $this->alias_service->getRegisteredClass($gateway);

		if ( ! is_a($class, '\EllisLab\ExpressionEngine\Model\Gateway\RowDataGateway', TRUE))
		{
			throw new \InvalidArgumentException('Can only create Gateways.');
		}

		return new $class($this->getValidation(), $this->alias_service, $data, $dirty);
	}

	/**
	 * Create a new model builder instance.
	 *
	 * @return \Ellislab\ExpressionEngine\Model\ModelBuilder
	 */
	public function newModelBuilder()
	{
		return new static($this->di, $this->alias_service);
	}

	/**
	 * Get the external validation.
	 *
	 * @return \Ellislab\ExpressionEngine\Core\Validation
	 */
	public function getValidation()
	{
		return $this->di->getValidation();
	}

	/**
	 * Create a new query object
	 *
	 * @return \Ellislab\ExpressionEngine\Model\Query
	 */
	protected function newQuery($model_name)
	{
		return new Query($this, $this->alias_service, $model_name);
	}
}

<?php
namespace EllisLab\ExpressionEngine\Model;

use EllisLab\ExpressionEngine\Core\AliasService;
use EllisLab\ExpressionEngine\Core\Dependencies;
use EllisLab\ExpressionEngine\Model\Query\Query;

use EllisLab\ExpressionEngine\Model\Relationship\RelationshipGraph;

/**
 * The model builder is our composition root for all models and queries.
 * Any external dependencies should be explicitly declared here by providing
 * getters similar to getValidation() to avoid breaking the law of demeter.
 */
class ModelFactory {

	protected $alias_service;
	protected $di;
	protected $relationship_graph;

	public function __construct(Dependencies $di, AliasService $aliases)
	{
		$this->di = $di;
		$this->alias_service = $aliases;
	}

	/**
	 * Query Factory
	 *
	 * @return \Ellislab\ExpressionEngine\Model\Query
	 */
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

	/**
	 * Model factory
	 *
	 * @return \Ellislab\ExpressionEngine\Model\Model
	 */
	public function make($model, array $data = array(), $dirty = TRUE)
	{
		$class = $this->alias_service->getRegisteredClass($model);

		if ( ! is_a($class, '\EllisLab\ExpressionEngine\Model\Model', TRUE))
		{
			throw new \InvalidArgumentException('Can only create Models.');
		}

		$polymorph = $class::getMetaData('polymorph');

		if ($polymorph !== NULL)
		{
			$class = $this->alias_service->getRegisteredClass($polymorph);
		}

		return new $class($this, $this->alias_service, $data, $dirty);
	}

	/**
	 * Gateway factory
	 *
	 * @return \Ellislab\ExpressionEngine\Model\Gateway
	 */
	public function makeGateway($gateway, $data = array())
	{
		$class = $this->alias_service->getRegisteredClass($gateway);

		if ( ! is_a($class, '\EllisLab\ExpressionEngine\Model\Gateway\RowDataGateway', TRUE))
		{
			throw new \InvalidArgumentException('Can only create Gateways.');
		}

		return new $class($this->getValidation(), $data);
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
	 * Get the external validation.
	 *
	 * @return \Ellislab\ExpressionEngine\Core\Validation
	 */
	public function getAliasService()
	{
		return $this->alias_service;
	}

	public function getRelationshipGraph()
	{
		if ( ! isset($this->relationship_graph))
		{
			$this->relationship_graph = $this->newRelationshipGraph();
		}
		return $this->relationship_graph;
	}

	protected function newRelationshipGraph()
	{
		 return new RelationshipGraph($this->getAliasService());
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

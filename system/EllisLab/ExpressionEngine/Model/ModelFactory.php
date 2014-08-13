<?php
namespace EllisLab\ExpressionEngine\Model;

use InvalidArgumentException;
use EllisLab\ExpressionEngine\Core\AliasServiceInterface;
use EllisLab\ExpressionEngine\Core\Validation\ValidationFactory;
use EllisLab\ExpressionEngine\Model\Relationship\RelationshipGraph;
use EllisLab\ExpressionEngine\Model\Query\Query;

/**
 * The model builder is our composition root for all models and queries.
 * Any external dependencies should be explicitly declared here as interfaces.
 *
 * Optional dependencies should be set on the constructed models using setters.
 *
 * Technically Validation should be an optional dependency on this class and
 * it should bubble down from there, but that would mean that it can be flipped
 * out globally, which we don't want. The alternative is to go full java and
 * create a configurator class. No thanks.
 */
class ModelFactory {

	protected $alias_service;
	protected $validation_factory;
	protected $relationship_graph;

	public function __construct(AliasServiceInterface $aliases, ValidationFactory $validation_factory)
	{
		$this->alias_service = $aliases;
		$this->validation_factory = $validation_factory;
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
			throw new InvalidArgumentException('Can only create Models.');
		}

		$polymorph = $class::getMetaData('polymorph');

		if ($polymorph !== NULL)
		{
			$class = $this->alias_service->getRegisteredClass($polymorph);
		}

		$obj = new $class($this, $this->alias_service, $data, $dirty);
		$obj->setValidationFactory($this->validation_factory);
		return $obj;
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
			throw new InvalidArgumentException('Can only create Gateways.');
		}

		return new $class($data);
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
		 return new RelationshipGraph($this->alias_service);
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

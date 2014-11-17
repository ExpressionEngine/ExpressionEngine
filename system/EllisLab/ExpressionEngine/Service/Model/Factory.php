<?php
namespace EllisLab\ExpressionEngine\Service\Model;

use InvalidArgumentException;
use EllisLab\ExpressionEngine\Service\AliasServiceInterface;
use EllisLab\ExpressionEngine\Service\Model\Relationship\RelationshipGraph;
use EllisLab\ExpressionEngine\Service\Model\Query\Builder as QueryBuilder;
use EllisLab\ExpressionEngine\Service\Model\Query\Query;
use EllisLab\ExpressionEngine\Service\Model\Query\ReferenceChain;
use EllisLab\ExpressionEngine\Service\Validation\Factory as ValidationFactory;

use EllisLab\ExpressionEngine\Service\Model\Graph\RelationshipDirectedGraph;
use EllisLab\ExpressionEngine\Service\Model\Graph\RelationshipGraphDecorator;
/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2014, EllisLab, Inc.
 * @license		http://ellislab.com/expressionengine/user-guide/license.html
 * @link		http://ellislab.com
 * @since		Version 3.0
 * @filesource
 */

// ------------------------------------------------------------------------

/**
 * ExpressionEngine Model Factory
 *
 * The model factory is our composition root for all models and queries.
 * Any external dependencies should be explicitly declared here as interfaces.
 *
 * Optional dependencies should be set on the constructed models using setters.
 *
 * Technically Validation should be an optional dependency on this class and
 * it should bubble down from there, but that would mean that it can be flipped
 * out globally, which we don't want. The alternative is to go full java and
 * create a configurator class. No thanks.
 *
 * @package		ExpressionEngine
 * @subpackage	Model
 * @category	Service
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */
/**
 */
class Factory {

	protected $alias_service;
	protected $validation_factory;

	// lazily instantiate as singletons
	protected $relationship_graph;
	protected $relationship_manager;

	public function __construct(AliasServiceInterface $aliases, ValidationFactory $validation_factory)
	{
		$this->alias_service = $aliases;
		$this->validation_factory = $validation_factory;
	}

	/**
	 * Get Meta Data from a Class (Gateway or Model)
	 *
	 * Retrieves meta data from either a Gateway or Model and serves
	 * as a mockable interface over their static methods.
	 *
	 * @param	string	$name	A model or gateway name.
	 * @param	string	$item	The meta property to be retrieved.
	 *
	 * @return mixed	The value of the meta property.
	 */
	public function getMetaData($name, $item)
	{
		// Did we get passed an object?
		if (is_object($name))
		{
			return $name->getMetaData($item);
		}

		// How about a fully qualified class name?
		if (class_exists($name))
		{
			return $name::getMetaData($item);
		}

		// Must be a model or gateway alias.  Retrieve the fully qualified
		// name.
		$class_name = $this->alias_service->getRegisteredClass($name);
		return $class_name::getMetaData($item);
	}

	/**
	 * Query Factory
	 *
	 * @return \Ellislab\ExpressionEngine\Model\Query
	 */
	public function get($model_name, $ids = NULL)
	{
		if (isset($model_name) && is_object($model_name))
		{
			$model_object = $model_name;
			$model_name = $this->alias_service->getAlias(get_class($model_object));
		}

		$query = $this->newQueryBuilder($model_name);

		if (isset($ids))
		{
			if (is_array($ids))
			{
				// we use getName() to allow for root aliasing
				// ee()->api->get('Member as M', array(1, 2, 3))
				$query->filter($query->getName(), 'IN', $ids);
			}
			else
			{
				$query->filter($query->getName(), $ids);
			}
		}

		if (isset($model_object))
		{
			$query->setModelObject($model_object);
		}

		return $query;
	}

	/**
	 * Model factory
	 *
	 * @return \Ellislab\ExpressionEngine\Model\Model
	 */
	public function make($model_name, array $data = array())
	{
		$class = $this->alias_service->getRegisteredClass($model_name);

		if ( ! is_a($class, '\EllisLab\ExpressionEngine\Service\Model\Model', TRUE))
		{
			throw new InvalidArgumentException('Can only create Models.');
		}

		$manager = $this->getRelationshipManager();
		$relationships = $manager->getRelationships($model_name);

		$obj = new $class($this, $data);

		$obj->setName($model_name);
		$obj->setRelationshipEdges($relationships);
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

		if ( ! is_a($class, '\EllisLab\ExpressionEngine\Service\Model\Gateway\RowDataGateway', TRUE))
		{
			throw new InvalidArgumentException('Can only create Gateways.');
		}

		return new $class($data);
	}

	/**
	 *
	 */
	public function getRelationshipManager()
	{
		if ( ! isset($this->relationship_manager))
		{
			$this->relationship_manager = $this->newRelationshipManager();
		}

		return $this->relationship_manager;
	}

	/**
	 *
	 */
	public function getRelationshipGraph()
	{
		if ( ! isset($this->relationship_graph))
		{
			$this->relationship_graph = $this->newRelationshipGraph();
		}

		return $this->relationship_graph;
	}

	/**
	 * Create a new query object
	 *
	 * @return \Ellislab\ExpressionEngine\Model\Query\Builder
	 */
	protected function newQueryBuilder($model_name)
	{
		return new QueryBuilder(
			$this->newQuery(),
			new ReferenceChain($this->getRelationshipManager()),
			$model_name
		);
	}

	/**
	 * Create a new query object
	 *
	 * @return \Ellislab\ExpressionEngine\Model\Query\Query
	 */
	protected function newQuery()
	{
		return new Query(
			$this,
			$this->getRelationshipGraph(),
			ee()->db
		);
	}

	/**
	 *
	 */
	protected function newRelationshipManager()
	{
		return new Relationship\Manager($this);
	}

	/**
	 * Create a new relationship graph object
	 *
	 * @return \EllisLab\ExpressionEngine\Model\Relationship\RelationshipGraph
	 */
	protected function newRelationshipGraph()
	{
		return new RelationshipGraphDecorator(
			new RelationshipDirectedGraph(),
			$this->getRelationshipManager()
		);
	}
}
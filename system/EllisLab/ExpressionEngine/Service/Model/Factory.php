<?php
namespace EllisLab\ExpressionEngine\Service\Model;

use InvalidArgumentException;
use EllisLab\ExpressionEngine\Service\AliasServiceInterface;
use EllisLab\ExpressionEngine\Service\Model\Relationship\RelationshipGraph;
use EllisLab\ExpressionEngine\Service\Model\Query\Builder as QueryBuilder;
use EllisLab\ExpressionEngine\Service\Model\Query\Connection;
use EllisLab\ExpressionEngine\Service\Validation\Factory as ValidationFactory;

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
	protected $relationship_graph;
	protected $connection;

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
		elseif (class_exists($name))
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

		if ( ! is_a($class, '\EllisLab\ExpressionEngine\Service\Model\Model', TRUE))
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

		if ( ! is_a($class, '\EllisLab\ExpressionEngine\Service\Model\Gateway\RowDataGateway', TRUE))
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

	protected function getConnection()
	{
		return new Connection(
			ee()->db,
			$this,
			$this->getRelationshipGraph(),
			$this->alias_service
		);
	}

	/**
	 * Create a new query object
	 *
	 * @return \Ellislab\ExpressionEngine\Model\Query
	 */
	protected function newQuery($model_name)
	{
		return new QueryBuilder($this->getConnection(), $model_name);
	}
}

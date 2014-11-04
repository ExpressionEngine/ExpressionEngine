<?php
namespace EllisLab\ExpressionEngine\Service\Model\Relationship;

use EllisLab\ExpressionEngine\Service\AliasServiceInterface;

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
 * ExpressionEngine Relationship Graph
 *
 * Class that manages direct access to any given graph node.
 *
 * @package		ExpressionEngine
 * @subpackage	Model\Relationship
 * @category	Service
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */
class RelationshipGraph {

	protected $nodes = array();

	/**
	 * @param $alias_service EllisLab\ExpressionEngine\Core\AliasServiceInterface
	 */
	public function __construct(AliasServiceInterface $alias_service)
	{
		$this->alias_service = $alias_service;
	}

	/**
	 * Get a node on the graph by class name
	 *
	 * @param String  $class_name  Fully qualified classname
	 * @return RelationshipGraphNode
	 */
	public function getNode($class_name)
	{
		if ( ! isset($this->nodes[$class_name]))
		{
			$this->addNode($class_name);
		}

		return $this->nodes[$class_name];
	}

	/**
	 * Add a node. Used for lazy graph building in `getNode()`
	 *
	 * @param String  $class_name  Fully qualified classname
	 * @return void
	 */
	public function addNode($class_name)
	{
		$this->nodes[$class_name] = new RelationshipGraphNode($this->alias_service, $class_name);
	}
}
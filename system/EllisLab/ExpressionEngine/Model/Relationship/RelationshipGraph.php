<?php
namespace EllisLab\ExpressionEngine\Model\Relationship;

/**
 *
 */
class RelationshipGraph {

	protected $nodes = array();

	/**
	 * @param AliasService $alias_service
	 */
	public function __construct($alias_service)
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
<?php
namespace EllisLab\ExpressionEngine\Model\Relationship;

class RelationshipGraph {

	protected $nodes = array();

	public function __construct($alias_service)
	{
		$this->alias_service = $alias_service;
	}

	public function getNode($class_name)
	{
		if ( ! isset($this->nodes[$class_name]))
		{
			$this->addNode($class_name);
		}

		return $this->nodes[$class_name];
	}

	public function addNode($class_name)
	{
		$this->nodes[$class_name] = new RelationshipGraphNode($class_name, $this->alias_service);
	}
}
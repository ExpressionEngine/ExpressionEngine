<?php

namespace EllisLab\ExpressionEngine\Service\Model\Graph;


use EllisLab\ExpressionEngine\Library\DataStructure\Graph\Decorator as GraphDecorator;

/**
 * Implementing this as a decorator instead of an extension makes
 * it all a little easier to test since it decouples the factory
 * object from the graph.
 */
class RelationshipGraphDecorator extends GraphDecorator {

	protected $factory;
	protected $manager;

	public function __construct(RelationshipDirectedGraph $delegate, $manager)
	{
		parent::__construct($delegate);

		$this->manager = $manager;
	}

	/**
	 *
	 */
	public function ensureGraph($root)
	{
		$visited = array();
		$add_models = array($root);

		foreach ($this->manager->getRelationships($root) as $edge)
		{
			$this->delegate->addEdge($edge, $edge->from, $edge->to);
		}
	}

}

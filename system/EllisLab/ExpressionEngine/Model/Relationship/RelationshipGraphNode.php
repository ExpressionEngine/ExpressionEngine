<?php
namespace EllisLab\ExpressionEngine\Model\Relationship;

// Graph node that holds the relationship info for this node.

// A node can return its edges. So if a template has a lastAuthor, then
// lastAuthor is an edge. Member, the model that lastAuthor points to is
// the node on the other end.

class RelationshipGraphNode {

	protected $relationship_infos = array();

	public function __construct($model_class, $alias_service)
	{
		$this->alias_service = $alias_service;
		$this->model_class = $model_class;
	}

	/**
	 * Get an edge by name.
	 */
	public function getEdgeByName($name, $info = NULL)
	{
		if ( ! isset($this->cached[$name]))
		{
			$class = $this->model_class;
			$info = $class::getMetaData('relationships');

			$this->cached[$name] = new RelationshipData($this->alias_service, $class, $name);
		}

		return $this->cached[$name];
	}

	/**
	 * Get all edges regardless of direction.
	 */
	public function getAllEdges()
	{
		$all = array();
		$class = $this->model_class;
		$data = $class::getMetaData('relationships');

		foreach ($data as $name => $value)
		{
			$all[$name] = $this->getEdgeByName($name);
		}

		return $all;
	}

	/**
	 * Incoming edges are those where we are on the many side.
	 * This equates to a `belongsTo` relationship, so we are not
	 * the parent.
	 */
	public function getAllIncomingEdges($force_outgoing = array())
	{
		$all = $this->getAll();

		return array_filter($all, function($rel) use ($force_outgoing)
		{
			return ! $rel->is_parent && ! in_array($rel->name, $force_outgoing);
		});
	}

	/**
	 * Outgoing edges are those where we are on the one side.
	 * This equates to a `has` relationship, so we are the
	 * parent.
	 */
	public function getAllOutgoingEdges($force_outgoing = array())
	{
		$all = $this->getAll();

		return array_filter($all, function($rel) use ($force_outgoing)
		{
			return $rel->is_parent || in_array($rel->name, $force_outgoing);
		});
	}
}
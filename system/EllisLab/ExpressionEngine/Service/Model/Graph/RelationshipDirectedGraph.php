<?php
namespace EllisLab\ExpressionEngine\Service\Model\Graph;

use EllisLab\ExpressionEngine\Library\DataStructure\Graph\SparseGraph;


class RelationshipDirectedGraph extends SparseGraph {

	protected $edge_names;

	public function __construct()
	{
		parent::__construct();

		$this->edge_names = array();
	}

	/**
	 * Instead of setting both vertices with the same edge
	 * ensure that the edges have the correct orientation.
	 */
	public function addEdge($e, $v1, $v2)
	{
		$f = $e->getInverse();

		$this->edges->set($e, array($v1, $v2));
		$this->edges->set($f, array($v2, $v1));

		$this->setNamedEdge($e->name, $v1, $e);
		$this->setNamedEdge($f->name, $v2, $f);

		$this->addVertexIfNotExists($v1);
		$this->addVertexIfNotExists($v2);

		$this->vertices->get($v1)->set($v2, $e);
		$this->vertices->get($v2)->set($v1, $f);
	}

	/**
	 *
	 */
	public function getOutgoingEdges($v)
	{
		$outgoing = $this->vertices->get($v);

		return $outgoing->getValues();
	}

	/**
	 *
	 */
	public function getIncomingEdges($v)
	{
		$outgoing = $this->vertices->get($v);
		$incoming = array();

		foreach ($outgoing->getKeys() as $opposite)
		{
			$incoming[] = $this->findEdge($v, $opposite);
		}

		return $incoming;
	}

	/**
	 *
	 */
	public function setNamedEdge($name, $v, $edge)
	{
		if ( ! isset($this->edge_names[$name]))
		{
			$this->edge_names[$name] = array();
		}

		$this->edge_names[$name][$v] = $edge;
	}

	/**
	 *
	 */
	public function getNamedEdge($v, $name)
	{
		return $this->edge_names[$name][$v];
	}

	/**
	 *
	 */
	public function hasNamedEdge($v, $name)
	{
		return isset($this->edge_names[$name][$v]);
	}
}
<?php

namespace EllisLab\ExpressionEngine\Library\DataStructure\Graph;

use EllisLab\ExpressionEngine\Library\DataStructure\HashMap;

/**
 * Graph Implementation using an Adjacency List style architecture.
 *
 */
class SparseGraph implements Graph {

	protected $edges; // [edge => [v1, v2]]
	protected $vertices; // [v1 => [v2 => e]]

	public function __construct()
	{
		$this->edges = new HashMap();
		$this->vertices = new HashMap();
	}

	public function getEdges()
	{
		return $this->edges->getKeys();
	}

	public function getVertices()
	{
		return $this->vertices->getKeys();
	}

	public function containsVertex($v)
	{
		return $this->vertices->hasKey($v);
	}

	public function containsEdge($e)
	{
		return $this->edges->hasKey($e);
	}

	public function addEdge($e, $v1, $v2)
	{
		$this->edges->set($e, array($v1, $v2));

		$this->addVertexIfNotExists($v1);
		$this->addVertexIfNotExists($v2);

		$this->vertices->get($v1)->set($v2, $e);
		$this->vertices->get($v2)->set($v1, $e);
	}

	public function addVertex($v)
	{
		$this->vertices->add($v, new HashMap());
	}

	public function findEdge($v1, $v2)
	{
		$outgoing = $this->vertices->get($v1);

		if (isset($outgoing))
		{
			return $outgoing->get($v2);
		}
	}

	public function getNeighbors($v)
	{
		$incident = $this->vertices->get($v);

		if (isset($incident))
		{
			return $incident->getKeys();
		}
	}

	public function getEndpoints($e)
	{
		return $this->edges->get($e);
	}

	public function getOpposite($v, $e)
	{
		list($v1, $v2) = $this->edges->get($e);

		if ($v == $v1)
		{
			return $v2;
		}

		if ($v == $v2)
		{
			return $v1;
		}
	}

	public function addVertexIfNotExists($v)
	{
		if ( ! $this->vertices->hasKey($v))
		{
			$this->addVertex($v);
		}
	}
}
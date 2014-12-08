<?php

namespace EllisLab\ExpressionEngine\Library\DataStructure\Graph;

class Decorator implements Graph {

	protected $delegate;

	public function __construct(Graph $delegate)
	{
		$this->delegate = $delegate;
	}

	public function __call($method, $parameters)
	{
		return call_user_func_array(array($this->delegate, $method), $parameters);
	}

	public function getEdges()
	{
		return $this->delegate->getEdges();
	}

	public function getVertices()
	{
		return $this->delegate->getVertices();
	}

	public function containsVertex($v)
	{
		return $this->delegate->containsVertex($v);
	}

	public function containsEdge($e)
	{
		return $this->delegate->containsEdge($e);
	}

	public function addEdge($e, $v1, $v2)
	{
		return $this->delegate->addEdge($e, $v1, $v2);
	}

	public function addVertex($v)
	{
		return $this->delegate->addVertex($v);
	}

	public function findEdge($v1, $v2)
	{
		return $this->delegate->findEdge($v1, $v2);
	}

	public function getNeighbors($v)
	{
		return $this->delegate->getNeighbors($v);
	}

	public function getEndpoints($e)
	{
		return $this->delegate->getEndpoints($e);
	}

	public function getOpposite($v, $e)
	{
		return $this->delegate->getOpposite($v, $e);
	}
}
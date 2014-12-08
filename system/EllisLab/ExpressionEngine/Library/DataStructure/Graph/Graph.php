<?php

namespace EllisLab\ExpressionEngine\Library\DataStructure\Graph;

interface Graph {

	public function getEdges();

	public function getVertices();

	public function containsVertex($v);

	public function containsEdge($e);

	public function addEdge($e, $v1, $v2);

	public function addVertex($v);

	public function findEdge($v1, $v2);

	public function getNeighbors($v);

	public function getEndpoints($e);

	public function getOpposite($v, $e);
}
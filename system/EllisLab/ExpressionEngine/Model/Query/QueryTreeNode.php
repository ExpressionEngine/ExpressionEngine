<?php
namespace EllisLab\ExpressionEngine\Model\Query;

use InvalidArgumentException;

use EllisLab\ExpressionEngine\Library\DataStructure\Tree\TreeNode;

class QueryTreeNode extends TreeNode {

	public static $top_id = 0;

	protected $path_string = NULL;

	protected $id;
	protected $model;
	protected $gateways;
	protected $relationship_name;

	public function __construct($name, $model)
	{
		parent::__construct($name, array());

		$this->id = ++self::$top_id;
		$this->model = $model;
	}

	public function getId()
	{
		return $this->id;
	}

	public function getGateways()
	{
		if ( ! isset($this->gateways))
		{
			$this->gateways = $this->model->getGateways();
		}

		return $this->gateways;
	}

	public function getModel()
	{
		return $this->model;
	}

	public function setRelationshipName($name)
	{
		$this->relationship_name = $name;
	}

	public function getRelationshipName()
	{
		return $this->relationship_name;
	}

	/**
	 * Overriden for the type hint
	 */
	public function add(QueryTreeNode $child)
	{
		parent::add($child);
	}

	/**
	 * Create a string representing the path from the root node
	 * to this node using the unique ids of each node along the
	 * path.
	 */
	public function getPathString()
	{
		if ( ! isset($this->path_string))
		{
			$node = $this;
			$path = $this->getId();

			while ( ! $node->isRoot())
			{
				$path = $node->getParent()->getId() . '_' . $path;
				$node = $node->getParent();
			}

			$this->path_string = $path;
		}

		return $this->path_string;
	}
}

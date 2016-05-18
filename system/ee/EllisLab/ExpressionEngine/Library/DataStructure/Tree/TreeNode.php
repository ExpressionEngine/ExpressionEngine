<?php

namespace EllisLab\ExpressionEngine\Library\DataStructure\Tree;


/**
 * ExpressionEngine Tree Node Class
 *
 * @package		ExpressionEngine
 * @subpackage	Core Datastructures
 * @category	Core
 * @author		EllisLab Dev Team
 * @link		https://ellislab.com
 *
 * If you're completely new to this ideas:
 * @see http://xlinux.nist.gov/dads/HTML/tree.html
 */
class TreeNode {

	protected $name;
	protected $data;

	protected $parent;
	protected $children;
	protected $children_names;

	private $_frozen = FALSE;

	public function __construct($name, $payload = NULL)
	{
		$this->name = $name;
		$this->data = $payload;

		$this->children = array();
		$this->children_names = array();
	}

	// --------------------------------------------------------------------

	/**
	 * Retrieve the payload data.
	 *
	 * If the payload is an array we treat the entire object as an
	 * accessor to the payload. Otherwise the key must be "data" to
	 * mimic regular object access.
	 *
	 * @return void
	 */
	public function __get($key)
	{
		if (is_array($this->data) && isset($this->data[$key]))
		{
			return $this->data[$key];
		}
		if ($key == 'data')
		{
			return $this->data;
		}

		throw new \InvalidArgumentException('Payload cannot be retrieved for key: "' . $key . '" in node "' . $this->getName() . '".');
	}

	// --------------------------------------------------------------------

	/**
	 * Change the payload data.
	 *
	 * If they payload is an array we treat the entire object as an
	 * accessor to the payload. Otherwise the key must be "data" to
	 * mimic regular object access.
	 *
	 * @return void
	 */
	public function __set($key, $value)
	{
		if ($this->_frozen)
		{
			throw new \RuntimeException('Cannot modify payload. Tree node is frozen.');
		}

		if (is_array($this->data))
		{
			$this->data[$key] = $value;
		}
		elseif ($key == 'data')
		{
			$this->data = $value;
		}
		else
		{
			throw new \InvalidArgumentException('Payload cannot be modified.');
		}
	}

	// --------------------------------------------------------------------

	/**
	 * Post-process node cloning
	 *
	 * Cloning needs to unfreeze the node for the benefit of the
	 * subtree_copy method. Not to mention dev sanity.
	 *
	 * @return void
	 */
	public function __clone()
	{
		$this->_frozen = FALSE;
	}

	// Public Setters

	// --------------------------------------------------------------------

	/**
	 * Add a child node to the current node.
	 *
	 * Notifies the child of its parent and adds the child name to
	 * the child name array. Does not enforce unique names since it
	 * may be desireable to have non-unique named children. It's on
	 * the developer to not rely on the get() method in that case
	 *
	 * @return void
	 */
	public function add(TreeNode $child)
	{
		if ($child == $this)
		{
			throw new \RuntimeException('Cannot add tree node to itself.');
		}

		if ($this->_frozen)
		{
			throw new \RuntimeException('Cannot add child. Tree node is frozen.');
		}

		$this->children[] = $child;
		$this->children_names[$child->name] = $child;
		$child->setParent($this);
	}

	// Getters

	// --------------------------------------------------------------------

	/**
	 * Get the node's name
	 *
	 * @return string name
	 */
	public function getName()
	{
		return $this->name;
	}

	// --------------------------------------------------------------------

	/**
	 * Get the node's payload
	 *
	 * @return mixed payload
	 */
	public function getData()
	{
		return $this->data;
	}

	// --------------------------------------------------------------------

	/**
	 * Get the node's depth relative to its root, where the root's
	 * depth is 0.
	 *
	 * @return Integer depth
	 */
	public function getDepth()
	{
		if ($this->isRoot())
		{
			return 0;
		}

		return 1 + $this->getParent()->getDepth();
	}

	// Traversal

	// --------------------------------------------------------------------

	/**
	 * Get the tree's root node
	 *
	 * If the current node is not a root node, we move our
	 * way up until we have a root.
	 *
	 * @return <TreeNode>
	 */
	public function getRoot()
	{
		$root = $this;

		while ( ! $root->isRoot())
		{
			$root = $root->getParent();
		}

		return $root;
	}

	// --------------------------------------------------------------------

	/**
	 * Get all of the node's children
	 *
	 * @return TreeNode[]
	 */
	public function getChildren()
	{
		return $this->children;
	}

	// --------------------------------------------------------------------

	/**
	 * Get the node's first child
	 *
	 * @return TreeNode
	 */
	public function getFirstChild()
	{
		return $this->children[0];
	}

	// --------------------------------------------------------------------

	/**
	 * Get the node's parent
	 *
	 * @return TreeNode
	 */
	public function getParent()
	{
		return $this->parent;
	}

	// --------------------------------------------------------------------

	/**
	 * Get all of a node's siblings
	 *
	 * @return TreeNode[]
	 */
	public function getSiblings()
	{
		$siblings = array();

		if ( ! $this->isRoot())
		{
			foreach ($this->getParent()->getChildren() as $sibling)
			{
				if ($sibling != $this)
				{
					$siblings[] = $sibling;
				}
			}
		}

		return $siblings;
	}

	// Utility

	// --------------------------------------------------------------------

	/**
	 * Check if the node has parents
	 *
	 * @return boolean
	 */
	public function isRoot()
	{
		return ! isset($this->parent);
	}

	// --------------------------------------------------------------------

	/**
	 * Check if the node has children
	 *
	 * @return boolean
	 */
	public function isLeaf()
	{
		return count($this->children) == 0;
	}

	// --------------------------------------------------------------------

	/**
	 * Freeze the node
	 *
	 * Prevents data and child manipulations. Cloning a frozen node will
	 * unfreeze it.
	 *
	 * @return void
	 */
	public function freeze()
	{
		$this->_frozen = TRUE;
	}

	// --------------------------------------------------------------------

	/**
	 * Get a child by name
	 *
	 * You are responsible for adding children with unique names. If you
	 * do not, then this method will return the last child node of the
	 * given name.
	 *
	 * @return TreeNode
	 */
	public function get($name)
	{
		return $this->children_names[$name];
	}

	// --------------------------------------------------------------------

	/**
	 * Create a subtree on this node.
	 *
	 * Clones the current node to turn it into a root node off the
	 * original tree.
	 *
	 * This is a *shallow* copy! The root node you receive is a clone, but
	 * its children remain on the tree. If you need a clone for anything
	 * other than traversal, consider using the subtree_copy() method instead.
	 *
	 * @return TreeNode[]
	 */
	public function getSubtree()
	{
		$root = clone $this;
		$root->parent = NULL;
		return $root;
	}

	// --------------------------------------------------------------------

	/**
	 * Create a full subtree copy from this node down.
	 *
	 * Clones the current node and all of its children. This is a deep
	 * copy, everything will be cloned. If all you need is a new root
	 * for traversal, consider using subtree() instead.
	 *
	 * @return TreeNode[]
	 */
	public function getSubtreeCopy()
	{
		$class = get_class($this);
		$root = new $class($this->getName(), $this->getData());

		foreach ($this->getChildren() as $node)
		{
			$root->add($node->getSubtreeCopy());
		}

		return $root;
	}

	// --------------------------------------------------------------------

	/**
	 * Preorder Tree Iterator
	 *
	 * Creates a preorder tree iterator from the current node down.
	 *
	 * @return <RecursiveIteratorIterator> with SELF_FIRST
	 */
	public function getPreorderIterator()
	{
		return new \RecursiveIteratorIterator(
			new TreeIterator(array($this)),
			\RecursiveIteratorIterator::SELF_FIRST
		);
	}

	// --------------------------------------------------------------------

	/**
	 * Postorder Tree Iterator
	 *
	 * Creates a postorder tree iterator from the current node down.
	 *
	 * @return <RecursiveIteratorIterator> with CHILD_FIRST
	 */
	public function getPostorderIterator()
	{
		return new \RecursiveIteratorIterator(
			new TreeIterator(array($this)),
			\RecursiveIteratorIterator::CHILD_FIRST
		);
	}

	// --------------------------------------------------------------------

	/**
	 * Leaf Iterator
	 *
	 * Iterates across all the leaf nodes
	 *
	 * @return RecursiveIteratorIterator with LEAVES_ONLY
	 */
	public function getLeafIterator()
	{
		return new \RecursiveIteratorIterator(
			new TreeIterator(array($this)),
			\RecursiveIteratorIterator::LEAVES_ONLY
		);
	}

	// --------------------------------------------------------------------

	/**
	 * Breadth First Iterator
	 *
	 * Iterates across all nodes in a level-by-level fashion
	 *
	 * @return BreadthFirstIterator
	 */
	public function getBreadthFirstIterator()
	{
		return new BreadthFirstIterator(
			new TreeIterator(array($this))
		);
	}

	// --------------------------------------------------------------------

	/**
	 * Set parent
	 *
	 * Links up the parent node for upwards traversal. Should only ever
	 * be called from add() to maintain referential integrity.
	 *
	 * In theory add() has access to the property directly, but sometimes
	 * it's useful to override this with additional functionality.
	 *
	 * @param TreeNode New parent node
	 * @return void
	 */
	protected function setParent(TreeNode $parent)
	{
		$this->parent = $parent;
	}
}

// EOF

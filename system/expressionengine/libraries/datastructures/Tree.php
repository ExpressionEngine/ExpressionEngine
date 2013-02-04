<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2012, EllisLab, Inc.
 * @license		http://ellislab.com/expressionengine/user-guide/license.html
 * @link		http://ellislab.com
 * @since		Version 2.0
 * @filesource
 */
 
// ------------------------------------------------------------------------

/*

 THIS FILE CONTAINS:

 EE_Tree		- singleton tree builder
 ImmutableTree	- main tree object (returned from EE_Tree::load)
 TreeIterator	- iteration helper (returned from ImmutableTree::iterator)

*/

// ------------------------------------------------------------------------

/**
 * ExpressionEngine Tree Factory Class
 *
 * @package		ExpressionEngine
 * @subpackage	Core Datastructures
 * @category	Core
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */
class EE_Tree {

	// --------------------------------------------------------------------

	/**
	 * Tree Factory
	 *
	 * Takes an array of rows that each have an id and parent id (as you
	 * would get from the db) and returns a tree structure
	 *
	 * @param data - array of array('unique_id' => x, 'parent_id' => y, ...data)
	 * @param config array
	 *		- key : data's unique id key
	 *		- parent_id: data's parent_id key
	 *
	 * @return Object<ImmutableTree>
	 */
	public function load($data, array $conf = NULL)
	{
		$conf = array_merge(
			array('key' => 'id', 'parent' => 'parent_id'),
			(array) $conf
		);

		return $this->_build_tree($data, $conf['key'], $conf['parent']);
	}

	// --------------------------------------------------------------------

	/**
	 * Tree Builder
	 *
	 * Re-sorts the data from load() and turns it into two datastructures:
	 *
	 * An array of tree root nodes, with children in the __children__ key
	 * of their respective parents. Thus forming a tree as a nested array.
	 * 
	 * A lookup table of id => row, where each item is actually a reference
	 * into the tree. This way we can do quick by-index lookups and prevent
	 * duplicate data between our lookup table and tree. This is important
	 * because the lookup table is reused for all subtrees.
	 *
	 * @param data - array of array('unique_id' => x, 'parent_id' => y, ...data)
	 * @param unique id key
	 * @param parent id key
	 *
	 * @return Object<ImmutableTree>
	 */
	protected function _build_tree($data, $child_key, $parent_key)
	{
		$tree = array();
		$nodes = array();

		// data is an array of arrays, multipl items could be
		// roots (indicated by parent_id of 0). In order to keep
		// tree operations consistent, we'll create an artifical
		// master root node.
		$data[] = array(
			$child_key => 0,
			$parent_key => -1
		);

		// First we create a lookup table of id => row
		// This lets us build the tree on references which
		// will in turn allow for quick subtree lookup.
		foreach ($data as &$node)
		{
			$id = $node[$child_key];
			$node['__children__'] = array();
			$nodes[$id] =& $node;
		}

		// And now build the actual tree by assigning children
		// and storing any root node references in the tree array.
		foreach ($data as &$node)
		{
			$id = $node[$child_key];
			$parent = $node[$parent_key];

			if ($parent >= 0)
			{
				$nodes[$parent]['__children__'][$id] =& $node;
			}
			else
			{
				$tree[$id] =& $node;
			}
		}

		// For sibling queries we need to be able to go up the tree
		// a level. The original tree of course doesn't have siblings
		// so in order to keep tree operations consistent we'll give
		// our master root node a fake parent with no children. -pk

		$nodes[-1] = array('__children__' => array());

		return new ImmutableTree($nodes[0], $nodes, $parent_key);
	}
}

// ------------------------------------------------------------------------

/**
 * ExpressionEngine Tree Container Class
 *
 * @package		ExpressionEngine
 * @subpackage	Core Datastructures
 * @category	Core
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */
class ImmutableTree {

	protected $ref;
	protected $_parent_key;
	protected $_tree = array();
	protected $_nodes = array();

	/**
	 * Constructor
	 *
	 * @param tree: array of roots with __children__ as their kids
	 * @param nodes: lookup table of unique ids to tree nodes
	 * @param parent id key
	 *
	 * @return Object<ImmutableTree>
	 */
	public function __construct($tree, $nodes, $parent_key)
	{
		$this->_tree = $tree;
		$this->_nodes = $nodes;
		$this->_parent_key = $parent_key;

		// Each tree has a local root node, that way we can
		// consistently use 'root' as a default for the current tree
		// Unfortunately 0 was taken thanks to our db defaults forcing
		// it to be the global root.

		$this->_nodes['root'] = &$this->_tree;
	}

	// --------------------------------------------------------------------

	// @todo TODO this or implement serializable?
	public function __sleep() { }
	public function __wakeup() { }

	// --------------------------------------------------------------------

	/**
	 * Get Siblings
	 *
	 * @param id of child whose sibling to get [optional]
	 * @return Array of sibling nodes
	 */
	public function siblings($id = 'root')
	{
		$parent_id = $this->_nodes[$id][$this->_parent_key];

		return $this->children($parent_id);
	}

	// --------------------------------------------------------------------

	/**
	 * Get Children
	 *
	 * @param id of node whose children to get [optional]
	 * @return Array of child nodes
	 */
	public function children($id = 'root')
	{
		$node = $this->_nodes[$id];

		return $this->_prune_children($node['__children__']);
	}

	// --------------------------------------------------------------------

	/**
	 * Flatten the tree to its original array form.
	 *
	 * @return array similar to what was passed to EE_Tree::load
	 */
	public function flatten()
	{
		$it = $this->iterator();
		$result = array();

		foreach ($it as $node)
		{
			unset($node['__children__']);
			$result[] = $node;
		}

		return $result;
	}

	// --------------------------------------------------------------------

	/**
	 * Move up a level in the tree
	 *
	 * @param id of node whose parent tree to get [optional]
	 * @return Object<ImmutableTree> with the parent at the root
	 */
	public function parent_tree($id = 'root')
	{
		$parent_id = $this->_nodes[$id][$this->_parent_key];

		return $this->_instance($parent_id);
	}

	// --------------------------------------------------------------------

	/**
	 * Grab a subtree with the specified node at its root or the
	 * subtree of the current node (which makes more sense if you
	 * imagine they're iterating over the tree).
	 *
	 * @param id of node whose subtree to get [optional]
	 * @return Object<ImmutableTree>
	 */
	public function subtree($id = 'root')
	{
		return $this->_instance($id);
	}

	public function by_level_iterator()
	{
		// @todo iterate with stack
	}

	// --------------------------------------------------------------------

	/**
	 * Get an iterator of the full tree
	 *
	 * Iterates root node first so that you can create nested
	 * displays by doing things like:
	 *
	 *		$it = $tree->iterator();
	 *
	 *		foreach ($it as $key => $element)
	 *		{
	 *		    $tab = str_repeat('--', $it->getDepth());
	 *		    $str .= $tab.$element['title'].'<br>';
	 *		}
	 *
	 * @return Object<RecursiveIteratorIterator>
	 */
	public function iterator() // flat_iterator?
	{
		$root = $this->_nodes['root'];

		if ($root == $this->_nodes[0])
		{
			$elements = $root['__children__'];
		}
		else
		{
			$elements = array($root);
		}

		return new RecursiveIteratorIterator(
			new TreeIterator($elements),
			RecursiveIteratorIterator::SELF_FIRST
		);
	}

	// --------------------------------------------------------------------

	/**
	 * Remove children from the top level array we're working on.
	 *
	 * @param Array of nodes to clean
	 * @return Cleaned array
	 */
	protected function _prune_children(array $items)
	{
		// We copy as well as unsetting to dereference the result
		// array. The tree is built from references and we definitely
		// don't want to return those to the user.
		$result = array();

		foreach ($items as $item)
		{
			unset($item['__children__']);
			$result[] = $item;
		}

		return $result;
	}

	// --------------------------------------------------------------------

	/**
	 * Get a new tree from a given node id
	 *
	 * @param id of new root node
	 * @return Object<ImmutableTree>
	 */
	protected function _instance($node_id)
	{
		if (empty($this->ref))
		{
			$this->ref = new ReflectionClass($this);
		}

		return $this->ref->newInstance(
			$this->_nodes[$node_id],
			$this->_nodes,
			$this->_parent_key
		);
	}
}


// ------------------------------------------------------------------------

/**
 * ExpressionEngine Tree Iterator Class
 *
 * @package		ExpressionEngine
 * @subpackage	Core Datastructures
 * @category	Core
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */
class TreeIterator extends RecursiveArrayIterator {

	/**
	 * Override RecursiveArrayIterator's child detection method.
	 * We usually have data rows that are arrays so we really only
	 * want to iterate over those that match our custom format.
	 *
	 * @return boolean
	 */
	public function hasChildren()
	{
		$current = $this->current();
		return isset($current['__children__']) && ! empty($current['__children__']);
	}

	// --------------------------------------------------------------------

	/**
	 * Override RecursiveArrayIterator's get child method to skip
	 * ahead into the __children__ array and not try to iterate
	 * over the data row's individual columns.
	 *
	 * @return Object<TreeIterator>
	 */
	public function getChildren()
	{
		$current = $this->current();
		$children = $current['__children__'];

		// Using ref as per PHP source
		if (empty($this->ref))
		{
			$this->ref = new ReflectionClass($this);
		}

		return $this->ref->newInstance($children);
	}
}

/* End of file Tree.php */
/* Location: ./system/expressionengine/libraries/datastructures/Tree.php */
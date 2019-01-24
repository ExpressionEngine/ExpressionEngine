<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2019, EllisLab Corp. (https://ellislab.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace EllisLab\ExpressionEngine\Library\DataStructure\Tree;

/**
 * Tree Factory
 */
class TreeFactory {

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
	 * @return TreeNode	The frozen root node of an immutable tree.
	 */
	public function fromList($data, array $conf = NULL)
	{
		$conf = array_merge(
			array(
				'id'	 		 => 'id',
				'parent' 	 	 => 'parent_id',
				'class_name'	 => '\EllisLab\ExpressionEngine\Library\DataStructure\Tree\TreeNode'
			),
			(array) $conf
		);

		if ( ! isset($conf['name_key']))
		{
			$conf['name_key'] = $conf['id'];
		}

		return $this->buildTree($data, $conf);
	}

	/**
	 * Flatten the tree to a list of data objects.
	 *
	 * @return array similar to what was passed to EE_Tree::load
	 */
	public function toList(TreeNode $tree)
	{
		$it = $tree->getPreorderIterator();
		$result = array();

		foreach ($it as $node)
		{
			$result[] = $node->getData();
		}

		return $result;
	}

	/**
	 * Tree Builder
	 *
	 * Re-sorts the data from from_list() and turns it into two datastructures:
	 *
	 * An array of tree root nodes, with children in the __children__ key
	 * of their respective parents. Thus forming a tree as a nested array.
	 *
	 * A lookup table of id => row, where each item is actually a reference
	 * into the tree. This way we can do quick by-index lookups.
	 *
	 * @param data - array of array('unique_id' => x, 'parent_id' => y, ...data)
	 * @param unique id key
	 * @param parent id key
	 *
	 * @return TreeNode	The root node of a tree.
	 */
	protected function buildTree($data, $conf)
	{
		$nodes = array();

		$child_key = $conf['id'];
		$parent_key = $conf['parent'];

		$name = $conf['name_key'];
		$class = $conf['class_name'];

		// First we create a lookup table of id => object
		// This lets us build the tree on references which
		// will in turn allow for quick subtree lookup.
		foreach ($data as $row)
		{
			$id = $row[$child_key];
			$nodes[$id] = new $class($row[$name], $row);
		}

		$tree = new TreeNode('__root__');

		// And now build the actual tree by assigning children
		foreach ($data as $row)
		{
			$parent = $row[$parent_key];
			$node = $nodes[$row[$child_key]];

			if (isset($nodes[$parent]))
			{
				$nodes[$parent]->add($node);
			}
			else
			{
				$tree->add($node);
			}
		}

		return $tree;
	}
}

// EOF

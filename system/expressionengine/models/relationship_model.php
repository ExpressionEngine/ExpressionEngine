<?php
/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2013, EllisLab, Inc.
 * @license		http://ellislab.com/expressionengine/user-guide/license.html
 * @link		http://ellislab.com
 * @since		Version 2.0
 * @filesource
 */

// ------------------------------------------------------------------------

/**
 * ExpressionEngine Relationship Model
 *
 * @package		ExpressionEngine
 * @subpackage	Core
 * @category	Model
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */
class Relationship_model extends CI_Model {
	
	const CHILD = 1;
	const PARENT = 2;
	const SIBLING = 3;

	protected $_table = 'relationships';

 	// --------------------------------------------------------------------
	
	/**
	 *
	 */
	public function node_query($node, $entry_ids)
	{
		if ($node->field_name() == 'siblings')
		{
			$entry_ids = array_keys($node->parent()->entry_ids);
		}

		if ( ! count($entry_ids))
		{
			return array();
		}

		$entry_ids = array_unique($entry_ids);
		$result_ids = $this->_run_node_query($node, $entry_ids);

		// @todo check node for categories
		return $result_ids;
	}

 	// --------------------------------------------------------------------
	
	/**
	 *
	 */
	protected function _run_node_query($node, $entry_ids)
	{
		$depths = $this->_min_max_branches($node);

		$longest_branch_length = $depths['longest'];
		$shortest_branch_length = $depths['shortest'];

		switch ($node->field_name())
		{
			case 'parents':
				$type = self::PARENT;
				$relative_child  = 'L0.parent_id';
				$relative_parent = 'L0.child_id';
					break;
			case 'siblings':
				$type = self::SIBLING;
				$relative_child  = 'L0.child_id';
				$relative_parent = 'S.child_id';
					break;
			default:
				$type = self::CHILD;
				$relative_child  = 'L0.child_id';
				$relative_parent = 'L0.parent_id';
		}

		$db = $this->db;

		$db->distinct();
		$db->select('L0.field_id as L0_field');
		$db->select($relative_parent.' AS L0_parent');
		$db->select($relative_child.' as L0_id');
		$db->from($this->_table.' as L0');

		for ($level = 0; $level <= $longest_branch_length; $level++)
		{
			$next_level = $level + 1;

			// If it's a parent tag, we reverse the query, which flips that
			// segment of the tree so that to the parser the parents simply
			// look like children of the name "parents". Savvy?
			if ($level == 0 && $type == self::PARENT)
			{
				$db->join(
					"{$this->_table} as L{$next_level}",
					"L{$level}.parent_id = L{$next_level}.parent_id".(($next_level >= $shortest_branch_length) ? " OR L{$next_level}.child_id = NULL" : ''),
					($next_level >= $shortest_branch_length) ? 'left' : ''
				);
			}
			else
			{
				$db->join(
					"{$this->_table} as L{$next_level}",
					"L{$level}.child_id = L{$next_level}.parent_id".(($next_level >= $shortest_branch_length) ? " OR L{$next_level}.parent_id = NULL" : ''),
					($next_level >= $shortest_branch_length) ? 'left' : ''
				);
			}

			if ($level > 0)
			{
				$db->select('L' . $level . '.field_id as L' . $level . '_field');
				$db->select('L' . $level . '.parent_id AS L' . $level . '_parent');
				$db->select('L' . $level . '.child_id as L' . $level . '_id');
			}
		}

		if ($type == self::SIBLING)
		{
			$db->join($this->_table.' as S', 'L0.parent_id = S.parent_id');
		}

		$db->where_in($relative_parent, $entry_ids);

		return $db->get()->result_array();
	}


 	// --------------------------------------------------------------------
	
	/**
	 * Branch length utility method.
	 *
	 */
	protected function _min_max_branches(EE_TreeNode $tree)
	{
		$it = new RecursiveIteratorIterator(
			new ParseNodeIterator(array($tree)),
			RecursiveIteratorIterator::LEAVES_ONLY
		);

		$shortest = INF;
		$longest = 0;

		foreach ($it as $leaf)
		{
			$depth = $it->getDepth();

			if ($tree->is_root())
			{
				$depth -= 1;
			}

			if ($depth < $shortest)
			{
				$shortest = $depth;
			}

			if ($depth > $longest)
			{
				$longest = $depth;
			}
		}

		if (is_infinite($shortest))
		{
			$shortest = 0;
		}

		return compact('shortest', 'longest');
	}
}
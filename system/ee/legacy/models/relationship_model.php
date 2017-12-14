<?php
/**
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2017, EllisLab, Inc. (https://ellislab.com)
 * @license   https://expressionengine.com/license
 */

/**
 * Relationship Model
 */
class Relationship_model extends CI_Model {

	const CHILD = 1;
	const PARENT = 2;
	const SIBLING = 3;
	const GRID = 4;

	protected $_table = 'relationships';

	/**
	 *
	 */
	public function node_query($node, $entry_ids, $grid_field_id = NULL, $fluid_field_data_id = NULL)
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
		return $this->_run_node_query($node, $entry_ids, $grid_field_id, $fluid_field_data_id);
	}

	/**
	 *
	 */
	protected function _run_node_query($node, $entry_ids, $grid_field_id, $fluid_field_data_id = NULL)
	{
		$shortest_branch_length = 0;
		$longest_branch_length = $this->_find_longest_branch($node);

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

		if ( ! $node->is_root() && $node->in_grid)
		{
			$type = self::GRID;
			$relative_parent = 'L0.grid_row_id';
		}

		$db = $this->db;

		$db->distinct();
		$db->select('L0.field_id as L0_field');
		$db->select('L0.grid_field_id as L0_grid_field_id');
		$db->select('L0.grid_col_id as L0_grid_col_id');
		$db->select('L0.grid_row_id as L0_grid_row_id');
		$db->select($relative_parent.' AS L0_parent');
		$db->select($relative_child.' as L0_id');
		$db->select('L0.order');
		$db->from($this->_table.' as L0');

		if (is_numeric($fluid_field_data_id))
		{
			$db->where('L0.fluid_field_data_id', $fluid_field_data_id);
		}
		else
		{
			$db->where('L0.fluid_field_data_id', 0);
		}

		if ($type == self::GRID)
		{
			$db->where_in('L0.grid_field_id', array($grid_field_id, '0'));
		}
		else
		{
			$db->where('L0.grid_field_id', 0);
		}

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
					"L{$level}.parent_id = L{$next_level}.parent_id",
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

			$db->order_by('L0.order', 'asc');

			if ($level > 0)
			{
				$db->order_by('L' . $level . '.order', 'asc');
				$db->select('L' . $level . '.field_id as L' . $level . '_field');
				$db->select('L' . $level . '.parent_id AS L' . $level . '_parent');
				$db->select('L' . $level . '.child_id as L' . $level . '_id');
				$db->select('L' . $level . '.order');
			}

		}

		if ($type == self::SIBLING)
		{
			$db->join($this->_table.' as S', 'L0.parent_id = S.parent_id');
		}

		$db->where_in($relative_parent, $entry_ids);

		// -------------------------------------------
		// 'relationships_query' hook.
		// - Use entry_ids and depths to reconstruct the above query as needed.
		//
		// 	 There are 3 ways to use this hook:
		// 	 	1) Add to the existing Active Record call, e.g. ee()->db->where('foo', 'bar');
		// 	 	2) Call ee()->db->_reset_select(); to terminate this AR call and start a new one
		// 	 	3) Call ee()->db->_reset_select(); and modify the currently compiled SQL string
		//
		//   All 3 require a returned query result array.
		//
			if (ee()->extensions->active_hook('relationships_query') === TRUE)
			{
				$result = ee()->extensions->call(
					'relationships_query',
					$node->field_name(),
					$entry_ids,
					array('longest' => $longest_branch_length, 'shortest' => 0),
					$db->_compile_select(FALSE, FALSE)
				);
			}
			else
			{
				$result = $db->get()->result_array();
			}
		//
		// -------------------------------------------

		return $this->overrideWithPreviewData($result, $fluid_field_data_id);
	}

	private function overrideWithPreviewData($result_array, $fluid_field_data_id)
	{
		$result = [];
		$fields = [];

		if (($data = ee()->session->cache('channel_entry', 'live-preview', FALSE)) !== FALSE)
		{
			$entry_id = $data['entry_id'];

			foreach ($result_array as $i => $row)
			{
				if ($row['L0_parent'] != $data['entry_id'])
				{
					$result[] = $row;
					continue;
				}

				$fields[$row['L0_field']] = TRUE;
			}

			if ($fluid_field_data_id)
			{
				list($fluid_field, $field_id) = explode(',', $fluid_field_data_id);
				$data = $data[$fluid_field]['fields'][$field_id];

				foreach (array_keys($data) as $rel_field)
				{
					$field_id = (int) str_replace('field_id_', '', $rel_field);
					$fields[$field_id] = TRUE;
				}
			}

			foreach (array_keys($fields) as $field_id)
			{
				if (isset($data['field_id_' . $field_id]) && array_key_exists('data', $data['field_id_' . $field_id]))
				{
					foreach ($data['field_id_' . $field_id]['data'] as $order => $id)
					{
						$result[] = [
							'L0_field' => $field_id,
							'L0_grid_field_id' => 0,
							'L0_grid_col_id' => 0,
							'L0_grid_row_id' => 0,
							'L0_parent' => $entry_id,
							'L0_id' => $id,
							'order' => $order,
						];
					}
				}
			}
		}

		return $result;
	}

	/**
	 * Branch length utility method.
	 *
	 */
	protected function _find_longest_branch(EE_TreeNode $tree)
	{
		$it = new RecursiveIteratorIterator(
			new ParseNodeIterator(array($tree)),
			RecursiveIteratorIterator::LEAVES_ONLY
		);

		$longest = 0;

		foreach ($it as $leaf)
		{
			$depth = $it->getDepth();

			if ($tree->is_root())
			{
				$depth -= 1;
			}

			if ($depth > $longest)
			{
				$longest = $depth;
			}
		}

		return $longest;
	}
}

// EOF

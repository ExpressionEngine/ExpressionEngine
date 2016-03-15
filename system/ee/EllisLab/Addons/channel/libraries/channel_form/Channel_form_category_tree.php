<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2016, EllisLab, Inc.
 * @license		https://expressionengine.com/license
 * @link		https://ellislab.com
 * @since		Version 2.7
 * @filesource
 */

// ------------------------------------------------------------------------

/**
 * ExpressionEngine Channel Form Category Tree Factory
 *
 * @package		ExpressionEngine
 * @subpackage	Core
 * @category	Core
 * @author		EllisLab Dev Team
 * @link		https://ellislab.com
 */
class Channel_form_category_tree {

	public function create($group_id = '', $action = '', $default = '', $selected = '')
	{
		return new Channel_form_category_tree_obj($group_id, $action, $default, $selected);
	}
}

// ------------------------------------------------------------------------

/**
 * ExpressionEngine Channel Form Category Tree Class
 *
 * @package		ExpressionEngine
 * @subpackage	Core
 * @category	Core
 * @author		EllisLab Dev Team
 * @link		https://ellislab.com
 */
class Channel_form_category_tree_obj {

	protected $categories = array();

	// --------------------------------------------------------------------

	/**
	 * Category Tree
	 *
	 * This function (and the next) create a hierarchy tree
	 * of categories.
	 *
	 * @param 	integer
	 * @param 	integer
	 * @param	mixed
	 * @param	mixed
	 */
	public function __construct($group_id = '', $action = '', $default = '', $selected = '')
	{
		// Fetch category group ID number
		if ($group_id == '')
		{
			if ( ! $group_id = ee()->input->get_post('group_id'))
			{
				return FALSE;
			}
		}

		// If we are using the category list on the "new entry" page
		// we need to gather the selected categories so we can highlight
		// them in the form.
		if ($action == 'preview')
		{
			$catarray = array();

			foreach ($_POST as $key => $val)
			{
				if (strpos($key, 'category') !== FALSE && is_array($val))
				{
						foreach ($val as $k => $v)
						{
							$catarray[$v] = $v;
						}
				}
			}
		}

		if ($action == 'edit')
		{
			$catarray = array();

			if (is_array($selected))
			{
				foreach ($selected as $key => $val)
				{
					$catarray[$val] = $val;
				}
			}
		}

		// Fetch category groups
		$group_ids = explode('|', $group_id);

		ee()->db->select('cat_name, cat_id, parent_id');
		ee()->db->where_in('group_id', $group_ids);
		ee()->db->order_by('group_id, parent_id, cat_order');
		$kitty_query = ee()->db->get('categories');

		if ($kitty_query->num_rows() == 0)
		{
			return FALSE;
		}

		// Assign the query result to a multi-dimensional array

		foreach($kitty_query->result_array() as $row)
		{
			$cat_array[$row['cat_id']]  = array($row['parent_id'], $row['cat_name']);
		}

		$size = count($cat_array) + 1;

		// Build our output...

		$sel = '';

		foreach($cat_array as $key => $val)
		{
			if (0 == $val['0'])
			{
				if ($action == 'new')
				{
					$sel = ($default == $key) ? '1' : '';
				}
				else
				{
					$sel = (isset($catarray[$key])) ? '1' : '';
				}

				$s = ($sel != '') ? " selected='selected'" : '';

				$this->categories[] = "<option value='".$key."'".$s.">".$val['1']."</option>\n";

				$this->_category_subtree_form($key, $cat_array, $depth=1, $action, $default, $selected);
			}
		}
	}

	// --------------------------------------------------------------------

	public function categories()
	{
		return $this->categories;
	}

	// --------------------------------------------------------------------

	/**
	 * Category sub-tree
	 *
	 * This function works with the preceeding one to show a
	 * hierarchical display of categories
	 *
	 * @param 	integer
	 * @param	array
	 * @param	integer
	 * @param	mixed
	 * @param 	mixed
	 * @param	mixed
	 */
	protected function _category_subtree_form($cat_id, $cat_array, $depth, $action, $default = '', $selected = '')
	{
		$spcr = "&nbsp;";

		// Just as in the function above, we'll figure out which items are selected.
		if ($action == 'preview')
		{
			$catarray = array();

			foreach ($_POST as $key => $val)
			{
				if (strpos($key, 'category') !== FALSE && is_array($val))
				{
					foreach ($val as $k => $v)
					{
						$catarray[$v] = $v;
					}
				}
			}
		}

		if ($action == 'edit')
		{
			$catarray = array();

			if (is_array($selected))
			{
				foreach ($selected as $key => $val)
				{
					$catarray[$val] = $val;
				}
			}
		}

		$indent = $spcr.$spcr.$spcr.$spcr;

		if ($depth == 1)
		{
			$depth = 4;
		}
		else
		{
			$indent = str_repeat($spcr, $depth).$indent;

			$depth = $depth + 4;
		}

		$sel = '';

		foreach ($cat_array as $key => $val)
		{
			if ($cat_id == $val['0'])
			{
				$pre = ($depth > 2) ? "&nbsp;" : '';

				if ($action == 'new')
				{
					$sel = ($default == $key) ? '1' : '';
				}
				else
				{
					$sel = (isset($catarray[$key])) ? '1' : '';
				}

				$s = ($sel != '') ? " selected='selected'" : '';

				$this->categories[] = "<option value='".$key."'".$s.">".$pre.$indent.$spcr.$val['1']."</option>\n";

				$this->_category_subtree_form($key, $cat_array, $depth, $action, $default, $selected);
			}
		}
	}
}
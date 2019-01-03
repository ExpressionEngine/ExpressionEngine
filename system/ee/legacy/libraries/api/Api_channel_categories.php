<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2019, EllisLab Corp. (https://ellislab.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

/**
 * Channel Categories API
 */
class Api_channel_categories extends Api {


	/**
	 * Just a note, these really should be protected/private and set by the API itself
	 * However, we're abusing them in other places, so they are set to public for now.
	 * Third-party devs, don't rely on these being public, m'kay?
	 */
	public $assign_cat_parent	= TRUE;
	public $categories			= array();
	public $cat_parents			= array();
	public $cat_array			= array();

	/**
	 * Constructor
	 */
	public function __construct()
	{
		parent::__construct();

		ee()->load->model('channel_model');
		$this->assign_cat_parent = (ee()->config->item('auto_assign_cat_parents') == 'n') ? FALSE : TRUE;
	}

	/**
	 * Category tree
	 *
	 * This function (and the next) create a higherarchy tree
	 * of categories.  There are two versions of the tree. The
	 * "text" version is a list of links allowing the categories
	 * to be edited.  The "form" version is displayed in a
	 * multi-select form on the new entry page.
	 *
	 * @param 	mixed		int or array of category group ids
	 * @param
	 * @param 	string		c for custom, a alphabetical
	 *
	 * @return 	mixed		FALSE if no results or cat array of results.
	 */
	public function category_tree($group_id, $selected = '', $order = 'c')
	{
		// reset $this->categories
		$this->initialize(array(
				'categories'	=> array(),
				'cat_parents'	=> array(),
				'cat_array'		=> array(),
			)
		);

		// Fetch the category group ID number
		// Drop it into an array for where_in()
		$group_ids = $group_id;

		if ( ! is_array($group_id))
		{
			$group_ids = explode('|', $group_id);
		}

		$catarray = array();

		if (is_array($selected))
		{
			foreach ($selected as $key => $val)
			{
				$catarray[$val] = $val;
			}
		}
		else
		{
			$catarray[$selected] = $selected;
		}

		$order = ($order == 'a') ? "cat_name" : "cat_order";

		$query = ee()->db->select('cat_name, cat_id, parent_id, cat_image, cat_description, g.group_id, group_name, cat_url_title')
						->from('category_groups g, categories c')
						->where('g.group_id', 'c.group_id', FALSE)
						->where_in('g.group_id', $group_ids)
						->order_by('group_id, parent_id, '.$order, 'asc')
						->get();

		if ($query->num_rows() === 0)
		{
			return FALSE;
		}

		// Assign the query result to a multi-dimensional array

		foreach($query->result_array() as $row)
		{
			$cat_array[$row['cat_id']]	= array(
				$row['parent_id'],
				$row['cat_name'],
				$row['group_id'],
				$row['group_name'],
				$row['cat_image'],
				$row['cat_description'],
				$row['cat_url_title']
			);
		}

		// Build our output...

		foreach($cat_array as $key => $val)
		{
			if (0 == $val[0])
			{
				$sel = (isset($catarray[$key])) ? TRUE : FALSE;
				$depth = 1;

				$this->categories[$key] = array($key, $val[1], (int) $val[2], $val[3], $sel, $depth, FALSE, $val[4], $val[5], $val[6]);
				//$this->categories[$key] = array('cat_id' => $key, 'cat_name' => $val['1'], 'group_id' => $val['2'], 'group_name' => $val['3'], 'selected' => $sel, 'depth' => $depth);
				$this->_category_subtree($key, $cat_array, $depth, $selected);
			}
		}

		return $this->categories;
	}

	/**
	 * Category sub-tree
	 *
	 * This function works with the preceeding one to show a
	 * hierarchical display of categories
	 *
	 * @param	mixed
	 * @param 	array
	 * @param 	int
	 * @param
	 */
	protected function _category_subtree($cat_id, $cat_array, $depth, $selected = array())
	{
		// Just as in the function above, we'll figure out which items are selected.

		$catarray = array();

		if (is_array($selected))
		{
			foreach ($selected as $key => $val)
			{
				$catarray[$val] = $val;
			}
		}

		$depth++;

		foreach ($cat_array as $key => $val)
		{
			if ($cat_id == $val['0'])
			{
				$sel = (isset($catarray[$key])) ? TRUE : FALSE;
				$this->categories[$key] = array($key, $val['1'], (int) $val['2'], $val['3'], $sel, $depth, (int) $val[0], $val[4], $val[5], $val[6]);
				//$this->categories[$key] = array('cat_id' => $key, 'cat_name' => $val['1'], 'group_id' => $val['2'], 'group_name' => $val['3'], 'selected' => $sel, 'depth' => $depth);

				$this->_category_subtree($key, $cat_array, $depth, $selected);
			}
		}
	}

	/**
	 * Category Form Tree
	 *
	 * @param 	string
	 * @param	mixed
	 * @param	boolean
	 */
	public function category_form_tree($nested = 'y', $categories = FALSE, $sites = FALSE)
	{
		$order  = ($nested == 'y') ? 'group_id, parent_id, cat_name' : 'cat_name';

		ee()->db->select('categories.group_id, categories.parent_id, categories.cat_id, categories.cat_name');
		ee()->db->from('categories');

		if ($sites == FALSE)
		{
			ee()->db->where('site_id', ee()->config->item('site_id'));
		}
		elseif ($sites != 'all')
		{
			if (is_array($sites))
			{
				$sites = implode('|', $sites);
			}

			ee()->functions->ar_andor_string($sites, 'site_id');
		}


		if ($categories !== FALSE)
		{
			if (is_array($categories))
			{
				$categories = implode('|', $categories);
			}

			ee()->functions->ar_andor_string($categories, 'cat_id', 'exp_categories');
		}

		ee()->db->order_by($order);

		$query = ee()->db->get();

		// Load the text helper
		ee()->load->helper('text');

		if ($query->num_rows() > 0)
		{
			$categories = array();

			foreach ($query->result_array() as $row)
			{
				$categories[] = array($row['group_id'], $row['cat_id'], entities_to_ascii($row['cat_name']), $row['parent_id']);
			}

			if ($nested == 'y')
			{
				foreach($categories as $key => $val)
				{
					if (0 == $val['3'])
					{
						$this->cat_array[] = array($val['0'], $val['1'], $val['2']);
						$this->category_form_subtree($val['1'], $categories, $depth=1);
					}
				}
			}
			else
			{
				$this->cat_array = $categories;
			}
		}

		return $this->cat_array;
	}

	/**
	 * Category Edit Sub-tree
	 *
	 * @param 	string	category id
	 * @param	array 	array of categories
	 * @param 	int
	 * @return 	void
	 */
	public function category_form_subtree($cat_id, $categories, $depth)
	{
		$spcr = '!-!';

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

		foreach ($categories as $key => $val)
		{
			if ($cat_id == $val['3'])
			{
				$pre = ($depth > 2) ? $spcr : '';

				$this->cat_array[] = array($val['0'], $val['1'], $pre.$indent.$spcr.$val['2']);

				$this->category_form_subtree($val['1'], $categories, $depth);
			}
		}
	}

	/**
	 * Fetch Parent Category ID
	 *
	 * @param 	array 	category array
	 */
	public function fetch_category_parents($cat_array)
	{
		if (count($cat_array) == 0)
		{
			return;
		}

		$sql = "SELECT parent_id FROM exp_categories WHERE site_id = '".ee()->db->escape_str(ee()->config->item('site_id'))."' AND (";

		foreach ($cat_array as $val)
		{
			$val = (int) $val;
			$sql .= " cat_id = '$val' OR ";
		}

		$sql = substr($sql, 0, -3).")";

		$query = ee()->db->query($sql);

		if ($query->num_rows() == 0)
		{
			return;
		}

		$temp = array();

		foreach ($query->result_array() as $row)
		{
			if ($row['parent_id'] != 0)
			{
				$this->cat_parents[] = $row['parent_id'];

				$temp[] = $row['parent_id'];
			}
		}

		$this->fetch_category_parents($temp);
	}

	/**
	 * Fetch Allowed Category Groups
	 *
	 * @param 	string		String of cat groups.  either one, or a piped list
	 * @return 	mixed
	 */
	public function fetch_allowed_category_groups($cat_group)
	{
		if (ee()->cp->allowed_group('can_admin_channels') OR ee()->cp->allowed_group('can_edit_categories'))
		{
			if (! is_array($cat_group))
			{
				$cat_group = explode('|', $cat_group);
			}

			ee()->load->model('category_model');
			$catg_query = ee()->category_model->get_category_group_name($cat_group);

			$link_info = array();

			foreach($catg_query->result_array() as $catg_row)
			{
				$link_info[] = array('group_id' => $catg_row['group_id'], 'group_name' => $catg_row['group_name']);
			}

			return $link_info;
		}
		else
		{
			return FALSE;
		}
	}

	/**
	 * Get Categories
	 *
	 * @return 	array
	 */
	public function get_categories()
	{
		return $this->categories;
	}

}

// END CLASS

// EOF

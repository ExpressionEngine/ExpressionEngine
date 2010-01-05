<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		ExpressionEngine Dev Team
 * @copyright	Copyright (c) 2003 - 2010, EllisLab, Inc.
 * @license		http://expressionengine.com/docs/license.html
 * @link		http://expressionengine.com
 * @since		Version 2.0
 * @filesource
 */
 
// ------------------------------------------------------------------------

/**
 * ExpressionEngine Categories Class
 *
 * @package		ExpressionEngine
 * @subpackage	Core
 * @category	Core
 * @author		ExpressionEngine Dev Team
 * @link		http://expressionengine.com
 */
class Categories {
	
	var $assign_cat_parent	= TRUE;
	var $categories			= array();
	var $cat_parents		= array();
	var $cat_array			= array();
	
	/**
	 * Constructor
	 *
	 */	
	function Categories()
	{
		$this->EE =& get_instance();
	}

	
	// --------------------------------------------------------------------
	
	/**
	 * Set Certain Default Control Panel View Variables
	 *
	 * @access	public
	 * @return	void
	 */		
	/** --------------------------------------------
	/**	 Category tree
	/** --------------------------------------------*/
	// This function (and the next) create a higherarchy tree
	// of categories.  There are two versions of the tree. The
	// "text" version is a list of links allowing the categories
	// to be edited.  The "form" version is displayed in a
	// multi-select form on the new entry page.
	//--------------------------------------------

	function category_tree($group_id = '', $action = '', $default = '', $selected = '')
	{
		// Fetch category group ID number

		if ($group_id == '')
		{
			if ( ! $group_id = $this->EE->input->get_post('group_id'))
				return false;
		}

		// If we are using the category list on the "new entry" page
		// and the person is returning to the edit page after previewing,
		// we need to gather the selected categories so we can highlight
		// them in the form.

		if ($action == 'preview' OR $action == 'save')
		{
			$catarray = array();

			if ($this->EE->input->post('category') && is_array($this->input->post('category')))
			{
				foreach($_POST['category'] as $v)
				{
					$catarray[$v] = $v;
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

		if ( ! is_numeric(str_replace('|', "", $group_id)))
		{
			return FALSE;
		}

		$query = $this->EE->db->query("SELECT cat_name, cat_id, parent_id, group_id
							 FROM exp_categories
							 WHERE group_id IN ('".str_replace('|', "','", $this->EE->db->escape_str($group_id))."')
							 ORDER BY group_id, parent_id, cat_order");

		if ($query->num_rows() == 0)
		{
			return false;
		}

		// Assign the query result to a multi-dimensional array

		foreach($query->result_array() as $row)
		{
			$cat_array[$row['cat_id']]	= array($row['parent_id'], $row['cat_name'], $row['group_id']);
		}

		$size = count($cat_array) + 1;

		$this->categories[] = $this->EE->dsp->input_select_header('category[]', 1, $size);

		// Build our output...

		$sel = '';

		foreach($cat_array as $key => $val)
		{
			if (0 == $val['0'])
			{
				if (isset($last_group) && $last_group != $val['2'])
				{
					$this->categories[] = $this->EE->dsp->input_select_option('', '-------');
				}

				if ($action == 'new')
				{
					$sel = ($default == $key) ? '1' : '';
				}
				else
				{
					$sel = (isset($catarray[$key])) ? '1' : '';
				}

				$this->categories[] = $this->EE->dsp->input_select_option($key, $val['1'], $sel);
				$this->category_subtree($key, $cat_array, $depth=1, $action, $default, $selected);

				$last_group = $val['2'];
			}
		}

		$this->categories[] = $this->EE->dsp->input_select_footer();
	}


	/** --------------------------------------------
	/**	 Category sub-tree
	/** --------------------------------------------*/
	// This function works with the preceeding one to show a
	// hierarchical display of categories
	//--------------------------------------------

	function category_subtree($cat_id, $cat_array, $depth, $action, $default = '', $selected = '')
	{
		$spcr = "&nbsp;";


		// Just as in the function above, we'll figure out which items are selected.

		if ($action == 'preview' OR $action == 'save')
		{
			$catarray = array();

			if ($this->EE->input->post('category') && is_array($this->EE->input->post('category')))
			{
				foreach($_POST['category'] as $v)
				{
					$catarray[$v] = $v;
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

				$this->categories[] = $this->EE->dsp->input_select_option($key, $pre.$indent.$spcr.$val['1'], $sel);
				$this->category_subtree($key, $cat_array, $depth, $action, $default, $selected);
			}
		}
	}
	
	// --------------------------------
	// Category Sub-tree
	// --------------------------------
	function category_edit_subtree($cat_id, $categories, $depth)
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

				$this->category_edit_subtree($val['1'], $categories, $depth);
			}
		}
	}
	

	/** ----------------------------------------
	/**	 Fetch the parent category ID
	/** ----------------------------------------*/

	function fetch_category_parents($cat_array = '')
	{
		if (count($cat_array) == 0)
		{
			return;
		}

		$sql = "SELECT parent_id FROM exp_categories WHERE site_id = '".$this->EE->db->escape_str($this->EE->config->item('site_id'))."' AND (";

		foreach($cat_array as $val)
		{
			$sql .= " cat_id = '$val' OR ";
		}

		$sql = substr($sql, 0, -3).")";

		$query = $this->EE->db->query($sql);

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
	
	
}

/* End of file Cp.php */
/* Location: ./system/expressionengine/libraries/Categories.php */
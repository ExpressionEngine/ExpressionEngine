<?php  if (!defined('BASEPATH')) exit('No direct script access allowed');
/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		ExpressionEngine Dev Team
 * @copyright	Copyright (c) 2003 - 2010, EllisLab, Inc.
 * @license		http://expressionengine.com/user_guide/license.html
 * @link		http://expressionengine.com
 * @since		Version 2.0
 * @filesource
 */
 
// ------------------------------------------------------------------------

/**
 * ExpressionEngine Channel Categories API Class
 *
 * @package		ExpressionEngine
 * @subpackage	Core
 * @category	Core
 * @author		ExpressionEngine Dev Team
 * @link		http://expressionengine.com
 */
class Api_channel_categories extends Api {

	
	var $assign_cat_parent	= TRUE;
	var $categories			= array();
	var $cat_parents		= array();
	var $cat_array			= array();
		
	/**
	 * Constructor
	 *
	 */
	function Api_channel_categories()
	{
		$this->EE =& get_instance();
		$this->EE->load->model('channel_model');
		$this->assign_cat_parent = ($this->EE->config->item('auto_assign_cat_parents') == 'n') ? FALSE : TRUE; 
	}

	// --------------------------------------------------------------------

	/** --------------------------------------------
	/**	 Category tree
	/** --------------------------------------------*/
	// This function (and the next) create a higherarchy tree
	// of categories.  There are two versions of the tree. The
	// "text" version is a list of links allowing the categories
	// to be edited.  The "form" version is displayed in a
	// multi-select form on the new entry page.
	//--------------------------------------------

	function category_tree($group_id, $selected = '', $order = 'c')
	{
		// Fetch category group ID number

		if (is_array($group_id))
		{
			$group_ids = implode("','", $group_id);
		}
		else
		{
			$group_ids = str_replace('|', "','", $this->EE->db->escape_str($group_id));
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

		// Fetch category groups
		if ( ! is_numeric(str_replace('|', "", $group_id)))
		{
			return FALSE;
		}

		$order = ($order == 'a') ? "cat_name" : "cat_order";
		
		$query = $this->EE->db->query("SELECT cat_name, cat_id, parent_id, g.group_id, group_name 
							 FROM exp_category_groups g, exp_categories c
							 WHERE g.group_id = c.group_id AND g.group_id IN ('".$group_ids."')
							 ORDER BY group_id, parent_id, ".$order);

		if ($query->num_rows() == 0)
		{
			return false;
		}

		// Assign the query result to a multi-dimensional array

		foreach($query->result_array() as $row)
		{
			$cat_array[$row['cat_id']]	= array($row['parent_id'], $row['cat_name'], $row['group_id'], $row['group_name']);
		}

		// Build our output...

		foreach($cat_array as $key => $val)
		{
			if (0 == $val['0'])
			{
				$sel = (isset($catarray[$key])) ? TRUE : FALSE;
				$depth = 1;

				$this->categories[$key] = array($key, $val['1'], $val['2'], $val['3'], $sel, $depth);
				//$this->categories[$key] = array('cat_id' => $key, 'cat_name' => $val['1'], 'group_id' => $val['2'], 'group_name' => $val['3'], 'selected' => $sel, 'depth' => $depth);				
				$this->_category_subtree($key, $cat_array, $depth, $selected);
			}
		}
		
		return $this->categories;
	}


	/** --------------------------------------------
	/**	 Category sub-tree
	/** --------------------------------------------*/
	// This function works with the preceeding one to show a
	// hierarchical display of categories
	//--------------------------------------------

	function _category_subtree($cat_id, $cat_array, $depth, $selected = array())
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
				$this->categories[$key] = array($key, $val['1'], $val['2'], $val['3'], $sel, $depth);
				//$this->categories[$key] = array('cat_id' => $key, 'cat_name' => $val['1'], 'group_id' => $val['2'], 'group_name' => $val['3'], 'selected' => $sel, 'depth' => $depth);					
				
				$this->_category_subtree($key, $cat_array, $depth, $selected);
			}
		}
	}

	// --------------------------------
	// Category Edit Sub-tree legacy
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

				$this->_category_form_subtree($val['1'], $categories, $depth);
			}
		}
	}


	function category_form_tree($nested = 'y', $categories = FALSE, $sites = FALSE)
	{
		$order  = ($nested == 'y') ? 'group_id, parent_id, cat_name' : 'cat_name';

		$this->EE->db->select('categories.group_id, categories.parent_id, categories.cat_id, categories.cat_name');
		$this->EE->db->from('categories');
		
		if ($sites == FALSE)
		{
			$this->EE->db->where('site_id', $this->EE->config->item('site_id'));
		}
		elseif ($sites != 'all')
		{
			if ( ! is_array($sites))
			{
				$sites = implode('|', $sites);
			}
			
			$this->EE->functions->ar_andor_string($sites, 'site_id');
		}		
			
		
		if ($categories !== FALSE)
		{
			if ( is_array($categories))
			{
				$categories = implode('|', $categories);
			}
			
			$this->EE->functions->ar_andor_string($categories, 'cat_id', 'exp_categories');
		}
				
		$this->EE->db->order_by($order);
		
		$query = $this->EE->db->get();

		// Load the text helper
		$this->EE->load->helper('text');
				
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
						$this->_category_form_subtree($val['1'], $categories, $depth=1);
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

	
	// --------------------------------
	// Category Edit Sub-tree
	// --------------------------------
	function _category_form_subtree($cat_id, $categories, $depth)
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

				$this->_category_form_subtree($val['1'], $categories, $depth);
			}
		}
	}


	/** ----------------------------------------
	/**	 Fetch the parent category ID
	/** ----------------------------------------*/

	function fetch_category_parents($cat_array)
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


	/** ----------------------------------------
	/**	 Fetch allowed category group edit links
	/** ----------------------------------------*/

	function fetch_allowed_category_groups($cat_group)
	{	
		if ($this->EE->cp->allowed_group('can_admin_channels') OR $this->EE->cp->allowed_group('can_edit_categories'))
		{
			if ( ! is_array($cat_group))
			{
				$cat_group = explode('|', $cat_group);
			}
			
			$this->EE->load->model('category_model');
			$catg_query = $this->EE->category_model->get_category_group_name($cat_group);

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
}

// END CLASS

/* End of file Api_channel_categories.php */
/* Location: ./system/expressionengine/libraries/api/Api_channel_categories.php */
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
 * ExpressionEngine Category Model
 *
 * @package		ExpressionEngine
 * @subpackage	Core
 * @category	Model
 * @author		ExpressionEngine Dev Team
 * @link		http://expressionengine.com
 */
class Category_model extends CI_Model {

	/**
	 * Get Categories
	 *
	 * @access	public
	 * @return	object
	 */
	function get_categories($group_id = '', $site_id = TRUE)
	{
		if ($group_id != '')
		{
			$this->db->where('group_id', $group_id);
		}

		if ($site_id !== TRUE)
		{
			$this->db->where('site_id', $this->config->item('site_id'));
		}

		$this->db->select('group_id, group_name, sort_order');
		$this->db->from('category_groups');
		$this->db->order_by('group_name');
		
		return $this->db->get();
	}

	// --------------------------------------------------------------------

	/**
	 * Get Channel Categories
	 *
	 * Gets category information for a given category group, by default only fetches cat_id and cat_name
	 *
	 * @access	public
	 * @param	int
	 * @return	mixed
	 */
	function get_channel_categories($cat_group, $additional_fields = array(), $additional_where = array())
	{
		if ( ! is_array($additional_fields))
		{
			$additional_fields = array($additional_fields);
		}

		if ( ! isset($additional_where[0]))
		{
			$additional_where = array($additional_where);
		}

		if (count($additional_fields) > 0)
		{
			$this->db->select(implode(',', $additional_fields));
		}


		$this->db->select("cat_id, cat_name");
		$this->db->from("categories");
		$this->db->where("group_id", $cat_group);

		foreach ($additional_where as $where)
		{
			foreach ($where as $field => $value)
			{
				if (is_array($value))
				{
					$this->db->where_in($field, $value);
				}
				else
				{
					$this->db->where($field, $value);
				}
			}
		}

		$this->db->order_by('cat_name');

		return $this->db->get();
	}

	// --------------------------------------------------------------------

	/**
	 * Delete Category
	 *
	 * @access	public
	 * @return	object
	 */
	function delete_category($cat_id = '')
	{
		// get group id for future queries
		$category_group = $this->get_category_name_group($cat_id);
		$group_id = $category_group->row('group_id');

		$this->db->where('cat_id', $cat_id);
		$this->db->delete('category_posts');

		$this->db->where('parent_id', $cat_id);
		$this->db->where('group_id', $group_id);
		$this->db->set('parent_id', 0);
		$this->db->update('categories');

		$this->db->where('cat_id', $cat_id);
		$this->db->where('group_id', $group_id);
		$this->db->delete('categories');

		$this->db->where('cat_id', $cat_id);
		$this->db->delete('category_field_data');

		// return the group (not category) that was deleted from so the calling function can make use of it
		return $group_id;
	}

	// --------------------------------------------------------------------

	/**
	 * Get Category Group Name
	 *
	 * @access	public
	 * @return	mixed
	 */
	function get_category_group_name($group_id)
	{
		$this->db->select('group_id, group_name');

		if (is_array($group_id))
		{
			$this->db->where_in('group_id', $group_id);			
		}
		else
		{
			$this->db->where('group_id', $group_id);
		}
		
		return $this->db->get('category_groups');
	}

	// --------------------------------------------------------------------

	/**
	 * Get Category Label Name
	 *
	 * @access	public
	 * @return	mixed
	 */
	function get_category_label_name($group_id, $field_id)
	{
		$this->db->select('field_label, field_name');
		$this->db->where('field_id', $field_id);
		$this->db->where('group_id', $group_id);
		return $this->db->get('category_fields');
	}

	// --------------------------------------------------------------------

	/**
	 * Get Category Name
	 *
	 * @access	public
	 * @return	mixed
	 */
	function get_category_name_group($cat_id)
	{
		$this->db->select('cat_name, group_id');
		$this->db->where('cat_id', $cat_id);
		return $this->db->get('categories');
	}

	// --------------------------------------------------------------------

	/**
	 * Update Category Group
	 *
	 * @access	public
	 * @return	mixed
	 */
	function update_category_group($group_id = '', $data)
	{
		$this->db->where('group_id', $group_id);
		$this->db->update('category_groups', $data);
	}

	// --------------------------------------------------------------------

	/**
	 * Insert Category Group
	 *
	 * @access	public
	 * @return	void
	 */
	function insert_category_group($data)
	{
		// new categories will not need a group_id (they're new)
		// but may have an id of "" if the input->post() is passed direct
		unset($data['group_id']);
		$data['site_id'] = $this->config->item('site_id');

		$this->db->insert('category_groups', $data);
	}

	// --------------------------------------------------------------------

	/**
	 * Delete Category Group
	 *
	 * @access	public
	 * @return	void
	 */
	function delete_category_group($group_id)
	{
		$this->db->select('cat_id');
		$this->db->where('group_id', $group_id);
		$query = $this->db->get('categories');

		if ($query->num_rows() > 0)
		{
			$cat_ids = array();

			foreach ($query->result() as $row)
			{
				$cat_ids[] = $row->cat_id;
			}

			$this->db->where_in('cat_id', $cat_ids);
			$this->db->delete('category_posts');
		}

		$this->db->delete('category_groups', array('group_id' => $group_id));
		$this->db->delete('categories', array('group_id' => $group_id));

		$this->db->select('field_id');
		$this->db->where('group_id', $group_id);
		$query = $this->db->get('category_fields');

		if ($query->num_rows() > 0)
		{
			// load dbforge for column dropping
			$this->load->dbforge();

			$field_ids = array();

			foreach ($query->result() as $row)
			{
				$field_ids[] = $row->field_id;
			}

			foreach ($field_ids as $field_id)
			{
				$this->dbforge->drop_column('category_field_data', 'field_id_'.$field_id);
				$this->dbforge->drop_column('category_field_data', 'field_ft_'.$field_id);
			}
		}

		$this->db->delete('category_fields', array('group_id' => $group_id));
		$this->db->delete('category_field_data', array('group_id' => $group_id));
	}

	// --------------------------------------------------------------------

	/**
	 * Duplicate Category Name Check
	 *
	 * @access	public
	 * @return	boolean
	 */
	function is_duplicate_category_name($cat_url_title = '', $cat_id = '', $group_id = '')
	{
		$this->db->where('site_id', $this->config->item('site_id'));
		$this->db->where('cat_url_title', $cat_url_title);
		$this->db->where('group_id', $group_id);
		$this->db->from('categories');

		if ($cat_id != '')
		{
			$this->db->where('cat_id != '.$cat_id);
		}

		$count = $this->db->count_all_results();

		// if we find any - it's a duplicate
		return ($count > 0);
	}

	// --------------------------------------------------------------------

	/**
	 * Duplicate Category Group Check
	 *
	 * @access	public
	 * @return	boolean
	 */
	function is_duplicate_category_group($group_name, $group_id = '')
	{
		$this->db->where('site_id', $this->config->item('site_id'));
		$this->db->where('group_name', $group_name);
		$this->db->from('category_groups');

		if ($group_id != '')
		{
			$this->db->where('group_id != '.$group_id);
		}

		$count = $this->db->count_all_results();

		// if we find any - it's a duplicate
		return ($count > 0);
	}

	// --------------------------------------------------------------------

	/**
	 * Delete Custom Category Field
	 *
	 * @access	public
	 * @return	void
	 */
	function delete_category_field($group_id, $field_id)
	{
		$this->db->delete('category_fields', array('field_id' => $field_id));

		$this->db->query("ALTER TABLE exp_category_field_data DROP COLUMN field_id_{$field_id}");
		$this->db->query("ALTER TABLE exp_category_field_data DROP COLUMN field_ft_{$field_id}");
	}

	// --------------------------------------------------------------------

}

/* End of file category_model.php */
/* Location: ./system/expressionengine/models/category_model.php */
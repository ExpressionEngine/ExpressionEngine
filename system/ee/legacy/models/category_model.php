<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2016, EllisLab, Inc.
 * @license		https://expressionengine.com/license
 * @link		https://ellislab.com
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
 * @author		EllisLab Dev Team
 * @link		https://ellislab.com
 */
class Category_model extends CI_Model {


	public function get_entry_categories($entry_ids)
	{
		$result = array();
		$entry_ids = (array) $entry_ids;

		if ( ! count($entry_ids))
		{
			return $result;
		}

		$sql = "SELECT c.*, cp.entry_id, cg.field_html_formatting, fd.*
				FROM exp_categories AS c
				LEFT JOIN exp_category_posts AS cp ON c.cat_id = cp.cat_id
				LEFT JOIN exp_category_field_data AS fd ON fd.cat_id = c.cat_id
				LEFT JOIN exp_category_groups AS cg ON cg.group_id = c.group_id
				WHERE cp.entry_id IN (".implode(', ', $entry_ids).")
				ORDER BY c.group_id, c.parent_id, c.cat_order";

		$category_query = $this->db->query($sql);

		foreach ($category_query->result_array() as $row)
		{
			if ( ! isset($result[$row['entry_id']]))
			{
				$result[$row['entry_id']] = array();
			}

			$result[$row['entry_id']][] = $row;
		}

		return $result;
	}

	// --------------------------------------------------------------------

	/**
	 * Get category groups
	 *
	 * This function returns the db object of category groups.
	 *
	 * @param 	int			group id to fetch
	 * @param 	Boolean		whether or not to limit by site_id
	 * @param 	int			whether or not to include the returned category
	 * 						groups in publish or files category assignment lists.
	 *
	 * Valid options are:
	 * $options = array(
	 *		(int) 0 => ALL Categories,
	 *		(int) 1 => Excluded from publish,
	 *		(int) 2 => Excluded form files
	 * );
	 *
	 * So in the file upload preferences, we use:
	 *			WHERE exclude_group = 0
	 *			OR exclude_group = 1
	 *
	 * And basically the opposite on channel group assignment preferences.
	 *
	 * @return 	object		db class result object
	 */
	public function get_category_groups($group_id = '', $site_id = TRUE, $include=0)
	{
		if ($group_id != '')
		{
			if ( ! is_array($group_id))
			{
				$group_id = array($group_id);
			}

			$this->db->where_in('group_id', $group_id);
		}

		if ($site_id !== TRUE)
		{
			$this->db->where('site_id', $this->config->item('site_id'));
		}

		if ($include !== 0)
		{
			$this->db->where('(exclude_group = "0" OR exclude_group = "' . (int) $include . '")', NULL, FALSE);
		}

		return $this->db->select('group_id, group_name, sort_order')
						->from('category_groups')
						->order_by('group_name')
						->get();
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

		// -------------------------------------------
		// 'category_delete' hook.
		//
		if (ee()->extensions->active_hook('category_delete') === TRUE)
		{
			ee()->extensions->call('category_delete', array($cat_id));
		}
		//
		// -------------------------------------------

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
	 * Get Category Parent ID
	 *
	 * @access	public
	 * @param integer $cat_id The category ID you need the parent ID for
	 * @return integer The parent_id of the supplied category, 0 if no
	 * 		parent exists
	 */
	function get_category_parent_id($cat_id)
	{
		$this->db->select('parent_id');
		$this->db->where('cat_id', $cat_id);
		$query = $this->db->get('categories');
		return $query->row('parent_id');
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

	public function get_category_id($url_title, $site_ids = array())
	{
		ee()->db->select('cat_id')
			->where('cat_url_title', $url_title);

		if ( ! empty($site_id) && is_array($site_ids))
		{
			ee()->db->where_in('site_id', $site_ids);
		}
		else
		{
			ee()->db->where('site_id', ee()->config->item('site_id'));
		}

		$result = ee()->db->get('categories');

		if ($result->num_rows() == 0)
		{
			return FALSE;
		}

		return $result->row('cat_id');
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

		$cat_ids = array();

		if ($query->num_rows() > 0)
		{
			foreach ($query->result() as $row)
			{
				$cat_ids[] = $row->cat_id;
			}

			$this->db->where_in('cat_id', $cat_ids);
			$this->db->delete('category_posts');
		}

		// -------------------------------------------
		// 'category_delete' hook.
		//
		if (ee()->extensions->active_hook('category_delete') === TRUE)
		{
			ee()->extensions->call('category_delete', $cat_ids);
		}
		//
		// -------------------------------------------

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

		// grab me some channels
		$qry = $this->db->select('channel_id, cat_group')
						->get_where('channels',
										array(
											'site_id' => $this->config->item('site_id')
										)
									);

		$channels = array();

		foreach ($qry->result() as $row)
		{
			$categories = explode('|', $row->cat_group);

			foreach ($categories as $num => $cat_group)
			{
				$channels[$row->channel_id][] = ($cat_group != $group_id) ? $cat_group : '';
			}

		}

		foreach ($channels as $k => $v)
		{
			$this->db->set('cat_group', implode('|', $v))
					 ->where('channel_id', $k)
					 ->update('channels');

		}
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
			$this->db->where('cat_id !=', $cat_id);
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
			$this->db->where('group_id !=', $group_id);
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

// EOF

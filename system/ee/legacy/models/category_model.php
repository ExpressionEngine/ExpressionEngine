<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

/**
 * Category Model
 */
class Category_model extends CI_Model
{
    public function get_entry_categories($entry_ids)
    {
        $result = array();
        $entry_ids = (array) $entry_ids;

        if (! count($entry_ids)) {
            return $result;
        }

        $sql = "SELECT c.*, cp.entry_id, cg.field_html_formatting, fd.*
				FROM exp_categories AS c
				LEFT JOIN exp_category_posts AS cp ON c.cat_id = cp.cat_id
				LEFT JOIN exp_category_field_data AS fd ON fd.cat_id = c.cat_id
				LEFT JOIN exp_category_groups AS cg ON cg.group_id = c.group_id
				WHERE cp.entry_id IN (" . implode(', ', $entry_ids) . ")
				ORDER BY c.group_id, c.parent_id, c.cat_order";

        $category_query = $this->db->query($sql);

        foreach ($category_query->result_array() as $row) {
            if (! isset($result[$row['entry_id']])) {
                $result[$row['entry_id']] = array();
            }

            $result[$row['entry_id']][] = $row;
        }

        return $result;
    }

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
    public function get_category_groups($group_id = '', $site_id = true, $include = 0)
    {
        if ($group_id != '') {
            if (! is_array($group_id)) {
                $group_id = array($group_id);
            }

            $this->db->where_in('group_id', $group_id);
        }

        if ($site_id !== true) {
            $this->db->where('site_id', $this->config->item('site_id'));
        }

        if ($include !== 0) {
            $this->db->where('(exclude_group = "0" OR exclude_group = "' . (int) $include . '")', null, false);
        }

        return $this->db->select('group_id, group_name, sort_order')
            ->from('category_groups')
            ->order_by('group_name')
            ->get();
    }

    /**
     * Get Channel Categories
     *
     * Gets category information for a given category group, by default only fetches cat_id and cat_name
     *
     * @access	public
     * @param	int
     * @return	mixed
     */
    public function get_channel_categories($cat_group, $additional_fields = array(), $additional_where = array())
    {
        if (! is_array($additional_fields)) {
            $additional_fields = array($additional_fields);
        }

        if (! isset($additional_where[0])) {
            $additional_where = array($additional_where);
        }

        if (count($additional_fields) > 0) {
            $this->db->select(implode(',', $additional_fields));
        }

        $this->db->select("cat_id, cat_name");
        $this->db->from("categories");
        $this->db->where("group_id", $cat_group);

        foreach ($additional_where as $where) {
            foreach ($where as $field => $value) {
                if (is_array($value)) {
                    $this->db->where_in($field, $value);
                } else {
                    $this->db->where($field, $value);
                }
            }
        }

        $this->db->order_by('cat_name');

        return $this->db->get();
    }

    /**
     * Delete Category
     *
     * @access	public
     * @return	object
     */
    public function delete_category($cat_id = '')
    {
        // get group id for future queries
        $category_group = $this->get_category_name_group($cat_id);
        $group_id = $category_group->row('group_id');

        // -------------------------------------------
        // 'category_delete' hook.
        //
        if (ee()->extensions->active_hook('category_delete') === true) {
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

    /**
     * Get Category Group Name
     *
     * @access	public
     * @return	mixed
     */
    public function get_category_group_name($group_id)
    {
        $this->db->select('group_id, group_name');

        if (is_array($group_id)) {
            $this->db->where_in('group_id', $group_id);
        } else {
            $this->db->where('group_id', $group_id);
        }

        return $this->db->get('category_groups');
    }

    /**
     * Get Category Parent ID
     *
     * @access	public
     * @param integer $cat_id The category ID you need the parent ID for
     * @return integer The parent_id of the supplied category, 0 if no
     * 		parent exists
     */
    public function get_category_parent_id($cat_id)
    {
        $this->db->select('parent_id');
        $this->db->where('cat_id', $cat_id);
        $query = $this->db->get('categories');

        return $query->row('parent_id');
    }

    /**
     * Get Category Label Name
     *
     * @access	public
     * @return	mixed
     */
    public function get_category_label_name($group_id, $field_id)
    {
        $this->db->select('field_label, field_name');
        $this->db->where('field_id', $field_id);
        $this->db->where('group_id', $group_id);

        return $this->db->get('category_fields');
    }

    /**
     * Get Category Name
     *
     * @access	public
     * @return	mixed
     */
    public function get_category_name_group($cat_id)
    {
        $this->db->select('cat_name, group_id');
        $this->db->where('cat_id', $cat_id);

        return $this->db->get('categories');
    }

    public function get_category_id($url_title, $site_ids = array())
    {
        ee()->db->select('cat_id')
            ->where('cat_url_title', $url_title);

        if (! empty($site_id) && is_array($site_ids)) {
            ee()->db->where_in('site_id', $site_ids);
        } else {
            ee()->db->where('site_id', ee()->config->item('site_id'));
        }

        $result = ee()->db->get('categories');

        if ($result->num_rows() == 0) {
            return false;
        }

        return $result->row('cat_id');
    }

    /**
     * Update Category Group
     *
     * @access	public
     * @return	mixed
     */
    public function update_category_group($group_id = '', $data = [])
    {
        $this->db->where('group_id', $group_id);
        $this->db->update('category_groups', $data);
    }

    /**
     * Insert Category Group
     *
     * @access	public
     * @return	void
     */
    public function insert_category_group($data)
    {
        // new categories will not need a group_id (they're new)
        // but may have an id of "" if the input->post() is passed direct
        unset($data['group_id']);
        $data['site_id'] = $this->config->item('site_id');

        $this->db->insert('category_groups', $data);
    }

    /**
     * Delete Category Group
     *
     * @access	public
     * @return	void
     */
    public function delete_category_group($group_id)
    {
        ee('Model')->get('CategoryGroup', $group_id)->delete();
    }

    /**
     * Duplicate Category Name Check
     *
     * @access	public
     * @return	boolean
     */
    public function is_duplicate_category_name($cat_url_title = '', $cat_id = '', $group_id = '')
    {
        $this->db->where('site_id', $this->config->item('site_id'));
        $this->db->where('cat_url_title', $cat_url_title);
        $this->db->where('group_id', $group_id);
        $this->db->from('categories');

        if ($cat_id != '') {
            $this->db->where('cat_id !=', $cat_id);
        }

        $count = $this->db->count_all_results();

        // if we find any - it's a duplicate
        return ($count > 0);
    }

    /**
     * Duplicate Category Group Check
     *
     * @access	public
     * @return	boolean
     */
    public function is_duplicate_category_group($group_name, $group_id = '')
    {
        $this->db->where('site_id', $this->config->item('site_id'));
        $this->db->where('group_name', $group_name);
        $this->db->from('category_groups');

        if ($group_id != '') {
            $this->db->where('group_id !=', $group_id);
        }

        $count = $this->db->count_all_results();

        // if we find any - it's a duplicate
        return ($count > 0);
    }

    /**
     * Delete Custom Category Field
     *
     * @access	public
     * @return	void
     */
    public function delete_category_field($group_id, $field_id)
    {
        $this->db->delete('category_fields', array('field_id' => $field_id));

        $this->db->query("ALTER TABLE exp_category_field_data DROP COLUMN field_id_{$field_id}");
        $this->db->query("ALTER TABLE exp_category_field_data DROP COLUMN field_ft_{$field_id}");
    }
}

// EOF

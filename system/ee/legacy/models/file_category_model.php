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
 * File Category Model
 */
class File_category_model extends CI_Model
{
    const TABLE_NAME = 'file_categories';

    /**
     * Set the file category
     *
     * @param int|string $file_id The id of the file from exp_files
     * @param int|string $cat_id The id of the category from exp_categories
     * @param int|string $sort The sort value, 1 being the top and higher values ranking lower
     * @param string $is_cover Either 'n' or 'y'
     * @return boolean TRUE if setting the category was successful, FALSE otherwise
     */
    public function set($file_id, $cat_id, $sort = null, $is_cover = null)
    {
        // Make sure sort is numeric and is not negative
        if (isset($sort) and $this->_is_valid_int($sort)) {
            $this->db->set('sort', $sort);
        }

        // Make sure is_cover is either n or y, though it should be y
        if (isset($is_cover) and ($is_cover === 'n' or $is_cover === 'y')) {
            $this->db->set('is_cover', $is_cover);
        }

        if (
            // Make sure the IDs are valid integers
            ! $this->_is_valid_int($file_id) or ! $this->_is_valid_int($cat_id) or

            // Make sure both exist in the database
            ! $this->_file_exists($file_id) or ! $this->_category_exists($cat_id)
        ) {
            return false;
        }

        // Check to see if parents need to be set and if this category has a parent
        if ($this->config->item('auto_assign_cat_parents') == 'y') {
            $this->load->model('category_model');
            $parent_id = $this->category_model->get_category_parent_id($cat_id);

            if ($parent_id != 0) {
                $this->set($file_id, $parent_id, $sort, $is_cover);
            }
        }

        $this->db->insert(self::TABLE_NAME, array(
            'file_id' => $file_id,
            'cat_id' => $cat_id
        ));

        return true;
    }

    /**
     * Get the categories from the database
     *
     * @param array $data Associative array of data to get
     *
     * @return DB Object Database object containing the query
     */
    public function get($data)
    {
        // Define valid array keys as keys to use in array_intersect_key
        $valid_keys = array(
            'file_id' => '',
            'cat_id' => '',
            'sort' => '',
            'is_cover' => ''
        );

        // Remove data that can't exist in the database
        $data = array_intersect_key($data, $valid_keys);

        return $this->db->get_where('file_categories', $data);
    }

    /**
     * Deletes category records for a specific file_id and optionally a cat_id as well
     *
     * @param integer $file_id The ID of the file from exp_files
     * @param integer $cat_id (Optional) The ID of the category to delete as well
     *
     * @return boolean TRUE if successful, FALSE otherwise
     */
    public function delete($file_id, $cat_id = null)
    {
        if ($file_id == null or $file_id == 0 or $file_id == false) {
            return false;
        }

        if ($cat_id != null) {
            $this->db->where('cat_id', $cat_id);
        }

        $this->db->delete(self::TABLE_NAME, array('file_id' => $file_id));

        return true;
    }

    /**
     * Make sure the parameter passed is a valid non-zero integer
     *
     * @param mixed $id Item to check integer validity
     * @return boolean TRUE if it's an integer or string integer, FALSE otherwise
     */
    private function _is_valid_int($id)
    {
        return (is_numeric($id) and intval($id) >= 0) ? true : false;
    }

    /**
     * Checks to see if the file exists in the database
     *
     * @param string|int $file_id ID of the file to check for
     * @return boolean TRUE if the file exists, FALSE otherwise
     */
    private function _file_exists($file_id)
    {
        $this->db->where('file_id', $file_id);

        return ($this->db->count_all_results('files') > 0) ? true : false;
    }

    /**
     * Checks to see if the category exists in the database
     *
     * @param string|int $cat_id ID of the category to check for
     * @return boolean TRUE if the category exists, FALSE otherwise
     */
    private function _category_exists($cat_id)
    {
        $this->db->where('cat_id', $cat_id);

        return ($this->db->count_all_results('categories') > 0) ? true : false;
    }
}

// EOF

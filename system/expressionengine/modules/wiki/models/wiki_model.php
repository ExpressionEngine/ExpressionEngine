<?php  if (!defined('BASEPATH')) exit('No direct script access allowed');
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
 * ExpressionEngine Wiki Model
 *
 * @package		ExpressionEngine
 * @subpackage	Module
 * @category	Model
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */
class Wiki_model extends CI_Model {

	/**
	 * Get Wikis
	 *
	 * @param mixed		can be a single integer or an array of ids.
	 * @param mixed 	string of columns to select, or an array
	 * @param array 	array('column_to_sort_on', 'asc/desc');
	 * @return object
	 */
	function get_wikis($id = NULL, $select = NULL, $order_sort = array())
	{
		if ($id)
		{
			if (is_array($id))
			{
				$this->db->where_in('wiki_id', $id);
			}
			else
			{
				$this->db->where('wiki_id', $id);
			}
		}

		if ($select)
		{
			if (is_array($select))
			{
				$select = implode(', ', $select);
			}

			$this->db->select($select);
		}

		if ( ! empty($order_sort) && count($order_sort) == 2)
		{
			$this->db->order_by($order_sort[0], $order_sort[1]);
		}

		return $this->db->get('wikis');
	}

	// ------------------------------------------------------------------------

	/**
	 * Delete Wiki
	 *
	 * @param array 	IDs of wiki to delete.
	 * @return string	Success Message
	 */
	function delete_wiki($wiki_id)
	{
		$this->db->where_in('wiki_id', $wiki_id);
		$this->db->delete(array('wikis', 'wiki_page', 'wiki_revisions', 'wiki_categories'));

		return (count($wiki_id) == 1) ? $this->lang->line('wiki_deleted') : $this->lang->line('wikis_deleted');
	}

	// ------------------------------------------------------------------------

	/**
	 * Select Max
	 *
	 * @param string	field to select
	 * @param string	field alias eg:  SELECT MAX(field_id) as max
	 * @param string	table to select from
	 * @return object
	 */
	function select_max($field, $as = NULL, $table)
	{
		$this->db->select_max($field, $as);

		return $this->db->get($table);
	}

	// ------------------------------------------------------------------------

	/**
	 * Create New Wiki
	 *
	 * @param array
	 * @return integer
	 */
	function create_new_wiki($prefix)
	{
		$data  = array(	'wiki_label_name'			=> "EE Wiki".str_replace('_', ' ', $prefix),
						'wiki_short_name'			=> 'default_wiki'.$prefix,
						'wiki_text_format'			=> 'xhtml',
						'wiki_html_format'			=> 'safe',
						'wiki_admins'				=> '1',
						'wiki_users'				=> '1|5',
						'wiki_upload_dir'			=> '0',
						'wiki_revision_limit'		=> 200,
						'wiki_author_limit'			=> 75,
						'wiki_moderation_emails'	=> '');

		$this->db->insert('wikis', $data);
		$wiki_id = $this->db->insert_id();

		//  Default Index Page
		$this->lang->loadfile('wiki');

		$data = array(	'wiki_id'		=> $wiki_id,
						'page_name'		=> 'index',
						'page_namespace'	=> '',						
						'last_updated'	=> $this->localize->now);

		$this->db->insert('wiki_page', $data);
		$page_id = $this->db->insert_id();

		$data = array(	'page_id'			=> $page_id,
						'wiki_id'			=> $wiki_id,
						'revision_date'		=> $this->localize->now,
						'revision_author'	=> $this->session->userdata('member_id'),
						'revision_notes'	=> $this->lang->line('default_index_note'),
						'page_content'		=> $this->lang->line('default_index_content')
					 );

		$this->db->insert('wiki_revisions', $data);
		$last_revision_id = $this->db->insert_id();

		$this->db->where('page_id', $page_id);
		$this->db->update('wiki_page', array('last_revision_id' => $last_revision_id));

		return $wiki_id;
	}

	// ------------------------------------------------------------------------

	/**
	 * Update wiki
	 *
	 * @param int	Wiki ID
	 * @param array
	 * @return void
	 */
	function update_wiki($wiki_id, $data)
	{
		$this->db->where('wiki_id', $wiki_id);
		return $this->db->update('wikis', $data);
	}

	// ------------------------------------------------------------------------

	/**
	 * Fetch Upload Options
	 *
	 * @return object
	 */
	function fetch_upload_options($value = '')
	{
		$this->load->model('file_upload_preferences_model');
		
		$options[0] = $this->lang->line('none');
		$options = $this->file_upload_preferences_model->get_dropdown_array(NULL, NULL, $options);
		
		return $options;
	}

	// ------------------------------------------------------------------------

	/**
	 * Member Group Options
	 *
	 * @return array
	 */
	function member_group_options()
	{
		$this->db->select('group_title, group_id');
		$this->db->where_not_in('group_id', array('2', '3', '4'));
		$this->db->where('site_id', $this->config->item('site_id'));
		$query = $this->db->get('member_groups');

		$options = array();

		foreach($query->result() as $row)
		{
			$options[$row->group_id] = $row->group_title;
		}

		return $options;
	}

	// ------------------------------------------------------------------------

	/**
	 * Check Duplicate
	 *
	 * @param mixed		id to check against
	 * @param string	name to check against
	 * @return boolean
	 */
	function check_duplicate($id = NULL, $str)
	{
		if ($id)
		{
			$this->db->where('wiki_id !=', $id);
		}

		$this->db->where('wiki_short_name', $str);

		if ($this->db->count_all_results('wikis') > 0)
		{
			return FALSE;
		}

		return TRUE;
	}

	// ------------------------------------------------------------------------

	/**
	 * Delete Namespace
	 *
	 * @param int		namespace ID
	 * @return boolean	TRUE on success / FALSE on failure
	 */	
	function delete_namespace($id)
	{
		return $this->db->delete('wiki_namespaces', array('namespace_id' => $id));
	}

}
// END CLASS

/* End of file wiki_model.php */
/* Location: ./system/expressionengine/modules/wiki/models/wiki_model.php */

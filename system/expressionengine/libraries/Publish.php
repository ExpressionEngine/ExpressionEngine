<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		ExpressionEngine Dev Team
 * @copyright	Copyright (c) 2003 - 2011, EllisLab, Inc.
 * @license		http://expressionengine.com/user_guide/license.html
 * @link		http://expressionengine.com
 * @since		Version 2.0
 * @filesource
 */
 
// ------------------------------------------------------------------------

/**
 * ExpressionEngine Publish Page Class
 *
 * @package		ExpressionEngine
 * @subpackage	Control Panel
 * @category	Control Panel
 * @author		ExpressionEngine Dev Team
 * @link		http://expressionengine.com
 */
class Publish 
{
	/**
	 * Constructor
	 */
	function __construct()
	{
		$this->EE =& get_instance();
	}
	
	public function build_categories_block($cat_group_ids, $entry_id, $selected_categories, $default_category = '', $file = FALSE)
	{
		$this->EE->load->library('api');
		$this->EE->api->instantiate('channel_categories');
		
		$default	= array(
			'string_override'		=> lang('no_categories'),
			'field_id'				=> 'category',
			'field_name'			=> 'category',
			'field_label'			=> lang('categories'),
			'field_required'		=> 'n',
			'field_type'			=> 'multiselect',
			'field_text_direction'	=> 'ltr',
			'field_data'			=> '',
			'field_fmt'				=> 'text',
			'field_instructions'	=> '',
			'field_show_fmt'		=> 'n',
			'selected'				=> 'n',
			'options'				=> array()
		);
		
		// No categories? Easy peasy
		if ( ! $cat_group_ids)
		{
			return array('category' => $default);
		}
		else if ( ! is_array($cat_group_ids))
		{
			if (strstr($cat_group_ids, '|'))
			{
				$cat_group_ids = explode('|', $cat_group_ids);
			}
			else
			{
				$cat_group_ids = array($cat_group_ids);
			}
		}
		
		$this->EE->api->instantiate('channel_categories');
		
		$catlist	= array();
		$categories	= array();

		// Figure out selected categories
		if ( ! count($_POST) && ! $entry_id && $default_category)
		{
			// new entry and a default exists
			$catlist = $default_category;
		}
		elseif (count($_POST) > 0)
		{
			$catlist = array();
			
			if (isset($_POST['category']) && is_array($_POST['category']))
			{
				foreach ($_POST['category'] as $val)
				{
					$catlist[$val] = $val;
				}
			}
		}
		elseif ( ! isset($selected_categories))
		{
			if ($file)
			{
				$this->EE->db->from(array('categories c', 'file_categories p'));
				$this->EE->db->where('p.file_id', $entry_id);
			}
			else
			{
				$this->EE->db->from(array('categories c', 'category_posts p'));
				$this->EE->db->where('p.entry_id', $entry_id);
			}
			
			$this->EE->db->select('c.cat_name, p.*');
			$this->EE->db->where_in('c.group_id', $cat_group_ids);
			$this->EE->db->where('c.cat_id = p.cat_id');
			
			$qry = $this->EE->db->get();

			foreach ($qry->result() as $row)
			{
				$catlist[$row->cat_id] = $row->cat_id;
			}
		}
		elseif (is_array($selected_categories))
		{
			foreach ($selected_categories as $val)
			{
				$catlist[$val] = $val;
			}
		}
		
		// Figure out valid category options
		$this->EE->api_channel_categories->category_tree($cat_group_ids, $catlist);

		if (count($this->EE->api_channel_categories->categories) > 0)
		{  
			// add categories in again, over-ride setting above
			foreach ($this->EE->api_channel_categories->categories as $val)
			{
				$categories[$val['3']][] = $val;
			}
		}
		
		
		// If the user can edit categories, we'll go ahead and
		// show the links to make that work
		$edit_links = FALSE;
		
		if ($this->EE->session->userdata('can_edit_categories') == 'y')
		{
			$link_info = $this->EE->api_channel_categories->fetch_allowed_category_groups($cat_group_ids);

			if (is_array($link_info) && count($link_info))
			{
				$edit_links = array();
				
				foreach ($link_info as $val)
				{
					$edit_links[] = array(
						'url' => BASE.AMP.'C=admin_content'.AMP.'M=category_editor'.AMP.'group_id='.$val['group_id'],
						'group_name' => $val['group_name']
					);
				}
			}
		}


		// Build the mess
		$data = compact('categories', 'edit_links');

		$default['options']			= $categories;		
		$default['string_override'] = $this->EE->load->view('content/_assets/categories', $data, TRUE);
		
		return array('category' => $default);
	}
	
}
// END CLASS

/* End of file Publish.php */
/* Location: ./system/expressionengine/libraries/Publish.php */
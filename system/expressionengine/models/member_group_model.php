<?PHP

if (!defined('BASEPATH')) {
	 exit('No direct script access allowed');
}


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
 * Model to manage Member Groups.  Manages the following
 * tables:
 * 	exp_member_groups
 * 	exp_channel_member_groups
 * 	exp_module_member_groups
 * 	exp_template_member_groups 
 *
 * @package		ExpressionEngine
 * @subpackage	Core
 * @category	Model
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */

class Member_group_model extends CI_Model 
{
	
	public function __construct()
	{
		parent::__construct();
	}

	/**
		Determine whether a group with a certain title exists or not.
		Throws an error when a title is found.

		TODO This could probably be made public and generalized,
		if we're ever going to use it elsewhere.  The show_error
		call would need to be bumped into parse_add_form and parse_edit_form

		@param int $group_id The id of the group we want to check.
		@param string $group_title The title to look for.

		@return boolean True if a group with the given title is found, false otherwise.
	*/
	private function _group_title_exists($site_id, $group_id, $group_title)
	{
		$this->db->from('member_groups')
					->where('group_title', $group_title)
					->where('site_id', $site_id)
					->where('group_id !=', $group_id);
		
		if ($this->db->count_all_results())
		{
			return true;
		}
		return false;
	}

	/**
		Parse the data from the post data we're passed. Builds a data array, 
		containing several permissions sub arrays that are meant to be pulled
		out before the data array is sent to the database as a row
		in the exp_member_groups table.  The subarrays to be
		pulled out are 'channel', 'template' and 'module'.

		@param int $group_id The id of the group we're parsing data for.
		@param int $site_id The site id submitted with the form, not necessarily the site we'll be creating a group for. 
	*/	
	private function _parse_form_data($post, $form_site_id, $group_id)
	{
				
		/** ----------------------------------------------------
		/**  Remove and Store Channel and Template Permissions
		/** ----------------------------------------------------*/
		
		$data = array('group_title' 		=> $this->input->post('group_title'),
					  'group_description'	=> $this->input->post('group_description'),
					  'group_id'			=> $group_id);
		
		// If editing Super Admin group, the is_locked field doesn't exist, so make sure we
		// got a value from the form before writing 0 to the database
		if ($this->input->post('is_locked') !== FALSE)
		{
			$data['is_locked'] = $this->input->post('is_locked');
		}
	
		$data['channel'] = array();
		$data['module'] = array();
		$data['template'] = array();	
		foreach ($post as $key => $val)
		{
			if (substr($key, 0, strlen($form_site_id.'_channel_id_')) == $form_site_id.'_channel_id_')
			{
				$channel_id = substr($key, strlen($form_site_id.'_channel_id_'));
				if ($val == 'y')
				{
					$data['channel'][$channel_id] = array(
						'channel_id' => $channel_id,
						'group_id' => $group_id
					);
				} 
				else 
				{
					$data['channel'][$channel_id] = false;	
				}
			}
			elseif (substr($key, 0, strlen('module_id_')) == 'module_id_')
			{
				$module_id = substr($key, strlen('module_id_'));
				if ($val == 'y')
				{
					$data['module'][$module_id] = array(
						'module_id' => $module_id,
						'group_id' => $group_id
					);
				}
				else
				{
					$data['module'][$module_id] = false;
				}
			}
			elseif (substr($key, 0, strlen($form_site_id.'_template_id_')) == $form_site_id.'_template_id_')
			{
				$template_id = substr($key, strlen($form_site_id.'_template_id_'));
				if ($val == 'y')
				{
					$data['template'][$template_id] = array(
						'template_group_id' => $template_id,
						'group_id' => $group_id
					);
				}
				else
				{
					$data['template'][$template_id] = false;
				}
			}
			elseif (substr($key, 0, strlen($form_site_id.'_')) == $form_site_id.'_')
			{
				$data[substr($key, strlen($form_site_id.'_'))] = $post[$key];
			}
			else
			{
				continue;
			}
			
			unset($post[$key]);
		}
		return $data;
	}

	/**
		TODO Anyone know what and why?  
	*/
	private function _update_uploads($group_id, $site_id)
	{
		$uploads = $this->db->query("SELECT exp_upload_prefs.id FROM exp_upload_prefs WHERE site_id = '".$this->db->escape_str($site_id)."'");
		if ($uploads->num_rows() > 0)
		{
			foreach($uploads->result_array() as $upload)
			{
				$this->db->query("INSERT INTO exp_upload_no_access (upload_id, upload_loc, member_group) VALUES ('".$this->db->escape_str($upload['id'])."', 'cp', '{$group_id}')");
			}
		}
	}

	/**
	 * Update Category Group Privileges
	 *
	 * Updates exp_category_groups privilege lists for
	 * editing and deleting categories
	 *
	 * @return	mixed
	 */		
	private function _update_cat_group_privs($params)
	{
		if ( ! is_array($params) OR empty($params))
		{
			return FALSE;
		}

		$expected = array('member_group', 'field', 'allow', 'site_id', 'clone_id');
		
		// turn parameters into variables
		foreach ($expected as $key)
		{
			// naughty!
			if ( ! isset($params[$key]))
			{
				return FALSE;
			}
			
			$$key = $params[$key];
		}
		
		$query = $this->db->query("SELECT group_id, ".$this->db->escape_str($field)." FROM exp_category_groups WHERE site_id = '".$this->db->escape_str($site_id)."'");
		
		// nothing to do?
		
		if ($query->num_rows() == 0)
		{
			return FALSE;
		}

		foreach ($query->result_array() as $row)
		{
			$can_do = explode('|', rtrim($row[$field], '|'));

			if ($allow === TRUE)
			{
				if (is_numeric($clone_id))
				{
					if (in_array($clone_id, $can_do) OR $clone_id == 1)
					{
						$can_do[] = $member_group;
					}						
				}
				elseif ($clone_id === FALSE)
				{
					$can_do[] = $member_group;
				}
			}
			else
			{
				$can_do = array_diff($can_do, array($member_group));
			}

			$this->db->query($this->db->update_string('exp_category_groups', array($field => implode('|', $can_do)), "group_id = '{$row['group_id']}'"));
		}
	}

	/**
		Update the related permissions tables
			
			exp_channel_member_groups
			exp_module_member_groups
			exp_template_member_groups

		@param int $group_id The id of the group who's relations we're updating.
		@param array $permissions The packed permissions array that comes from _parse_form_data()
	*/
	private function _update_permissions($group_id, array $permissions) 
	{
		// Unpack the data we packed into the data
		// array.  Yes, this isn't optimal.
		$channel_ids = array();
		$channel_ids_yes = array();
		foreach($permissions['channel'] as $id=>$value) 
		{
			$channel_ids[] = $id;
			if($value !== FALSE) 
			{
				$channel_ids_yes[] = $value;
			}
		}

		$module_ids = array();
		$module_ids_yes = array();
		foreach($permissions['module'] as $id=>$value) 
		{
			$module_ids[] = $id;
			if($value !== FALSE) 
			{
				$module_ids_yes[] = $value;
			}
		}

		$template_ids = array();
		$template_ids_yes = array();
		foreach($permissions['template'] as $id=>$value) 
		{
			$template_ids[] = $id;
			if($value !== FALSE) 
			{
				$template_ids_yes[] = $value;
			}
		}
	
		// First, delete old channel, module and template permissions for this site
		if ( ! empty($channel_ids))
		{
			$this->delete_channel_permissions($group_id, $channel_ids);
		}
		if ( ! empty($module_ids))
		{
			$this->delete_module_permissions($group_id, $module_ids);
		}
		if ( ! empty($template_ids))
		{
			$this->delete_template_permissions($group_id, $template_ids);
		}
		
		// Then, add back in the only ones that should exist based on the form submission
		if ( ! empty($channel_ids_yes))
		{
			$this->db->insert_batch('channel_member_groups', $channel_ids_yes);
		}
		if ( ! empty($module_ids_yes))
		{
			$this->db->insert_batch('module_member_groups', $module_ids_yes);
		}
		if ( ! empty($template_ids_yes))
		{
			$this->db->insert_batch('template_member_groups', $template_ids_yes);
		}
	}

	/**
		Handle the input from the Member Groups form when we're adding.
			(CP Home > Members > Member Groups > Add Member Group)

		Update the exp_member_groups table and several child and related tables.

		@param array $post The post data from the form that we are processing.
		@param int $site_id The id of the site to which the group belongs.
		@param int $clone_id TODO  Anyone know what this is?
		@param string $group_title The title of the group we're adding.

		@return The message to send to the CP.		
	*/
	public function parse_add_form(array $post, $form_site_id, $clone_id, $group_title)
	{
		// This is less than optimal, but it allows us to use
		// that foreach loop for both multi-site manager and
		// single site.  
		// FIXME This could be done much better. -Daniel
		if($this->config->item('multiple_sites_enabled') == 'y')
		{		
			$this->load->model('site_model');		
			$site_ids = $this->site_model->get_site_ids();
		}
		else
		{
			$site_ids = array($this->config->item('site_id'));
		}

		// Get the next available group id to use for our new group.
		$query = $this->db->query("SELECT MAX(group_id) as max_group FROM exp_member_groups");
		$group_id = $query->row('max_group') + 1;

		$data = $this->_parse_form_data($post, $form_site_id, $group_id);
		// We'll need this later when we call $this->_update_permissions()	
		// We'll have to unpack them and it's klutzy, but for now, this is the best I got. -Daniel
		$permissions = array('channel'=>$data['channel'],'module'=>$data['module'],'template'=>$data['template']);
		// And we don't want them hanging around in the data arround to be stuck into the database.
		unset($data['channel']);
		unset($data['module']);
		unset($data['template']);

		$created_group = FALSE;
		foreach($site_ids as $site_id) 
		{		
			if($this->_group_title_exists($site_id, $group_id, $group_title))
			{
				continue;	
			}
			// Override the site id set by _parse_form_data() with
			// the one we're using in our loop.
			$data['site_id'] = $site_id;

			$this->create($data);
		
			$this->_update_uploads($group_id, $site_id);	
			
			if ($group_id != 1)
			{
				$cat_group_privs = array('can_edit_categories', 'can_delete_categories');
				foreach ($cat_group_privs as $field)
				{
					$privs = array(
									'member_group' => $group_id,
									'field' => $field,
									'allow' => ($data[$field] == 'y') ? TRUE : FALSE,
									'site_id' => $site_id,
									'clone_id' => $clone_id
								);

					$this->_update_cat_group_privs($privs);	
				}
			}
			
			$created_group = TRUE;
		}

		// This doesn't use site_id, only group_id.  The ids recieved
		// in the form for permissions will either be specific to the
		// site the form is submitted for, or they'll be common across
		// all sites (in the case of modules).  So this can happen
		// out side the creation loop.
		$this->_update_permissions($group_id, $permissions);

		if($created_group)
		{
			return lang('member_group_created').NBS.NBS.$group_title;
		}
		else 
		{
 			show_error(lang('group_title_exists'));
			return;
		}
	}

	/**
		Handle the input from the Member Groups form when we're editing.
			(CP Home > Members > Member Groups > Edit Member Group)

		Update the exp_member_groups table and several child and related tables.

		@param array $post The post data from the form that we are processing.
		@param int $group_id The id of the group we're editing.
		@param int $site_id The id of the site to which the group belongs.
		@param int $clone_id TODO  Anyone know what this is?
		@param string $group_title The title of the group we're editing.

		@return The message to send to the CP.		
	*/
	public function parse_edit_form(array $post, $group_id, $site_id, $clone_id, $group_title) 
	{
		if($this->_group_title_exists($site_id, $group_id, $group_title))
		{
 			show_error(lang('group_title_exists'));
			return;	
		}

		$is_multisite = ($this->config->item('multiple_sites_enabled') == 'y' ? TRUE : FALSE);
		if($is_multisite)
		{	
			$groups_query = $this->get(array('group_id'=> $group_id));
			$title_changed = FALSE;
			foreach($groups_query->result_array() as $group)
			{
				if($group['site_id'] != $site_id && $group['group_title'] != $group_title)
				{
					$title_changed = TRUE;
				}
			}
		}

		if($is_multisite && $title_changed)
		{
			$data = array('group_title'=>$group_title);
			$this->update_all_sites($group_id, $data);
		}

		$query = $this->db->query('SELECT site_id, can_edit_categories, can_delete_categories FROM exp_member_groups WHERE group_id = "'.$this->db->escape_str($group_id).'"');
		
		$old_cat_privs = array();
		foreach ($query->result_array() as $row)
		{
			$old_cat_privs[$row['site_id']]['can_edit_categories'] = $row['can_edit_categories'];
			$old_cat_privs[$row['site_id']]['can_delete_categories'] = $row['can_delete_categories'];
		}

		$data = $this->_parse_form_data($post, $site_id, $group_id);
		unset($data['group_id']);

		// We'll need this later when we call $this->_update_permissions()	
		// We'll have to unpack them and it's klutzy, but for now, this is the best I got.
		$permissions = array('channel'=>$data['channel'], 'module'=>$data['module'], 'template'=>$data['template']);
		// And we don't want them hanging around in the data arround to be stuck into the database.
		unset($data['channel']);
		unset($data['module']);
		unset($data['template']);
		
		$this->update($group_id, $site_id, $data);
		
		if ($group_id != 1)
		{
			// update category group discrete privileges
			$cat_group_privs = array('can_edit_categories', 'can_delete_categories');
			foreach ($cat_group_privs as $field)
			{
				// only modify category group privs if value changed, so we do not
				// globally overwrite existing defined privileges carelessly
				if ($old_cat_privs[$site_id][$field] != $data[$field])
				{
					$privs = array(
									'member_group' => $group_id,
									'field' => $field,
									'allow' => ($data[$field] == 'y') ? TRUE : FALSE,
									'site_id' => $site_id,
									'clone_id' => $clone_id
								);

					$this->_update_cat_group_privs($privs);						
				}
			}
		}

		$this->_update_permissions($group_id, $permissions);
	
		return lang('member_group_updated').NBS.NBS.$group_title;
	}
	
	/**
		Retrieve rows from the exp_member_groups table.  $fields is an array
		of fields to select.  It defaults to group_id, site_id and group_title.
		$where_group is an array of where condition groupings.

		@param array $where_conditions The where conditions you wish to limit your search by. 
		@param array $fields The fields you wish to select.
	*/
	public function get(array $where_conditions=array(), array $fields=array('group_id', 'site_id', 'group_title'))
	{
		$this->db->select(implode(',', $fields));

		foreach($where_conditions as $field=>$value)
		{
			if(is_array($value))
			{
				$this->db->where_in($field, $value);
			}
			else 
			{
				$this->db->where($field, $value);
			}
		}

		return $this->db->get('exp_member_groups');
	}

	/**
		Create a row in the exp_member_groups table.

		@param array $data  The data we will insert into the table.

		@return void
	*/	
	public function create(array $data)
	{
		$this->db->insert('exp_member_groups', $data);
	}
	
	/**
		Apply the update to all groups with with the given group_id.

		NOTE: Made this a seperate method instead of making site_id
		optional in update, because leaving out the site_id is not
		something we want to happen accidentially.  If we're going
		to be updating all member groups, it should be done with 
		intention.
		
		@param int $group_id The id of the member group that we'll be updating.
		@param array $data The data we'll changing to in the form db_field=>value.
	*/
	public function update_all_sites($group_id, array $data) 
	{
		$this->db->where('group_id', $group_id);
		$this->db->update('exp_member_groups', $data);
		return $this->db->affected_rows();
	}

	/**
		Update a row in the exp_member_groups table.
	
		@param int $group_id The id of the group we're updating
		@param int $site_id The id of the site that the group belongs to, this is the second half of the primary key
		@param array $data The array of data we will use to update.
		
		@return void
	*/
	public function update($group_id, $site_id, array $data)
	{
		$this->db->where('group_id', $group_id)
			->where('site_id', $site_id);
		$this->db->update('exp_member_groups', $data);
		return $this->db->affected_rows();
	}

	/**
		Delete a group of rows from the exp_channel_member_groups table.  The
		rows have ids in the $channel_ids array and a group_id equal to $group_id.
	
		@param int $group_id The group for which to delete the permissions rows.
		@param array $channel_ids A list of channel ids to delete.
	*/
	public function delete_channel_permissions($group_id, array $channel_ids) 
	{
		$this->db->where('group_id', $group_id);
		$this->db->where_in('channel_id', $channel_ids);
		$this->db->delete('channel_member_groups');
	}
	
	/**
		Delete a group of rows from the exp_template_member_groups table.  The
		rows have ids in the $template_ids array and a group_id equal to $group_id.
	
		@param int $group_id The group for which to delete the permissions rows.
		@param array $template_ids A list of template ids to delete.
	*/
	public function delete_template_permissions($group_id, array $template_ids)
	{
		$this->db->where('group_id', $group_id);
		$this->db->where_in('template_group_id', $template_ids);
		$this->db->delete('template_member_groups');
	}

	/**
		Delete a group of rows from the exp_module_member_groups table.  The
		rows have ids in the $module_ids array and a group_id equal to $group_id.
	
		@param int $group_id The group for which to delete the permissions rows.
		@param array $module_ids A list of channel ids to delete.
		
		@return void
	*/
	public function delete_module_permissions($group_id, array $module_ids)
	{
		$this->db->where('group_id', $group_id);
		$this->db->where_in('module_id', $module_ids);
		$this->db->delete('module_member_groups');
	}

}


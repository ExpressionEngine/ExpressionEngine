<?php  if (!defined('BASEPATH')) exit('No direct script access allowed');
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
 * ExpressionEngine Template Model
 *
 * @package		ExpressionEngine
 * @subpackage	Core
 * @category	Model
 * @author		ExpressionEngine Dev Team
 * @link		http://expressionengine.com
 */
class Template_model extends CI_Model {

	
	/**
	 * Get Template Group Metadata
	 *
	 * @access	public
	 * @param	int
	 * @return	object
	 */
	function get_group_info($group_id)
	{
		return $this->db->get_where('template_groups', array('group_id' => $group_id));
	}

	// --------------------------------------------------------------------
	
	/**
	 * Create Group
	 *
	 * Inserts a new template group into the db
	 *
	 * @access	public
	 * @param	array
	 * @return	int
	 */
	function create_group($data)
	{		
		if ($data['is_site_default'] == 'y')
		{
			$this->db->where('site_id', $data['site_id']);
			$this->db->update('template_groups', array('is_site_default' => 'n'));
		}
		
		if ( ! isset($data['group_order']))
		{
			$data['group_order'] = $this->db->count_all('template_groups') + 1;
		}

		$this->db->insert('template_groups', $data);
		return $this->db->insert_id();
	}

	// --------------------------------------------------------------------
	
	/**
	 * Create Template
	 *
	 * Inserts a new template into the db
	 *
	 * @access	public
	 * @param	array
	 * @return	int
	 */
	function create_template($data)
	{
		$this->db->insert('templates', $data);
		return $this->db->insert_id();
	}

	// --------------------------------------------------------------------

	/**
	 * Get Template Groups
	 *
	 * @access	public
	 * @return	object
	 */
	function get_template_groups()
	{
		$this->db->where('site_id', $this->config->item('site_id'));
		$this->db->order_by('group_order, group_name ASC');
		return $this->db->get('template_groups');
	}

	// --------------------------------------------------------------------

	/**
	 * Update Template Group
	 *
	 * @access	public
	 * @param	int
	 * @param	array
	 * @return	void
	 */
	function update_template_group($group_id, $fields = array())
	{
		if (isset($fields['is_site_default']) && $fields['is_site_default'] == 'y')
		{
			$this->db->where('site_id', $fields['site_id']);
			$this->db->update('template_groups', array('is_site_default' => 'n'));
		}

		$this->db->where('group_id', $group_id);
		$this->db->set($fields);
		$this->db->update('template_groups');
	}

	// --------------------------------------------------------------------

	/**
	 * Get Template Info
	 *
	 * @access	public
	 * @param	int
	 * @param	array
	 * @return	object
	 */
	function get_template_info($template_id, $fields = array())
	{
		if (count($fields) > 0)
		{
			$this->db->select(implode(",", $fields));
		}

		$this->db->where('template_id', $template_id);
		$this->db->where('site_id', $this->config->item('site_id'));
		return $this->db->get('templates');
	}

	// --------------------------------------------------------------------

	/**
	 * Rename Template File
	 *
	 * @access	public
	 * @return	bool
	 */
	function rename_template_file($template_group, $template_type, $old_name, $new_name)
	{
		$this->load->library('api');
		$this->api->instantiate('template_structure');
		$ext = $this->api_template_structure->file_extensions($template_type);
		
		$basepath  = $this->config->slash_item('tmpl_file_basepath');
		$basepath .= $this->config->item('site_short_name');
		$basepath .= '/'.$template_group.'.group';
		
		$existing_path = $basepath.'/'.$old_name.$ext;
		
		if ( ! file_exists($existing_path))
		{
			return FALSE;
		}
		
		return rename($existing_path, $basepath.'/'.$new_name.$ext);
	}

	// --------------------------------------------------------------------
	
	/**
	 * Update Template Ajax
	 *
	 * Used when editing template prefs inline from the manager
	 *
	 * @access	public
	 * @return	array
	 */
	function update_template_ajax($template_id, $data)
	{
		$this->db->where('site_id', $this->config->item('site_id'));
		$this->db->where('template_id', $template_id);

		$this->db->update('templates', $data);

		if ($this->db->affected_rows() != 1)
		{
			return FALSE;
		}
		
		return TRUE;
	}
	
	// --------------------------------------------------------------------

	/**
	 * Update Access Ajax
	 *
	 * Used when editing template access prefs inline from the manager
	 *
	 * @access	public
	 * @return	array
	 */
	function update_access_ajax($template_id, $m_group_id, $new_status)
	{
		// Check if it is in there
		$this->db->where('template_id', $template_id);
		$this->db->where('member_group', $m_group_id);
		$count = $this->db->count_all_results('template_no_access');
		
		// if they are allowed to access it - remove from no_access
		if ($new_status == 'y')
		{
			if ($count > 0)
			{
				$this->db->where('template_id', $template_id);
				$this->db->where('member_group', $m_group_id);
				$this->db->delete('template_no_access');
			}
		}
		else
		{
			if ($count == 0)
			{
				$this->db->insert('template_no_access', array(
					'template_id'	=> $template_id,
					'member_group'	=> $m_group_id
				));
			}
		}
		
		return TRUE;
	}
	
	// --------------------------------------------------------------------

	/**
	 * Update Access Ajax Details
	 *
	 * Used when editing template access prefs inline from the manager
	 * @access	public
	 * @return	bool
	 */
	function update_access_details_ajax($template_id, $data)
	{
		$this->db->where('site_id', $this->config->item('site_id'));
		$this->db->where('template_id', $template_id);

		$this->db->update('templates', $data);
		
		return TRUE;
	}

	// --------------------------------------------------------------------

	/**
	 * Delete Template
	 *
	 * @access	public
	 * @param	int
	 * @return	bool
	 */
	function delete_template($template_id, $path = FALSE)
	{
		if ($path !== FALSE)
		{
			if ( ! @unlink($path))
			{
				return FALSE;
			}
		}
		
		$this->db->where('item_id', $template_id);
		$this->db->where('item_table', 'templates');
		$this->db->where('item_field', 'template_data');
		$this->db->delete('revision_tracker');

		$this->db->where('template_id', $template_id);
		$this->db->delete('template_no_access');

		$this->db->where('template_id', $template_id);
		$this->db->delete('templates');

		if ($this->db->affected_rows() == 1)
		{
			return TRUE;
		}
		else
		{
			return FALSE;
		}
	}

	// --------------------------------------------------------------------

	/**
	 * Get Templates
	 *
	 * @access	public
	 * @param	string
	 * @return	array
	 */
	function get_templates($site_id = NULL, $additional_fields = array(), $additional_where = array())
	{
		if ($site_id === NULL OR ! is_numeric($site_id))
		{
			$site_id = $this->config->item('site_id');
		}

		if ( ! is_array($additional_fields))
		{
			$additional_fields = array($additional_fields);
		}

		if (count($additional_fields) > 0)
		{
			$this->db->select(implode(',', $additional_fields));
		}

		$this->db->select("template_id, template_name, group_name");
		$this->db->from("templates");
		$this->db->join("template_groups", "templates.group_id = template_groups.group_id");
		$this->db->where('templates.site_id', $site_id);

		// add additional WHERE clauses
		foreach ($additional_where as $field => $value)
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

		$this->db->order_by('group_name, template_name');
		$results = $this->db->get();

		return $results;
	}

	// --------------------------------------------------------------------

	/**
	 * Get Snippets
	 *
	 * Return all Snippets
	 *
	 * @access	public
	 * @return	object
	 */
	function get_snippets()
	{
		$this->db->where('(site_id = '.$this->db->escape_str($this->config->item('site_id')).' OR site_id = 0)');
		$this->db->order_by('snippet_name');
		return $this->db->get('snippets');
	}

	// --------------------------------------------------------------------
	
	/**
	 * Get Snippet
	 *
	 * Gets the details of a specific Snippet
	 *
	 * @access	public
	 * @param	string
	 * @return	array
	 */
	function get_snippet($snippet, $by_name = FALSE)
	{
		if (ctype_digit($snippet) && $by_name === FALSE)
		{
			$this->db->where('snippet_id', $snippet);
		}
		else
		{
			$this->db->where('snippet_name', $snippet);
		}

		$this->db->where('(site_id = '.$this->db->escape_str($this->config->item('site_id')).' OR site_id = 0)');

		$result = $this->db->get('snippets');
		
		if ($result->num_rows() != 1)
		{
			return FALSE;
		}

		// return an associative array for convenience
		return $result->row_array();
	}

	// --------------------------------------------------------------------

	/**
	 * Check Snippet for Uniqueness
	 *
	 * @access	public
	 * @param	string
	 * @return	bool
	 */
	function unique_snippet_name($snippet_name)
	{
		$this->db->where('snippet_name', $snippet_name);
		$results = $this->db->get('snippets');
	
		if ($results->num_rows() == 0)
		{
			return TRUE;
		}
		else
		{
			return FALSE;
		}
	}

	// --------------------------------------------------------------------
	
	/**
	 * Delete Snippet
	 *
	 * @access	public
	 * @param	int
	 * @return	int
	 */
	function delete_snippet($snippet_id)
	{
		$this->db->where('snippet_id', $snippet_id);
		$this->db->delete('snippets');
		return $this->db->affected_rows();
	}

	// --------------------------------------------------------------------
	
	/**
	 * Get Global Variables
	 *
	 * Return all global variables
	 *
	 * @access	public
	 * @return	object
	 */
	function get_global_variables()
	{
		$this->db->where('site_id', $this->config->item('site_id'));
		$this->db->order_by('variable_name');
		return $this->db->get('global_variables');
	}
	
	// --------------------------------------------------------------------

	/**
	 * Get Global Variable
	 *
	 * Get the values of one global variable
	 *
	 * @access	public
	 * @param	integer
	 * @return	array
	 */
	function get_global_variable($variable_id = '')
	{
		$this->db->where('site_id', $this->config->item('site_id'));
		$this->db->where('variable_id', $variable_id);
		$this->db->order_by('variable_name');
		$results = $this->db->get('global_variables');
	
		return $results;
	}

	// --------------------------------------------------------------------

	/**
	 * Check Duplicate Global Variable Name
	 *
	 * Used to check for already existing global variables with the same name
	 *
	 * @access	public
	 * @param	string
	 * @return	boolean
	 */
	function check_duplicate_global_variable_name($variable_name = '')
	{
		$this->db->where('site_id', $this->config->item('site_id'));
		$this->db->where('variable_name', $variable_name);
		$this->db->order_by('variable_name');
		$results = $this->db->get('global_variables');
	
		if ($results->num_rows() == 0)
		{
			return TRUE;
		}
		else
		{
			return FALSE;
		}
	}
	
	// --------------------------------------------------------------------

	/**
	 * Update Global Variable
	 *
	 * @access	public
	 * @param	integer
	 * @param	string
	 * @param	string
	 * @return	integer
	 */
	function update_global_variable($variable_id, $variable_name, $variable_data)
	{
		$this->db->set('variable_name', $variable_name);
		$this->db->set('variable_data', $variable_data);
		$this->db->set('site_id', $this->config->item('site_id'));
		$this->db->where('variable_id', $variable_id);

		$this->db->update('global_variables');
		
		return $this->db->affected_rows();
	}
	
	// --------------------------------------------------------------------

	/**
	 * Create Global Variable
	 *
	 * @access	public
	 * @param	string
	 * @param	string
	 * @return	integer
	 */
	function create_global_variable($variable_name, $variable_data)
	{
		$this->db->set('variable_name', $variable_name);
		$this->db->set('variable_data', $variable_data);
		$this->db->set('site_id', $this->config->item('site_id'));

		$this->db->insert('global_variables');
		
		return $this->db->insert_id();
	}

	// --------------------------------------------------------------------

	/**
	 * Delete Global Variable
	 *
	 * @access	public
	 * @param	integer
	 * @return	integer
	 */
	function delete_global_variable($variable_id)
	{
		$this->db->where('variable_id', $variable_id);
		$this->db->delete('global_variables');
		
		return $this->db->affected_rows();
	}

	// --------------------------------------------------------------------

	/**
	 * Get Specialty Email Templates Summary
	 *
	 * Gets the ids and names of all specialty email templates
	 *
	 * @access	public
	 * @return	array
	 */
	function get_specialty_email_templates_summary()
	{
		$this->db->select('template_id, template_name');
		$this->db->from("specialty_templates");
		$this->db->where('site_id', $this->config->item('site_id'));
		$this->db->where('template_name !=', "message_template");
		$this->db->where('template_name !=', "offline_template");
		$this->db->order_by('template_name');
		$results = $this->db->get();
	
		return $results;
	}

	// --------------------------------------------------------------------

	/**
	 * Get Specialty Template Data
	 *
	 * Returns a specialty template
	 *
	 * @access	public
	 * @param	string
	 * @return	string
	 */
	function get_specialty_template($template_name)
	{
		$this->db->select('data_title, template_id, template_data, enable_template');
		$this->db->from("specialty_templates");
		$this->db->where('site_id', $this->config->item('site_id'));
		$this->db->where('template_name', $template_name);
		$results = $this->db->get();
				
		return $results;
	}
	
	// --------------------------------------------------------------------

	/**
	 * Get Specialty Template Variables
	 *
	 * Returns available variables to a given specialty template
	 *
	 * @access	public
	 * @param	string
	 * @return	array
	 */
	function get_specialty_template_vars($template_name)
	{
		$vars = array(
						'admin_notify_reg'						=> array('name', 'username', 'email', 'site_name', 'control_panel_url'),
						'admin_notify_entry'					=> array('channel_name', 'entry_title', 'entry_url', 'comment_url', 'name', 'email'),
						'admin_notify_comment'					=> array('channel_name', 'entry_title', 'entry_id', 'url_title', 'channel_id', 'comment_url_title_auto_path',  'comment_url', 'comment', 'comment_id', 'name', 'url', 'email', 'location', 'unwrap}{delete_link}{/unwrap', 'unwrap}{close_link}{/unwrap', 'unwrap}{approve_link}{/unwrap'),
						'admin_notify_forum_post'				=> array('name_of_poster', 'forum_name', 'title', 'body', 'thread_url', 'post_url'),
						'admin_notify_mailinglist'				=> array('email', 'mailing_list'),
						'mbr_activation_instructions'			=> array('name',  'username', 'email', 'activation_url', 'site_name', 'site_url'),
						'forgot_password_instructions'			=> array('name', 'reset_url', 'site_name', 'site_url'),
						'reset_password_notification'			=> array('name', 'username', 'password', 'site_name', 'site_url'),
						'decline_member_validation'				=> array('name', 'site_name', 'site_url'),
						'validated_member_notify'				=> array('name', 'site_name', 'site_url'),
						'mailinglist_activation_instructions'	=> array('activation_url', 'site_name', 'site_url', 'mailing_list'),
						'comment_notification'					=> array('name_of_commenter', 'name_of_recipient', 'channel_name', 'entry_title', 'entry_id', 'url_title', 'channel_id', 'comment_url_title_auto_path', 'comment_url', 'comment', 'notification_removal_url', 'site_name', 'site_url', 'comment_id'),
						
						'comments_opened_notification'					=> array('name_of_recipient', 'channel_name', 'entry_title', 'entry_id', 'url_title', 'channel_id', 'comment_url_title_auto_path', 'comment_url', 'notification_removal_url', 'site_name', 'site_url', 'total_comments_added', 'comments', 'name_of_commenter', 'comment_id', 'comment', '/comments'),

						'forum_post_notification'				=> array('name_of_recipient', 'name_of_poster', 'forum_name', 'title', 'thread_url', 'body', 'post_url'),
						'private_message_notification'			=> array('sender_name', 'recipient_name','message_subject', 'message_content', 'site_url', 'site_name'),
						'pm_inbox_full'							=> array('sender_name', 'recipient_name', 'pm_storage_limit','site_url', 'site_name'),
						'forum_moderation_notification'			=> array('name_of_recipient', 'forum_name', 'moderation_action', 'title', 'thread_url'),
						'forum_report_notification'				=> array('forum_name', 'reporter_name', 'author', 'body', 'reasons', 'notes', 'post_url')
					);

			return (isset($vars[$template_name])) ? $vars[$template_name] : array();
	}

	// --------------------------------------------------------------------
	
	/**
	 * Update Specialty Template
	 *
	 * @access	public
	 * @param	integer
	 * @param	string
	 * @return	string
	 */
	function update_specialty_template($template_id, $template_data, $enable_template = 'y', $template_title = NULL)
	{
		$this->db->set('template_data', $template_data);
		$this->db->set('enable_template', $enable_template);
		
		if ($template_title)
		{
			$this->db->set('data_title', $template_title);
		}

		$this->db->where('template_id', $template_id);
		$this->db->where('site_id', $this->config->item('site_id'));
		$this->db->update('specialty_templates');
		
		return $this->db->affected_rows();
	}

}

/* End of file template_model.php */
/* Location: ./system/expressionengine/models/template_model.php */
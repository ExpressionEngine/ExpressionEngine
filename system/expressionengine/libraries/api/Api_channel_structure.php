<?php  if (!defined('BASEPATH')) exit('No direct script access allowed');
/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2014, EllisLab, Inc.
 * @license		http://ellislab.com/expressionengine/user-guide/license.html
 * @link		http://ellislab.com
 * @since		Version 2.0
 * @filesource
 */

// ------------------------------------------------------------------------

/**
 * ExpressionEngine Channel Structure API Class
 *
 * @package		ExpressionEngine
 * @subpackage	Core
 * @category	Core
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */
class Api_channel_structure extends Api {

	/**
	 * @php4 -- Class properties are protected.
	 */
	var $channel_info	= array();	// cache of previously fetched channel info
	var $channels		= array();	// cache of previously fetched channels

	/**
	 * Constructor
	 *
	 */
	function __construct()
	{
		parent::__construct();
		ee()->load->model('channel_model');
	}

	// --------------------------------------------------------------------

	/**
	 * Get Channel Info
	 *
	 * Fetches all metadata for a channel
	 *
	 * @access	public
	 * @param	int
	 * @return	object
	 */
	function get_channel_info($channel_id = '')
	{
		if ($channel_id == '')
		{
			$this->_set_error('channel_id_required');
			return FALSE;
		}

		// return cached query object if available
		if (isset($this->channel_info[$channel_id]))
		{
			return $this->channel_info[$channel_id];
		}

		$query = ee()->channel_model->get_channel_info($channel_id);

		if ($query->num_rows() == 0)
		{
			$this->_set_error('invalid_channel_id');
			return FALSE;
		}

		$this->channel_info[$channel_id] = $query;
		return $query;
	}

	// --------------------------------------------------------------------

	/**
	 * Get Channels
	 *
	 * Fetches channel names and ids
	 *
	 * @access	public
	 * @param	int
	 * @return	object
	 */
	function get_channels($site_id = NULL)
	{
		if ($site_id === NULL OR ! is_numeric($site_id))
		{
			$site_id = ee()->config->item('site_id');
		}

		// return cached query object if available
		if (isset($this->channels[$site_id]))
		{
			return $this->channels[$site_id];
		}

		$query = ee()->channel_model->get_channels($site_id);

		if ( ! $query OR $query->num_rows() == 0)
		{
			return FALSE;
		}

		$this->channels[$site_id] = $query;
		return $query;
	}

	// --------------------------------------------------------------------

	/**
	 * Delete Channel
	 *
	 * @access	public
	 * @param	int
	 * @return	string	returns the Channel Title on successful delete
	 */
	function delete_channel($channel_id = '', $site_id = NULL)
	{
		// validate channel id
		if (($query = $this->get_channel_info($channel_id)) === FALSE)
		{
			// errors will have already been set by get_channel_info()
			return FALSE;
		}

		$channel_title = $query->row('channel_title');

		if ($site_id === NULL OR ! is_numeric($site_id))
		{
			$site_id = ee()->config->item('site_id');
		}

		// load the channel entries model
		ee()->load->model('channel_entries_model');

		// get entry ids and authors, we'll need this for the delete and stats updates
		$entries = array();
		$authors = array();

		$query = ee()->channel_entries_model->get_entries($channel_id, 'author_id');

		if ($query->num_rows() > 0)
		{
			foreach ($query->result_array() as $row)
			{
				$entries[] = $row['entry_id'];
				$authors[] = $row['author_id'];
			}
		}

		$authors = array_unique($authors);

		// gather related fields, we use this later if needed
		ee()->db->select('field_id');
		$fquery = ee()->db->get_where('channel_fields', array('field_type' => 'rel'));

		// delete from data, titles, comments and the channel itself
		ee()->channel_model->delete_channel($channel_id, $entries, $authors);

		// log the action
		ee()->logger->log_action(ee()->lang->line('channel_deleted').NBS.NBS.$channel_title);

		return $channel_title;
	}

	// --------------------------------------------------------------------

	/**
	 * Create Channel
	 *
	 * Creates a new Channel
	 *
	 * @access	public
	 * @param	array
	 * @return	int		id of newly created channel
	 */
	function create_channel($data)
	{
		if ( ! is_array($data) OR count($data) == 0)
		{
			return FALSE;
		}

		ee()->load->model('super_model');

		$channel_title		= '';
		$channel_name		= '';
		$url_title_prefix	= '';

		// turn our array into variables
		extract($data);

		// validate Site ID
		if ( ! isset($site_id) OR ! is_numeric($site_id))
		{
			$site_id = ee()->config->item('site_id');
		}

		// validate Channel title
		if ( ! isset($channel_title) OR $channel_title == '')
		{
			$this->_set_error('no_channel_title');
		}

		// validate Channel name
		if ( ! isset($channel_name) OR $channel_name == '')
		{
			$this->_set_error('no_channel_name');
		}

		if ( ! $this->is_url_safe($channel_name))
		{
			$this->_set_error('invalid_short_name');
		}

		// validate URL title prefix
		if (isset($url_title_prefix) && $url_title_prefix != '')
		{
			$url_title_prefix = strtolower(strip_tags($url_title_prefix));

			if ( ! $this->is_url_safe($url_title_prefix))
			{
				$this->_set_error('invalid_url_title_prefix');
			}
		}

		// check channel name availability
		$count = ee()->super_model->count('channels', array('site_id' => $site_id, 'channel_name' => $channel_name));

		if ($count > 0)
		{
			$this->_set_error('taken_channel_name');
		}

		// validate comment expiration
		if (isset($comment_expiration) && ( ! is_numeric($comment_expiration) OR $comment_expiration == ''))
		{
			$comment_expiration = 0;
		}

		// validate template creation options
		if (isset($create_templates)  && $create_templates != 'no' &&
			isset($old_group_id) && isset($group_name) && isset($template_theme))
		{
			// load the template structure library
			ee()->load->library('api/api_template_structure', 'template_structure');
			ee()->lang->loadfile('design');

			$group_name = strtolower($group_name);
			$template_theme = ee()->security->sanitize_filename($template_theme);

			// validate group name
			if ($group_name == '')
			{
				$this->_set_error('group_required');
			}

			if ( ! $this->is_url_safe($group_name))
			{
				$this->_set_error('illegal_characters');
			}

			if (in_array($group_name, ee()->api_template_structure->reserved_names))
			{
				$this->_set_error('reserved_name');
			}

			// check if it's taken, too
			$count = ee()->super_model->count('template_groups', array('site_id' => $site_id, 'group_name' => $group_name));

			if ($count > 0)
			{
				$this->_set_error('template_group_taken');
			}
		}

		// haveth we category group assignments?
		if (isset($cat_group))
		{
			if ( ! is_array($cat_group))
			{
				$cat_group = array($cat_group);
			}

			$count = ee()->super_model->count('category_groups', array('group_id' => $cat_group));

			if ($count != count($cat_group))
			{
				$this->_set_error('invalid_category_group');
			}

			$cat_group = implode('|', $cat_group);
		}

		// duplicating preferences?
		if (isset($dupe_id))
		{
			if (($query = $this->get_channel_info($dupe_id)) !== FALSE)
			{
				$exceptions = array('channel_id', 'site_id', 'channel_name', 'channel_title', 'total_entries',
									'total_comments', 'last_entry_date', 'last_comment_date');

				foreach($query->row_array() as $key => $val)
				{
					// don't duplicate fields that are unique to each channel
					if ( ! in_array($key, $exceptions))
					{
						switch ($key)
						{
							// category, field, and status fields should only be duped
							// if both channels are assigned to the same group of each
							case 'cat_group':
								// allow to implicitly set category group to "None"
								if ( ! isset(${$key}))
								{
									${$key} = $val;
								}
								break;
							case 'status_group':
							case 'field_group':
								if ( ! isset(${$key}) OR ${$key} == '')
								{
									${$key} = $val;
								}
								break;
							case 'deft_status':
								if ( ! isset($status_group) OR $status_group == $query->row('status_group'))
								{
									${$key} = $val;
								}
								break;
							case 'search_excerpt':
								if ( ! isset($field_group) OR $field_group == $query->row('field_group'))
								{
									${$key} = $val;
								}
								break;
							case 'deft_category':
								if ( ! isset($cat_group) OR count(array_diff(explode('|', $cat_group), explode('|', $query->row('cat_group')))) == 0)
								{
									${$key} = $val;
								}
								break;
							case 'blog_url':
							case 'comment_url':
							case 'search_results_url':
							case 'rss_url':
								if ($create_templates != 'no')
								{
									if ( ! isset($old_group_name))
									{
										ee()->db->select('group_name');
										$gquery = ee()->db->get_where('template_groups', array('group_id' => $old_group_id));
										$old_group_name = $gquery->row('group_name');
									}

									${$key} = str_replace("/{$old_group_name}/", "/{$group_name}/", $val);
								}
								else
								{
									${$key} = $val;
								}
								break;
							default :
								${$key} = $val;
								break;
						}
					}
				}
			}
		}

		// error trapping is all over, shall we continue?
		if ($this->error_count() > 0)
		{
			return FALSE;
		}

		// do it do it do it
		$channel_url	= ( ! isset($channel_url))  ? ee()->functions->fetch_site_index() : $channel_url;
		$channel_lang	= ( ! isset($channel_lang)) ? ee()->config->item('xml_lang') : $channel_lang;

		// Assign field group if there is only one
		if ( ! isset($field_group) OR ! is_numeric($field_group))
		{
			ee()->db->select('group_id');
			$query = ee()->db->get_where('field_groups', array('site_id' => $site_id));

			if ($query->num_rows() == 1)
			{
				$field_group = $query->row('group_id');
			}
		}

		// valid fields for insertion
		$fields = ee()->db->list_fields('channels');

		// we don't allow these for new channels
		$exceptions = array('channel_id', 'total_entries', 'total_comments', 'last_entry_date', 'last_comment_date');
		$data = array();

		foreach ($fields as $field)
		{
			if (isset(${$field}) && ! in_array($field, $exceptions))
			{
				$data[$field] = ${$field};
			}
		}

		$channel_id = ee()->channel_model->create_channel($data);

		// log it
		ee()->load->library('logger');
		ee()->logger->log_action(ee()->lang->line('channel_created').NBS.NBS.$channel_title);

		// Are we making templates?
		/*
		if ($create_templates != 'no')
		{
			$group_order = ee()->super_model->count('template_groups') + 1;
			$group_data = array(
								'group_name' => $group_name,
								'group_order' => $group_order,
								'is_site_default' => 'n',
								'site_id' => $site_id
								);

			if (($group_id = ee()->api_template_structure->create_template_group($group_data)) !== FALSE)
			{
				if ($create_templates == 'duplicate')
				{
					ee()->api_template_structure->duplicate_templates($old_group_id, $group_id, $channel_name);
				}
				else
				{
					ee()->api_template_structure->create_templates_from_theme($template_theme, $group_id, $channel_name);
				}
			}
		}
		*/

		// for superadmins, assign it right away
		if (ee()->session->userdata('group_id') == 1)
		{
			ee()->session->userdata['assigned_channels'][$channel_id] = $data['channel_title'];
		}

		return $channel_id;
	}

	// --------------------------------------------------------------------

	/**
	 * Modify Channel
	 *
	 * Updates an existing Channel
	 *
	 * @access	public
	 * @param	array
	 * @return	int		// channel id
	 */
	function modify_channel($data)
	{
		if ( ! is_array($data) OR count($data) == 0)
		{
			return FALSE;
		}

		$channel_title		= '';
		$channel_name		= '';
		$url_title_prefix	= '';

		// turn our array into variables
		extract($data);

		// validate Site ID
		if ( ! isset($site_id) OR ! is_numeric($site_id))
		{
			$site_id = ee()->config->item('site_id');
		}

		// validate Channel ID
		if ( ! isset($channel_id) OR ! is_numeric($channel_id))
		{
			$this->_set_error('invalid_channel_id');
		}

		if ($this->get_channel_info($channel_id) === FALSE)
		{
			// errors will have already been set by get_channel_info()
			return FALSE;
		}

		// validate Channel title
		if ( ! isset($channel_title) OR $channel_title == '')
		{
			$this->_set_error('no_channel_title');
		}

		// validate Channel name
		if ( ! isset($channel_name) OR $channel_name == '')
		{
			$this->_set_error('no_channel_name');
		}

		if ( ! $this->is_url_safe($channel_name))
		{
			$this->_set_error('invalid_short_name');
		}

		// validate URL title prefix
		if (isset($url_title_prefix) && $url_title_prefix != '')
		{
			$url_title_prefix = strtolower(strip_tags($url_title_prefix));

			if ( ! $this->is_url_safe($url_title_prefix))
			{
				$this->_set_error('invalid_url_title_prefix');
			}
		}

		// check channel name availability
		ee()->load->model('super_model');
		$count = ee()->super_model->count('channels', array('site_id' => $site_id, 'channel_name' => $channel_name, 'channel_id !=' => $channel_id));

		if ($count > 0)
		{
			$this->_set_error('taken_channel_name');
		}

		// error trapping is all over, shall we continue?
		if ($this->error_count() > 0)
		{
			return FALSE;
		}

		if (isset($apply_expiration_to_existing) && isset($comment_system_enabled))
		{
			if ($comment_system_enabled == 'y')
			{
				$this->channel_model->update_comments_allowed($channel_id, 'y');
			}
			elseif ($comment_system_enabled == 'n')
			{
				$this->channel_model->update_comments_allowed($channel_id, 'n');
			}
		}

		// validate comment expiration
		if (isset($comment_expiration) && ( ! is_numeric($comment_expiration) OR $comment_expiration == ''))
		{
			$comment_expiration = 0;
		}

		if (isset($apply_expiration_to_existing) && isset($comment_expiration))
		{
			ee()->channel_model->update_comment_expiration($channel_id, $comment_expiration * 86400);
		}

		if (isset($clear_versioning_data))
		{
			ee()->channel_model->clear_versioning_data($channel_id);
		}

		// valid fields for update
		$fields = ee()->db->list_fields('channels');

		// we don't allow these to be modified
		$exceptions = array('channel_id', 'total_entries', 'total_comments', 'last_entry_date', 'last_comment_date');
		$data = array();

		foreach ($fields as $field)
		{
			if (isset(${$field}) && ! in_array($field, $exceptions))
			{
				$data[$field] = ${$field};
			}
		}

		ee()->channel_model->update_channel($data, $channel_id);

		return $channel_id;
	}

	// --------------------------------------------------------------------

}
// END CLASS

/* End of file Api_channel_structure.php */
/* Location: ./system/expressionengine/libraries/api/Api_channel_structure.php */
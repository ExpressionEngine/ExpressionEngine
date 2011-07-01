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
 * ExpressionEngine Channel Entries API Class
 *
 * @package		ExpressionEngine
 * @subpackage	Core
 * @category	Core
 * @author		ExpressionEngine Dev Team
 * @link		http://expressionengine.com
 */
class Api_channel_entries extends Api {
	
	var $entry_data = array();
	
	var $channel_id;
	var $entry_id	= 0;
	var $autosave	= FALSE;
	var $data		= array();
	var $meta		= array();
	var $c_prefs	= array();
	var $_cache		= array();

	var $autosave_entry_id = 0;
	
	/**
	 * Constructor
	 *
	 */
	function __construct()
	{
		parent::__construct();
		$this->EE->load->model('channel_entries_model');
	}

	// --------------------------------------------------------------------
	
	/**
	 * Initialize
	 *
	 * Reset our caching arrays and any other config values
	 *
	 * @access	private
	 * @param	array
	 * @return	void
	 */
	protected function initialize($params = array())
	{
		$this->c_prefs = array();
		$this->_cache = array();

		parent::initialize($params);
	}

	// --------------------------------------------------------------------
	
	/**
	 * Submit New Entry
	 *
	 * Handles entry submission from an arbitrary, authenticated source
	 *
	 * @access	public
	 * @param	int
	 * @param	array
	 * @return	mixed
	 */
	function submit_new_entry($channel_id, $data, $autosave = FALSE)
	{
		$this->entry_id = 0;
		$this->autosave_entry_id = isset($data['autosave_entry_id']) ? $data['autosave_entry_id'] : 0;
		
		// yoost incase
		$data['channel_id'] = $channel_id;
		
		$this->data =& $data;
		$mod_data = array();
		
		$this->initialize(array('channel_id' => $channel_id, 'entry_id' => 0, 'autosave' => $autosave));

		if ( ! $this->_base_prep($data))
		{
			return FALSE;
		}

		if ($this->trigger_hook('entry_submission_start') === TRUE)
		{
			return TRUE;
		}
		
		// Data cached by base_prep is only needed for updates - toss it
		
		$this->_cache = array();
		
		$this->_fetch_channel_preferences();
		$this->_do_channel_switch($data);

		// We break out the third party data here
		$this->_fetch_module_data($data, $mod_data);		

		$this->_check_for_data_errors($data);
				
		// Lets make sure those went smoothly
		
		if (count($this->errors) > 0)
		{
			return ($this->autosave) ? $this->errors : FALSE;
		}
		
		$this->_prepare_data($data, $mod_data);
		$this->_build_relationships($data);

		$meta = array(
						'channel_id'				=> $this->channel_id,
						'author_id'					=> $data['author_id'],
						'site_id'					=> $this->EE->config->item('site_id'),
						'ip_address'				=> $this->EE->input->ip_address(),
						'title'						=> ($this->EE->config->item('auto_convert_high_ascii') == 'y') ? ascii_to_entities($data['title']) : $data['title'],
						'url_title'					=> $data['url_title'],
						'entry_date'				=> $data['entry_date'],
						'edit_date'					=> date("YmdHis"),
						'versioning_enabled'		=> $data['versioning_enabled'],
						'year'						=> date('Y', $data['entry_date']),
						'month'						=> date('m', $data['entry_date']),
						'day'						=> date('d', $data['entry_date']),
						'expiration_date'			=> $data['expiration_date'],
						'comment_expiration_date'	=> $data['comment_expiration_date'],
						'recent_comment_date'		=> (isset($data['recent_comment_date']) && $data['recent_comment_date']) ? $data['recent_comment_date'] : 0,
						'sticky'					=> (isset($data['sticky']) && $data['sticky'] == 'y') ? 'y' : 'n',
						'status'					=> $data['status'],
						'allow_comments'			=> $data['allow_comments'],
					 );

		$this->meta =& $meta;
		
		$meta_keys = array_keys($meta);
		$meta_keys = array_diff($meta_keys, array('channel_id', 'entry_id', 'site_id'));

		foreach($meta_keys as $k)
		{
			unset($data[$k]);
		}

		if ($this->trigger_hook('entry_submission_ready') === TRUE)
		{
			return TRUE;
		}
		
		if ($this->autosave)
		{
			// autosave is done at this point, title and custom field insertion.
			// no revisions, stat updating or cache clearing needed.
			return $this->_insert_entry($meta, $data, $mod_data);
		}
		
		$this->_insert_entry($meta, $data, $mod_data);
		
		if (count($mod_data) > 0)
		{
			$this->_set_mod_data($meta, $data, $mod_data);
		}
		
		$this->_sync_related($meta, $data);
		
		if (isset($data['save_revision']) && $data['save_revision'])
		{
			return TRUE;
		}
		
		if (isset($data['ping_servers']) && count($data['ping_servers']) > 0)
		{
			$this->send_pings($data['ping_servers'], $channel_id, $this->entry_id);
		}
		
		$this->EE->stats->update_channel_stats($channel_id);
		
		if ($this->EE->config->item('new_posts_clear_caches') == 'y')
		{
			$this->EE->functions->clear_caching('all');
		}
		else
		{
			$this->EE->functions->clear_caching('sql');
		}
		
		// I know this looks redundant in July of 2009, but if the code moves
		// around, putting this return here now will ensure it doesn't get
		// forgotten in the future. -dj
		if ($this->trigger_hook('entry_submission_end') === TRUE)
		{
			return TRUE;
		}
		
		return TRUE;
	}
	
	// --------------------------------------------------------------------
	
	/**
	 * Update entry
	 *
	 * Handles updating of existing entries from arbitrary, authenticated source
	 *
	 * @access	public
	 * @param	int
	 * @param	array
	 * @return	mixed
	 */
	function update_entry($entry_id, $data, $autosave = FALSE)
	{
		$this->data =& $data;
		$mod_data = array();
		$this->initialize(array('entry_id' => $entry_id, 'autosave' => $autosave));

		if ( ! $this->entry_exists($this->entry_id))
		{
			return $this->_set_error('no_entry_to_update');
		}
		
		if ( ! $this->_base_prep($data))
		{
			return FALSE;
		}

		if ($this->trigger_hook('entry_submission_start') === TRUE)
		{
			return TRUE;
		}
				
		$this->_fetch_channel_preferences();
		$this->_do_channel_switch($data);

		// We break out the third party data here
		$this->_fetch_module_data($data, $mod_data);
		
		$this->_check_for_data_errors($data);
	
		// Lets make sure those went smoothly
		
		if (count($this->errors) > 0)
		{
			return ($this->autosave) ? $this->errors : FALSE;
		}
		
		$this->_prepare_data($data, $mod_data);
		
		$this->_build_relationships($data);
		
		$meta = array(
						'channel_id'				=> $this->channel_id,
						'author_id'					=> $data['author_id'],
						'site_id'					=> $this->EE->config->item('site_id'),
						'ip_address'				=> $this->EE->input->ip_address(),
						'title'						=> ($this->EE->config->item('auto_convert_high_ascii') == 'y') ? ascii_to_entities($data['title']) : $data['title'],
						'url_title'					=> $data['url_title'],
						'entry_date'				=> $data['entry_date'],
						'edit_date'					=> date("YmdHis"),
						'versioning_enabled'		=> $data['versioning_enabled'],
						'year'						=> date('Y', $data['entry_date']),
						'month'						=> date('m', $data['entry_date']),
						'day'						=> date('d', $data['entry_date']),
						'expiration_date'			=> $data['expiration_date'],
						'comment_expiration_date'	=> $data['comment_expiration_date'],
						'recent_comment_date'		=> (isset($data['recent_comment_date']) && $data['recent_comment_date']) ? $data['recent_comment_date'] : 0,
						'sticky'					=> (isset($data['sticky']) && $data['sticky'] == 'y') ? 'y' : 'n',
						'status'					=> $data['status'],
						'allow_comments'			=> $data['allow_comments'],
					 );

		$this->meta =& $meta;
		
		$meta_keys = array_keys($meta);
		$meta_keys = array_diff($meta_keys, array('channel_id', 'entry_id', 'site_id'));

		foreach($meta_keys as $k)
		{
			unset($data[$k]);
		}

		if ($this->trigger_hook('entry_submission_ready') === TRUE)
		{
			return TRUE;
		}
				
		
		if ($this->autosave)
		{
			// autosave is done at this point, title and custom field insertion.
			// no revisions, stat updating or cache clearing needed.
			return $this->_update_entry($meta, $data, $mod_data);
		}
		
		$this->_update_entry($meta, $data, $mod_data);



		if (count($mod_data) > 0)
		{
			$this->_set_mod_data($meta, $data, $mod_data);
		}		
		
		$this->_sync_related($meta, $data);

		if (isset($data['save_revision']) && $data['save_revision'])
		{
			return TRUE;
		}
		
		if (isset($data['ping_servers']) && count($data['ping_servers']) > 0)
		{
			$this->send_pings($data['ping_servers'], $this->channel_id, $entry_id);
		}

		$this->EE->stats->update_channel_stats($this->channel_id);

		if (isset($data['old_channel']))
		{
			$this->EE->stats->update_channel_stats($data['old_channel']);
		}

		if ($this->EE->config->item('new_posts_clear_caches') == 'y')
		{
			$this->EE->functions->clear_caching('all');
		}
		else
		{
			$this->EE->functions->clear_caching('sql');
		}
		
		// I know this looks redundant in July of 2009, but if the code moves
		// around, putting this return here now will ensure it doesn't get
		// forgotten in the future. -dj
		if ($this->trigger_hook('entry_submission_end') === TRUE)
		{
			return TRUE;
		}

		return TRUE;
	}
	
	// --------------------------------------------------------------------
	
	/**
	 * Autosave Entry
	 *
	 * Handles deleting of existing entries from arbitrary, authenticated source
	 *
	 * @access	public
	 * @param	int
	 * @return	bool
	 */
	function autosave_entry($data)
	{
		$this->autosave_entry_id = 0;
		
		if (isset($data['autosave_entry_id']))
		{
			$this->autosave_entry_id = $data['autosave_entry_id'];
		}
		
		if ( ! isset($data['entry_id']) OR ! $data['entry_id'])
		{
			// new entry
			if ( ! $data['title'])
			{
				$data['title'] = 'autosave_'.$this->EE->localize->now;
			}
			
			return $this->submit_new_entry($data['channel_id'], $data, TRUE);
		}
		
		return $this->update_entry($data['entry_id'], $data, TRUE);
	}

	// --------------------------------------------------------------------
	
	/**
	 * Delete entry
	 *
	 * Handles deleting of existing entries from arbitrary, authenticated source
	 *
	 * @access	public
	 * @param	int
	 * @return	bool
	 */
	function delete_entry($entry_ids)
	{
		$this->EE->load->library('api');
		$this->EE->load->library('addons');
		$this->EE->api->instantiate('channel_fields');

		if ( ! is_array($entry_ids))
		{
			$entry_ids = array($entry_ids);
		}
		
		if (array_key_exists('comment', $this->EE->addons->get_installed('modules')))
		{
			$comments_installed = TRUE;
		}
		else
		{
			$comments_installed = FALSE;
		}

		// grab entry meta data
		$this->EE->db->select('channel_id, author_id, entry_id');
		$this->EE->db->from('channel_titles');
		$this->EE->db->where_in('entry_id', $entry_ids);
		$query = $this->EE->db->get();

		
		// Check permissions
		$allowed_channels = $this->EE->functions->fetch_assigned_channels();
		$authors = array();

		foreach ($query->result_array() as $row)
		{
			if ($this->EE->session->userdata('group_id') != 1)
			{
				if ( ! in_array($row['channel_id'], $allowed_channels))
				{
					return FALSE;
				}
			}

			if ($row['author_id'] == $this->EE->session->userdata('member_id'))
			{
				if ($this->EE->session->userdata('can_delete_self_entries') != 'y')
				{
					return $this->_set_error('unauthorized_to_delete_self');
				}
			}
			else
			{
				if ($this->EE->session->userdata('can_delete_all_entries') != 'y')
				{
					return $this->_set_error('unauthorized_to_delete_self');
				}
			}

			$authors[$row['entry_id']] = $row['author_id'];
		}
		
		
		// grab channel field groups
		$this->EE->db->select('channel_id, field_group');
		$cquery = $this->EE->db->get('channels');
		
		$channel_groups = array();
		
		foreach($cquery->result_array() as $row)
		{
			$channel_groups[$row['channel_id']] = $row['field_group'];
		}


		// grab fields and order by group
		$this->EE->db->select('field_id, field_type, group_id');
		$fquery = $this->EE->db->get('channel_fields');
		
		$group_fields = array();
		
		foreach($fquery->result_array() as $row)
		{
			$group_fields[$row['group_id']][] = $row['field_type'];
		}
		

		// Delete primary data
		$this->EE->db->where_in('entry_id', $entry_ids);
		$this->EE->db->delete(array('channel_titles', 'channel_data', 'category_posts'));


		$entries = array();
		$ft_to_ids = array();
		
		foreach($query->result_array() as $row)
		{
			$val = $row['entry_id'];
			$channel_id = $row['channel_id'];
			
			// No field group- skip this bit
			if ( ! isset($channel_groups[$channel_id]) OR ! isset($group_fields[$channel_groups[$channel_id]]))
			{
				continue;
			}
			
			// Map entry id to fieldtype
			$group_id = $channel_groups[$channel_id];
			$field_type = $group_fields[$group_id];
			
			foreach($field_type as $ft)
			{
				if ( ! isset($ft_to_ids[$ft]))
				{
					$ft_to_ids[$ft] = array($val);
				}
				else if ( ! in_array($val, $ft_to_ids[$ft]))
				{
					$ft_to_ids[$ft][] = $val;
				}
			}
			
			
			// Check for silly relationship children
			
			// We do the regular relationship data in the relationship
			// fieldtype, but we have no way of knowing that a child
			// exists until we check. So it happens here.
			
			$this->EE->db->select('rel_id');
			$child_results = $this->EE->db->get_where('relationships', array('rel_child_id' => $val));

			if ($child_results->num_rows() > 0)
			{
				// We have children, so we need to do a bit of housekeeping
				// so parent entries don't continue to try to reference them
				$cids = array();

				foreach ($child_results->result_array() as $row)
				{
					$cids[] = $row['rel_id'];
				}

				foreach($fquery->result_array() as $row)
				{
					$field = 'field_id_'.$row['field_id'];
					$this->EE->db->where_in($field, $cids);
					$this->EE->db->update('channel_data', array($field => '0'));
				}

				$this->EE->db->delete('relationships', array('rel_child_id' => $val));
			}


			// Correct member post count
			$this->EE->db->select('total_entries');
			$mquery = $this->EE->db->get_where('members', array('member_id' => $authors[$val]));

			$tot = $mquery->row('total_entries');

			if ($tot > 0)
			{
				$tot -= 1;
			}

			$this->EE->db->where('member_id', $authors[$val]);
			$this->EE->db->update('members', array('total_entries' => $tot));

			if ($comments_installed)
			{
				$this->EE->db->where('status', 'o');
				$this->EE->db->where('entry_id', $val);
				$this->EE->db->where('author_id', $authors[$val]);
				$count = $this->EE->db->count_all_results('comments');

				if ($count > 0)
				{
					$this->EE->db->select('total_comments');
					$mc_query = $this->EE->db->get_where('members', array('member_id' => $authors[$val]));

					$this->EE->db->where('member_id', $authors[$val]);
					$this->EE->db->update('members', array('total_comments' => ($mc_query->row('total_comments') - $count)));
				}

				$this->EE->db->delete('comments', array('entry_id' => $val));
				$this->EE->db->delete('comment_subscriptions', array('entry_id' => $val));
			}
			
			// Delete entries in the channel_entries_autosave table
			$this->EE->db->where('original_entry_id', $val)
						 ->delete('channel_entries_autosave');
			
			// Delete entries from the versions table
			$this->EE->db->where('entry_id', $val)
						 ->delete('entry_versioning');


			// -------------------------------------------
			// 'delete_entries_loop' hook.
			//  - Add additional processing for entry deletion in loop
			//  - Added: 1.4.1
			//
				$edata = $this->EE->extensions->call('delete_entries_loop', $val, $channel_id);
				if ($this->EE->extensions->end_script === TRUE) return;
			//
			// -------------------------------------------

			// Update statistics
			$this->EE->stats->update_channel_stats($channel_id);
			
			if ($comments_installed)
			{
				$this->EE->stats->update_comment_stats($channel_id);
			}

			$entries[] = $val;
		}
		
		$fts = $this->EE->api_channel_fields->fetch_installed_fieldtypes();
		
		// Pass to custom fields
		foreach($ft_to_ids as $fieldtype => $ids)
		{
			$this->EE->api_channel_fields->setup_handler($fieldtype);
			$this->EE->api_channel_fields->apply('delete', array($ids));
		}
		
		
		// Pass to module defined fields		
		$methods = array('publish_data_delete_db');
		$params = array('publish_data_delete_db' => array('entry_ids' => $entry_ids));
		
		$this->EE->api_channel_fields->get_module_methods($methods, $params);
		
		// Clear caches
		$this->EE->functions->clear_caching('all', '', TRUE);

		// -------------------------------------------
		// 'delete_entries_end' hook.
		//  - Add additional processing for entry deletion
		//
			$edata = $this->EE->extensions->call('delete_entries_end');
			if ($this->EE->extensions->end_script === TRUE) return TRUE;
		//
		// -------------------------------------------

		return TRUE;
	}

	// --------------------------------------------------------------------
	
	/**
	 * Entry exists
	 *
	 * Checks if an entry exists and is editable by the user
	 *
	 * @access	public
	 * @param	int
	 * @return	bool
	 */
	function entry_exists($entry_id)
	{
		if ( ! is_numeric($entry_id))
		{
			return FALSE;
		}

		$query = $this->EE->channel_entries_model->get_entry($entry_id);
		
		if ($query->num_rows() == 0)
		{
			return FALSE;
		}
		
		$this->_cache['orig_author_id'] = $query->row('author_id');
		
		return TRUE;
	}
	
	// --------------------------------------------------------------------
	
	/**
	 * Update Relationship Cache
	 *
	 * Updates the relationship cache
	 *
	 * @access	public
	 * @param	int
	 * @return	void
	 */
	function update_related_cache($entry_id)
	{
		// Is this entry a child of another parent?
		//
		// If the entry being submitted is a "child" of another parent
		// we need to re-compile and cache the data.  Confused?	 Me too...

		$this->EE->db->where('rel_type', 'channel');
		$this->EE->db->where('rel_child_id', $entry_id);
		$count = $this->EE->db->count_all_results('relationships');

		if ($count > 0)
		{
			$reldata = array(
							'type'		=> 'channel',
							'child_id'	=> $entry_id
			);

			$this->EE->functions->compile_relationship($reldata, FALSE);
		}


		//	Is this entry a parent of a child?

		$this->EE->db->where('rel_parent_id', $entry_id);
		$this->EE->db->where('reverse_rel_data !=', '');
		$count = $this->EE->db->count_all_results('relationships');

		if ($count > 0)
		{
			$reldata = array(
							'type'		=> 'channel',
							'parent_id' => $entry_id
			);

			$this->EE->functions->compile_relationship($reldata, FALSE, TRUE);
		}
	}
	
	// --------------------------------------------------------------------

	/**
	 * Send Pings
	 *
	 * Send xml-rpc pings
	 *
	 * @access	public
	 * @param	string
	 * @param	int
	 * @param	bool
	 * @return	void
	 */
	function send_pings($ping_servers, $channel_id, $entry_id, $send_now = TRUE)
	{
		if ( ! $ping_servers)
		{
			return FALSE;
		}
		
		$result = TRUE;
		
		if ( ! isset($this->c_prefs['rss_url']))
		{
			$this->_fetch_channel_preferences($channel_id);
		}

		// We only ping entries that are posted now, not in the future
		if ($send_now == TRUE)
		{
			$ping_result = $this->_process_pings($ping_servers, $this->c_prefs['channel_title'], $this->c_prefs['ping_url'], $this->c_prefs['rss_url']);

			if (is_array($ping_result) AND count($ping_result) > 0)
			{
				$this->_set_error($ping_result, 'pings');
				$result = FALSE;
			}
		}
			
		//	Save ping button state
		$this->EE->db->delete('entry_ping_status', array('entry_id' => $entry_id));

		foreach ($ping_servers as $val)
		{
			$ping_data['entry_id'] = $entry_id;
			$ping_data['ping_id'] = $val;

			$this->EE->db->insert('entry_ping_status', $ping_data); 
		}

		return $result;
	}
	
	// --------------------------------------------------------------------
	
	/**
	 * Get errors
	 *
	 * Convenience function to access errors
	 *
	 * @access	public
	 * @param	string	optional field name
	 * @return	mixed
	 */
	function get_errors($field = FALSE)
	{
		if ($field)
		{
			return isset($this->errors[$field]) ? $this->errors[$field] : FALSE;
		}
		
		return (count($this->errors) > 0) ? $this->errors : FALSE;
	}
	
	// --------------------------------------------------------------------
	
	/**
	 * Trigger Hook
	 *
	 * Trigger an entry related hook. Use the second parameter to pass a
	 * variable that the hook would otherwise erroneously reassign. This
	 * replaces the active_hook() check.  last_call?
	 *
	 * 
	 * @access	public
	 * @param	mixed	variable that gets assigned by the hook
	 * @return	mixed
	 */
	function trigger_hook($hook, $orig_var = NULL)
	{
		// For hooks that modify a variable, we need to check if they're active

		if ($orig_var !== NULL)
		{
			if ($this->EE->extensions->active_hook($hook) !== TRUE)
			{
				return $orig_var;
			}
		}

		$cp_call = (REQ == 'CP') ? TRUE : FALSE;
		
		switch($hook)
		{
			case 'entry_submission_start':
					$this->EE->extensions->call('entry_submission_start', $this->channel_id, $this->autosave);
					break;
			case 'entry_submission_ready':
					$this->EE->extensions->call('entry_submission_ready', $this->meta, $this->data, $this->autosave);
					break;
			case 'entry_submission_redirect':	
					$loc = $this->EE->extensions->call('entry_submission_redirect', $this->entry_id, $this->meta, $this->data, $cp_call, $orig_var);
					if ($this->EE->extensions->end_script === TRUE)
					{
						return $loc;
					}
					return $loc;
				break;
			case 'entry_submission_absolute_end':
					$this->EE->extensions->call('entry_submission_absolute_end', $this->entry_id, $this->meta, $this->data, $orig_var);
				break;
			case 'entry_submission_end':
					$this->EE->extensions->call('entry_submission_end', $this->entry_id, $this->meta, $this->data);
				break;
			default:
				return FALSE;
		}

		if ($this->EE->extensions->end_script === TRUE)
		{
			return TRUE;
		}
	}
	
	// --------------------------------------------------------------------
	
	/**
	 * Set errors
	 *
	 * Sets error using a language key and optional field name
	 *
	 * @access	private
	 * @param	string	optional field name
	 * @return	mixed
	 */
	function _set_error($err, $field = '')
	{
		if ($field != '')
		{
			if (is_array($err))
			{
				$this->errors[$field] = $err;
			}
			else
			{
				$this->errors[$field] = $this->EE->lang->line($err);
			}
		}
		else
		{
			if (is_array($err))
			{
				$this->errors[] = $err;
			}
			else
			{
				$this->errors[] = $this->EE->lang->line($err);
			}
		}

		return FALSE;
	}
	
	// --------------------------------------------------------------------
	
	/**
	 * Base Prep
	 *
	 * Do basic sanity checks, and grab prerequisites
	 *
	 * @access	private
	 * @param	string	optional field name
	 * @return	mixed
	 */
	function _base_prep(&$data)
	{	
		$this->EE->lang->loadfile('admin_content');
		
		// Sanity Check
		if ( ! is_array($data) OR ! isset($data['channel_id']) OR ! is_numeric($data['channel_id']))
		{
			show_error($this->EE->lang->line('invalid_api_parameter'));
		}

		$this->channel_id = $data['channel_id'];

		// Is this user allowed to post here?
		$this->_cache['assigned_channels'] = $this->EE->functions->fetch_assigned_channels();
		
		if ( ! in_array($this->channel_id, $this->_cache['assigned_channels']))
		{
			show_error($this->EE->lang->line('unauthorized_for_this_channel'));
		}
		
		// Make sure all the fields have a key in our data array even
		// if no data was sent

		if ($this->autosave === FALSE)
		{
			if ( ! isset($this->EE->api_channel_fields) OR ! isset($this->EE->api_channel_fields->settings))
			{
				$this->instantiate('channel_fields');
				$this->EE->api_channel_fields->fetch_custom_channel_fields();
			}
			
			$field_ids = array_keys($this->EE->api_channel_fields->settings);

			foreach($field_ids as $id)
			{
				if (is_numeric($id))
				{
					$nid = $id;
					$id = 'field_id_'.$id;
					
					if ($this->entry_id == 0 && ! isset($data['field_ft_'.$nid]))
					{
						$data['field_ft_'.$nid] = $this->EE->api_channel_fields->settings[$nid]['field_fmt'];
					}
				}

				if ( ! isset($data[$id]))
				{
					$data[$id] = '';
				}
			}
		}
		// Helpers
		$this->EE->load->helper('text');
		$this->EE->load->helper('custom_field');
		return TRUE;
	}
	
	// --------------------------------------------------------------------

	/**
	 * Fetch Channel Preferences
	 *
	 * Grabs required channel information and preps a few fields
	 *
	 * @access	private
	 * @param	int
	 * @return	bool
	 */
	function _fetch_channel_preferences($channel_id = FALSE)
	{
		// Add another api
		$this->instantiate('channel_structure');
		
		if ( ! $channel_id)
		{
			$channel_id = $this->channel_id;
		}
		
		$query = $this->EE->api_channel_structure->get_channel_info($channel_id);

		foreach(array('channel_url', 'rss_url', 'deft_status', 'comment_url', 'comment_system_enabled', 'enable_versioning', 'max_revisions') as $key)
		{
			$this->c_prefs[$key] = $query->row($key);
		}
		
		$this->c_prefs['channel_title']		= ascii_to_entities($query->row('channel_title'));
		$this->c_prefs['ping_url']			= ($query->row('ping_return_url') == '') ? $query->row('channel_url')	: $query->row('ping_return_url') ;
		$this->c_prefs['notify_address']	= ($query->row('channel_notify')  == 'y' AND $query->row('channel_notify_emails')  != '') ? $query->row('channel_notify_emails')  : '';
	}
	
	// --------------------------------------------------------------------
	
	/**
	 * Channel Switch
	 *
	 * Checks if the channel was changed and verifies if the switch is valid
	 *
	 * @access	private
	 * @param	int
	 * @return	bool
	 */
	function _do_channel_switch(&$data)
	{
		if (isset($data['new_channel']) && $data['new_channel'] && $data['new_channel'] != $this->channel_id)
		{
			$this->EE->db->select('status_group, cat_group, field_group, channel_id');
			$this->EE->db->where_in('channel_id', array($this->channel_id, $data['new_channel']));
			$query = $this->EE->db->get('channels');
			
			if ($query->num_rows() == 2)
			{
				$result_zero = $query->row(0);
				$result_one = $query->row(1);

				if ($result_zero->status_group == $result_one->status_group &&
					$result_zero->cat_group == $result_one->cat_group &&
					$result_zero->field_group == $result_one->field_group)
				{
					if ($this->EE->session->userdata('group_id') == 1 OR in_array($data['new_channel'], $this->_cache['assigned_channels']))
					{
						$data['old_channel'] = $this->channel_id;
						$this->channel_id = $data['new_channel'];
					}
				}
			}
		}
	}

	
	// --------------------------------------------------------------------
	
	/**
	 * Get module data
	 *
	 * Get module information
	 *
	 * @access	private
	 * @param	mixed
	 * @return	void
	 */
	function _fetch_module_data(&$data, &$mod_data)
	{
		//$errors = $this->EE->api_channel_fields->get_module_methods('validate_publish', array('data' => $data));

		// Note coming from cp- return
		if ( ! isset($data['cp_call']) OR $data['cp_call'] !== TRUE)
		{
			return;
		}

		$methods = array('validate_publish', 'publish_tabs');
		$params = array('validate_publish' => array($data), 'publish_tabs' => array($data['channel_id'], $this->entry_id));

		$this->instantiate('channel_fields');
		$module_data = $this->EE->api_channel_fields->get_module_methods($methods, $params);		

		if ($module_data !== FALSE)
		{
			foreach ($module_data as $class => $m)
			{
				if (is_array($m['validate_publish']))
				{
					foreach($m['validate_publish'] as $msg => $field)
					{
						$this->_set_error($msg, $class.'__'.$field);
					}
				}

				if (is_array($m['publish_tabs']))
				{
					foreach($m['publish_tabs'] as $tab => $v)
					{
						//foreach ($v as $val)
						//{
							$name = $class.'__'.$v['field_id'];
							//print_r($v);
						//}
				
						// Break out module fields here
						$mod_data[$name] = (isset($data[$name])) ? $data[$name] : '';
						unset($data[$name]);
					}
				}
			}
		}
	}
	
	// --------------------------------------------------------------------
	
	/**
	 * Check for data errors
	 *
	 * Error trapping function
	 *
	 * @access	private
	 * @param	mixed
	 * @return	void
	 */
	function _check_for_data_errors(&$data)
	{
		// Always required fields
		
		$required = array(
			'title'			=> 'missing_title',
			'entry_date'	=> 'missing_date'
		);
		
		if ( ! isset($data['title']) OR ! $data['title'] = strip_tags(trim($data['title'])))
		{
			$data['title'] = '';
			$this->_set_error('missing_title', 'title');
		}
		
		
		//	Convert dates to unix timestamps

		$dates = array('entry_date');

		foreach(array('expiration_date', 'comment_expiration_date') as $date)
		{
			if ( ! isset($data[$date]) OR ! $data[$date])
			{
				$data[$date] = 0;
			}
			else
			{
				$dates[] = $date;
			}
		}
		
		foreach($dates as $date)
		{
			if ( ! is_numeric($data[$date]))
			{
				$data[$date] = $this->EE->localize->convert_human_date_to_gmt($data[$date]);
			}
			
			if ( ! is_numeric($data[$date]))
			{
				if ($data[$date] !== FALSE)
				{
					$this->_set_error('invalid_date', $date);
				}
				else
				{
					$this->_set_error('invalid_date_formatting', $date);
				}
			}

			if (isset($data['revision_post'][$date]))
			{
				$data['revision_post'][$date] = $data[$date];
			}
		}
		
		// Required and custom fields
		
		$this->EE->db->select('field_id, field_label, field_type, field_required');
		$this->EE->db->join('channels', 'channels.field_group = channel_fields.group_id', 'left');
		$this->EE->db->where('channel_id', $this->channel_id);
		$query = $this->EE->db->get('channel_fields');

		if ($query->num_rows() > 0)
		{
			foreach ($query->result_array() as $row)
			{
				// Required field?
				if ($row['field_required'] == 'y')
				{
					if ($row['field_type'] == "file" AND isset($data['field_id_'.$row['field_id'].'_hidden']) AND $data['field_id_'.$row['field_id'].'_hidden'] == '')
					{
						$this->_set_error('custom_field_empty', $row['field_label']);
						continue;
					}

					if (isset($data['field_id_'.$row['field_id']]) AND $data['field_id_'.$row['field_id']] === '')
					{
						$this->_set_error('custom_field_empty', $row['field_label']);
						continue;
					}
				}
				elseif ( ! isset($data['field_id_'.$row['field_id']]))
				{
					// fields that aren't required should still be set
					$data['field_id_'.$row['field_id']] = '';
				}
				
				// Custom fields that need processing
				
				if ($row['field_type'] == 'file')
				{
					if ($this->autosave && isset($data['field_id_'.$row['field_id'].'_hidden']))
					{
						$directory = $data['field_id_'.$row['field_id'].'_directory'];
						$data['field_id_'.$row['field_id']] =  '{filedir_'.$directory.'}'.$data['field_id_'.$row['field_id'].'_hidden'];
						unset($data['field_id_'.$row['field_id'].'_hidden']);

					}
					
					unset($data['field_id_'.$row['field_id'].'_directory']);
				}
				elseif ($row['field_type'] == 'date')
				{
					$func = '_prep_'.$row['field_type'].'_field';
					$this->$func($data, $row);
				}
				elseif ($row['field_type'] == 'multi_select' OR $row['field_type'] == 'checkboxes')
				{
					$this->_prep_multi_field($data, $row);
				}
			}
		}

		// Channel data present for pings?
		if (isset($data['ping_servers']) && count($data['ping_servers']) > 0)
		{
			if ($this->c_prefs['channel_title'] == '')
			{
				$this->_set_error('missing_channel_data_for_pings');
			}
		}
		
		// Clean / create the url title
		
		$data['url_title'] = isset($data['url_title']) ? $data['url_title'] : '';
		$data['url_title'] = $this->_validate_url_title($data['url_title'], $data['title'], (bool) $this->entry_id);
		
		// Validate author id
		
		$data['author_id'] = ( ! isset($data['author_id']) OR ! $data['author_id']) ? $this->EE->session->userdata('member_id'): $data['author_id'];

		if ($data['author_id'] != $this->EE->session->userdata('member_id') && $this->EE->session->userdata('can_edit_other_entries') != 'y')
		{
			$this->_set_error('not_authorized');
		}
		
		if (isset($this->_cache['orig_author_id']) && $data['author_id'] != $this->_cache['orig_author_id'] && ($this->EE->session->userdata('can_edit_other_entries') != 'y' OR $this->EE->session->userdata('can_assign_post_authors') != 'y'))
		{
			$this->_set_error('not_authorized');
		}
				
		if ($data['author_id'] != $this->EE->session->userdata('member_id') && $this->EE->session->userdata('group_id') != 1)
		{
			if ( ! isset($this->_cache['orig_author_id']) OR $data['author_id'] != $this->_cache['orig_author_id'])
			{
				if ($this->EE->session->userdata('can_assign_post_authors') != 'y')
				{
					$this->_set_error('not_authorized');
				}
				else
				{
					$allowed_authors = array();
					
					$this->EE->load->model('member_model');
					$query = $this->EE->member_model->get_authors_simple();

					if ($query->num_rows() > 0)
					{
						foreach($query->result_array() as $row)
						{
							$allowed_authors[] = $row['member_id'];
						}
					}
					
					if ( ! in_array($data['author_id'], $allowed_authors))
					{
						$this->_set_error('invalid_author');
					}
				}
			}
		}
		
		// Validate Status
		
		$data['status'] = ( ! isset($data['status']) OR $data['status'] === FALSE) ? $this->c_prefs['deft_status'] : $data['status'];

		if ($this->EE->session->userdata('group_id') != 1)
		{
			$disallowed_statuses = array();
			$valid_statuses = array();
			
			$this->EE->load->model('status_model');
			$query = $this->EE->status_model->get_statuses('', $this->channel_id);

			if ($query->num_rows() > 0)
			{
				foreach ($query->result_array() as $row)
				{
					$valid_statuses[$row['status_id']] = strtolower($row['status']); // lower case to match MySQL's case-insensitivity
				}
			}

			$query = $this->EE->status_model->get_disallowed_statuses($this->EE->session->userdata('group_id'));

			if ($query->num_rows() > 0)
			{
				foreach ($query->result_array() as $row)
				{
					$disallowed_statuses[$row['status_id']] = strtolower($row['status']); // lower case to match MySQL's case-insensitivity
				}

				$valid_statuses = array_diff_assoc($valid_statuses, $disallowed_statuses);
			}
			
			if ( ! in_array(strtolower($data['status']), $valid_statuses))
			{
				// if there are no valid statuses, set to closed
				$data['status'] = 'closed';
			}
		}
	}
	
	// --------------------------------------------------------------------
	
	/**
	 * Validate url title
	 *
	 * Checks url title and regenerates if it's invalid
	 *
	 * @access	private
	 * @param	string
	 * @return	string
	 */
	function _validate_url_title($url_title = '', $title = '', $update = FALSE)
	{
		$word_separator = $this->EE->config->item('word_separator');
		
		$this->EE->load->helper('url');

		if ( ! trim($url_title))
		{
			$url_title = url_title($title, $word_separator, TRUE);
		}

		// Remove extraneous characters

		if ($update)
		{
			$this->EE->db->select('url_title');
			$url_query = $this->EE->db->get_where('channel_titles', array('entry_id' => $this->entry_id));

			if ($url_query->row('url_title') != $url_title)
			{
				$url_title = url_title($url_title, $word_separator);
			}
		}
		else
		{
			$url_title = url_title($url_title, $word_separator);
		}

		// URL title cannot be a number

		if (is_numeric($url_title))
		{
			$this->_set_error('url_title_is_numeric', 'url_title');
		}

		// It also cannot be empty

		if ( ! trim($url_title))
		{
			$this->_set_error('unable_to_create_url_title', 'url_title');
		}

		// And now we need to make sure it's unique

		if ($update)
		{
			$url_title = $this->_unique_url_title($url_title, $this->entry_id, $this->channel_id);
		}
		else
		{
			$url_title = $this->_unique_url_title($url_title, '', $this->channel_id);
		}
		
		// One more safety
		
		if ( ! $url_title)
		{
			$this->_set_error('unable_to_create_url_title', 'url_title');
		}

		// And lastly, we prevent this potentially problematic case

		if ($url_title == 'index')
		{
			$this->_set_error('url_title_is_index', 'url_title');
		}
		
		return $url_title;
	}
	
	// --------------------------------------------------------------------
	
	/**
	 * Prep date field
	 *
	 * Prepare custom date fields
	 *
	 * @access	private
	 * @param	mixed
	 * @param	mixed
	 * @return	void
	 */
	function _prep_date_field(&$data, $row)
	{
		if ( ! isset($data['field_id_'.$row['field_id']]))
		{
			return;
		}

		// Should prevent non-integers from going into the field
		
		if ( ! $data['field_id_'.$row['field_id']])
		{
			$data['field_id_'.$row['field_id']] = 0;
			return;
		}

		//  Date might already be numeric format- so we check
		if ( ! is_numeric($data['field_id_'.$row['field_id']]))
		{
			$data['field_id_'.$row['field_id']] = $this->EE->localize->convert_human_date_to_gmt($data['field_id_'.$row['field_id']]);
		}

		if ( ! is_numeric($data['field_id_'.$row['field_id']]))
		{
			if ($data['field_id_'.$row['field_id']] !== FALSE)
			{
				$this->_set_error('invalid_date', $row['field_label']);
			}
			else
			{
				$this->_set_error('invalid_date_formatting', $row['field_label']);
			}
		}
		else
		{
			$this->_cache['dst_enabled'] = 'n';
			
			if ( ! isset($data['field_offset_'.$row['field_id']]))
			{
				$data['field_dt_'.$row['field_id']] = '';
			}
			elseif ($data['field_offset_'.$row['field_id']] == 'y')
			{
				$data['field_dt_'.$row['field_id']] = '';
			}
			else
			{
				$data['field_dt_'.$row['field_id']] = $this->EE->session->userdata('timezone');
			}
		}
	}
	
	// --------------------------------------------------------------------
	
	/**
	 * Prep multi field
	 *
	 * Prepare multi_select and option_group fields
	 *
	 * @access	private
	 * @param	mixed
	 * @param	mixed
	 * @return	void
	 */
	function _prep_multi_field(&$data, $row)
	{
		if (isset($data['field_id_'.$row['field_id']]))
		{
			if (is_array($data['field_id_'.$row['field_id']]))
			{
				$data['field_id_'.$row['field_id']] = encode_multi_field($data['field_id_'.$row['field_id']]);
				return;
			}
		}
		
		//unset($data['field_id_'.$row['field_id']]);
	}
	
	// --------------------------------------------------------------------
	
	/**
	 * Prep data
	 *
	 * Prep all data we need to create an entry
	 *
	 * @access	private
	 * @param	mixed
	 * @param	mixed
	 * @return	void
	 */
	function _prepare_data(&$data, &$mod_data)
	{
		$this->instantiate('channel_categories');

		$this->EE->api_channel_categories->initialize(array(
			'categories'  => array(),
			'cat_parents' => array(),
			'cat_array'   => array()
		));
		
		// Category parents - we toss the rest
		
		if (isset($data['category']) AND is_array($data['category']))
		{
			foreach ($data['category'] as $cat_id)
			{
				$this->EE->api_channel_categories->cat_parents[] = $cat_id;
			}

			if ($this->EE->api_channel_categories->assign_cat_parent == TRUE)
			{
				$this->EE->api_channel_categories->fetch_category_parents($data['category']);
			}
		}

		unset($data['category']);

		// Prep y / n values		
		
		$data['allow_comments'] = (isset($data['allow_comments']) && $data['allow_comments'] == 'y') ? 'y' : 'n';

		if (isset($data['cp_call']) && $data['cp_call'] == TRUE)
		{
			$data['allow_comments'] = ($data['allow_comments'] !== 'y' OR $this->c_prefs['comment_system_enabled'] == 'n') ? 'n' : 'y';
		}
		
		if ($this->c_prefs['enable_versioning'] == 'n')
		{
			$data['versioning_enabled'] = 'y';
		}
		else
		{
			if (isset($data['versioning_enabled']))
			{
				$data['versioning_enabled'] = 'y';
			}
			else
			{
				$data['versioning_enabled'] = 'n';
				
				// In 1.6, this happened right before inserting new revisions,
				// but it makes more sense here.
				$this->c_prefs['enable_versioning'] = 'n';
			}
		}
		
		
		$this->_cache['dst_enabled'] = 'n';
		
		$this->instantiate('channel_fields');

		$this->EE->db->select('field_id, field_name, field_label, field_type, field_required');
		$this->EE->db->join('channels', 'channels.field_group = channel_fields.group_id', 'left');
		$this->EE->db->where('channel_id', $this->channel_id);
		$query = $this->EE->db->get('channel_fields');
		
		if ($query->num_rows() > 0)
		{
			foreach ($query->result_array() as $row)
			{
				$field_name = 'field_id_'.$row['field_id'];
				
				// @todo remove in 2.1.2
				// backwards compatible for some incorrect code noticed in a few third party modules.
				// Will be removed in 2.1.2, and a note to that effect is in the 2.1.1 update notes
				// $this->field_id should be used instead as documented
				// http://expressionengine.com/user_guide/development/fieldtypes.html#class_variables
				$this->EE->api_channel_fields->settings[$row['field_id']]['field_id'] = $row['field_id'];
				
				if (isset($data[$field_name]) OR isset($mod_data[$field_name]))
				{
					$this->EE->api_channel_fields->setup_handler($row['field_id']);

					// Break out module fields here
					if (isset($data[$field_name]))
					{
						$data[$field_name] = $this->EE->api_channel_fields->apply('save', array($data[$field_name]));
						
						if (isset($data['revision_post'][$field_name]))
						{
							$data['revision_post'][$field_name] = $data[$field_name];
						}
						
					}
					elseif (isset($mod_data[$field_name]))
					{
						$mod_data[$field_name] = $this->EE->api_channel_fields->apply('save', array($mod_data[$field_name]));

						if (isset($data['revision_post'][$field_name]))
						{
							$data['revision_post'][$field_name] = $mod_data[$field_name];
						}
					}
				}				
			}
		}
	}
	
	// --------------------------------------------------------------------
	
	/**
	 * Build Relationships
	 *
	 * Build the relationships for our entry
	 *
	 * @access	private
	 * @param	mixed
	 * @return	void
	 */
	function _build_relationships(&$data)
	{
		if ($this->autosave)
		{
			return;
		}

		$this->EE->db->select('field_id, field_related_to, field_related_id');
		$query = $this->EE->db->get_where('channel_fields', array('field_type' => 'rel'));
		
		// No results, bail out early
		if ( ! $query->num_rows())
		{
			$this->_cache['rel_updates'] = array();
			return;
		}

		$rel_updates = array();
		
		foreach ($query->result_array() as $row)
		{
			$field_id = $row['field_id'];

			// No field - skip
			if ( ! isset($data['field_id_'.$field_id]))
			{
				continue;
			}

			$data['field_ft_'.$field_id] = 'none';
			$rel_exists = FALSE;

			// If editing an existing entry....
			// Does an existing relationship exist? If so, we may not need to recompile the data
					
			if ($this->entry_id)
			{
				// First we fetch the previously stored related child id.

				$this->EE->db->select('field_id_'.$field_id.', rel_child_id, rel_id');
				$this->EE->db->from('channel_data');
				$this->EE->db->join('relationships', 'field_id_'.$field_id.' = rel_id', 'left');
				$this->EE->db->where('entry_id', $this->entry_id);
				$rel_data = $this->EE->db->get();
						
				$current_related = FALSE;
				$rel_id = FALSE;
						
				if ($rel_data->num_rows() > 0)
				{
					foreach ($rel_data->result() as $r)
					{
						$current_related = $r->rel_child_id;
						$rel_id = $r->rel_id;
					}
				}
												
				// If the previous ID matches the current ID being submitted it means that
				// the existing relationship has not changed so there's no need to recompile.
				// If it has changed we'll clear the old relationship.

				if ($current_related  == $data['field_id_'.$field_id])
				{
					$rel_exists = TRUE;
					$data['field_id_'.$field_id] = $rel_id;
				}
				elseif ($rel_id)
				{
					$this->EE->db->where('rel_id', $rel_id);
					$this->EE->db->delete('relationships');
				}
			}

			if (is_numeric($data['field_id_'.$field_id]) && $data['field_id_'.$field_id] != '0' && $rel_exists == FALSE)
			{
				$reldata = array(
					'type'			=> $row['field_related_to'],
					'parent_id'		=> $this->entry_id, // we may have an empty entry_id at this point, if so, zero for now, gets updated below
					'child_id'		=> $data['field_id_'.$field_id]
				);

				$data['field_id_'.$field_id] = $this->EE->functions->compile_relationship($reldata, TRUE);
				$rel_updates[] = $data['field_id_'.$field_id];
			}
			elseif($data['field_id_'.$field_id] == '')
			{
				$data['field_id_'.$field_id] = 0;
			}
		}
		
		$this->_cache['rel_updates'] = $rel_updates;
	}
	
	// --------------------------------------------------------------------
	
	/**
	 * Insert Entry
	 *
	 * Creates primary data for a new entry
	 *
	 * @access	private
	 * @param	mixed
	 * @param	mixed
	 * @return	void
	 */
	function _insert_entry($meta, &$data, &$mod_data)
	{
		$meta['dst_enabled'] =  $this->_cache['dst_enabled'];
		
		if ($this->autosave)
		{
			if ($this->autosave_entry_id)
			{
				$this->EE->db->where('entry_id', $this->autosave_entry_id);
				$this->EE->db->update('channel_entries_autosave', $meta);
				$this->entry_id = $this->autosave_entry_id;
			}
			else
			{
				// In the event there's no original_entry_id assign it to 0
				if ( ! isset($meta['original_entry_id']))
				{
					$meta['original_entry_id'] = 0;
				}
				
				$this->EE->db->insert('channel_entries_autosave', $meta);
				$this->entry_id = $this->EE->db->insert_id();
			}
		}
		else
		{
			$this->EE->db->insert('channel_titles', $meta);
			$this->entry_id = $this->EE->db->insert_id();
		}		
		
		// Update Relationships (autosave skips this)
		
		if ( ! $this->autosave && count($this->_cache['rel_updates']) > 0)
		{
			$this->EE->db->set('rel_parent_id', $this->entry_id);
			$this->EE->db->where_in('rel_id', $this->_cache['rel_updates']);
			$this->EE->db->update('relationships');
		}
		
		
		// Insert custom field data
		
		$cust_fields = array('entry_id' => $this->entry_id, 'channel_id' => $this->channel_id, 'site_id' => $this->EE->config->item('site_id'));
		

		foreach($data as $key => $val)
		{
			if (strncmp($key, 'field_offset_', 13) == 0)
			{
				unset($data[$key]);
				continue;
			}

			if (strncmp($key, 'field', 5) == 0) 
			{
				if (strncmp($key, 'field_id_', 9) == 0 && ! is_numeric($val))
				{
					if ($this->EE->config->item('auto_convert_high_ascii') == 'y')
					{
						$cust_fields[$key] = (is_array($val)) ? $this->_recursive_ascii_to_entities($val) : $val;
					}
					else
					{
						$cust_fields[$key] = $val;
					}
				}
				else
				{
					$cust_fields[$key] = $val;
				}
				
				// set missing defaults here.  					$data['field_ft_'.$row['field_id']] = 'none';
			}
		}
		

		// Check that data complies with mysql strict mode rules
		$all_fields = $this->EE->db->field_data('channel_data');

		foreach ($all_fields as $field)
		{
			if (strncmp($field->name, 'field_id_', 9) == 0)
			{
				if ($field->type == 'text' OR $field->type == 'blob')
				{
					if ( ! isset($cust_fields[$field->name]) OR is_null($cust_fields[$field->name]))
					{
						$cust_fields[$field->name] = '';
					}
				}
				elseif ($field->type == 'int' && isset($cust_fields[$field->name]) && $cust_fields[$field->name] === '')
				{
					unset($cust_fields[$field->name]);
				}
			}
		}
		
		if ($this->autosave)
		{
			// Entry for this was made earlier, now its an update not an insert
			$cust_fields['entry_id'] = $this->entry_id;
			$cust_fields['original_entry_id'] = 0;
			$this->EE->db->where('entry_id', $this->entry_id);
			$this->EE->db->set('entry_data', serialize(array_merge($cust_fields, $mod_data))); 
			$this->EE->db->update('channel_entries_autosave'); // reinsert
			
			return $this->entry_id;
		}

		$this->EE->db->insert('channel_data', $cust_fields);


		// If remove old autosave data
		if ($this->autosave_entry_id)
		{
			$this->EE->db->delete('channel_entries_autosave', array('entry_id' => $this->autosave_entry_id));
		}


		// Update member stats
		
		if ($meta['author_id'] == $this->EE->session->userdata('member_id'))
		{
			$total_entries = $this->EE->session->userdata('total_entries') + 1;
		}
		else
		{
			$this->EE->db->select('total_entries');
			$query = $this->EE->db->get_where('members', array('member_id' => $meta['author_id']));
			$total_entries = $query->row('total_entries')  + 1;
		}
		
		$this->EE->db->set(array('total_entries' => $total_entries, 'last_entry_date' => $this->EE->localize->now));
		$this->EE->db->where('member_id', $meta['author_id']);
		$this->EE->db->update('members');

		// Send admin notification
		if ($this->c_prefs['notify_address'] != '')
		{
			$this->EE->load->library('notifications');
			$this->EE->notifications->send_admin_notification($this->c_prefs['notify_address'], $this->channel_id, $this->entry_id);
		}
	}
	
	// --------------------------------------------------------------------
	
	/**
	 * Update Entry
	 *
	 * Updates primary data for an entry
	 *
	 * @access	private
	 * @param	mixed
	 * @param	mixed
	 * @return	void
	 */
	function _update_entry($meta, &$data, &$mod_data)
	{
		$meta['dst_enabled'] =  $this->_cache['dst_enabled'];
		
		// Check if the author changed
		$this->EE->db->select('author_id');
		$query = $this->EE->db->get_where('channel_titles', array('entry_id' => $this->entry_id));
		$old_author = $query->row('author_id') ;

		// autosave doesn't impact these stats
		
		if ( ! $this->autosave && $old_author != $meta['author_id'])
		{
			// Decremenet the counter on the old author

			$this->EE->db->where('member_id', $old_author);
			$this->EE->db->set('total_entries', 'total_entries-1', FALSE);
			$this->EE->db->update('members');


			$this->EE->db->where('member_id', $meta['author_id']);
			$this->EE->db->set('total_entries', 'total_entries+1', FALSE);
			$this->EE->db->update('members');
		}

		// Update the entry data
		
		unset($meta['entry_id']);
		
		if ($this->autosave)
		{
			$this->EE->db->delete('channel_entries_autosave', array('original_entry_id' => $this->entry_id)); // remove all entries for this
			$meta['original_entry_id'] = $this->entry_id;
			$this->EE->db->insert('channel_entries_autosave', $meta); // reinsert
			
			$autosave_entry_id = $this->EE->db->insert_id();
		}
		else
		{
			$this->EE->db->where('entry_id', $this->entry_id);
			$this->EE->db->update('channel_titles', $meta);
		}
		
		// Update Custom fields
		$cust_fields = array('channel_id' =>  $this->channel_id);

		foreach ($data as $key => $val)
		{
			if (strncmp($key, 'field_offset_', 13) == 0)
			{
				unset($data[$key]);
				continue;
			}

			if (strncmp($key, 'field', 5) == 0) 
			{
				if (strncmp($key, 'field_id_', 9) == 0 && ! is_numeric($val))
				{
					if ($this->EE->config->item('auto_convert_high_ascii') == 'y')
					{
						$cust_fields[$key] = (is_array($val)) ? $this->_recursive_ascii_to_entities($val) : $val;
					}
					else
					{
						$cust_fields[$key] = $val;
					}
				}
				else
				{
					$cust_fields[$key] = $val;
				}
			}
		}

		if (count($cust_fields) > 0)
		{
			if ($this->autosave)
			{
				// Need to add to our custom fields array
				
				$this->instantiate('channel_categories');
		
				if ($this->EE->api_channel_categories->cat_parents > 0)
				{
					$this->EE->api_channel_categories->cat_parents = array_unique($this->EE->api_channel_categories->cat_parents);
					
					sort($this->EE->api_channel_categories->cat_parents);

					foreach($this->EE->api_channel_categories->cat_parents as $val)
					{
						if ($val != '')
						{
							$mod_data['category'][] = $val;
						}
					}
				}

				// Entry for this was made earlier, now its an update not an insert
				$cust_fields['entry_id'] = $this->entry_id;
				$cust_fields['original_entry_id'] = $this->entry_id;
				$this->EE->db->where('original_entry_id', $this->entry_id);
				$this->EE->db->set('entry_data', serialize(array_merge($cust_fields, $mod_data))); 
				$this->EE->db->update('channel_entries_autosave'); // reinsert
			}
			else
			{
				// Check that data complies with mysql strict mode rules
				$all_fields = $this->EE->db->field_data('channel_data');

				foreach ($all_fields as $field)
				{
					if (strncmp($field->name, 'field_id_', 9) == 0)
					{
						if ($field->type == 'text' OR $field->type == 'blob')
						{
							if ( ! isset($cust_fields[$field->name]) OR is_null($cust_fields[$field->name]))
							{
								$cust_fields[$field->name] = '';
							}
						}
						elseif ($field->type == 'int' && isset($cust_fields[$field->name]) && $cust_fields[$field->name] === '')
						{
							//$cust_fields[$field->name] = 0;
							unset($cust_fields[$field->name]);
						}
					}
				} 		

				$this->EE->db->where('entry_id', $this->entry_id);
				$this->EE->db->update('channel_data', $cust_fields);
			}
		}

		if ($this->autosave)
		{
			return $autosave_entry_id;
		}

		// Remove any autosaved data
		$this->EE->db->delete('channel_entries_autosave', array('original_entry_id' => $this->entry_id));

		// Delete Categories - resubmitted in the next step
		$this->EE->db->delete('category_posts', array('entry_id' => $this->entry_id));
	}

	// --------------------------------------------------------------------

	/**
	 * Recursive ASCII to entities.
	 *
	 * This is a helper method used for Arrays POSTed, a la Matrix
	 *
	 * @param 	array
	 * @return 	array
	 */
	function _recursive_ascii_to_entities($arr)
	{
	    $result = array();

	    foreach($arr as $key => $value)
	    {
	        if (is_array($value))
	        {
	            $result[$key] = $this->_recursive_ascii_to_entities($value);
	        }
	        else
	        {
	            $result[$key] = ascii_to_entities($value);
	        }
	    }
	
		return $result;
	}

	// --------------------------------------------------------------------
	
	/**
	 * Sync Related
	 *
	 * Inserts/updates related data for an entry
	 *
	 * @access	private
	 * @param	mixed
	 * @param	mixed
	 * @return	void
	 */
	function _sync_related($meta, &$data)
	{
		// Insert Categories
		
		$this->instantiate('channel_categories');
		
		if ($this->EE->api_channel_categories->cat_parents > 0)
		{
			$this->EE->api_channel_categories->cat_parents = array_unique($this->EE->api_channel_categories->cat_parents);

			sort($this->EE->api_channel_categories->cat_parents);

			foreach($this->EE->api_channel_categories->cat_parents as $val)
			{
				if ($val != '')
				{
					$this->EE->db->insert('category_posts', array('entry_id' => $this->entry_id, 'cat_id' => $val));
				}
			}
		}

		// Recompile Relationships
		$this->update_related_cache($this->entry_id);
		
		// Save revisions if needed
		
		if ($this->c_prefs['enable_versioning'] == 'y')
		{
			$this->EE->db->insert('entry_versioning', array(
				'entry_id'		=> $this->entry_id,
				'channel_id'	=> $this->channel_id,
				'author_id'		=> $this->EE->session->userdata('member_id'),
				'version_date'	=> $this->EE->localize->now,
				'version_data'	=> serialize($data['revision_post'])
			));
			
			$max = (is_numeric($this->c_prefs['max_revisions']) AND $this->c_prefs['max_revisions'] > 0) ? $this->c_prefs['max_revisions'] : 10;
			
			$this->EE->channel_entries_model->prune_revisions($this->entry_id, $max);
		}
		
		// Post update custom fields
		$this->EE->db->select('field_id, field_name, field_label, field_type, field_required');
		$this->EE->db->join('channels', 'channels.field_group = channel_fields.group_id', 'left');
		$this->EE->db->where('channel_id', $this->channel_id);
		$query = $this->EE->db->get('channel_fields');

		if ($query->num_rows() > 0)
		{
			foreach ($query->result_array() as $row)
			{
				$field_name = 'field_id_'.$row['field_id'];
				
				$this->EE->api_channel_fields->settings[$row['field_id']]['entry_id'] = $this->entry_id;
				
				// @todo remove in 2.1.2
				// backwards compatible for some incorrect code noticed in a few third party modules.
				// Will be removed in 2.1.2, and a note to that effect is in the 2.1.1 update notes
				// $this->field_id should be used instead as documented
				// http://expressionengine.com/user_guide/development/fieldtypes.html#class_variables
				$this->EE->api_channel_fields->settings[$row['field_id']]['field_id'] = $row['field_id'];
				
				$fdata = isset($data[$field_name]) ? $data[$field_name] : '';
				$this->EE->api_channel_fields->setup_handler($row['field_id']);
				$this->EE->api_channel_fields->apply('post_save', array($fdata));				
			}
		}
	}
	
	// --------------------------------------------------------------------
	
	/**
	 * Pass third party fields off for processing
	 *
	 * @access	private
	 * @param	string
	 * @return	string
	 */
	function _set_mod_data($meta, $data, $mod_data)
	{
		$methods = array('publish_data_db');
		$params = array('publish_data_db' => array('meta' => $meta, 'data' => $data, 'mod_data' => $mod_data, 'entry_id' => $this->entry_id));
		
		$module_data = $this->EE->api_channel_fields->get_module_methods($methods, $params);

	}
	
	// --------------------------------------------------------------------
	
	/**
	 * Process Pings
	 *
	 * Send pings
	 *
	 * @access	private
	 * @param	mixed
	 * @param	mixed
	 * @return	mixed
	 */
	function _process_pings($ping_servers, $channel_title, $ping_url, $rss_url)
	{
		$this->EE->db->select('server_name, server_url, port');
		$this->EE->db->where_in('id', $ping_servers);
		$query = $this->EE->db->get('ping_servers');

		if ($query->num_rows() == 0)
		{
			return FALSE;
		}

		$this->EE->load->library('xmlrpc');

		$result = array();

		foreach ($query->result_array() as $row)
		{
			if (($response = $this->EE->xmlrpc->weblogs_com_ping($row['server_url'], $row['port'], $channel_title, $ping_url, $rss_url)) !== TRUE)
			{
				$result[] = array($row['server_name'], $response);
			}
		}

		return $result;
	}
}
// END CLASS

/* End of file Api_channel_entries.php */
/* Location: ./system/expressionengine/libraries/api/Api_channel_entries.php */
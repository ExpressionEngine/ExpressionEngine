<?php  if (!defined('BASEPATH')) exit('No direct script access allowed');
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
 * ExpressionEngine Channel Entries API Class
 *
 * @package		ExpressionEngine
 * @subpackage	Core
 * @category	Core
 * @author		EllisLab Dev Team
 * @link		https://ellislab.com
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
		ee()->load->model('channel_entries_model');
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
		$this->_cache = (isset($this->_cache['orig_author_id'])) ?
			array('orig_author_id' => $this->_cache['orig_author_id']) : array();

		parent::initialize($params);
	}

	// --------------------------------------------------------------------

	/**
	 * Saves a new or existing channel entry
	 *
	 * @param string $data Entry data
	 * @param string $channel_id Channel ID when adding new entries
	 * @param string $entry_id Entry ID when editing an existing entry
	 * @param string $autosave
	 * @return bool
	 */
	public function save_entry($data, $channel_id = NULL, $entry_id = 0, $autosave = FALSE)
	{
		$entry_id = (empty($entry_id)) ? 0 : $entry_id;

		$this->entry_id = $entry_id;
		$this->autosave_entry_id = isset($data['autosave_entry_id']) ? $data['autosave_entry_id'] : 0;
		$this->data =& $data;

		$initialize = array(
			'entry_id' => $entry_id,
			'autosave' => $autosave
		);

		if ( ! empty($channel_id))
		{
			$initialize['channel_id'] = $channel_id;
			$data['channel_id'] = $channel_id;
		}

		$this->initialize($initialize);

		if ( ! $this->_base_prep($data))
		{
			return FALSE;
		}

		if ($this->trigger_hook('entry_submission_start') === TRUE)
		{
			return TRUE;
		}

		$save_function = '_update_entry';
		if (empty($entry_id))
		{
			// Data cached by base_prep is only needed for updates - toss it
			$this->_cache = array();
			$save_function = '_insert_entry';
		}

		$this->_fetch_channel_preferences();
		$this->_do_channel_switch($data);

		// We break out the third party data here
		$mod_data = array();
		$this->_fetch_module_data($data, $mod_data);

		$this->_check_for_data_errors($data);

		// Lets make sure those went smoothly

		if ( ! $this->autosave && count($this->errors) > 0)
		{
			return FALSE;
		}

		$this->_prepare_data($data, $mod_data, $autosave);

		$meta = array(
			'channel_id'				=> $this->channel_id,
			'author_id'					=> $data['author_id'],
			'site_id'					=> ee()->config->item('site_id'),
			'ip_address'				=> ee()->input->ip_address(),
			'title'						=> (ee()->config->item('auto_convert_high_ascii') == 'y') ? ascii_to_entities($data['title']) : $data['title'],
			'url_title'					=> $data['url_title'],
			'entry_date'				=> $data['entry_date'],
			'edit_date'					=> (isset($data['edit_date'])) ? $data['edit_date'] : ee()->localize->now(),
			'versioning_enabled'		=> $data['versioning_enabled'],
			'year'						=> ee()->localize->format_date('%Y', $data['entry_date']),
			'month'						=> ee()->localize->format_date('%m', $data['entry_date']),
			'day'						=> ee()->localize->format_date('%d', $data['entry_date']),
			'expiration_date'			=> $data['expiration_date'],
			'comment_expiration_date'	=> $data['comment_expiration_date'],
			'sticky'					=> (isset($data['sticky']) && $data['sticky'] == 'y') ? 'y' : 'n',
			'status'					=> $data['status'],
			'allow_comments'			=> $data['allow_comments'],
		);

		if (isset($data['recent_comment_date']))
		{
			$meta['recent_comment_date'] = $data['recent_comment_date'];
		}
		elseif ($entry_id == 0)
		{
			$meta['recent_comment_date'] = 0;
		}

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
			return $this->$save_function($meta, $data, $mod_data);
		}

		$this->$save_function($meta, $data, $mod_data);

		if (count($mod_data) > 0)
		{
			$this->_set_mod_data($meta, $data, $mod_data);
		}

		$this->_sync_related($meta, $data);

		if (isset($data['save_revision']) && $data['save_revision'])
		{
			return TRUE;
		}

		ee()->stats->update_channel_stats($this->channel_id);

		if (isset($data['old_channel']))
		{
			ee()->stats->update_channel_stats($data['old_channel']);
		}

		if (ee()->config->item('new_posts_clear_caches') == 'y')
		{
			ee()->functions->clear_caching('all');
		}
		else
		{
			ee()->functions->clear_caching('sql');
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
		return $this->save_entry($data, $channel_id, NULL, $autosave);
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
		return $this->save_entry($data, NULL, $entry_id, $autosave);
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
				$data['title'] = 'autosave_'.ee()->localize->now;
			}

			return $this->submit_new_entry($data['channel_id'], $data, TRUE);
		}

		return $this->save_entry($data, NULL, $data['entry_id'], TRUE);
	}

	// --------------------------------------------------------------------

	/**
	 * Delete entry
	 *
	 * Handles deleting of existing entries from arbitrary, authenticated source
	 *
	 * @access	public
	 * @param	mixed	An entry ID or array of entry IDs to delete
	 * @return	bool
	 */
	function delete_entry($entry_ids)
	{
		ee()->load->library('api');
		ee()->load->library('addons');
		ee()->legacy_api->instantiate('channel_fields');

		if ( ! is_array($entry_ids))
		{
			$entry_ids = array($entry_ids);
		}

		if (array_key_exists('comment', ee()->addons->get_installed('modules')))
		{
			$comments_installed = TRUE;
		}
		else
		{
			$comments_installed = FALSE;
		}

		// grab entry meta data
		ee()->db->select('channel_id, author_id, entry_id');
		ee()->db->from('channel_titles');
		ee()->db->where_in('entry_id', $entry_ids);
		$query = ee()->db->get();


		// Check permissions
		$allowed_channels = ee()->functions->fetch_assigned_channels();
		$authors = array();
		$channel_ids = array();

		foreach ($query->result_array() as $row)
		{
			if (ee()->session->userdata('group_id') != 1)
			{
				if ( ! in_array($row['channel_id'], $allowed_channels))
				{
					return $this->_set_error('unauthorized_for_this_channel');
				}
			}

			if ($row['author_id'] == ee()->session->userdata('member_id'))
			{
				if (ee()->session->userdata('can_delete_self_entries') != 'y')
				{
					return $this->_set_error('unauthorized_to_delete_self');
				}
			}
			else
			{
				if (ee()->session->userdata('can_delete_all_entries') != 'y')
				{
					return $this->_set_error('unauthorized_to_delete_others');
				}
			}

			$authors[$row['entry_id']] = $row['author_id'];
		}


		// grab channel field groups
		ee()->db->select('channel_id, field_group');
		$cquery = ee()->db->get('channels');

		$channel_groups = array();

		foreach($cquery->result_array() as $row)
		{
			$channel_groups[$row['channel_id']] = $row['field_group'];
		}


		// grab fields and order by group
		ee()->db->select('field_id, field_type, group_id');
		$fquery = ee()->db->get('channel_fields');

		$group_fields = array();

		foreach($fquery->result_array() as $row)
		{
			$group_fields[$row['group_id']][] = $row['field_id'];
		}


		// Delete primary data
		ee()->db->where_in('entry_id', $entry_ids);
		ee()->db->delete(array('channel_titles', 'channel_data', 'category_posts'));

		// Get a listing of relationship fields and their settings so we can
		// correctly run the relationship cleanup for entries that are related
		// to other channels
		$relationship_fields = ee()->db->select('field_id, field_settings')
			->get_where(
				'channel_fields',
				array('field_type' => 'relationship')
			)
			->result_array();

		$entries = array();
		$ft_to_ids = array();

		foreach($query->result_array() as $row)
		{
			$val = $row['entry_id'];
			$channel_id = $row['channel_id'];
			$channel_ids[$row['channel_id']] = $row['channel_id'];

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

			// Add all relationship fields
			foreach ($relationship_fields as $field)
			{
				$ft_to_ids[$field['field_id']][] = $val;
			}

			// Correct member post count
			ee()->db->select('total_entries');
			$mquery = ee()->db->get_where('members', array('member_id' => $authors[$val]));

			$tot = 0;

			if ($mquery->num_rows() > 0)
			{
				$tot = $mquery->row('total_entries');
			}

			if ($tot > 0)
			{
				$tot -= 1;
			}

			ee()->db->where('member_id', $authors[$val]);
			ee()->db->update('members', array('total_entries' => $tot));


			// -------------------------------------------
			// 'delete_entries_loop' hook.
			//  - Add additional processing for entry deletion in loop
			//  - Added: 1.4.1
			//
				ee()->extensions->call('delete_entries_loop', $val, $channel_id);
				if (ee()->extensions->end_script === TRUE) return;
			//
			// -------------------------------------------

			$entries[] = $val;
		}

		if ($comments_installed)
		{
			// Remove comments for deleted entries
			ee()->db->where_in('entry_id', $entries)
					 ->delete('comments');

			// Remove comment subscriptions for deleted entries
			ee()->db->where_in('entry_id', $entries)
					 ->delete('comment_subscriptions');
		}

		// Delete entries in the channel_entries_autosave table
		ee()->db->where_in('original_entry_id', $entries)
					 ->delete('channel_entries_autosave');

		// Delete entries from the versions table
		ee()->db->where_in('entry_id', $entries)
					 ->delete('entry_versioning');

		// Let's run through some stats updates
		foreach ($channel_ids as $channel_id)
		{
			ee()->stats->update_channel_stats($channel_id);

			if ($comments_installed)
			{
				ee()->stats->update_comment_stats($channel_id);
			}
		}

		if ($comments_installed)
		{
			ee()->stats->update_authors_comment_stats(array_unique($authors));
		}


		$fts = ee()->api_channel_fields->fetch_custom_channel_fields();

		// Pass to custom fields
		foreach($ft_to_ids as $fieldtype => $ids)
		{
			ee()->api_channel_fields->setup_handler($fieldtype);
			ee()->api_channel_fields->apply('delete', array($ids));
		}

		// Pass to module defined fields
		$methods = array('publish_data_delete_db');
		$params = array('publish_data_delete_db' => array('entry_ids' => $entry_ids));

		ee()->api_channel_fields->get_module_methods($methods, $params);

		// Clear caches
		ee()->functions->clear_caching('all', '');

		// -------------------------------------------
		// 'delete_entries_end' hook.
		//  - Add additional processing for entry deletion
		//
			ee()->extensions->call('delete_entries_end');
			if (ee()->extensions->end_script === TRUE) return TRUE;
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

		$query = ee()->channel_entries_model->get_entry($entry_id);

		if ($query->num_rows() == 0)
		{
			return FALSE;
		}

		$this->_cache['orig_author_id'] = $query->row('author_id');

		return TRUE;
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
			return $orig_var;
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
				$this->errors[$field] = ee()->lang->line($err);
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
				$this->errors[] = ee()->lang->line($err);
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
		ee()->lang->loadfile('admin_content');

		// Sanity Check
		if ( ! is_array($data) OR ! isset($data['channel_id']) OR ! is_numeric($data['channel_id']))
		{
			show_error(ee()->lang->line('invalid_api_parameter'));
		}

		$this->channel_id = $data['channel_id'];

		// Is this user allowed to post here?
		$this->_cache['assigned_channels'] = ee()->functions->fetch_assigned_channels();

		if (ee()->session->userdata('group_id') != 1)
		{
			if ( ! in_array($this->channel_id, $this->_cache['assigned_channels']))
			{
				show_error(ee()->lang->line('unauthorized_for_this_channel'));
			}
		}

		// Make sure all the fields have a key in our data array even
		// if no data was sent

		if ($this->autosave === FALSE)
		{
			if ( ! isset(ee()->api_channel_fields) OR ! isset(ee()->api_channel_fields->settings))
			{
				$this->instantiate('channel_fields');
				ee()->api_channel_fields->fetch_custom_channel_fields();
			}

			$field_ids = array_keys(ee()->api_channel_fields->settings);

			foreach($field_ids as $id)
			{
				if (is_numeric($id))
				{
					$nid = $id;
					$id = 'field_id_'.$id;

					if ($this->entry_id == 0 && ! isset($data['field_ft_'.$nid]))
					{
						$data['field_ft_'.$nid] = ee()->api_channel_fields->settings[$nid]['field_fmt'];
					}
				}

				if ( ! isset($data[$id]))
				{
					$data[$id] = '';
				}
			}
		}
		// Helpers
		ee()->load->helper('text');
		ee()->load->helper('custom_field');
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

		$query = ee()->api_channel_structure->get_channel_info($channel_id);

		foreach(array('channel_url', 'rss_url', 'deft_status', 'comment_url', 'comment_system_enabled', 'enable_versioning', 'max_revisions') as $key)
		{
			$this->c_prefs[$key] = $query->row($key);
		}

		$this->c_prefs['channel_title']		= ascii_to_entities($query->row('channel_title'));
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
			ee()->db->select('status_group, cat_group, field_group, channel_id');
			ee()->db->where_in('channel_id', array($this->channel_id, $data['new_channel']));
			$query = ee()->db->get('channels');

			if ($query->num_rows() == 2)
			{
				$result_zero = $query->row(0);
				$result_one = $query->row(1);

				if ($result_zero->status_group == $result_one->status_group &&
					$result_zero->cat_group == $result_one->cat_group &&
					$result_zero->field_group == $result_one->field_group)
				{
					if (ee()->session->userdata('group_id') == 1 OR in_array($data['new_channel'], $this->_cache['assigned_channels']))
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
		//$errors = ee()->api_channel_fields->get_module_methods('validate_publish', array('data' => $data));

		// Note coming from cp- return
		if ( ! isset($data['cp_call']) OR $data['cp_call'] !== TRUE)
		{
			return;
		}

		$methods = array('validate_publish', 'publish_tabs');
		$params = array('validate_publish' => array($data), 'publish_tabs' => array($data['channel_id'], $this->entry_id));

		$this->instantiate('channel_fields');
		$module_data = ee()->api_channel_fields->get_module_methods($methods, $params);

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
			'title'			=> 'missing_title'
		);

		if ( ! isset($data['title']) OR ! $data['title'] = strip_tags(trim($data['title'])))
		{
			$data['title'] = '';
			$this->_set_error('missing_title', 'title');
		}

		// Set entry_date and edit_date to "now" if empty

		$data['entry_date'] = empty($data['entry_date']) ? ee()->localize->now : $data['entry_date'];
		$data['edit_date'] = empty($data['edit_date']) ? ee()->localize->now : $data['edit_date'];

		//	Convert built-in date fields to UNIX timestamps

		$dates = array('entry_date', 'edit_date');

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
			if ( ! is_numeric($data[$date]) && trim($data[$date]))
			{
				$data[$date] = ee()->localize->string_to_timestamp($data[$date], TRUE, ee()->localize->get_date_format());
			}

			if ($data[$date] === FALSE)
			{
				$this->_set_error('invalid_date', $date);
			}

			if (isset($data['revision_post'][$date]))
			{
				$data['revision_post'][$date] = $data[$date];
			}
		}

		// Required and custom fields
		$result_array = $this->_get_custom_fields();

		foreach ($result_array as $row)
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
				if ($this->autosave && ! empty($data['field_id_'.$row['field_id'].'_hidden']))
				{
					$directory = $data['field_id_'.$row['field_id'].'_directory'];
					$data['field_id_'.$row['field_id']] =  '{filedir_'.$directory.'}'.$data['field_id_'.$row['field_id'].'_hidden'];

				}

				unset($data['field_id_'.$row['field_id'].'_hidden_file']);
				unset($data['field_id_'.$row['field_id'].'_hidden_dir']);
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

		// Clean / create the url title

		$data['url_title'] = isset($data['url_title']) ? $data['url_title'] : '';
		$data['url_title'] = $this->_validate_url_title($data['url_title'], $data['title'], (bool) $this->entry_id);

		// Validate author id

		$data['author_id'] = ( ! isset($data['author_id']) OR ! $data['author_id']) ? ee()->session->userdata('member_id'): $data['author_id'];

		if ($data['author_id'] != ee()->session->userdata('member_id') && ee()->session->userdata('can_edit_other_entries') != 'y')
		{
			$this->_set_error('not_authorized');
		}

		if (isset($this->_cache['orig_author_id']) && $data['author_id'] != $this->_cache['orig_author_id'] && (ee()->session->userdata('can_edit_other_entries') != 'y' OR ee()->session->userdata('can_assign_post_authors') != 'y'))
		{
			$this->_set_error('not_authorized');
		}

		if ($data['author_id'] != ee()->session->userdata('member_id') && ee()->session->userdata('group_id') != 1 && ee()->session->userdata('can_edit_other_entries') != 'y')
		{
			if ( ! isset($this->_cache['orig_author_id']) OR $data['author_id'] != $this->_cache['orig_author_id'])
			{
				if (ee()->session->userdata('can_assign_post_authors') != 'y')
				{
					$this->_set_error('not_authorized', 'author');
				}
				else
				{
					$allowed_authors = array();

					ee()->load->model('member_model');
					$query = ee()->member_model->get_authors();

					if ($query->num_rows() > 0)
					{
						foreach($query->result_array() as $row)
						{
							$allowed_authors[] = $row['member_id'];
						}
					}

					if ( ! in_array($data['author_id'], $allowed_authors))
					{
						$this->_set_error('invalid_author', 'author');
					}
				}
			}
		}

		// Validate Status

		$data['status'] = ( ! isset($data['status']) OR $data['status'] === FALSE) ? $this->c_prefs['deft_status'] : $data['status'];

		if (ee()->session->userdata('group_id') != 1)
		{
			$disallowed_statuses = array();
			$valid_statuses = array();

			ee()->load->model('status_model');
			$query = ee()->status_model->get_statuses('', $this->channel_id);

			if ($query->num_rows() > 0)
			{
				foreach ($query->result_array() as $row)
				{
					$valid_statuses[$row['status_id']] = strtolower($row['status']); // lower case to match MySQL's case-insensitivity
				}
			}

			$query = ee()->status_model->get_disallowed_statuses(ee()->session->userdata('group_id'));

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
		$word_separator = ee()->config->item('word_separator');

		ee()->load->helper('url');

		if ( ! trim($url_title))
		{
			$url_title = url_title($title, $word_separator, TRUE);
		}

		// Remove extraneous characters

		if ($update)
		{
			ee()->db->select('url_title');
			$url_query = ee()->db->get_where('channel_titles', array('entry_id' => $this->entry_id));

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

		if ( ! trim($data['field_id_'.$row['field_id']]))
		{
			$data['field_id_'.$row['field_id']] = 0;
			return;
		}

		//  Date might already be numeric format- so we check
		if ( ! is_numeric($data['field_id_'.$row['field_id']]))
		{
			$data['field_id_'.$row['field_id']] = ee()->localize->string_to_timestamp($data['field_id_'.$row['field_id']], TRUE, ee()->localize->get_date_format());
		}

		if ($data['field_id_'.$row['field_id']] === FALSE)
		{
			$this->_set_error('invalid_date', $row['field_label']);
		}
		else
		{

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
				$data['field_dt_'.$row['field_id']] = ee()->session->userdata('timezone', ee()->config->item('default_site_timezone'));
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
	function _prepare_data(&$data, &$mod_data, $autosave = FALSE)
	{
		$this->instantiate('channel_categories');

		ee()->api_channel_categories->initialize(array(
			'categories'  => array(),
			'cat_parents' => array(),
			'cat_array'   => array()
		));

		// Category parents - we toss the rest

		if (isset($data['category']) AND is_array($data['category']))
		{
			foreach ($data['category'] as &$cat_id)
			{
				$cat_id = (int) $cat_id;
				ee()->api_channel_categories->cat_parents[] = $cat_id;
			}

			if (ee()->api_channel_categories->assign_cat_parent == TRUE)
			{
				ee()->api_channel_categories->fetch_category_parents($data['category']);
			}
		}

		// Remove invisible characters from entry title
		if (isset($data['title']))
		{
			$data['title'] = remove_invisible_characters($data['title']);
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



		$this->instantiate('channel_fields');

		$result_array = $this->_get_custom_fields();

		foreach ($result_array as $row)
		{
			$field_name = 'field_id_'.$row['field_id'];

			// @todo remove in 2.1.2
			// backwards compatible for some incorrect code noticed in a few third party modules.
			// Will be removed in 2.1.2, and a note to that effect is in the 2.1.1 update notes
			// $this->field_id should be used instead as documented
			// https://ellislab.com/expressionengine/user-guide/development/fieldtypes.html#class-variables
			ee()->api_channel_fields->settings[$row['field_id']]['field_id'] = $row['field_id'];

			if (isset($data[$field_name]) OR isset($mod_data[$field_name]))
			{
				ee()->api_channel_fields->setup_handler($row['field_id']);
				ee()->api_channel_fields->apply('_init', array(array(
					'content_id' => $this->entry_id
				)));

				// Break out module fields here
				if (isset($data[$field_name]))
				{
					if ( ! $autosave)
					{
						$data[$field_name] = ee()->api_channel_fields->apply('save', array($data[$field_name]));
					}
				}
				elseif (isset($mod_data[$field_name]))
				{
					if ( ! $autosave)
					{
						$mod_data[$field_name] = ee()->api_channel_fields->apply('save', array($mod_data[$field_name]));
					}
				}
			}
		}
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

		if ($this->autosave)
		{
			if ($this->autosave_entry_id)
			{
				ee()->db->where('entry_id', $this->autosave_entry_id);
				ee()->db->update('channel_entries_autosave', $meta);
				$this->entry_id = $this->autosave_entry_id;
			}
			else
			{
				// In the event there's no original_entry_id assign it to 0
				if ( ! isset($meta['original_entry_id']))
				{
					$meta['original_entry_id'] = 0;
				}

				ee()->db->insert('channel_entries_autosave', $meta);
				$this->entry_id = ee()->db->insert_id();
			}
		}
		else
		{
			ee()->db->insert('channel_titles', $meta);
			$this->entry_id = ee()->db->insert_id();
		}

		// Insert custom field data

		$cust_fields = array('entry_id' => $this->entry_id, 'channel_id' => $this->channel_id, 'site_id' => ee()->config->item('site_id'));


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
					if (ee()->config->item('auto_convert_high_ascii') == 'y')
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
		$all_fields = ee()->db->field_data('channel_data');

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
				elseif($field->type == 'real' && isset($cust_fields[$field->name]))
				{
					$cust_fields[$field->name] = (float)$cust_fields[$field->name];
				}
			}
		}

		if ($this->autosave)
		{
			// Entry for this was made earlier, now its an update not an insert
			$cust_fields['entry_id'] = $this->entry_id;
			$cust_fields['original_entry_id'] = 0;
			ee()->db->where('entry_id', $this->entry_id);
			ee()->db->set('entry_data', serialize(array_merge($cust_fields, $mod_data)));
			ee()->db->update('channel_entries_autosave'); // reinsert

			return $this->entry_id;
		}
		ee()->db->insert('channel_data', $cust_fields);


		// If remove old autosave data
		if ($this->autosave_entry_id)
		{
			ee()->db->delete('channel_entries_autosave', array('entry_id' => $this->autosave_entry_id));
		}


		// Update member stats

		if ($meta['author_id'] == ee()->session->userdata('member_id'))
		{
			$total_entries = ee()->session->userdata('total_entries') + 1;
		}
		else
		{
			ee()->db->select('total_entries');
			$query = ee()->db->get_where('members', array('member_id' => $meta['author_id']));
			$total_entries = $query->row('total_entries')  + 1;
		}

		ee()->db->set(array('total_entries' => $total_entries, 'last_entry_date' => ee()->localize->now));
		ee()->db->where('member_id', $meta['author_id']);
		ee()->db->update('members');

		// Send admin notification
		if ($this->c_prefs['notify_address'] != '')
		{
			ee()->load->library('notifications');
			ee()->notifications->send_admin_notification($this->c_prefs['notify_address'], $this->channel_id, $this->entry_id);
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

		// See if the author changed and store the old author ID for updating stats later
		ee()->db->select('author_id');
		$query = ee()->db->get_where('channel_titles', array('entry_id' => $this->entry_id));
		$old_author = $query->row('author_id');

		// Update the entry data

		unset($meta['entry_id']);

		if ($this->autosave)
		{
			ee()->db->delete('channel_entries_autosave', array('original_entry_id' => $this->entry_id)); // remove all entries for this
			$meta['original_entry_id'] = $this->entry_id;
			ee()->db->insert('channel_entries_autosave', $meta); // reinsert

			$autosave_entry_id = ee()->db->insert_id();
		}
		else
		{
			ee()->db->where('entry_id', $this->entry_id);
			ee()->db->update('channel_titles', $meta);
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
					if (ee()->config->item('auto_convert_high_ascii') == 'y')
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

				if (ee()->api_channel_categories->cat_parents > 0)
				{
					ee()->api_channel_categories->cat_parents = array_unique(ee()->api_channel_categories->cat_parents);

					sort(ee()->api_channel_categories->cat_parents);

					foreach(ee()->api_channel_categories->cat_parents as $val)
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
				ee()->db->where('original_entry_id', $this->entry_id);
				ee()->db->set('entry_data', serialize(array_merge($cust_fields, $mod_data)));
				ee()->db->update('channel_entries_autosave'); // reinsert
			}
			else
			{
				// Check that data complies with mysql strict mode rules
				$all_fields = ee()->db->field_data('channel_data');

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

				ee()->db->where('entry_id', $this->entry_id);
				ee()->db->update('channel_data', $cust_fields);
			}
		}

		if ($this->autosave)
		{
			return $autosave_entry_id;
		}

		// If the original auther changed, update member entry stats
		// for old author and new author
		if ( ! $this->autosave && $old_author != $meta['author_id'])
		{
			ee()->load->model('member_model');
			ee()->member_model->update_member_entry_stats(
				array(
					$old_author,
					$meta['author_id']
				)
			);
		}

		// Remove any autosaved data
		ee()->db->delete('channel_entries_autosave', array('original_entry_id' => $this->entry_id));

		// Delete Categories - resubmitted in the next step
		ee()->db->delete('category_posts', array('entry_id' => $this->entry_id));
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

		if (ee()->api_channel_categories->cat_parents > 0)
		{
			ee()->api_channel_categories->cat_parents = array_unique(ee()->api_channel_categories->cat_parents);

			sort(ee()->api_channel_categories->cat_parents);

			foreach(ee()->api_channel_categories->cat_parents as $val)
			{
				if ($val != '')
				{
					ee()->db->insert('category_posts', array('entry_id' => $this->entry_id, 'cat_id' => $val));
				}
			}
		}

		// Save revisions if needed

		if ($this->c_prefs['enable_versioning'] == 'y')
		{
			// If a revision was saved before a submit new entry had ever occured?
			// $data['revision_post'] will not have a correct entry_id at this point
			// so let's overwrite it now
			$data['revision_post']['entry_id'] = $this->entry_id;

			ee()->db->insert('entry_versioning', array(
				'entry_id'		=> $this->entry_id,
				'channel_id'	=> $this->channel_id,
				'author_id'		=> ee()->session->userdata('member_id'),
				'version_date'	=> ee()->localize->now,
				'version_data'	=> serialize($data['revision_post'])
			));

			$max = (is_numeric($this->c_prefs['max_revisions']) AND $this->c_prefs['max_revisions'] > 0) ? $this->c_prefs['max_revisions'] : 10;

			ee()->channel_entries_model->prune_revisions($this->entry_id, $max);
		}

		// Post update custom fields
		$result_array = $this->_get_custom_fields();

		foreach ($result_array as $row)
		{
			$field_name = 'field_id_'.$row['field_id'];

			ee()->api_channel_fields->settings[$row['field_id']]['entry_id'] = $this->entry_id;

			// @todo remove in 2.1.2
			// backwards compatible for some incorrect code noticed in a few third party modules.
			// Will be removed in 2.1.2, and a note to that effect is in the 2.1.1 update notes
			// $this->field_id should be used instead as documented
			// https://ellislab.com/expressionengine/user-guide/development/fieldtypes.html#class-variables
			ee()->api_channel_fields->settings[$row['field_id']]['field_id'] = $row['field_id'];

			$fdata = isset($data[$field_name]) ? $data[$field_name] : '';
			ee()->api_channel_fields->setup_handler($row['field_id']);

			ee()->api_channel_fields->apply('_init', array(array(
				'content_id' => $this->entry_id
			)));

			ee()->api_channel_fields->apply('post_save', array($fdata));
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

		$module_data = ee()->api_channel_fields->get_module_methods($methods, $params);

	}

	// --------------------------------------------------------------------

	/**
	 * Custom Field Query
	 *
	 *
	 * @access	private
	 * @return	mixed
	 */
	private function _get_custom_fields()
	{
		ee()->db->select('field_id, field_name, field_label, field_type, field_required');
		ee()->db->join('channels', 'channels.field_group = channel_fields.group_id', 'left');
		ee()->db->where('channel_id', $this->channel_id);
		$query = ee()->db->get('channel_fields');
		$result = $query->result_array();

		// ----------------------------------------------------------------
		// 'api_channel_entries_custom_field_query' hook.
		// - Take the custom fields query array result, do what you wish
		// - added 2.6
		//
		if (ee()->extensions->active_hook('api_channel_entries_custom_field_query') === TRUE)
		{
			$result = ee()->extensions->call('api_channel_entries_custom_field_query', $result);
			if (ee()->extensions->end_script === TRUE) return;
		}
		//
		// ----------------------------------------------------------------

		$query->free_result();
		return $result;
	}
}
// END CLASS

// EOF

<?php  if (!defined('BASEPATH')) exit('No direct script access allowed');
/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		ExpressionEngine Dev Team
 * @copyright	Copyright (c) 2003 - 2010, EllisLab, Inc.
 * @license		http://expressionengine.com/user_guide/license.html
 * @link		http://expressionengine.com
 * @since		Version 2.0
 * @filesource
 */

// ------------------------------------------------------------------------

/**
 * ExpressionEngine Tools Model
 *
 * @package		ExpressionEngine
 * @subpackage	Core
 * @category	Model
 * @author		ExpressionEngine Dev Team
 * @link		http://expressionengine.com
 */
class Tools_model extends CI_Model {

	/**
	 * Get Recount Batch Total
	 *
	 * @access	public
	 * @return	string
	 */
	function get_recount_batch_total()
	{
		return $this->config->item('recount_batch_total');
	}

	// --------------------------------------------------------------------

	/**
	 * Get Search and Replace Options
	 *
	 * This method populates the dropdown field on the search and replace form
	 *
	 * @access	public
	 * @return	mixed
	 */
	function get_search_replace_options()
	{
		$options = array();
			
		// site prefs
		
		foreach ($this->session->userdata('assigned_sites') as $site_id => $site_label)
		{
			$prefs["site_preferences_{$site_id}"] = $site_label;
		}
		
		$options['preferences'] = array('name' => $this->lang->line('site_preferences'), 'choices' => $prefs);
				
		// entry titles
		$options['title'] = array('name' => $this->lang->line('channel_entry_title'));
		
		// channel fields
		
		$this->db->select('fg.group_name, cf.field_id, cf.field_label, s.site_label');
		$this->db->from('field_groups AS fg');
		$this->db->join('sites AS s', 's.site_id = fg.site_id');
		$this->db->join('channel_fields AS cf', 'cf.group_id = fg.group_id');
		
		if ($this->config->item('multiple_sites_enabled') !== 'y')
		{
			$this->db->where('cf.site_id', 1);
		}
		
		$this->db->order_by('s.site_label, fg.group_id, cf.field_label');
		
		$query = $this->db->get();
		
		$site = '';

		$fields = array();

		foreach($query->result() as $row)
		{
			if ($this->config->item('multiple_sites_enabled') == 'y')
			{
				$fields["field_id_{$row->field_id}"] = $row->site_label.' - '.$row->field_label.' ('.$row->group_name.')';
			}
			else
			{
				$fields["field_id_{$row->field_id}"] = $row->field_label.' ('.$row->group_name.')';
			}
		}

		$options['channel_fields'] = array('name' => $this->lang->line('channel_fields'), 'choices' => $fields);

		// ALL templates
		$options['template_data'] = array('name' => $this->lang->line('templates'));

		// template groups

		$this->db->select('group_id, group_name, site_label');
		$this->db->from('template_groups');
		$this->db->join('sites', 'sites.site_id = template_groups.site_id');

		if ($this->config->item('multiple_sites_enabled') !== 'y')
		{
			$this->db->where('sites.site_id', 1);
		}

		$this->db->order_by('site_label, group_name');

		$query = $this->db->get();

		$templates = array();

		foreach ($query->result() as $row)
		{
			if ($this->config->item('multiple_sites_enabled') == 'y')
			{
				$templates["template_{$row->group_id}"] = $row->site_label.' - '.$row->group_name;
			}
			else
			{
				$templates["template_{$row->group_id}"] = $row->group_name;
			}			
		}
		
		$options['template_groups'] = array('name' => $this->lang->line('template_groups'), 'choices' => $templates);
		
		return $options;
	}
	
	// --------------------------------------------------------------------

	/**
	 * Get Control Panel Log
	 *
	 * @access	public
	 * @return	mixed
	 */

	function get_cp_log($limit = 50, $offset = 0, $order = array())
	{
		$this->db->select('cp_log.*, sites.site_id, sites.site_label');
		$this->db->from('cp_log');
		$this->db->join('sites', 'sites.site_id=cp_log.site_id');

		if (is_array($order) && count($order) > 0)
		{
			foreach ($order as $key => $val)
			{
				$this->db->order_by($key, $val);
			}
		}
		else
		{
			$this->db->order_by('act_date', 'desc');			
		}

		$this->db->limit($limit, $offset);
		return $this->db->get();	
	}


	// --------------------------------------------------------------------

	/**
	 * Get Search Log
	 *
	 * @access	public
	 * @return	mixed
	 */
	function get_search_log($limit = 50, $offset = 0, $order = array())
	{
		$this->db->select('search_log.*, sites.site_id, sites.site_label');
		$this->db->from('search_log');
		$this->db->join('sites', 'sites.site_id=search_log.site_id');

		if (is_array($order) && count($order) > 0)
		{
			foreach ($order as $key => $val)
			{
				$this->db->order_by($key, $val);
			}
		}
		else
		{
			$this->db->order_by('search_date', 'desc');			
		}

		$this->db->limit($limit, $offset);
		return $this->db->get();
	}

	// --------------------------------------------------------------------

	/**
	 * Get Throttle Log
	 *
	 * @access	public
	 * @param	int		maximum page loads
	 * @param	int		lockout time
	 * @return	mixed
	 */
	function get_throttle_log($max_page_loads = 10, $lockout_time = 30, $limit = 50, $offset = 0, $order = array())
	{
		$this->db->select('ip_address, hits, locked_out, last_activity');
		$this->db->from('throttle');
		$this->db->where('(hits >= "'.$max_page_loads.'" OR (locked_out = "y" AND last_activity > "'.$lockout_time.'"))', NULL, FALSE);

		if (is_array($order) && count($order) > 0)
		{
			foreach ($order as $key => $val)
			{
				$this->db->order_by($key, $val);
			}
		}
		else
		{
			$this->db->order_by('ip_address', 'desc');			
		}

		$this->db->limit($limit, $offset);
		return $this->db->get();
	}
	
	// --------------------------------------------------------------------

	/**
	 * Blacklist IP addresses
	 *
	 * @access	public
	 * @param	mixed	list of ips
	 * @return	int		inserted count
	 */
	function blacklist_ips($naughty = array())
	{
		// Get all previously blacklisted ips
		$this->db->select('blacklisted_value');
		$query = $this->db->get_where('blacklisted', array('blacklisted_type' => 'ip'));
		
		// Merge old and new
		if ($query->num_rows() > 0)
		{
			$naughty = array_merge($naughty, explode('|', $query->row('blacklisted_value')));
		}

		// Clear the old data
		$this->db->where('blacklisted_type', 'ip');
		$this->db->delete('blacklisted');
		
		// And put the new data back in
		$data = array(	'blacklisted_type'	=> 'ip',
						'blacklisted_value' => implode("|", array_unique($naughty)));

		$this->db->insert('blacklisted', $data);

		return $this->db->affected_rows();
	}

	// --------------------------------------------------------------------

	/**
	 * Get Email Logs
	 *
	 * @access	public
	 * @param	int
	 * @return	mixed
	 */
	function get_email_logs($group_id = FALSE, $limit = 50, $offset = 0, $order = array())
	{
		$this->db->select('cache_id, member_id, member_name, recipient_name, cache_date, subject');
		$this->db->from('email_console_cache');

		if (is_array($order) && count($order) > 0)
		{
			foreach ($order as $key => $val)
			{
				$this->db->order_by($key, $val);
			}
		}
		else
		{
			$this->db->order_by('cache_id', 'desc');			
		}

		$this->db->limit($limit, $offset);
		return $this->db->get();	
	}

	// --------------------------------------------------------------------

	/**
	 * Get Language Filelist
	 *
	 * Returns an array of language files
	 *
	 * @access	public
	 * @return	array
	 */
	function get_language_filelist($language_directory = 'english')
	{
		$this->load->helper('file');
		
		$path = APPPATH.'language/'.$language_directory;
		$ext_len = strlen(EXT);

		$filename_end = '_lang'.EXT;
		$filename_end_len = strlen($filename_end);

		$languages = array();
		
		$language_files = get_filenames($path);

		foreach ($language_files as $file)
		{
			if (substr($file, -$filename_end_len) && substr($file, -$ext_len) == EXT)
			{
				$languages[] = $file;
			}
		}
		
		sort($languages);
		
		return $languages;
	}

	// --------------------------------------------------------------------

	/**
	 * Get Language List
	 *
	 * Returns an array of language variables in the file
	 *
	 * @access	public
	 * @param	string	the language file to return
	 * @param	string	the folder to save the new file to
	 * @return	array
	 */
	function get_language_list($language_file = '', $dest_folder = 'translations')
	{
		if ($language_file == '')
		{
			show_error('no_lang_file');
		}
		
		$language_file = $this->security->sanitize_filename($language_file);

		$source_dir = APPPATH.'language/english/';
		$dest_dir = APPPATH.$dest_folder.'/';

		if ( ! file_exists($source_dir.$language_file))
		{
			show_error(lang('no_lang_keys'));
		}

		require($source_dir.$language_file);

		$M = $lang;
		
		unset($lang);
			
		if (file_exists($dest_dir.$language_file))
		{
			require($dest_dir.$language_file);
		}
		else
		{
			$lang = $M;
		}

		$lang_list = array();

		foreach ($M as $key => $val)
		{
			if ($key != '')
			{
				$trans = ( ! isset($lang[$key])) ? '' : $lang[$key];
				$lang_list[$key]['original'] = $val;
				$lang_list[$key]['trans'] = $trans;
			}
		}

		return $lang_list;
	}

	// --------------------------------------------------------------------

	/**
	 * Delete Upload Preferences
	 *
	 * @access	public
	 * @param	int
	 * @return	string
	 */
	function delete_upload_preferences($id = '')
	{
		$this->db->where('upload_id', $id);
		$this->db->delete('upload_no_access');

		// get the name we're going to delete so that we can return it when we're done
		$this->db->select('name');
		$this->db->where('id', $id);
		$deleting = $this->db->get('upload_prefs');

		// ok, now remove the pref
		$this->db->where('id', $id);
		$this->db->delete('upload_prefs');

		return $deleting->row('name');
	}

	// --------------------------------------------------------------------

	/**
	 * Get Upload Preferences
	 *
	 * @access	public
	 * @param	int
	 * @return	mixed
	 */
	function get_upload_preferences($group_id = NULL, $id = '')
	{
		// for admins, no specific filtering, just give them everything
		if ($group_id == 1)
		{
			// there a specific upload location we're looking for?
			if ($id != '')
			{
				$this->db->where('id', $id);
			}

			$this->db->from('upload_prefs');
			$this->db->where('site_id', $this->config->item('site_id'));
			$this->db->order_by('name');

			$upload_info = $this->db->get();
		}
		else
		{
			// non admins need to first be checked for restrictions
			// we'll add these into a where_not_in() check below
			$this->db->select('upload_id');
			$no_access = $this->db->get_where('upload_no_access', array('member_group'=>$group_id));

			if ($no_access->num_rows() > 0)
			{
				$denied = array();
				foreach($no_access->result() as $result)
				{
					$denied[] = $result->upload_id;
				}
				$this->db->where_not_in('id', $denied);
			}

			// there a specific upload location we're looking for?
			if ($id != '')
			{
				$this->db->where('id', $id);
			}

			$this->db->from('upload_prefs');
			$this->db->where('site_id', $this->config->item('site_id'));
			$this->db->order_by('name');

			$upload_info = $this->db->get();
		}

		return $upload_info;
	}
	
	// --------------------------------------------------------------------

	/**
	 * Get Files
	 *
	 * @access	public
	 * @param	int
	 * @return	mixed
	 */
	function get_files($directories = array(), $allowed_types = array(), $full_server_path = '', $hide_sensitive_data = FALSE, $get_dimensions = FALSE)
	{
		$files = array();

		if ( ! is_array($directories))
		{
			$directories = array($directories);
		}
		
		if ( ! is_array($allowed_types))
		{
			$allowed_types = array($allowed_types);
		}
	
		$this->load->helper('file');
		$this->load->helper('string');
		$this->load->helper('text');
		$this->load->helper('directory');
		$this->load->library('encrypt');

		if (count($directories) == 0)
		{
			return $files;
		}

		foreach ($directories as $key => $directory)
		{
			$directory_files = get_dir_file_info($directory); //, array('name', 'server_path', 'size', 'date'));

			if ($allowed_types[$key] == 'img')
			{
				$allowed_type = array('image/gif','image/jpeg','image/png');
			}
			elseif ($allowed_types[$key] == 'all')
			{
				$allowed_type = array();
			}

			$dir_name_length = strlen(reduce_double_slashes($directory)); // used to create relative paths below

			if ($directory_files)
			{
				foreach ($directory_files as $file)
				{
					if ($full_server_path != '')
					{
						$file['relative_path'] = $full_server_path; // allow for paths to be passed into this function
					}

					$file['short_name'] = ellipsize($file['name'], 16, .5);

					$file['relative_path'] = reduce_double_slashes($file['relative_path']);

					$file['encrypted_path'] = rawurlencode($this->encrypt->encode($file['relative_path'].$file['name'], $this->session->sess_crypt_key));

					$file['mime'] = get_mime_by_extension($file['name']);

					if ($get_dimensions)
					{
						if (function_exists('getimagesize')) 
						{
							if ($D = @getimagesize($file['relative_path'].$file['name']))
							{
								$file['dimensions'] = $D[3];
							}
						}
						else
						{
							// We can't give dimensions, so return a blank string
							$file['dimensions'] = '';
						}
					}

					// Add relative directory path information to name
					$file['name'] = substr($file['relative_path'], $dir_name_length).$file['name'];

					// Don't include server paths - useful for ajax requests
					if ($hide_sensitive_data)
					{
						unset($file['relative_path'], $file['server_path']);
					}

					if (count($allowed_type) == 0 OR in_array($file['mime'], $allowed_type))
					{
						$files[] = $file;
					}
				}
			}
		}

		sort($files);

		return $files;
	}

	// --------------------------------------------------------------------
	
	/**
	 * Get Image Properties
	 *
	 * Fetches image width, height, and type
	 *
	 * @access	public
	 * @return	string	filepath
	 */
	function image_properties($file)
	{
		if (function_exists('getimagesize')) 
		{
			if ( ! $D = @getimagesize($file))
			{
				return FALSE;
			}

			$this->width	= $D['0'];
			$this->height  = $D['1'];
			$this->imgtype = $D['2'];

			return $D;
		}
		else
		{
			return FALSE;
		}
	}

	// --------------------------------------------------------------------
		
	/**
	 * Get SQL Info
	 *
	 * Fetches various stats for the database
	 *
	 * @access	public
	 * @return	array
	 */
	function get_sql_info()
	{
		$this->load->helper('number');
		
		$info = array();
		
		// database type
		$info['database_type'] = $this->db->dbdriver;
		
		// db version
		$info['sql_version'] = $this->db->version();
		
		// db records and size
		$query = $this->db->query("SHOW TABLE STATUS FROM `{$this->db->database}`");

		$totsize = 0;
		$records = 0;

		$prefix_len = strlen($this->db->dbprefix);
		
		foreach ($query->result_array() as $row)
		{
			if (strncmp($row['Name'], $this->db->dbprefix, $prefix_len) != 0)
			{
				continue;
			}
			
			$totsize += $row['Data_length'] + $row['Index_length'];
			$records += $row['Rows'];
		}
		
		$info['records'] = $records;	
		$info['size'] = byte_format($totsize);
		
		// db uptime	
		$query = $this->db->query("SHOW STATUS");

		$uptime	 = '';
		$queries = '';

		// We need this a bit later
		$res = $query->result_array();

		foreach ($res as $key => $val)
		{
			foreach ($val as $v)
			{
				if (strncasecmp($v, 'uptime', 6) == 0)
				{
					$uptime = $key;
				}

				if (strncasecmp($v, 'questions', 9) == 0)
				{
					$queries = $key;
				}
			}		
		}	

		$info['database_uptime'] = $this->localize->format_timespan($res[$uptime]['Value']);
		$info['total_queries'] = number_format($query->result_array[$queries]['Value']);	
				
		return $info;
	}

	// --------------------------------------------------------------------
	
	/**
	 * Get Table Status
	 *
	 * Runs a STATUS query on the database, returns query object
	 *
	 * @access	public
	 * @return	object
	 */
	function get_table_status()
	{
		$this->load->helper('number');
		
		$status = array();

		$query = $this->db->query("SHOW TABLE STATUS FROM `{$this->db->database}`");

		$i = 0;
		$records = 0;
		$tables	 = 0;
		$totsize = 0;
		
		$prefix_len = strlen($this->db->dbprefix);
		
		foreach ($query->result() as $row)
		{
			if (strncmp($row->Name, $this->db->dbprefix, $prefix_len) != 0)
			{
				continue;
			}
					
			$len = $row->Data_length + $row->Index_length;
			
			$status[$i]['name'] = $row->Name;
			$status[$i]['rows'] = $row->Rows;
			$status[$i]['size'] = byte_format($len);
			$status[$i]['browse_link'] = BASE.AMP.'C=tools_data'.AMP.'M=sql_run_query'.AMP.'thequery='.rawurlencode(base64_encode('SELECT * FROM `'.$row->Name.'`'));
			
			$records += $row->Rows;
			$totsize += $len;
			$tables++;
			$i++;
		}
		
		return array('status' => $status, 'records' => $records, 'total_size' => byte_format($totsize), 'tables' => $tables);
	}

	// --------------------------------------------------------------------

}

/* End of file tools_model.php */
/* Location: ./system/expressionengine/models/tools_model.php */
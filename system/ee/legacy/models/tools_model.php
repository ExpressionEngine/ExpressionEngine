<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
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
 * ExpressionEngine Tools Model
 *
 * @package		ExpressionEngine
 * @subpackage	Core
 * @category	Model
 * @author		EllisLab Dev Team
 * @link		https://ellislab.com
 */
class Tools_model extends CI_Model {

	/**
	 * Get Search and Replace Options
	 *
	 * This method populates the dropdown field on the search and replace form
	 *
	 * @return	array
	 */
	public function get_search_replace_options()
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
		$options['template_data'] = array('name' => $this->lang->line('replace_in_templates'));

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
	 * @return	mixed
	 */
	public function get_cp_log($limit = 50, $offset = 0, $order = array())
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
	 * @return	mixed
	 */
	public function get_search_log($limit = 50, $offset = 0, $order = array())
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
	 * @param	int		maximum page loads
	 * @param	int		lockout time
	 * @return	mixed
	 */
	public function get_throttle_log($max_page_loads = 10, $lockout_time = 30, $limit = 50, $offset = 0, $order = array())
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
	 * Get items in the Developer Log
	 *
	 * @param	int $limit Query limit
	 * @param	int $offset Query offset
	 * @param	array $order Array of fields to order by
	 *		e.g. 'field1' => 'asc', 'field2' => 'desc'
	 * @return	DB result object
	 */
	public function get_developer_log($limit = 50, $offset = 0, $order = array())
	{
		// Apply custom ordering if it is set
		if (is_array($order) && count($order) > 0)
		{
			foreach ($order as $key => $val)
			{
				$this->db->order_by($key, $val);
			}
		}
		// Otherwise, order by latest log item
		else
		{
			$this->db->order_by('timestamp', 'desc');
		}

		$this->db->limit($limit, $offset);

		return $this->db->get('developer_log');
	}

	// --------------------------------------------------------------------

	/**
	 * Returns number of unviewed items in the developer log to display in
	 * a notice on the CP home screen
	 *
	 * @return	int Number of unviewed developer logs
	 */
	public function count_unviewed_developer_logs()
	{
		$this->db->where('viewed', 'n');

		return $this->db->count_all_results('developer_log');
	}

	// --------------------------------------------------------------------

	/**
	 * Marks developer logs as viewed
	 *
	 * Given an array of logs formatted for datatables, updates each record
	 * with associated log_id and sets its 'viewed' field to 'y'
	 *
	 * @param	array $logs Array of existing logs to mark as viewed
	 * @return	void
	 */
	public function mark_developer_logs_as_viewed($logs)
	{
		$log_ids = array();

		// Build an array of log IDs
		foreach ($logs as $log)
		{
			// Don't take on any more logs than we have to
			if ($log['viewed'] == 'n')
			{
				$log_ids[] = $log['log_id']['data'];
			}
		}

		// Set 'viewed' to 'y' where the ID exists in the $log_ids array
		if (count($log_ids))
		{
			$this->db->where_in('log_id', $log_ids);
			$this->db->update('developer_log', array('viewed' => 'y'));
		}
	}

	// --------------------------------------------------------------------

	/**
	 * Delete logs
	 *
	 * Given a table name, clears out its contents. Given a table name and
	 * array of log IDs, deletes only those rows from table.
	 *
	 * @param	string $table Table name
	 * @param	string $id_field Name of ID field in table
	 * @param	array $log_ids IDs of rows to delete
	 * @return	void
	 */
	public function delete_logs($table = NULL, $id_field = NULL, $log_ids = array())
	{
		// Can't do anything without the table name
		if (empty($table))
		{
			return FALSE;
		}

		// If no log IDs were passed, clear out the table
		if (empty($log_ids))
		{
			$this->db->empty_table($table);
		}
		// Otherwise, delete WHERE IN the array of log IDs
		else
		{
			$this->db->where_in($id_field, $log_ids);
			$this->db->delete($table);
		}
	}

	// --------------------------------------------------------------------

	/**
	 * Blacklist IP addresses
	 *
	 * @param	mixed	list of ips
	 * @return	int		inserted count
	 */
	public function blacklist_ips($naughty = array())
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
	 * @param	int
	 * @return	mixed
	 */
	public function get_email_logs($group_id = FALSE, $limit = 50, $offset = 0, $order = array())
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
	 * @return	array
	 */
	public function get_language_filelist($language_directory = 'english')
	{
		$this->load->helper('file');

		$path = APPPATH.'language/'.$language_directory;
		$ext_len = strlen('.php');

		$filename_end = '_lang.php';
		$filename_end_len = strlen($filename_end);

		$languages = array();

		$language_files = get_filenames($path);

		foreach ($language_files as $file)
		{
			if ($file == 'email_data.php')
			{
				continue;
			}

			if (substr($file, -$filename_end_len) && substr($file, -$ext_len) == '.php')
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
	 * @param	string	the language file to return
	 * @param	string	the folder to save the new file to
	 * @return	array
	 */
	public function get_language_list($language_file = '', $dest_folder = 'translations')
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
	 * Get Image Properties
	 *
	 * Fetches image width, height, and type
	 *
	 * @return	string	filepath
	 */
	public function image_properties($file)
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
	 * @return	array
	 */
	public function get_sql_info()
	{
		$this->load->helper(array('date', 'number'));

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

		$info['database_uptime'] = timespan($this->localize->now - $res[$uptime]['Value']);

		return $info;
	}

	// --------------------------------------------------------------------

	/**
	 * Get Table Status
	 *
	 * Runs a STATUS query on the database, returns query object
	 *
	 * @return	object
	 */
	public function get_table_status()
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

// EOF

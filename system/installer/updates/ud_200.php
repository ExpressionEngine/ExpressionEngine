<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2012, EllisLab, Inc.
 * @license		http://ellislab.com/expressionengine/user-guide/license.html
 * @link		http://ellislab.com
 * @since		Version 2.0
 * @filesource
 */
 
// ------------------------------------------------------------------------

/**
 * ExpressionEngine Update Class
 *
 * @package		ExpressionEngine
 * @subpackage	Core
 * @category	Core
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */
class Updater {

	var $version_suffix			= 'pb01';	
	var $mylang					= 'english';
	var $large_db				= FALSE;
	var $large_db_threshold		= 150;	// database size in megabytes
	var $errors					= array();
	
	
	public function Updater()
	{
		$this->EE =& get_instance();

		// Grab the config file
		if ( ! @include($this->EE->config->config_path))
		{
			show_error('Your config'.EXT.' file is unreadable. Please make sure the file exists and that the file permissions to 666 on the following file: expressionengine/config/config.php');
		}

		if (isset($conf))
		{
			$config = $conf;
		}

		// Does the config array exist?
		if ( ! isset($config) OR ! is_array($config))
		{
			show_error('Your config'.EXT.'file does not appear to contain any data.');
		}

		$this->EE->load->library('progress');

		$this->config =& $config;
		
		// truncate some tables
		$trunc = array('captcha', 'sessions', 'security_hashes', 'search');

		foreach ($trunc as $table_name)
		{
			$this->EE->db->truncate($table_name);
		}
		
		// we will use this conditionally to branch the update path
		if ($this->_fetch_db_size() > $this->large_db_threshold)
		{
			$this->large_db = TRUE;
		}
	}

	// --------------------------------------------------------------------
	
	public function do_update()
	{
		$ignore = FALSE;
		$manual_move = FALSE;

 		if ($this->EE->input->get('language') != FALSE && $this->EE->input->get('language') != '')
		{
			$this->mylang = $this->EE->input->get_post('language');
		}

		if ($this->EE->input->get_post('templates') == 'ignore')
		{
			$this->EE->config->_update_config(array('ignore_templates' => 'y'));
			$ignore = 'y';
		}
		elseif ($this->EE->input->get_post('templates') == 'manual')
		{
			$this->EE->config->_update_config(array('manual_templates_move' => 'y'));
			$manual_move = 'y';
		}

		// turn off extensions
		$this->EE->db->update('extensions', array('enabled' => 'n'));
		
		$this->_update_site_prefs($ignore, $manual_move);
		
		// step 1, utf8 conversion
		return ($this->large_db) ? 'large_db_check' : 'convert_db_to_utf8';
	}
	
	// --------------------------------------------------------------------
	
	private function _update_site_prefs($ignore, $manual_move)
	{		
		// Load the string helper
		$this->EE->load->helper('string');

		$query = $this->EE->db->query("SELECT es.* FROM exp_sites AS es");

		// Update Flat File Templates if we have any
		$this->_update_templates_saved_as_files($query, $ignore, $manual_move);

		foreach($query->result_array() as $row)
		{
			foreach($row as $name => $data)
			{
				if (substr($name, -12) == '_preferences')
				{
					// base64 encode the serialized arrays
					$data = strip_slashes(unserialize($data));

					// one quick path adjustment if they were using the old default location for template files
					if (isset($data['tmpl_file_basepath']) && trim($data['tmpl_file_basepath'], '/') == BASEPATH.'templates')
					{
						$data['tmpl_file_basepath'] = EE_APPPATH.'/templates/';
					}
					
					// also, make sure they start with the default cp theme
					if (isset($data['cp_theme']) && $data['cp_theme'] != 'default')
					{
						$data['cp_theme'] = 'default';
					}
					
					// new name for a debugging preference
					if (isset($data['show_queries']))
					{
						$data['show_profiler'] = $data['show_queries'];
						unset($data['show_queries']);
					}
					
					// docs location
					if (isset($data['doc_url']))
					{
						$data['doc_url'] = 'http://ellislab.com/expressionengine/user-guide/';
					}

					$data = base64_encode(serialize($data));
					$row[$name] = $data;
				}
			}

			$this->EE->db->query($this->EE->db->update_string('exp_sites', $row, "site_id = '".$this->EE->db->escape_str($row['site_id'])."'"));
		}
	}
	
	// ------------------------------------------------------------------------
	
	/**
	 * Look for any templates saved as files, sync them with the database
	 * And move them out of the way.
	 *
	 * @access private
	 * @param object	Query object from sites query.
	 * @return void
	 */
	private function _update_templates_saved_as_files($sites, $ignore_setting, $manual_move)
	{
		$sites_with_templates = array();
		$template_move_errors = FALSE;

		$ignore_templates = (isset($this->config['ignore_templates'])) ? $this->config['ignore_templates'] : $ignore_setting;
		$manual_move_templates = (isset($this->config['manual_templates_move'])) ? $this->config['manual_templates_move'] : $manual_move;

		foreach ($sites->result() as $site)
		{
			$templates = unserialize($site->site_template_preferences);

			if ($manual_move_templates == 'y')
			{
				$sites_with_templates[$site->site_name]['query'] = FALSE;
				continue;
			}

			if ($templates['save_tmpl_files'] == 'n')
			{
				$sites_with_templates[$site->site_name]['query'] = FALSE;
			}

			$sites_with_templates[$site->site_name] = array(
				'site_id'		=> $site->site_id,
				'site_name'		=> $site->site_name
			);
		}

		// This should be impossible?  If there aren't any, bail out!
		if (empty($sites_with_templates))
		{
			return;
		}

		//  If we are retrying after a manual removal of templates, check
		//  that folder is gone and then move along
		if ($manual_move_templates == 'y')
		{
			$must_remove = array();
			$retry = anchor('C=wizard&M=do_update&agree=yes&ajax_progress=yes&language='.$this->mylang.'&templates=manual', $this->EE->lang->line('template_retry'));
			foreach ($sites_with_templates as $name => $site)
			{
				if (is_dir(EE_APPPATH.'templates/'.$name.'/'))
				{
					$must_remove[] = $name;
				}
			}

			if ( ! empty($must_remove))
			{
				//$removal_error = implode(', <br />', $must_remove);
				show_error(sprintf($this->EE->lang->line('template_move_errors'), $retry));
			}
			else
			{
				return;
			}
		}


		// We have sites that are saving templates as files.
		// We'll query to see if any templates are actually saved as
		// files, and prune the above array if necessary.
		// Or, tack on the object returned to the query on the array
		$template_query = array();

		foreach ($sites_with_templates as $site)
		{
			if (isset($sites_with_templates[$site['site_name']]['query']))
			{
				continue;
			}

			// Onward
			$this->EE->db->select('templates.template_id, templates.template_name, 
									templates.template_data, template_groups.group_name');
			$this->EE->db->where('save_template_file', 'y');
			$this->EE->db->where('template_groups.site_id', $site['site_id']);
			$this->EE->db->join('template_groups', 'template_groups.group_id = templates.group_id');
			$query = $this->EE->db->get('templates');

			if ($query->num_rows() == 0)
			{
				// No templates saved as files for this site.  Set to FALSE, but move to backup
				$query = FALSE;
			}

			// Stay with me here.  Saving some queries.
			$sites_with_templates[$site['site_name']]['query'] = $query;
		}

		// There are sites with templates, make sure the templates have been uploaded.

		$old_template_upload_errors = array();

		// On to the real deal.
		foreach ($sites_with_templates as $site)
		{
			$template_path = EE_APPPATH.'templates/'.$site['site_name'].'/';

			$this->EE->progress->update_state($this->EE->lang->line('updating_templates_as_files'));

			// Error Array
			$template_errors = array();

			// Templates to move
			$templates_to_move = array();

			// Template Groups
			$template_groups = array();

			if ($site['query'] !== FALSE)
			{
				if ( ! is_really_writable(EE_APPPATH.'templates/'.$site['site_name'].'/'))
				{
					$old_template_upload_errors[] = $site['site_name'];
				}

				foreach ($site['query']->result() as $row)
				{
					// Check overhead on testing here
					if (read_file($template_path.$row->group_name.'/'.$row->template_name.EXT) === FALSE)
					{
						$template_errors[] = $row->group_name.'/'.$row->template_name;
					}
					else
					{
						$templates_to_move[] = $row;
						$template_groups[] = $row->group_name;
					}
				}
			}

			$template_groups = array_unique($template_groups);

			// Are there errors?
			if (( ! empty($old_template_upload_errors) OR ! empty($template_errors)) && $ignore_templates != 'y')
			{
				$folder_error_str = '';
				$template_error_str = '';

				$ignore = anchor('C=wizard&M=do_update&agree=yes&ajax_progress=yes&language='.$this->mylang.'&templates=ignore', $this->EE->lang->line('template_ignore'));
				$retry = anchor('C=wizard&M=do_update&agree=yes&ajax_progress=yes&language='.$this->mylang, $this->EE->lang->line('template_retry'));

				if ( ! empty($old_template_upload_errors))
				{
					$folder_error = '<ul>';

					foreach ($old_template_upload_errors as $key => $val)
					{
						$folder_error .= '<li>'.$val.'</li>';
					}

					$folder_error .= '</ul>';

					$folder_error_str = $this->EE->lang->line('template_folders_not_located').$folder_error.$this->EE->lang->line('template_folders_not_located_instr');
				}

				if ( ! empty($template_errors))
				{
					$template_error_str = '<br /><br />'.$this->EE->lang->line('template_files_not_located');

					$template_error_str .= '<ul>';

					foreach ($template_errors as $key => $val)
					{
						$template_error_str .= '<li>'.$val.'</li>';
					}

					$template_error_str .= '</ul>';
				}

				$template_error_explain = sprintf($this->EE->lang->line('template_missing_explain_retry'), $retry);
				$template_error_explain .= sprintf($this->EE->lang->line('template_missing_explain_ignore'), $ignore);
				show_error($folder_error_str.$template_error_str.$template_error_explain);
			}

			foreach ($templates_to_move as $key => $val)
			{
				$one_six_file = read_file($template_path.$val->group_name.'/'.$val->template_name.EXT);

				if ($one_six_file === FALSE)
				{
					continue;
				}

				$this->EE->db->where('template_id', $val->template_id);
				$this->EE->db->update('templates', array('template_data' => $one_six_file));

			}

			// Now we make our backup folder

			$old_template_folder = EE_APPPATH.'templates/1.x_templates/'.$site['site_name'].'/';

			// First, make sure the 1.x template folder exists
			if ( ! (is_dir(EE_APPPATH.'templates/1.x_templates')))
			{
				if (@mkdir(EE_APPPATH.'templates/1.x_templates') === FALSE)
				{
					$template_move_errors = TRUE;
				}
			}

			// Move the site's template folder
			if (is_dir(EE_APPPATH.'templates/'.$site['site_name']) && ! @rename(EE_APPPATH.'templates/'.$site['site_name'], $old_template_folder))
			{
				$template_move_errors = TRUE;
			}

		}

		if ($template_move_errors)
		{
			$retry = anchor('C=wizard&M=do_update&agree=yes&ajax_progress=yes&language='.$this->mylang.'&templates=manual', $this->EE->lang->line('template_retry'));
			show_error(sprintf($this->EE->lang->line('template_move_errors'), $retry));
		}

		return;
	}

	// ------------------------------------------------------------------------
	
	/**=====================================================================
	 * Standard Database UTF8/Time Conversion
	 * - convert_db_to_utf8
	 * - standardize_datetime
	 * ===================================================================== */
	
	public function convert_db_to_utf8()
	{
		// this step can be a doozy.  Set time limit to infinity.
		// Server process timeouts are out of our control, unfortunately
		$this->EE->db->save_queries = FALSE;

		$this->EE->progress->update_state('Converting Database Tables to UTF-8');

		// make sure STRICT MODEs aren't in use, at least on servers that don't default to that
		$this->EE->db->query('SET SESSION sql_mode=""');

		$tables = $this->EE->db->list_tables(TRUE); // TRUE prefix limit, only operate on EE tables
		$batch = 100;
		
		foreach ($tables as $table)
		{
			$progress	= "Converting Database Table {$table}: %s";
			$count		= $this->EE->db->count_all($table);
			$offset	 = 0;
			
			if ($count > 0)
			{
				for ($i = 0; $i < $count; $i = $i + $batch)
				{
					$this->EE->progress->update_state(str_replace('%s', "{$offset} of {$count} queries", $progress));

					// set charset to latin1 to read 1.x's written values properly
					$this->EE->db->db_set_charset('latin1', 'latin1_swedish_ci');
					$query = $this->EE->db->query("SELECT * FROM {$table} LIMIT $offset, $batch");
					$data = $query->result_array();
					$query->free_result();

					// set charset to utf8 to write them back to the database properly
					$this->EE->db->db_set_charset('utf8', 'utf8_general_ci');

					foreach ($data as $row)
					{
						$where = array();
						$update = FALSE;

						foreach ($row as $field => $value)
						{
							// Wet the WHERE using all numeric fields to ensure accuracy
							// since we have no clue what the keys for the current table are.
							if (is_numeric($value))
							{
								$where[$field] = $value;
							}
							// Also check to see if this row contains any fields that have
							// characters not shared between latin1 and utf8 (7-bit ASCII shared only).
							// If it does, then we need to update this row.
							elseif (preg_match('/[^\x00-\x7F]/S', $value) > 0)
							{
								$update = TRUE;
							}
						}

						if ($update === TRUE)
						{
							$this->EE->db->where($where);
							$this->EE->db->update($table, $row, $where);
						}
					}

					$offset = $offset + $batch;
				}
			}

			// finally, set the table's charset and collation in MySQL to utf8
			$this->EE->db->query("ALTER TABLE `{$table}` CONVERT TO CHARACTER SET utf8 COLLATE utf8_general_ci");
		}
		
		// And update the database to use utf8 in the future
		$this->EE->db->query("ALTER DATABASE `{$this->EE->db->database}` CHARACTER SET utf8 COLLATE utf8_general_ci;");

		// more work to do
		return 'standardize_datetime';
	}

	// --------------------------------------------------------------------
	
	public function standardize_datetime()
	{
		$queries = $this->_standardize_datetime_queries();
		
		$this->_run_queries('Standardizing Timestamps', $queries);
				
		return 'trackback_check';
	}

	// ------------------------------------------------------------------------
	
	/**=====================================================================
	 * Large Database UTF8/Time Conversion
	 * - large_db_check
	 * - generate_queries
	 * - convert_large_db_to_utf8
	 * - standardize_datetime_large_db
	 * ===================================================================== */
	
	/**
	 * Large Database Gatekeeper
	 */
	public function large_db_check()
	{
		if ( ! $this->EE->db->table_exists('large_db_update_completed'))
		{
			return $this->generate_queries();
		}
		
		// This table is only used as an indicator
		$this->EE->load->dbforge();
		$this->EE->dbforge->drop_table('large_db_update_completed');
		
		// The sysadmin may not have removed this file
		if (is_dir(EE_APPPATH.'cache/installer'))
		{
			if (file_exists(EE_APPPATH.'cache/installer/update.sh'))
			{
				unlink(EE_APPPATH.'cache/installer/update.sh');
			}
		}
		
		return 'trackback_check';
	}

	// ------------------------------------------------------------------------
	
	/**
	 * Generate queries for the sysadmin to run to update a large EE2 
	 * installation
	 */
	public function generate_queries()
	{
		// Show commands for converting large_db_to_utf8
		
		// Queries include:
		// - Changing datetime to be GMT time (get queries from standardize_datetime)
		// - Convert database to UTF8 (first part of convert_large_db_to_utf8)
		// - Change collation to UTF8 on tables and database (second part of convert_large_db_to_utf8)

		// Grab datetime queries
		$queries = $this->_standardize_datetime_queries();
		
		
		// Add utf-8 conversion queries
		foreach ($this->EE->db->list_tables(TRUE) as $table)
		{
			$queries[] = "ALTER TABLE `{$table}` CHARACTER SET utf8 COLLATE utf8_general_ci;";
		}
		
		$queries[] = "ALTER DATABASE `{$this->EE->db->database}` CHARACTER SET utf8 COLLATE utf8_general_ci;";
		
		
		// Lastly, create a table to indicate a successful update
		$queries[] = "CREATE TABLE {$this->EE->db->dbprefix}large_db_update_completed(`id` int);";
		
		
		// Write bash file
		$this->EE->progress->update_state('Imploding queries.');
		
		
		$queries = implode("\n", $queries);	// @todo ensure semicolons?
		
		
		$tables = implode(' ', $this->EE->db->list_tables(TRUE));
		$password_parameter = ($this->EE->db->password != '') ? '-p'.$this->EE->db->password : '';
		
		$data = <<<BSH
#!/bin/sh

##
# UTF-8 Conversion
##

echo "Starting Large Database Conversion"

echo "UTF-8 Conversion (Step 1: Dumping database with current charset)"
mysqldump -h {$this->EE->db->hostname} -u {$this->EE->db->username} \
	{$password_parameter} --opt --quote-names --skip-set-charset \
	--default-character-set=latin1 {$this->EE->db->database} \
	{$tables} \
	> {$this->EE->db->database}-pre-upgrade-dump.sql

echo "UTF-8 Conversion (Step 2: Importing database with UTF-8 charset)"
mysql -h {$this->EE->db->hostname} -u {$this->EE->db->username} \
	{$password_parameter} --default-character-set=utf8 \
	{$this->EE->db->database} < {$this->EE->db->database}-pre-upgrade-dump.sql


echo "UTF-8 Conversion (Step 3: Removing database dump)"
rm {$this->EE->db->database}-pre-upgrade-dump.sql


##
# Rest of the Queries (Datetime and some finalization) 
##

echo "DST Conversion (Step 1: Reading queries)"

read -d '' Queries <<"EOF"

{$queries}

EOF

echo "DST Conversion (Step 2: Writing temporary SQL file)"
echo "\${Queries}" > temp.sql

echo "DST Conversion (Step 3: Importing temp file, this may take several minutes)"
mysql -h {$this->EE->db->hostname} -u {$this->EE->db->username} \
	{$password_parameter} {$this->EE->db->database} < temp.sql

echo "DST Conversion (Step 4: Removing temp file)"
rm temp.sql

echo "Large Database Conversion Completed: Please return to the browser to finish your upgrade."
BSH;

		$this->EE->progress->update_state('Writing large db update file.');
		
		if ( ! is_dir(EE_APPPATH.'cache/installer'))
		{
			mkdir(EE_APPPATH.'cache/installer', DIR_WRITE_MODE);
			@chmod(EE_APPPATH.'cache/installer', DIR_WRITE_MODE);
		}

		$filepath = EE_APPPATH.'cache/installer/update.sh';

		if (file_put_contents($filepath, $data))
		{
			@chmod($filepath, FILE_WRITE_MODE);
		}
		
		
		// We need to wait for them to run the update,
		// so we'll nudge them in the right direction
		// and then bail out.
		
		$this->errors[] = "Your database is too large to perform this part of the upgrade via a web request.
							Please contact your system administrator and have them run the script located at:
							<br /><br />
							{$filepath}
							<br /><br />
							Then access this page again.";

		return FALSE;
	}

	// --------------------------------------------------------------------
	
	/**=====================================================================
	 * Normal/Large Database Conversion Tasks
	 * - trackback_check
	 * - backup_trackbacks
	 * - database_clean
	 * - database_changes_new
	 * - database_changes_members
	 * - database_changes_weblog
	 * - update_custom_fields
	 * - resync_member_groups
	 * - convert_fresh_variables
	 * - weblog_terminology_changes
	 * ===================================================================== */
	
	public function trackback_check()
	{
		// Do we need to consider trackbacks?
		if (( ! isset($this->config['trackbacks_to_comments']) OR $this->config['trackbacks_to_comments'] != 'y') AND
			( ! isset($this->config['archive_trackbacks']) OR $this->config['archive_trackbacks'] != 'y'))
		{
			// Remove temporary keys
			$this->EE->config->_update_config(array(), array('trackbacks_to_comments' => '', 'archive_trackbacks' => ''));

			// continue with general database changes
			return 'database_clean';
		}

		// update site prefs
		return 'backup_trackbacks';
	}
	
	// --------------------------------------------------------------------
	
	public function backup_trackbacks()
	{
		$next_step = 'database_clean';

		// Grab the main table
		$t_query = $this->EE->db->get('trackbacks');

		if ($t_query->num_rows() == 0)
		{
			// Whee - that was easy, remove config keys
			$this->EE->config->_update_config(array(), array('trackbacks_to_comments' => '', 'archive_trackbacks' => '', 'trackback_zip_path' => ''));
			return $next_step;
		}

		if (isset($this->config['trackbacks_to_comments']) && $this->config['trackbacks_to_comments'] == 'y')
		{
			$this->EE->progress->update_state('Converting Trackbacks to Comments');

			$data = array();
			$weblogs = array();
			$entry_count = array();

			// convert to comments
			foreach($t_query->result_array() as $row)
			{
				$data[] = array(
					'site_id'		=> $row['site_id'],
					'entry_id'		=> $row['entry_id'],
					'weblog_id'		=> $row['weblog_id'],
					'author_id'		=> 0,
					'status'		=> 'o',
					'name'			=> $row['trackback_url'],
					'email'			=> '',
					'url'			=> $row['trackback_url'],
					'location'		=> '',
					'ip_address'	=> $row['trackback_ip'],
					'comment_date'	=> $row['trackback_date'],
					'comment'		=> $row['content'],
					'notify'		=> 'n'
				);

				if ( ! in_array($row['weblog_id'], $weblogs))
				{
					$weblogs[] = $row['weblog_id'];
				}

				if ( ! isset($entry_count[$row['entry_id']]))
				{
					$entry_count[$row['entry_id']] = 0;
				}

				$entry_count[$row['entry_id']] += 1;
			}

			$this->EE->progress->update_state('Recounting Comments');

			// Update entry comment totals
			foreach($entry_count as $entry_id => $add)
			{
				$this->EE->db->set('comment_total', 'comment_total + '.$add, FALSE);
				$this->EE->db->where('entry_id', $entry_id);

				$this->EE->db->update('weblog_titles');
			}

			// Update weblog comment totals
			foreach($weblogs as $weblog_id)
			{
				$query = $this->EE->db->query("SELECT COUNT(comment_id) AS count FROM exp_comments WHERE status = 'o' AND weblog_id = '$weblog_id'");
				$total = $query->row('count');

				$query = $this->EE->db->query("SELECT last_comment_date, site_id FROM exp_weblogs WHERE weblog_id = '$weblog_id'");
				$date = ($newtime > $query->row('last_comment_date') ) ? $newtime : $query->row('last_comment_date');

				$this->EE->db->query("UPDATE exp_weblogs SET total_comments = '$total', last_comment_date = '$date' WHERE weblog_id = '$weblog_id'");
			}

			$this->EE->db->insert_batch('comments', $data);
		}

		if (isset($this->config['archive_trackbacks']) && $this->config['archive_trackbacks'] == 'y')
		{
			$this->EE->progress->update_state('Backing up Trackbacks');

			// Dump the whole lot into xml files, zip it up, and save it to disk

			$this->EE->load->library('zip');
			$this->EE->load->dbutil();

			$this->EE->zip->add_data('exp_trackbacks.xml', $this->EE->dbutil->xml_from_result($t_query));

			$query = $this->EE->db->get_where('specialty_templates', array('template_name' => 'admin_notify_trackback'));
			if ($query->num_rows() > 0)
			{
				$this->EE->zip->add_data('exp_specialty_templates.xml', $this->EE->dbutil->xml_from_result($query));
			}

			$trackback_fields = array(
				'stats' => array(
					'weblog_id',
					'total_trackbacks',
					'last_trackback_date'
				),
				'weblogs' => array(
					'weblog_id',
					'total_trackbacks',
					'last_trackback_date',
					'enable_trackbacks',
					'trackback_use_url_title',
					'trackback_max_hits',
					'trackback_field',
					'deft_trackbacks',
					'trackback_system_enabled',
					'show_trackback_field',
					'trackback_use_captcha',
					'tb_return_url'
				),
				'weblog_titles' => array(
					'entry_id',
					'allow_trackbacks',
					'trackback_total',
					'sent_trackbacks',
					'recent_trackback_date'
				)
			);

			foreach($trackback_fields as $table => $fields)
			{
				$this->EE->db->select($fields);
				$query = $this->EE->db->get($table);

				if ($query->num_rows() > 0)
				{
					$this->EE->zip->add_data('exp_'.$table.'.xml', $this->EE->dbutil->xml_from_result($query));
				}
			}

			$this->EE->zip->archive($this->config['trackback_zip_path']);
		}

		// Remove temporary keys
		$this->EE->config->_update_config(array(), array('trackbacks_to_comments' => '', 'archive_trackbacks' => '', 'trackback_zip_path' => ''));

		return $next_step;
	}

	// --------------------------------------------------------------------

	public function database_clean()
	{
		$has_duplicates = $this->_dupe_check();

		$Q = array();

		// Eliminate duplicate primaries

		if (in_array('upload_no_access', $has_duplicates))
		{

			$Q[] = "CREATE TABLE exp_tmp_upload_no_access SELECT DISTINCT upload_id, upload_loc, member_group FROM exp_upload_no_access";
			$Q[] = "DROP TABLE exp_upload_no_access";
			$Q[] = "ALTER TABLE exp_tmp_upload_no_access RENAME TO exp_upload_no_access";
		}

		if (in_array('message_folders', $has_duplicates))
		{

			$Q[] = "CREATE TABLE exp_tmp_message_folders SELECT DISTINCT * FROM exp_message_folders";
			$Q[] = "DROP TABLE exp_message_folders";
			$Q[] = "ALTER TABLE exp_tmp_message_folders RENAME TO exp_message_folders";
		}

		if (in_array('category_posts', $has_duplicates))
		{
			$Q[] = "CREATE TABLE exp_tmp_category_posts SELECT DISTINCT * FROM exp_category_posts";
			$Q[] = "DROP TABLE exp_category_posts";
			$Q[] = "ALTER TABLE exp_tmp_category_posts RENAME TO exp_category_posts";
		}

		$this->_run_queries('Cleaning duplicate data.', $Q);

		return 'database_changes_new';
	}

	// ------------------------------------------------------------------------ 

	public function database_changes_new()
	{
		$this->EE->progress->update_state("Creating new database tables");

		$Q[] = "CREATE TABLE `exp_snippets` (
				`snippet_id` int(10) unsigned NOT NULL auto_increment,
				`site_id` int(4) NOT NULL,
				`snippet_name` varchar(75) NOT NULL,
				`snippet_contents` text NULL,
				PRIMARY KEY (`snippet_id`),
				KEY `site_id` (`site_id`)
				)";

		$Q[] = "CREATE TABLE `exp_accessories` (
				`accessory_id` int(10) unsigned NOT NULL auto_increment,
				`class` varchar(75) NOT NULL default '',
				`member_groups` varchar(50) NOT NULL default 'all',
				`controllers` text NULL,
				`accessory_version` VARCHAR(12) NOT NULL,
				PRIMARY KEY `accessory_id` (`accessory_id`)
				)";

		// Layout Publish
		// Custom layout for for the publish page.
		$Q[] = "CREATE TABLE exp_layout_publish (
			  layout_id int(10) UNSIGNED NOT NULL auto_increment,
			  site_id int(4) UNSIGNED NOT NULL default 1,
			  member_group int(4) UNSIGNED NOT NULL default 0,
			  channel_id int(4) UNSIGNED NOT NULL default 0,
			  field_layout text,
			  PRIMARY KEY  (`layout_id`),
			  KEY `site_id` (`site_id`),
			  KEY `member_group` (`member_group`),
			  KEY `channel_id` (`channel_id`)
		)";

		// CP Search Index
		$Q[] = "CREATE TABLE `exp_cp_search_index` (
				`search_id` int(10) UNSIGNED NOT NULL auto_increment, 
				`controller` varchar(20) default NULL, 
				`method` varchar(50) default NULL,
				`language` varchar(20) default NULL, 
				`access` varchar(50) default NULL, 
				`keywords` text, 
				PRIMARY KEY `search_id` (`search_id`),
				FULLTEXT(`keywords`) 
		) ENGINE=MyISAM ";

		// Channel Titles Autosave
		// Used for the autosave functionality
		$Q[] = "CREATE TABLE exp_channel_entries_autosave (
			 entry_id int(10) unsigned NOT NULL auto_increment,
			 original_entry_id int(10) unsigned NOT NULL,
			 site_id INT(4) UNSIGNED NOT NULL DEFAULT 1,
			 channel_id int(4) unsigned NOT NULL,
			 author_id int(10) unsigned NOT NULL default 0,
			 pentry_id int(10) NOT NULL default 0,
			 forum_topic_id int(10) unsigned NULL DEFAULT NULL,
			 ip_address varchar(16) NOT NULL,
			 title varchar(100) NOT NULL,
			 url_title varchar(75) NOT NULL,
			 status varchar(50) NOT NULL,
			 versioning_enabled char(1) NOT NULL default 'n',
			 view_count_one int(10) unsigned NOT NULL default 0,
			 view_count_two int(10) unsigned NOT NULL default 0,
			 view_count_three int(10) unsigned NOT NULL default 0,
			 view_count_four int(10) unsigned NOT NULL default 0,
			 allow_comments varchar(1) NOT NULL default 'y',
			 sticky varchar(1) NOT NULL default 'n',
			 entry_date int(10) NOT NULL,
			 dst_enabled varchar(1) NOT NULL default 'n',
			 year char(4) NOT NULL,
			 month char(2) NOT NULL,
			 day char(3) NOT NULL,
			 expiration_date int(10) NOT NULL default 0,
			 comment_expiration_date int(10) NOT NULL default 0,
			 edit_date bigint(14),
			 recent_comment_date int(10) NULL DEFAULT NULL,
			 comment_total int(4) unsigned NOT NULL default 0,
			 entry_data text NULL,
			 PRIMARY KEY `entry_id` (`entry_id`),
			 KEY `channel_id` (`channel_id`),
			 KEY `author_id` (`author_id`),
			 KEY `url_title` (`url_title`),
			 KEY `status` (`status`),
			 KEY `entry_date` (`entry_date`),
			 KEY `expiration_date` (`expiration_date`),
			 KEY `site_id` (`site_id`)
		)";

		$this->_run_queries('Adding new tables', $Q);

		return 'database_changes_members';
	}

	// ------------------------------------------------------------------------ 

	public function database_changes_members()
	{
		$this->EE->progress->update_state("Updating member tables");
	
		// Update members table: parse_smileys and crypt_key
		$Q[] = "ALTER TABLE `exp_members` ADD `parse_smileys` CHAR(1) NOT NULL DEFAULT 'y' AFTER `display_signatures`";
		$Q[] = "ALTER TABLE `exp_members` ADD `crypt_key` varchar(40) NULL DEFAULT NULL AFTER `unique_id`";

		// drop user weblog related fields
		$Q[] = "ALTER TABLE `exp_members` DROP COLUMN `weblog_id`";
		$Q[] = "ALTER TABLE `exp_members` DROP COLUMN `tmpl_group_id`";
		$Q[] = "ALTER TABLE `exp_members` DROP COLUMN `upload_id`";
		$Q[] = "ALTER TABLE `exp_template_groups` DROP COLUMN `is_user_blog`";
		$Q[] = "ALTER TABLE `exp_weblogs` DROP COLUMN `is_user_blog`";
		$Q[] = "ALTER TABLE `exp_global_variables` DROP COLUMN `user_blog_id`";
		$Q[] = "ALTER TABLE `exp_online_users` DROP COLUMN `weblog_id`";

		// members table default tweaks
		$Q[] = "ALTER TABLE `exp_members` CHANGE `authcode` `authcode` varchar(10) NULL DEFAULT NULL";
		$Q[] = "ALTER TABLE `exp_members` CHANGE `url` `url` varchar(150) NULL DEFAULT NULL";
		$Q[] = "ALTER TABLE `exp_members` CHANGE `location` `location`  varchar(50) NULL DEFAULT NULL";
		$Q[] = "ALTER TABLE `exp_members` CHANGE `occupation` `occupation` varchar(80) NULL DEFAULT NULL";
		$Q[] = "ALTER TABLE `exp_members` CHANGE `interests` `interests` varchar(120) NULL DEFAULT NULL";
		$Q[] = "ALTER TABLE `exp_members` CHANGE `bday_d` `bday_d` int(2) NULL DEFAULT NULL";
		$Q[] = "ALTER TABLE `exp_members` CHANGE `bday_m` `bday_m` int(2) NULL DEFAULT NULL";
		$Q[] = "ALTER TABLE `exp_members` CHANGE `bday_y` `bday_y` int(4) NULL DEFAULT NULL";
		$Q[] = "ALTER TABLE `exp_members` CHANGE `aol_im` `aol_im` varchar(50) NULL DEFAULT NULL";
		$Q[] = "ALTER TABLE `exp_members` CHANGE `yahoo_im` `yahoo_im` varchar(50) NULL DEFAULT NULL";
		$Q[] = "ALTER TABLE `exp_members` CHANGE `msn_im` `msn_im` varchar(50) NULL DEFAULT NULL";
		$Q[] = "ALTER TABLE `exp_members` CHANGE `icq` `icq` varchar(50) NULL DEFAULT NULL";
		$Q[] = "ALTER TABLE `exp_members` CHANGE `bio` `bio` text NULL";
		$Q[] = "ALTER TABLE `exp_members` CHANGE `signature` `signature` text NULL";
		$Q[] = "ALTER TABLE `exp_members` CHANGE `avatar_filename` `avatar_filename` varchar(120) NULL DEFAULT NULL";
		$Q[] = "ALTER TABLE `exp_members` CHANGE `avatar_width` `avatar_width` int(4) unsigned NULL DEFAULT NULL";
		$Q[] = "ALTER TABLE `exp_members` CHANGE `avatar_height` `avatar_height` int(4) unsigned NULL DEFAULT NULL";
		$Q[] = "ALTER TABLE `exp_members` CHANGE `photo_filename` `photo_filename` varchar(120) NULL DEFAULT NULL";
		$Q[] = "ALTER TABLE `exp_members` CHANGE `photo_width` `photo_width` int(4) unsigned NULL DEFAULT NULL";
		$Q[] = "ALTER TABLE `exp_members` CHANGE `photo_height` `photo_height` int(4) unsigned NULL DEFAULT NULL";
		$Q[] = "ALTER TABLE `exp_members` CHANGE `sig_img_filename` `sig_img_filename` varchar(120) NULL DEFAULT NULL";
		$Q[] = "ALTER TABLE `exp_members` CHANGE `sig_img_width` `sig_img_width` int(4) unsigned NULL DEFAULT NULL";
		$Q[] = "ALTER TABLE `exp_members` CHANGE `sig_img_height` `sig_img_height` int(4) unsigned NULL DEFAULT NULL";
		$Q[] = "ALTER TABLE `exp_members` CHANGE `ignore_list` `ignore_list` text NULL";
		$Q[] = "ALTER TABLE `exp_members` CHANGE `cp_theme` `cp_theme` varchar(32) NULL DEFAULT NULL";
		$Q[] = "ALTER TABLE `exp_members` CHANGE `profile_theme` `profile_theme` varchar(32) NULL DEFAULT NULL";
		$Q[] = "ALTER TABLE `exp_members` CHANGE `forum_theme` `forum_theme` varchar(32) NULL DEFAULT NULL";
		$Q[] = "ALTER TABLE `exp_members` CHANGE `tracker` `tracker` text NULL";
		$Q[] = "ALTER TABLE `exp_members` CHANGE `notepad` `notepad` text NULL";
		$Q[] = "ALTER TABLE `exp_members` CHANGE `quick_links` `quick_links` text NULL";
		$Q[] = "ALTER TABLE `exp_members` CHANGE `quick_tabs` `quick_tabs` text NULL";
		$Q[] = "UPDATE exp_members SET quick_tabs = ''";

		$Q[] = "ALTER TABLE `exp_member_groups` ADD `can_access_content` CHAR(1) NOT NULL DEFAULT 'n' AFTER `can_access_cp`";
		$Q[] = "ALTER TABLE `exp_member_groups` ADD `can_access_files` CHAR(1) NOT NULL DEFAULT 'n' AFTER `can_access_edit`";
		$Q[] = "ALTER TABLE `exp_member_groups` ADD `can_access_addons` CHAR(1) NOT NULL DEFAULT 'n' AFTER `can_access_design`";
		$Q[] = "ALTER TABLE `exp_member_groups` MODIFY COLUMN `can_access_modules` CHAR(1) NOT NULL DEFAULT 'n' AFTER `can_access_addons`";
		$Q[] = "ALTER TABLE `exp_member_groups` ADD `can_access_extensions` CHAR(1) NOT NULL DEFAULT 'n' AFTER `can_access_modules`";
		$Q[] = "ALTER TABLE `exp_member_groups` ADD `can_access_accessories` CHAR(1) NOT NULL DEFAULT 'n' AFTER `can_access_extensions`";
		$Q[] = "ALTER TABLE `exp_member_groups` ADD `can_access_plugins` CHAR(1) NOT NULL DEFAULT 'n' AFTER `can_access_accessories`";	  
		$Q[] = "ALTER TABLE `exp_member_groups` ADD `can_access_members` CHAR(1) NOT NULL DEFAULT 'n' AFTER `can_access_plugins`";  
		$Q[] = "ALTER TABLE `exp_member_groups` ADD `can_access_sys_prefs` CHAR(1) NOT NULL DEFAULT 'n' AFTER `can_access_admin`";  
		$Q[] = "ALTER TABLE `exp_member_groups` ADD `can_access_content_prefs` CHAR(1) NOT NULL DEFAULT 'n' AFTER `can_access_sys_prefs`";  
		$Q[] = "ALTER TABLE `exp_member_groups` ADD `can_access_tools` CHAR(1) NOT NULL DEFAULT 'n' AFTER `can_access_content_prefs`";  
		$Q[] = "ALTER TABLE `exp_member_groups` ADD `can_access_utilities` CHAR(1) NOT NULL DEFAULT 'n' AFTER `can_access_comm`";
		$Q[] = "ALTER TABLE `exp_member_groups` ADD `can_access_data` CHAR(1) NOT NULL DEFAULT 'n' AFTER `can_access_utilities`";
		$Q[] = "ALTER TABLE `exp_member_groups` ADD `can_access_logs` CHAR(1) NOT NULL DEFAULT 'n' AFTER `can_access_data`";
		$Q[] = "ALTER TABLE `exp_member_groups` ADD `can_admin_design` CHAR(1) NOT NULL DEFAULT 'n' AFTER `can_admin_weblogs`";	 

		$this->_run_queries('Updating member tables', $Q);

		return 'database_changes_weblog';
	}

	// ------------------------------------------------------------------------ 

	public function database_changes_weblog()
	{
		$this->EE->progress->update_state("Updating weblog tables");

		$has_duplicates = ( ! isset($this->config['table_duplicates'])) ? array() : explode('|', $this->config['table_duplicates']);

		$Q[] = "INSERT INTO `exp_actions` (`class`, `method`) VALUES ('Jquery', 'output_javascript')";

		$Q[] = "UPDATE `exp_templates` SET template_type = 'feed' WHERE template_type = 'rss'";

		// Channel fields can now have content restrictions
		$Q[] = "ALTER TABLE `exp_weblog_fields` ADD COLUMN `field_content_type` VARCHAR(20) NOT NULL default 'any'";

		// get rid of 'blog_encoding from exp_weblogs' - everything's utf-8 now
		$Q[] = "ALTER TABLE `exp_weblogs` DROP COLUMN `blog_encoding`";

		// HTML buttons now have an identifying classname
		$Q[] = "ALTER TABLE `exp_html_buttons` ADD `classname` varchar(20) NULL DEFAULT NULL";

		// The sites table now stores bootstrap file checksums
		$Q[] = "ALTER TABLE `exp_sites` ADD `site_bootstrap_checksums` text NOT NULL";

		// insert default buttons
		include(EE_APPPATH.'config/html_buttons.php');

		// Remove EE 1.6.X default button set
		$Q[] = "DELETE FROM `exp_html_buttons` WHERE `member_id`=0";
		
		$site_query = $this->EE->db->query("SELECT site_id FROM `exp_sites`");
		
		foreach ($site_query->result() as $site)
		{
			// Add in the EE 2 default button set (as determined by expressionengine/config/html_buttons.php)
			$buttoncount = 1;
			
			foreach ($installation_defaults as $button)
			{
				$Q[] = "INSERT INTO exp_html_buttons 
					(site_id, member_id, tag_name, tag_open, tag_close, accesskey, tag_order, tag_row, classname)
					values ({$site->site_id}, '0', '".$predefined_buttons[$button]['tag_name']."', '".$predefined_buttons[$button]['tag_open']."', '".$predefined_buttons[$button]['tag_close']."', '".$predefined_buttons[$button]['accesskey']."', '".$buttoncount++."', '1', '".$predefined_buttons[$button]['classname']."')";
			}
		}

		// Any current HTML buttons need to be changed up now to match the EE2 styles.
		// This means classes added, and tag_names escaped.

		$buttons = array('<b>', '<i>', '<bq>', '<strike>', '<em>', '<ins>', '<ul>', '<ol>', '<li>', '<p>', '<blockquote>', '<h1>', '<h2>', '<h3>', '<h4>', '<h5>', '<h6>');

		foreach ($buttons as $button)
		{
			$Q[] = "UPDATE `exp_html_buttons` SET `classname`='btn_".str_replace(array('<', '>'), array(''), $button)."' WHERE `tag_name`='".$button."'";
			$Q[] = "UPDATE `exp_html_buttons` SET `tag_name`='".str_replace(array('<', '>'), array('&lt;', '&gt;'), $button)."' WHERE `tag_name`='".$button."'";
		}

		// increase path fields to 150 characters
		$Q[] = "ALTER TABLE `exp_upload_prefs` CHANGE `server_path` `server_path` VARCHAR(150) NOT NULL default ''";
		$Q[] = "ALTER TABLE `exp_message_attachments` CHANGE `attachment_location` `attachment_location` VARCHAR(150) NOT NULL default ''";		 

		// drop trackback related fields
		$Q[] = "ALTER TABLE `exp_stats` DROP COLUMN `total_trackbacks`";
		$Q[] = "ALTER TABLE `exp_stats` DROP COLUMN `last_trackback_date`";
		$Q[] = "ALTER TABLE `exp_weblogs` DROP COLUMN `total_trackbacks`";
		$Q[] = "ALTER TABLE `exp_weblogs` DROP COLUMN `last_trackback_date`";
		$Q[] = "ALTER TABLE `exp_weblogs` DROP COLUMN `enable_trackbacks`";
		$Q[] = "ALTER TABLE `exp_weblogs` DROP COLUMN `trackback_use_url_title`";
		$Q[] = "ALTER TABLE `exp_weblogs` DROP COLUMN `trackback_max_hits`";
		$Q[] = "ALTER TABLE `exp_weblogs` DROP COLUMN `trackback_field`";
		$Q[] = "ALTER TABLE `exp_weblogs` DROP COLUMN `deft_trackbacks`";
		$Q[] = "ALTER TABLE `exp_weblogs` DROP COLUMN `trackback_system_enabled`";
		$Q[] = "ALTER TABLE `exp_weblogs` DROP COLUMN `show_trackback_field`";
		$Q[] = "ALTER TABLE `exp_weblogs` DROP COLUMN `trackback_use_captcha`";
		$Q[] = "ALTER TABLE `exp_weblogs` DROP COLUMN `tb_return_url`";
		$Q[] = "ALTER TABLE `exp_weblog_titles` DROP COLUMN `allow_trackbacks`";
		$Q[] = "ALTER TABLE `exp_weblog_titles` DROP COLUMN `trackback_total`";
		$Q[] = "ALTER TABLE `exp_weblog_titles` DROP COLUMN `sent_trackbacks`";
		$Q[] = "ALTER TABLE `exp_weblog_titles` DROP COLUMN `recent_trackback_date`";
		$Q[] = "DROP TABLE IF EXISTS `exp_trackbacks`";

		// Add primary keys as needed for normalization of all tables
		$Q[] = "ALTER TABLE `exp_throttle` ADD COLUMN `throttle_id` int(10) UNSIGNED AUTO_INCREMENT NOT NULL PRIMARY KEY FIRST";
		$Q[] = "ALTER TABLE `exp_stats` ADD COLUMN `stat_id` int(10) UNSIGNED AUTO_INCREMENT NOT NULL PRIMARY KEY FIRST";
		$Q[] = "ALTER TABLE `exp_online_users` ADD COLUMN `online_id` int(10) UNSIGNED AUTO_INCREMENT NOT NULL PRIMARY KEY FIRST";
		$Q[] = "ALTER TABLE `exp_security_hashes` ADD COLUMN `hash_id` int(10) UNSIGNED AUTO_INCREMENT NOT NULL PRIMARY KEY FIRST";
		$Q[] = "ALTER TABLE `exp_password_lockout` ADD COLUMN `lockout_id` int(10) UNSIGNED AUTO_INCREMENT NOT NULL PRIMARY KEY FIRST";
		$Q[] = "ALTER TABLE `exp_reset_password` ADD COLUMN `reset_id` int(10) UNSIGNED AUTO_INCREMENT NOT NULL PRIMARY KEY FIRST";
		$Q[] = "ALTER TABLE `exp_email_cache_mg` DROP KEY `cache_id`";
		$Q[] = "ALTER TABLE `exp_email_cache_mg` ADD PRIMARY KEY `cache_id_group_id` (`cache_id`, `group_id`)";
		$Q[] = "ALTER TABLE `exp_email_cache_ml` DROP KEY `cache_id`";
		$Q[] = "ALTER TABLE `exp_email_cache_ml` ADD PRIMARY KEY `cache_id_list_id` (`cache_id`, `list_id`)";
		$Q[] = "ALTER TABLE `exp_member_homepage` DROP KEY `member_id`";
		$Q[] = "ALTER TABLE `exp_member_homepage` ADD PRIMARY KEY `member_id` (`member_id`)";
		$Q[] = "ALTER TABLE `exp_member_groups` DROP KEY `group_id`";
		$Q[] = "ALTER TABLE `exp_member_groups` DROP KEY `site_id`";
		$Q[] = "ALTER TABLE `exp_member_groups` ADD PRIMARY KEY `group_id_site_id` (`group_id`, `site_id`)";
		$Q[] = "ALTER TABLE `exp_weblog_member_groups` DROP KEY `group_id`";
		$Q[] = "ALTER TABLE `exp_weblog_member_groups` ADD PRIMARY KEY `group_id_weblog_id` (`group_id`, `weblog_id`)";
		$Q[] = "ALTER TABLE `exp_module_member_groups` DROP KEY `group_id`";
		$Q[] = "ALTER TABLE `exp_module_member_groups` ADD PRIMARY KEY `group_id_module_id` (`group_id`, `module_id`)";
		$Q[] = "ALTER TABLE `exp_template_member_groups` DROP KEY `group_id`";
		$Q[] = "ALTER TABLE `exp_template_member_groups` ADD PRIMARY KEY `group_id_template_group_id` (`group_id`, `template_group_id`)";
		$Q[] = "ALTER TABLE `exp_member_data` DROP KEY `member_id`";
		$Q[] = "ALTER TABLE `exp_member_data` ADD PRIMARY KEY `member_id` (`member_id`)";
		$Q[] = "ALTER TABLE `exp_field_formatting` DROP KEY `field_id`";
		$Q[] = "ALTER TABLE `exp_field_formatting` ADD COLUMN `formatting_id` int(10) UNSIGNED AUTO_INCREMENT NOT NULL PRIMARY KEY FIRST";
		$Q[] = "ALTER TABLE `exp_weblog_data` DROP KEY `entry_id`";
		$Q[] = "ALTER TABLE `exp_weblog_data` ADD PRIMARY KEY `entry_id` (`entry_id`)";
		$Q[] = "ALTER TABLE `exp_entry_ping_status` ADD PRIMARY KEY `entry_id_ping_id` (`entry_id`, `ping_id`)";
		$Q[] = "ALTER TABLE `exp_status_no_access` ADD PRIMARY KEY `status_id_member_group` (`status_id`, `member_group`)";

		if ( ! in_array('category_posts', $has_duplicates))
		{
			$Q[] = "ALTER TABLE `exp_category_posts` DROP KEY `entry_id`";
			$Q[] = "ALTER TABLE `exp_category_posts` DROP KEY `cat_id`";
		}

		$Q[] = "ALTER TABLE `exp_category_posts` ADD PRIMARY KEY `entry_id_cat_id` (`entry_id`, `cat_id`)";
		$Q[] = "ALTER TABLE `exp_template_no_access` DROP KEY `template_id`";
		$Q[] = "ALTER TABLE `exp_template_no_access` ADD PRIMARY KEY `template_id_member_group` (`template_id`, `member_group`)";
		$Q[] = "ALTER TABLE `exp_upload_no_access` ADD PRIMARY KEY `upload_id_member_group` (`upload_id`, `member_group`)";

		if ( ! in_array('message_folders', $has_duplicates))
		{
			$Q[] = "ALTER TABLE `exp_message_folders` DROP KEY `member_id`";
		}

		$Q[] = "ALTER TABLE `exp_message_folders` ADD PRIMARY KEY `member_id` (`member_id`)";

		// Add default values for a few columns and switch some to NULL
		$Q[] = "ALTER TABLE `exp_templates` CHANGE `template_data` `template_data` MEDIUMTEXT NULL";
		$Q[] = "ALTER TABLE `exp_templates` CHANGE `template_notes` `template_notes` TEXT NULL";
		$Q[] = "ALTER TABLE `exp_templates` CHANGE `last_author_id` `last_author_id` INT(10) NOT NULL DEFAULT 0";
		$Q[] = "ALTER TABLE `exp_templates` CHANGE `refresh` `refresh` INT(6) UNSIGNED NOT NULL DEFAULT 0";
		$Q[] = "ALTER TABLE `exp_templates` CHANGE `no_auth_bounce` `no_auth_bounce` VARCHAR(50) NOT NULL DEFAULT ''";
		$Q[] = "ALTER TABLE `exp_templates` CHANGE `hits` `hits` INT(10) UNSIGNED NOT NULL DEFAULT 0";
		$Q[] = "ALTER TABLE `exp_member_groups` CHANGE `mbr_delete_notify_emails` `mbr_delete_notify_emails` varchar(255) NULL DEFAULT NULL";
		$Q[] = "ALTER TABLE `exp_weblog_fields` CHANGE `field_pre_field_id` `field_pre_field_id` int(6) unsigned NULL DEFAULT NULL";
		$Q[] = "ALTER TABLE `exp_weblog_titles` CHANGE `recent_comment_date` `recent_comment_date` int(10) NULL DEFAULT NULL";

		// Add moar!
		$Q[] = "ALTER TABLE `exp_sites` CHANGE `site_description` `site_description` TEXT NULL";
		$Q[] = "ALTER TABLE `exp_category_groups` CHANGE `can_edit_categories` `can_edit_categories` TEXT NULL";
		$Q[] = "ALTER TABLE `exp_category_groups` CHANGE `can_delete_categories` `can_delete_categories` TEXT NULL";
		$Q[] = "ALTER TABLE `exp_categories` CHANGE `cat_description` `cat_description` TEXT NULL";
		$Q[] = "ALTER TABLE `exp_categories` CHANGE `cat_image` `cat_image` varchar(120) NULL";
		$Q[] = "ALTER TABLE `exp_upload_prefs` CHANGE `max_size` `max_size` varchar(16) NULL DEFAULT NULL";
		$Q[] = "ALTER TABLE `exp_upload_prefs` CHANGE `max_height` `max_height` varchar(6) NULL DEFAULT NULL";
		$Q[] = "ALTER TABLE `exp_upload_prefs` CHANGE `max_width` `max_width` varchar(6) NULL DEFAULT NULL";
		$Q[] = "ALTER TABLE `exp_upload_prefs` CHANGE `properties` `properties` varchar(120) NULL DEFAULT NULL";
		$Q[] = "ALTER TABLE `exp_upload_prefs` CHANGE `pre_format` `pre_format` varchar(120) NULL DEFAULT NULL";
		$Q[] = "ALTER TABLE `exp_upload_prefs` CHANGE `post_format` `post_format` varchar(120) NULL DEFAULT NULL";
		$Q[] = "ALTER TABLE `exp_upload_prefs` CHANGE `file_properties` `file_properties` varchar(120) NULL DEFAULT NULL";
		$Q[] = "ALTER TABLE `exp_upload_prefs` CHANGE `file_pre_format` `file_pre_format` varchar(120) NULL DEFAULT NULL";
		$Q[] = "ALTER TABLE `exp_upload_prefs` CHANGE `file_post_format` `file_post_format` varchar(120) NULL DEFAULT NULL";

		$Q[] = "ALTER TABLE `exp_weblog_fields` CHANGE `field_instructions` `field_instructions` TEXT NULL";
		$Q[] = "ALTER TABLE `exp_weblog_fields` CHANGE `field_pre_field_id` `field_pre_field_id` int(6) unsigned NULL DEFAULT NULL";
		$Q[] = "ALTER TABLE `exp_weblog_fields` CHANGE `field_maxl` `field_maxl` smallint(3) NULL DEFAULT NULL";

		$Q[] = "ALTER TABLE `exp_weblog_titles` CHANGE `forum_topic_id` `forum_topic_id` int(10) unsigned NULL DEFAULT NULL";
		$Q[] = "ALTER TABLE `exp_weblog_titles` CHANGE `recent_comment_date` `recent_comment_date` int(10) NULL DEFAULT NULL";		  

		$Q[] = "ALTER TABLE `exp_weblogs` CHANGE `cat_group` `cat_group` varchar(225) NULL DEFAULT NULL";
		$Q[] = "ALTER TABLE `exp_weblogs` CHANGE `status_group` `status_group` int(4) unsigned NULL DEFAULT NULL";
		$Q[] = "ALTER TABLE `exp_weblogs` CHANGE `field_group` `field_group` int(4) unsigned NULL DEFAULT NULL";

		$Q[] = "ALTER TABLE `exp_weblogs` CHANGE `search_excerpt` `search_excerpt` int(4) unsigned NULL DEFAULT NULL";
		$Q[] = "ALTER TABLE `exp_weblogs` CHANGE `deft_category` `deft_category` varchar(60) NULL DEFAULT NULL";
		$Q[] = "ALTER TABLE `exp_weblogs` CHANGE `comment_url` `comment_url` varchar(80) NULL DEFAULT NULL";
		$Q[] = "ALTER TABLE `exp_weblogs` CHANGE `comment_max_chars` `comment_max_chars` int(5) unsigned NULL DEFAULT '5000'";
		$Q[] = "ALTER TABLE `exp_weblogs` CHANGE `comment_notify_emails` `comment_notify_emails` varchar(255) NULL DEFAULT NULL";
		$Q[] = "ALTER TABLE `exp_weblogs` CHANGE `search_results_url` `search_results_url` varchar(80) NULL DEFAULT NULL";
		$Q[] = "ALTER TABLE `exp_weblogs` CHANGE `ping_return_url` `ping_return_url` varchar(80) NULL DEFAULT NULL";
		$Q[] = "ALTER TABLE `exp_weblogs` CHANGE `rss_url` `rss_url` varchar(80) NULL DEFAULT NULL";
		$Q[] = "ALTER TABLE `exp_weblogs` DROP COLUMN `enable_qucksave_versioning`";

		// Remove trackback actions
		$Q[] = "DELETE FROM `exp_actions` WHERE `class` = 'Trackback'";
		$Q[] = "DELETE FROM `exp_actions` WHERE `class` = 'Trackback_CP'";

		// Update CP action names
		$query = $this->EE->db->query("SELECT action_id, class FROM exp_actions");

		if ($query->num_rows() > 0)
		{
			foreach ($query->result() as $row)
			{
				if (substr($row->class, -3) == '_CP')
				{
					$Q[] = "UPDATE `exp_actions` SET `class` = '".substr($row->class, 0, -3)."_mcp' WHERE `action_id` = '{$row->action_id}'";
				}
			}
		}

		// Run the queries
		$this->_run_queries('Updating weblog tables', $Q);

		$this->EE->progress->update_state("Installing default Accessories");
		$this->EE->_install_accessories();  

		if ( ! empty($has_duplicates))
		{
			$this->EE->config->_update_config(array(), array('table_duplicates' => ''));
		}

		// weblogs are channels!
		return 'update_custom_fields';
	}

	// ------------------------------------------------------------------------ 

	public function update_custom_fields()
	{
		$this->EE->progress->update_state("Updating custom field tables");
		
		// Update category custom fields to allow null
		$query = $this->EE->db->query("SELECT field_id FROM exp_category_fields");

		if ($query->num_rows() > 0)
		{
			foreach ($query->result() as $row)
			{
				$Q[] = "ALTER TABLE `exp_category_field_data` CHANGE `field_id_{$row->field_id}` `field_id_{$row->field_id}` text NULL";
				$Q[] = "ALTER TABLE `exp_category_field_data` CHANGE `field_ft_{$row->field_id}` `field_ft_{$row->field_id}` varchar(40) NULL DEFAULT 'none'";
			}
		}

		// Update custom fields to allow null
		$query = $this->EE->db->query("SELECT field_id, field_type FROM exp_weblog_fields");

		if ($query->num_rows() > 0)
		{
			foreach ($query->result() as $row)
			{
				if ($row->field_type == 'date' OR $row->field_type == 'rel')
				{
					$Q[] = "ALTER TABLE `exp_weblog_data` CHANGE `field_id_{$row->field_id}` `field_id_{$row->field_id}` int(10) NOT NULL DEFAULT 0";

					if ($row->field_type == 'date')
					{
						$Q[] = "ALTER TABLE `exp_weblog_data` CHANGE `field_dt_{$row->field_id}` `field_dt_{$row->field_id}` varchar(8) NULL";  
					}
				}
				else
				{
					$Q[] = "ALTER TABLE `exp_weblog_data` CHANGE `field_id_{$row->field_id}` `field_id_{$row->field_id}` text NULL";
				}
			}
		}

		$this->_run_queries('Updating custom fields', $Q);

		return 'resync_member_groups';
	}

	// ------------------------------------------------------------------------ 

	public function resync_member_groups()
	{
		$this->EE->progress->update_state("Synchronizing member groups");
		
		//  Update access priveleges for 2.0
		// resync member groups.  In 1.x, a bug existed where deleting a member group would only delete it from the currently logged in site,
		// leaving orphaned member groups in the member groups table.
		$query = $this->EE->db->query("SELECT group_id, site_id, can_access_publish, can_access_edit, can_access_modules, can_admin_utilities, can_admin_members, can_admin_preferences, can_access_admin, can_access_comm FROM exp_member_groups");
		$groups = array();

		foreach ($query->result() as $row)
		{
			$new_privs = '';

			if ($row->can_admin_utilities == 'y')
			{
				$new_privs .= "`can_access_addons` = 'y', `can_access_extensions` = 'y', `can_access_plugins` = 'y', `can_access_tools` = 'y', `can_access_utilities` = 'y', `can_access_data` = 'y', `can_access_logs` = 'y', ";
			}
			elseif ($row->can_access_comm == 'y')
			{
				$new_privs .= "`can_access_tools` = 'y', "; 
			}

			if ($row->can_access_modules == 'y')
			{
				$new_privs .= "`can_access_addons` = 'y', ";
			}

			if ($row->can_access_publish == 'y' OR $row->can_access_edit == 'y')
			{
				$new_privs .= "`can_access_content` = 'y', ";
			}

			if ($row->can_admin_members == 'y')
			{
				$new_privs .= "`can_access_members` = 'y', ";
			}

			if ($row->can_admin_preferences == 'y')
			{
				$new_privs .= "`can_access_sys_prefs` = 'y', ";			 
				$new_privs .= "`can_admin_design` = 'y', "; 
			}

			if ($row->can_access_admin == 'y')
			{
				$new_privs .= "`can_access_content_prefs` = 'y', ";			 
			}

			if ($row->group_id == 1)
			{
				$new_privs .= "`can_access_accessories` = 'y', `can_access_files` = 'y', `can_edit_categories` = 'y', `can_delete_categories` = 'y', ";			 
			}


			if ($new_privs != '')
			{
				$new_privs = substr($new_privs, 0, -2);

				$Q[] = "UPDATE `exp_member_groups` SET {$new_privs} WHERE `group_id` = '{$row->group_id}'";
			}

			$groups[$row->group_id][] = $row->site_id;
		}

		$Q[] = "ALTER TABLE `exp_member_groups` DROP COLUMN `can_admin_preferences`";
		$Q[] = "ALTER TABLE `exp_member_groups` DROP COLUMN `can_admin_utilities`"; 

		$query = $this->EE->db->query("SELECT site_id FROM exp_sites");

		foreach ($query->result() as $row)
		{
			foreach ($groups as $group_id => $group_site_ids)
			{
				if ( ! in_array($row->site_id, $group_site_ids))
				{
					// vanquish!
					$this->EE->db->query("DELETE FROM exp_member_groups WHERE group_id = {$group_id}");
				}
			}
		}

		// Run the queries
		$this->_run_queries('Synchronizing member groups', $Q);

		return 'convert_fresh_variables';
	}

	// ------------------------------------------------------------------------ 

	public function convert_fresh_variables()
	{
		// port over old Fresh Variables to Snippets?
		$this->EE->progress->update_state('Checking for Fresh Variables');
		$this->EE->db->select('settings');
		$this->EE->db->where('class', 'Fresh_variables');
		$query = $this->EE->db->get('extensions', 1);

		if ($query->num_rows() > 0 && $query->row('settings') != '')
		{
			$this->EE->progress->update_state("Converting Fresh Variables");
			
			// Load the string helper
			$this->EE->load->helper('string');

			$snippets = strip_slashes(unserialize($query->row('settings')));

			foreach ($snippets as $site_id => $vars)
			{
				foreach ($vars as $var)
				{
					$this->EE->progress->update_state('Adding Snippet: '.$var['var_name']);
					$data = array(
						'site_id'			=> ($site_id == 'all') ? 0 : $site_id,
						'snippet_name'		=> $var['var_name'],
						'snippet_contents'	=> $var['var_value']
					);

					$this->EE->db->insert('snippets', $data);
				}
			}

			unset($snippets);

			$this->EE->progress->update_state('Deleting Fresh Variables');

			// uninstall Fresh Variables
			$this->EE->db->query("DELETE FROM exp_extensions WHERE class = 'Fresh_variables'");
			$query = $this->EE->db->query("SELECT module_id FROM exp_modules WHERE module_name = 'Fresh_variables'"); 

			$this->EE->db->query("DELETE FROM exp_module_member_groups WHERE module_id = '".$query->row('module_id')."'");
			$this->EE->db->query("DELETE FROM exp_modules WHERE module_name = 'Fresh_variables'");
			$this->EE->db->query("DELETE FROM exp_actions WHERE class = 'Fresh_variables'");
		}

		return 'weblog_terminology_changes';
	}

	// ------------------------------------------------------------------------ 

	public function weblog_terminology_changes()
	{
		$this->EE->progress->update_state("Replacing weblog with channel.");

		$Q[] = "ALTER TABLE `exp_sites` CHANGE `site_weblog_preferences` `site_channel_preferences` TEXT NOT NULL";
		$Q[] = "ALTER TABLE `exp_member_groups` CHANGE `can_admin_weblogs` `can_admin_channels` CHAR(1) NOT NULL DEFAULT 'n'";
		$Q[] = "ALTER TABLE `exp_weblog_member_groups` CHANGE `weblog_id` `channel_id` INT(6) UNSIGNED NOT NULL";
		$Q[] = "ALTER TABLE `exp_weblog_member_groups` RENAME TO `exp_channel_member_groups`";
		$Q[] = "ALTER TABLE `exp_weblogs` CHANGE `weblog_id` `channel_id` int(6) unsigned NOT NULL auto_increment";
		$Q[] = "ALTER TABLE `exp_weblogs` CHANGE `blog_name` `channel_name` varchar(40) NOT NULL";
		$Q[] = "ALTER TABLE `exp_weblogs` CHANGE `blog_title` `channel_title` varchar(100) NOT NULL";
		$Q[] = "ALTER TABLE `exp_weblogs` CHANGE `blog_url` `channel_url` varchar(100) NOT NULL";
		$Q[] = "ALTER TABLE `exp_weblogs` CHANGE `blog_description` `channel_description` varchar(225) NULL DEFAULT NULL";
		$Q[] = "ALTER TABLE `exp_weblogs` CHANGE `blog_lang` `channel_lang` varchar(12) NOT NULL";
		$Q[] = "ALTER TABLE `exp_weblogs` CHANGE `weblog_max_chars` `channel_max_chars` int(5) unsigned NULL DEFAULT NULL";
		$Q[] = "ALTER TABLE `exp_weblogs` CHANGE `weblog_notify` `channel_notify` CHAR(1) NOT NULL default 'n'";
		$Q[] = "ALTER TABLE `exp_weblogs` CHANGE `weblog_require_membership` `channel_require_membership` char(1) NOT NULL default 'y'";
		$Q[] = "ALTER TABLE `exp_weblogs` CHANGE `weblog_html_formatting` `channel_html_formatting` char(4) NOT NULL default 'all'";
		$Q[] = "ALTER TABLE `exp_weblogs` CHANGE `weblog_allow_img_urls` `channel_allow_img_urls` char(1) NOT NULL default 'y'";
		$Q[] = "ALTER TABLE `exp_weblogs` CHANGE `weblog_auto_link_urls` `channel_auto_link_urls` char(1) NOT NULL default 'y'";
		$Q[] = "ALTER TABLE `exp_weblogs` CHANGE `weblog_notify_emails` `channel_notify_emails` varchar(255) NULL DEFAULT NULL";
		$Q[] = "ALTER TABLE `exp_weblogs` RENAME TO `exp_channels`";
		$Q[] = "ALTER TABLE `exp_weblog_titles` CHANGE `weblog_id` `channel_id` int(4) unsigned NOT NULL";
		$Q[] = "ALTER TABLE `exp_weblog_titles` RENAME TO `exp_channel_titles`";
		$Q[] = "ALTER TABLE `exp_entry_versioning` CHANGE `weblog_id` `channel_id` int(4) unsigned NOT NULL";
		$Q[] = "ALTER TABLE `exp_weblog_fields` CHANGE `field_pre_blog_id` `field_pre_channel_id` int(6) unsigned NULL DEFAULT NULL";
		$Q[] = "ALTER TABLE `exp_weblog_fields` CHANGE `field_related_to` `field_related_to` varchar(12) NOT NULL default 'channel'";

		// @todo DROP column field_related_to once gallery is gone
		$Q[] = "UPDATE `exp_weblog_fields` SET `field_related_to` = 'channel' WHERE `field_related_to` = 'blog'";
		$Q[] = "ALTER TABLE `exp_weblog_fields` RENAME TO `exp_channel_fields`";
		$Q[] = "ALTER TABLE `exp_weblog_data` CHANGE `weblog_id` `channel_id` int(4) unsigned NOT NULL";
		$Q[] = "ALTER TABLE `exp_weblog_data` RENAME TO `exp_channel_data`";
		$Q[] = "UPDATE `exp_templates` SET `template_data` = REPLACE(`template_data`, 'weblog:weblog_name', 'channel:channel_name')";
		$Q[] = "UPDATE `exp_templates` SET `template_data` = REPLACE(`template_data`, 'exp:weblog', 'exp:channel')";
		$Q[] = "UPDATE `exp_templates` SET `template_data` = REPLACE(`template_data`, '{assign_variable:', '{preload_replace:')";	// this is necessary before the following query
		$Q[] = "UPDATE `exp_templates` SET `template_data` = REPLACE(`template_data`, '{preload_replace:my_weblog=', '{preload_replace:my_channel=')";
		$Q[] = "UPDATE `exp_templates` SET `template_data` = REPLACE(`template_data`, '{my_weblog}', '{my_channel}')";
		$Q[] = "UPDATE `exp_templates` SET `template_data` = REPLACE(`template_data`, '{weblog}', '{channel}')";
		$Q[] = "UPDATE `exp_templates` SET `template_data` = REPLACE(`template_data`, 'weblog_', 'channel_')";
		$Q[] = "UPDATE `exp_templates` SET `template_data` = REPLACE(`template_data`, '_weblog', '_channel')";
		$Q[] = "UPDATE `exp_templates` SET `template_data` = REPLACE(`template_data`, 'weblog=', 'channel=')";
		$Q[] = "UPDATE `exp_templates` SET `template_data` = REPLACE(`template_data`, '{blog_title}', '{channel_title}')";
		$Q[] = "UPDATE `exp_templates` SET `template_data` = REPLACE(`template_data`, '{blog_description}', '{channel_description}')";
		$Q[] = "UPDATE `exp_templates` SET `template_data` = REPLACE(`template_data`, '{blog_encoding}', '{channel_encoding}')";
		$Q[] = "UPDATE `exp_templates` SET `template_data` = REPLACE(`template_data`, '{blog_lang}', '{channel_lang}')";
		$Q[] = "UPDATE `exp_templates` SET `template_data` = REPLACE(`template_data`, '{blog_url}', '{channel_url}')";
		$Q[] = "UPDATE `exp_modules` SET `module_name` = 'Channel' WHERE `module_name` = 'Weblog'";


		$this->_run_queries('Replacing weblog with channel', $Q);
		
		// Finished!
		return TRUE;
	}
	
	// ------------------------------------------------------------------------
	
	private function _standardize_datetime_queries()
	{
		// @todo - doesn't work for entries made in the DST period opposite of that of
		// when you run this script!!  Blargh!

		/**
		 * What's the Offset, Kenneth?
		 */

		$now = time();

		$new = gmmktime(
			gmdate("H", $now),
			gmdate("i", $now),
			gmdate("s", $now),
			gmdate("m", $now),
			gmdate("d", $now),
			gmdate("Y", $now)
		);

		$old = mktime(
			gmdate("H", $now),
			gmdate("i", $now),
			gmdate("s", $now),
			gmdate("m", $now),
			gmdate("d", $now),
			gmdate("Y", $now)
		);

		$add_time = $new - $old;

		/**
		 * EE's default timestamp fields
		 */

		$tables = $this->EE->db->list_tables(TRUE); 

		// List of known date fields
		$field_list = array(
			'exp_captcha'					=> array('date'),
			'exp_comments'					=> array('comment_date'),
			'exp_cp_log'					=> array('act_date'),
			'exp_email_cache'				=> array('cache_date'),
			'exp_email_console_cache'		=> array('cache_date'),
			'exp_email_tracker'				=> array('email_date'),
			'exp_entry_versioning'			=> array('version_date'),
			'exp_forum_attachments'			=> array('attachment_date'),
			'exp_forum_boards'				=> array('board_install_date'),
			'exp_forum_polls'				=> array('poll_date'),
			'exp_forum_posts'				=> array('post_date', 'post_edit_date'),
			'exp_forum_read_topics'			=> array('last_visit'),
			'exp_forum_search'				=> array('search_date'),
			'exp_forum_subscriptions'		=> array('subscription_date'),
			'exp_forum_topics'				=> array('topic_date', 'last_post_date', 'topic_edit_date'),
			'exp_forums'					=> array('forum_last_post_date'),
			'exp_gallery_categories'		=> array('recent_entry_date', 'recent_comment_date'),
			'exp_gallery_comments'			=> array('comment_date'),
			'exp_gallery_entries'			=> array('entry_date', 'recent_comment_date', 'comment_expiration_date'),
			'exp_mailing_list_queue'		=> array('date'),
			'exp_member_search'				=> array('search_date'),
			'exp_member_bulletin_board'		=> array('bulletin_date', 'bulletin_expires'),
			'exp_members'					=> array('last_view_bulletins', 'last_bulletin_date', 'join_date', 'last_visit', 'last_activity', 'last_entry_date', 'last_forum_post_date', 'last_comment_date', 'last_email_date'),
			'exp_message_attachments'		=> array('attachment_date'),
			'exp_message_copies'			=> array('message_time_read'),
			'exp_message_data'				=> array('message_date'),
			'exp_online_users'				=> array('date'),
			'exp_referrers'					=> array('ref_date'),
			'exp_reset_password'			=> array('date'),
			'exp_revision_tracker'			=> array('item_date'),
			'exp_search'					=> array('search_date'),
			'exp_search_log'				=> array('search_date'),
			'exp_sessions'					=> array('last_activity'),
			'exp_simple_commerce_purchases'	=> array('purchase_date'),
			'exp_stats'						=> array('last_entry_date', 'last_visitor_date', 'most_visitor_date', 'last_cache_clear', 'last_forum_post_date', 'last_comment_date', 'last_trackback_date'),
			'exp_templates'					=> array('edit_date'),
			'exp_throttle'					=> array('last_activity'),
			'exp_trackbacks'				=> array('trackback_date'),
			'exp_updated_site_pings'		=> array('ping_date'),
			'exp_weblog_data'				=> array(),
			'exp_weblog_titles'				=> array('entry_date', 'expiration_date', 'comment_expiration_date', 'recent_comment_date', 'recent_trackback_date'),
			'exp_weblogs'					=> array('last_entry_date', 'last_comment_date', 'last_trackback_date'),
			'exp_wiki_page'					=> array('last_updated'),
			'exp_wiki_revisions'			=> array('revision_date'),
			'exp_wiki_uploads'				=> array('upload_date'),
		);

		// Also find all custom fields that are date fields as well
		$query = $this->EE->db->query("SELECT field_id FROM exp_weblog_fields WHERE field_type = 'date'");

		if ($query->num_rows() > 0)
		{
			foreach($query->result_array() as $row)
			{
				$field_list['exp_weblog_data'][] = 'field_id_'.$row['field_id'];
			}
		}

		$table_keys = array();
		
		// Remove any tables we do not have
		foreach(array_keys($field_list) as $table)
		{
			if ( ! in_array($table, $tables))
			{
				unset($field_list[$table]);
			}
		}
		
		// Get a list of our timestamp fields
		// Use some logic to determine 3rd party
		foreach(array_keys($field_list) as $table)
		{
			$query = $this->EE->db->query("SHOW FIELDS FROM `".$this->EE->db->escape_str($table)."`");
		
			if ($query->num_rows() > 0)
			{
				foreach($query->result_array() as $row)
				{
					if (strtolower($row['Key']) == 'pri')
					{
						$table_keys[$table] = $row['Field'];
					}
				}
			}
		}

		// Perform the Updates

		$conversion_queries = array();
		
		foreach($field_list as $table => $fields)
		{
			$table = $this->EE->db->escape_str($table);

			foreach($fields as $field)
			{
				$field = $this->EE->db->escape_str($field);

				// Compensate for 1.x's $LOC->now DST behavior by adding an hour
				// to all dates that the server considers to have been in DST

				if (isset($table_keys[$table]))
				{
					$count = $this->EE->db->count_all($table);

					// Split up into 50,000 records per update so we don't
					// run mysql into the ground
					
					for($i = 0; $i <= $count; $i = $i + 50000)
					{
						$this->EE->progress->update_state("Searching `{$table}.{$field}` for DST discrepancies ({$i} / {$count})");

						$query = $this->EE->db->query("SELECT `{$field}`, `".$this->EE->db->escape_str($table_keys[$table])."`
														FROM `{$table}` LIMIT {$i}, 50000");

						// check the field value to see if the record needs to be updated,
						// if so we add that row's primary key to $dst_dates
						if ($query->num_rows() > 0)
						{
							$dst_dates = array();

							foreach ($query->result_array() as $row)
							{
								if (date('I', $row[$field]) == 1)
								{
									$dst_dates[] = $row[$table_keys[$table]];
								}
							}

							$query->free_result();

							if ( ! empty($dst_dates))
							{
								$tot = count($dst_dates);
								$this->EE->progress->update_state("Generating queries to compensate for DST discrepancies in `{$table}` ({$tot} records)");

								// add one hour to the field we're converting, for all the
								// rows we gathered above ($dst_dates == array of primary keys)
								
								$conversion_queries[] = "UPDATE `{$table}` SET `{$field}` = `{$field}` + 3600
									WHERE `".$this->EE->db->escape_str($table_keys[$table])."` IN ('".implode("','", $dst_dates)."');";
							}
						}
					}
				}

				// add the offset, which may be a negative number
				$conversion_queries[] = "UPDATE `{$table}` SET `{$field}` = `{$field}` + {$add_time} WHERE `{$field}` != 0;";
			}
		}
		
		return $conversion_queries;
	}

	// ------------------------------------------------------------------------
	
	private function _run_queries($summary = 'Creating and updating database tables', $queries = array())
	{
		$count = count($queries);
		
		foreach ($queries as $num => $sql)
		{
			$this->EE->progress->update_state("{$summary} (Query {$num} of {$count})");

			$this->EE->db->query($sql);
		}
	}

	// ------------------------------------------------------------------------

	private function _dupe_check()
	{
		$has_duplicates = array();

		// Check whether we need to run duplicate record clean up
		$query = $this->EE->db->query("SELECT `upload_id`, `member_group`, count(`member_group`) FROM `exp_upload_no_access` GROUP BY `upload_id`, `member_group` HAVING COUNT(`member_group`) > 1");

		if ($query->num_rows() > 0)
		{
			$has_duplicates[] = 'upload_no_access';
		}

		$query = $this->EE->db->query("SELECT `member_id`, count(`member_id`) FROM `exp_message_folders` GROUP BY `member_id` HAVING COUNT(`member_id`) > 1");

		if ($query->num_rows() > 0)
		{
			$has_duplicates[] = 'message_folders';
		}

		$query = $this->EE->db->query("SELECT `entry_id`, `cat_id`, count(`cat_id`) FROM `exp_category_posts` GROUP BY `entry_id`, `cat_id` HAVING count(`cat_id`) > 1");

		if ($query->num_rows() > 0)
		{
			$has_duplicates[] = 'category_posts';
		}

		if ( ! empty($has_duplicates))
		{
			$this->EE->config->_update_config(array('table_duplicates' => implode('|', $has_duplicates)));
		}

		return $has_duplicates;
	}

	// --------------------------------------------------------------------
	
	/**
	 * Fetch DB Size
	 *
	 * Returns the size of the db, in megabytes
	 *
	 * @access	private
	 * @return	string
	 */
	private function _fetch_db_size()
	{
		// db records and size
		$query = $this->EE->db->query("SHOW TABLE STATUS FROM `{$this->EE->db->database}`");

		$totsize = 0;
		$records = 0;

		$prefix_len = strlen($this->EE->db->dbprefix);
		
		foreach ($query->result_array() as $row)
		{
			if (strncmp($row['Name'], $this->EE->db->dbprefix, $prefix_len) != 0)
			{
				continue;
			}
			
			$totsize += $row['Data_length'] + $row['Index_length'];
		}
		
		return round($totsize / 1048576);
	}

	// --------------------------------------------------------------------

}
/* END CLASS */

/* End of file ud_200.php */
/* Location: ./system/expressionengine/installer/updates/ud_200.php */

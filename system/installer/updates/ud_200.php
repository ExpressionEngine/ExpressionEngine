<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
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
		if ( ! @include(ee()->config->config_path))
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

		ee()->load->library('progress');

		$this->config =& $config;

		// truncate some tables
		$trunc = array('captcha', 'sessions', 'security_hashes', 'search');

		foreach ($trunc as $table_name)
		{
			ee()->db->truncate($table_name);
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

 		if (ee()->input->get('language') != FALSE && ee()->input->get('language') != '')
		{
			$this->mylang = ee()->input->get_post('language');
		}

		if (ee()->input->get_post('templates') == 'ignore')
		{
			ee()->config->_update_config(array('ignore_templates' => 'y'));
			$ignore = 'y';
		}
		elseif (ee()->input->get_post('templates') == 'manual')
		{
			ee()->config->_update_config(array('manual_templates_move' => 'y'));
			$manual_move = 'y';
		}

		// turn off extensions
		ee()->db->update('extensions', array('enabled' => 'n'));

		$this->_update_site_prefs($ignore, $manual_move);

		// step 1, utf8 conversion
		return ($this->large_db) ? 'large_db_check' : 'convert_db_to_utf8';
	}

	// --------------------------------------------------------------------

	private function _update_site_prefs($ignore, $manual_move)
	{
		// Load the string helper
		ee()->load->helper('string');

		// Change site_preferences column to medium text
		// We do this in 2.5.3 already.  Adding here to prevent truncating the
		// site_preferences data and subsequently generating an unserialize
		// error.  base64 encoding takes about 33% more space, so this should
		// eliminate that possibility

		$this->_change_site_preferences_column_type();

		$query = ee()->db->query("SELECT es.* FROM exp_sites AS es");

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

			ee()->db->query(ee()->db->update_string('exp_sites', $row, "site_id = '".ee()->db->escape_str($row['site_id'])."'"));
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
			$retry = anchor('C=wizard&M=do_update&agree=yes&ajax_progress=yes&language='.$this->mylang.'&templates=manual', ee()->lang->line('template_retry'));
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
				show_error(sprintf(ee()->lang->line('template_move_errors'), $retry));
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
			ee()->db->select('templates.template_id, templates.template_name,
									templates.template_data, template_groups.group_name');
			ee()->db->where('save_template_file', 'y');
			ee()->db->where('template_groups.site_id', $site['site_id']);
			ee()->db->join('template_groups', 'template_groups.group_id = templates.group_id');
			$query = ee()->db->get('templates');

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

			ee()->progress->update_state(ee()->lang->line('updating_templates_as_files'));

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

				$ignore = anchor('C=wizard&M=do_update&agree=yes&ajax_progress=yes&language='.$this->mylang.'&templates=ignore', ee()->lang->line('template_ignore'));
				$retry = anchor('C=wizard&M=do_update&agree=yes&ajax_progress=yes&language='.$this->mylang, ee()->lang->line('template_retry'));

				if ( ! empty($old_template_upload_errors))
				{
					$folder_error = '<ul>';

					foreach ($old_template_upload_errors as $key => $val)
					{
						$folder_error .= '<li>'.$val.'</li>';
					}

					$folder_error .= '</ul>';

					$folder_error_str = ee()->lang->line('template_folders_not_located').$folder_error.ee()->lang->line('template_folders_not_located_instr');
				}

				if ( ! empty($template_errors))
				{
					$template_error_str = '<br /><br />'.ee()->lang->line('template_files_not_located');

					$template_error_str .= '<ul>';

					foreach ($template_errors as $key => $val)
					{
						$template_error_str .= '<li>'.$val.'</li>';
					}

					$template_error_str .= '</ul>';
				}

				$template_error_explain = sprintf(ee()->lang->line('template_missing_explain_retry'), $retry);
				$template_error_explain .= sprintf(ee()->lang->line('template_missing_explain_ignore'), $ignore);
				show_error($folder_error_str.$template_error_str.$template_error_explain);
			}

			foreach ($templates_to_move as $key => $val)
			{
				$one_six_file = read_file($template_path.$val->group_name.'/'.$val->template_name.EXT);

				if ($one_six_file === FALSE)
				{
					continue;
				}

				ee()->db->where('template_id', $val->template_id);
				ee()->db->update('templates', array('template_data' => $one_six_file));

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
			$retry = anchor('C=wizard&M=do_update&agree=yes&ajax_progress=yes&language='.$this->mylang.'&templates=manual', ee()->lang->line('template_retry'));
			show_error(sprintf(ee()->lang->line('template_move_errors'), $retry));
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
		ee()->db->save_queries = FALSE;

		ee()->progress->update_state('Converting Database Tables to UTF-8');

		// make sure STRICT MODEs aren't in use, at least on servers that don't default to that
		ee()->db->query('SET SESSION sql_mode=""');

		$tables = ee()->db->list_tables(TRUE); // TRUE prefix limit, only operate on EE tables
		$batch = 100;

		foreach ($tables as $table)
		{
			$progress	= "Converting Database Table {$table}: %s";
			$count		= ee()->db->count_all($table);
			$offset	 = 0;

			if ($count > 0)
			{
				for ($i = 0; $i < $count; $i = $i + $batch)
				{
					ee()->progress->update_state(str_replace('%s', "{$offset} of {$count} queries", $progress));

					// set charset to latin1 to read 1.x's written values properly
					ee()->db->db_set_charset('latin1', 'latin1_swedish_ci');
					$query = ee()->db->query("SELECT * FROM {$table} LIMIT $offset, $batch");
					$data = $query->result_array();
					$query->free_result();

					// set charset to utf8 to write them back to the database properly
					ee()->db->db_set_charset('utf8', 'utf8_general_ci');

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
							ee()->db->where($where);
							ee()->db->update($table, $row, $where);
						}
					}

					$offset = $offset + $batch;
				}
			}

			// finally, set the table's charset and collation in MySQL to utf8
			ee()->db->query("ALTER TABLE `{$table}` CONVERT TO CHARACTER SET utf8 COLLATE utf8_general_ci");
		}

		// And update the database to use utf8 in the future
		ee()->db->query("ALTER DATABASE `".ee()->db->database."` CHARACTER SET utf8 COLLATE utf8_general_ci;");

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
		if ( ! ee()->db->table_exists('large_db_update_completed'))
		{
			return $this->generate_queries();
		}

		// This table is only used as an indicator
		ee()->load->dbforge();
		ee()->dbforge->drop_table('large_db_update_completed');

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
		foreach (ee()->db->list_tables(TRUE) as $table)
		{
			$queries[] = "ALTER TABLE `{$table}` CONVERT TO CHARACTER SET utf8 COLLATE utf8_general_ci;";
		}

		$queries[] = "ALTER DATABASE `".ee()->db->database."` CHARACTER SET utf8 COLLATE utf8_general_ci;";


		// Lastly, create a table to indicate a successful update
		$queries[] = "CREATE TABLE ".ee()->db->dbprefix."large_db_update_completed(`id` int);";


		// Write bash file
		ee()->progress->update_state('Imploding queries.');


		$queries = implode("\n", $queries);	// @todo ensure semicolons?


		$tables = implode(' ', ee()->db->list_tables(TRUE));
		$password_parameter = (ee()->db->password != '') ? '-p'.ee()->db->password : '';

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

		ee()->progress->update_state('Writing large db update file.');

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
			ee()->config->_update_config(array(), array('trackbacks_to_comments' => '', 'archive_trackbacks' => ''));

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
		$t_query = ee()->db->get('trackbacks');

		if ($t_query->num_rows() == 0)
		{
			// Whee - that was easy, remove config keys
			ee()->config->_update_config(array(), array('trackbacks_to_comments' => '', 'archive_trackbacks' => '', 'trackback_zip_path' => ''));
			return $next_step;
		}

		if (isset($this->config['trackbacks_to_comments']) && $this->config['trackbacks_to_comments'] == 'y')
		{
			ee()->progress->update_state('Converting Trackbacks to Comments');

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

			ee()->progress->update_state('Recounting Comments');

			// Update entry comment totals
			foreach($entry_count as $entry_id => $add)
			{
				ee()->db->set('comment_total', 'comment_total + '.$add, FALSE);
				ee()->db->where('entry_id', $entry_id);

				ee()->db->update('weblog_titles');
			}

			// Update weblog comment totals
			foreach($weblogs as $weblog_id)
			{
				$query = ee()->db->query("SELECT COUNT(comment_id) AS count FROM exp_comments WHERE status = 'o' AND weblog_id = '$weblog_id'");
				$total = $query->row('count');

				$query = ee()->db->query("SELECT last_comment_date, site_id FROM exp_weblogs WHERE weblog_id = '$weblog_id'");
				$date = ($newtime > $query->row('last_comment_date') ) ? $newtime : $query->row('last_comment_date');

				ee()->db->query("UPDATE exp_weblogs SET total_comments = '$total', last_comment_date = '$date' WHERE weblog_id = '$weblog_id'");
			}

			ee()->db->insert_batch('comments', $data);
		}

		if (isset($this->config['archive_trackbacks']) && $this->config['archive_trackbacks'] == 'y')
		{
			ee()->progress->update_state('Backing up Trackbacks');

			// Dump the whole lot into xml files, zip it up, and save it to disk

			ee()->load->library('zip');
			ee()->load->dbutil();

			ee()->zip->add_data('exp_trackbacks.xml', ee()->dbutil->xml_from_result($t_query));

			$query = ee()->db->get_where('specialty_templates', array('template_name' => 'admin_notify_trackback'));
			if ($query->num_rows() > 0)
			{
				ee()->zip->add_data('exp_specialty_templates.xml', ee()->dbutil->xml_from_result($query));
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
				ee()->db->select($fields);
				$query = ee()->db->get($table);

				if ($query->num_rows() > 0)
				{
					ee()->zip->add_data('exp_'.$table.'.xml', ee()->dbutil->xml_from_result($query));
				}
			}

			ee()->zip->archive($this->config['trackback_zip_path']);
		}

		// Remove temporary keys
		ee()->config->_update_config(array(), array('trackbacks_to_comments' => '', 'archive_trackbacks' => '', 'trackback_zip_path' => ''));

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
		ee()->progress->update_state("Creating new database tables");

		// Add exp_snippets table
		ee()->dbforge->add_field(
			array(
				'snippet_id'		=> array('type' => 'int',		'constraint' => 10,	'unsigned' => TRUE,	'null' => FALSE,	'auto_increment' => TRUE),
				'site_id'			=> array('type' => 'int',		'constraint' => 4,	'null' => FALSE ),
				'snippet_name'		=> array('type' => 'varchar',	'constraint' => 75,	'null' => FALSE ),
				'snippet_contents'	=> array('type' => 'text',		'null' => TRUE)
			)
		);

		ee()->dbforge->add_key('snippet_id', TRUE);
		ee()->dbforge->add_key('site_id');
		ee()->smartforge->create_table('snippets');


		// Add exp_accessories table
		ee()->dbforge->add_field(
			array(
				'accessory_id'		=> array('type' => 'int',		'constraint' => 10,	'unsigned' => TRUE,	'null' => FALSE,	'auto_increment' => TRUE),
				'class'				=> array('type' => 'varchar',	'constraint' => 75,	'null' => FALSE,	'default' => ''),
				'member_groups'		=> array('type' => 'varchar',	'constraint' => 50,	'null' => FALSE,	'default' => 'all'),
				'controllers'		=> array('type' => 'text',		'null' => TRUE ),
				'accessory_version'	=> array('type' => 'varchar',	'constraint' => 12,	'null' => FALSE),
			)
		);

		ee()->dbforge->add_key('accessory_id', TRUE);
		ee()->smartforge->create_table('accessories');


		// Layout Publish
		// Custom layout for for the publish page.
		// Add layout_publish table
		ee()->dbforge->add_field(
			array(
				'layout_id'		=> array('type' => 'int',	'constraint' => 10,	'unsigned' => TRUE,	'null' => FALSE,	'auto_increment' => TRUE),
				'site_id'		=> array('type' => 'int',	'constraint' => 4,	'unsigned' => TRUE,	'null' => FALSE,	'default' => 1),
				'member_group'	=> array('type' => 'int',	'constraint' => 4,	'unsigned' => TRUE,	'null' => FALSE,	'default' => 0),
				'channel_id'	=> array('type' => 'int',	'constraint' => 4,	'unsigned' => TRUE,	'null' => FALSE,	'default' => 0),
				'field_layout'	=> array('type' => 'text')
			)
		);

		ee()->dbforge->add_key('layout_id', TRUE);
		ee()->dbforge->add_key('site_id');
		ee()->dbforge->add_key('member_group');
		ee()->dbforge->add_key('channel_id');
		ee()->smartforge->create_table('layout_publish');

		// CP Search Index
		// (Can't use SmartForge because of FULLTEXT and ENGINE)
		ee()->db->query(
				"CREATE TABLE IF NOT EXISTS `exp_cp_search_index` (
					`search_id` int(10) UNSIGNED NOT NULL auto_increment,
					`controller` varchar(20) default NULL,
					`method` varchar(50) default NULL,
					`language` varchar(20) default NULL,
					`access` varchar(50) default NULL,
					`keywords` text,
					PRIMARY KEY `search_id` (`search_id`),
					FULLTEXT(`keywords`)
			) ENGINE=MyISAM "
		);

		// Channel Titles Autosave
		// Used for the autosave functionality
		// Add exp_channel_entries_autosave table
		ee()->dbforge->add_field(
			array(
				'entry_id'					=> array('type' => 'int',		'constraint' => 10,		'unsigned' => TRUE,	'null' => FALSE,	'auto_increment' => TRUE),
				'original_entry_id'			=> array('type' => 'int',		'constraint' => 10,		'unsigned' => TRUE,	'null' => FALSE),
				'site_id'					=> array('type' => 'int',		'constraint' => 4,		'unsigned' => TRUE,	'null' => FALSE,	'default' => 1),
				'channel_id'				=> array('type' => 'int',		'constraint' => 4,		'unsigned' => TRUE,	'null' => FALSE),
				'author_id'					=> array('type' => 'int',		'constraint' => 10,		'unsigned' => TRUE,	'null' => FALSE,	'default' => 0),
				'forum_topic_id'			=> array('type' => 'int',		'constraint' => 10,		'unsigned' => TRUE,	'null' => TRUE),
				'ip_address'				=> array('type' => 'varchar',	'constraint' => 16,		'null' => FALSE),
				'title'						=> array('type' => 'varchar',	'constraint' => 100,	'null' => FALSE),
				'url_title'					=> array('type' => 'varchar',	'constraint' => 75,		'null' => FALSE),
				'status'					=> array('type' => 'varchar',	'constraint' => 50,		'null' => FALSE),
				'versioning_enabled'		=> array('type' => 'char',		'constraint' => 1,		'null' => FALSE,	'default' => 'n'),
				'view_count_one'			=> array('type' => 'int',		'constraint' => 10,		'unsigned' => TRUE,	'null' => FALSE,	'default' => 0),
				'view_count_two'			=> array('type' => 'int',		'constraint' => 10,		'unsigned' => TRUE,	'null' => FALSE,	'default' => 0),
				'view_count_three'			=> array('type' => 'int',		'constraint' => 10,		'unsigned' => TRUE,	'null' => FALSE,	'default' => 0),
				'view_count_four'			=> array('type' => 'int',		'constraint' => 10,		'unsigned' => TRUE,	'null' => FALSE,	'default' => 0),
				'allow_comments'			=> array('type' => 'varchar',	'constraint' => 1,		'null' => FALSE,	'default' => 'y'),
				'sticky'					=> array('type' => 'varchar',	'constraint' => 1,		'null' => FALSE,	'default' => 'n'),
				'entry_date'				=> array('type' => 'int',		'constraint' => 10,		'null' => FALSE),
				'dst_enabled'				=> array('type' => 'varchar',	'constraint' => 1,		'null' => FALSE,	'default' => 'n',
				),
				'year'						=> array('type' => 'char',		'constraint' => 4,		'null' => FALSE),
				'month'						=> array('type' => 'char',		'constraint' => 2,		'null' => FALSE),
				'day'						=> array('type' => 'char',		'constraint' => 3,		'null' => FALSE),
				'expiration_date'			=> array('type' => 'int',		'constraint' => 10,		'null' => FALSE,	'default' => 0),
				'comment_expiration_date'	=> array('type' => 'int',		'constraint' => 10,		'null' => FALSE,	'default' => 0),
				'edit_date'					=> array('type' => 'bigint',	'constraint' => 14),
				'recent_comment_date'		=> array('type' => 'int',		'constraint' => 10,		'null' => TRUE,		'default' => TRUE),
				'comment_total'				=> array('type' => 'int',		'constraint' => 4,		'unsigned' => TRUE,	'null' => FALSE,	'default' => 0),
				'entry_data'				=> array('type' => 'text',		'null' => TRUE),
			)
		);

		ee()->dbforge->add_key('entry_id', TRUE);
		ee()->dbforge->add_key('channel_id');
		ee()->dbforge->add_key('author_id');
		ee()->dbforge->add_key('url_title');
		ee()->dbforge->add_key('status');
		ee()->dbforge->add_key('entry_date');
		ee()->dbforge->add_key('expiration_date');
		ee()->dbforge->add_key('site_id');
		ee()->smartforge->create_table('channel_entries_autosave');

		return 'database_changes_members';
	}

	// ------------------------------------------------------------------------

	public function database_changes_members()
	{
		ee()->progress->update_state("Updating member tables");

		// Update members table: parse_smileys and crypt_key
		$add_columns = array(
			array(
				'field'			=> array('parse_smileys'	=> array('type' => 'char',		'constraint' => 1,	'null' => FALSE,	'default' => 'y')),
				'after_field'	=> 'display_signatures',
			),
			array(
				'field'			=> array('crypt_key'		=> array('type' => 'varchar',	'constraint' => 40,	'null' => TRUE)),
				'after_field'	=> 'unique_id',
			)
		);

		foreach ($add_columns as $v)
		{
			ee()->smartforge->add_column('members', $v['field'], $v['after_field']);
		}

		// drop user weblog related fields
		ee()->smartforge->drop_column('members', 'weblog_id');
		ee()->smartforge->drop_column('members', 'tmpl_group_id');
		ee()->smartforge->drop_column('members', 'upload_id');
		ee()->smartforge->drop_column('template_groups', 'is_user_blog');
		ee()->smartforge->drop_column('weblogs', 'is_user_blog');
		ee()->smartforge->drop_column('global_variables', 'user_blog_id');
		ee()->smartforge->drop_column('online_users', 'weblog_id');

		// members table default tweaks
		$fields = array(
			'authcode'			=> array('type' => 'varchar',	'constraint' => 10,		'null' => TRUE),
			'url'				=> array('type' => 'varchar',	'constraint' => 150,	'null' => TRUE),
			'location'			=> array('type' => 'varchar',	'constraint' => 50,		'null' => TRUE),
			'occupation'		=> array('type' => 'varchar',	'constraint' => 80,		'null' => TRUE),
			'interests'			=> array('type' => 'varchar',	'constraint' => 120,	'null' => TRUE),
			'bday_d'			=> array('type' => 'int',		'constraint' => 2,		'null' => TRUE),
			'bday_m'			=> array('type' => 'int',		'constraint' => 2,		'null' => TRUE),
			'bday_y'			=> array('type' => 'int',		'constraint' => 4,		'null' => TRUE),
			'aol_im'			=> array('type' => 'varchar',	'constraint' => 50,		'null' => TRUE),
			'yahoo_im'			=> array('type' => 'varchar',	'constraint' => 50,		'null' => TRUE),
			'msn_im'			=> array('type' => 'varchar',	'constraint' => 50,		'null' => TRUE),
			'icq'				=> array('type' => 'varchar',	'constraint' => 50,		'null' => TRUE),
			'bio'				=> array('type' => 'text',		'null' => TRUE),
			'signature'			=> array('type' => 'text',		'null' => TRUE),
			'avatar_filename'	=> array('type' => 'varchar',	'constraint' => 120,	'null' => TRUE),
			'avatar_width'		=> array('type' => 'int',		'constraint' => 4,		'unsigned' => TRUE,	'null' => TRUE),
			'avatar_height'		=> array('type' => 'int',		'constraint' => 4,		'unsigned' => TRUE,	'null' => TRUE),
			'photo_filename'	=> array('type' => 'varchar',	'constraint' => 120,	'null' => TRUE),
			'photo_width'		=> array('type' => 'int',		'constraint' => 4,		'unsigned' => TRUE,	'null' => TRUE),
			'photo_height'		=> array('type' => 'int',		'constraint' => 4,		'unsigned' => TRUE,	'null' => TRUE),
			'sig_img_filename'	=> array('type' => 'varchar',	'constraint' => 120,	'null' => TRUE),
			'sig_img_width'		=> array('type' => 'int',		'constraint' => 4,		'unsigned' => TRUE,	'null' => TRUE),
			'sig_img_height'	=> array('type' => 'int',		'constraint' => 4,		'unsigned' => TRUE,	'null' => TRUE),
			'ignore_list'		=> array('type' => 'text',		'null' => TRUE),
			'cp_theme'			=> array('type' => 'varchar',	'constraint' => 32,		'null' => TRUE),
			'profile_theme'		=> array('type' => 'varchar',	'constraint' => 32,		'null' => TRUE),
			'forum_theme'		=> array('type' => 'varchar',	'constraint' => 32,		'null' => TRUE),
			'tracker'			=> array('type' => 'text',		'null' => TRUE),
			'notepad'			=> array('type' => 'text',		'null' => TRUE),
			'quick_links'		=> array('type' => 'text',		'null' => TRUE),
			'quick_tabs'		=> array('type' => 'text',		'null' => TRUE)
		);

		ee()->smartforge->modify_column('members', $fields);

		ee()->db->set('quick_tabs', '');
		ee()->db->update('members');

		$add_columns = array(
			array(
				'field'			=> array('can_access_content'	=> array('type' => 'char',	'constraint' => 1,	'null' => FALSE,	'default' => 'n')),
				'after_field'	=> 'can_access_cp',
			),
			array(
				'field'			=> array('can_access_files'		=> array('type' => 'char',	'constraint' => 1,	'null' => FALSE,	'default' => 'n')),
				'after_field'	=> 'can_access_edit',
			),
			array(
				'field'			=> array('can_access_addons'	=> array('type' => 'char',	'constraint' => 1,	'null' => FALSE,	'default' => 'n')),
				'after_field'	=> 'can_access_design',
			)
		);

		foreach ($add_columns as $v)
		{
			ee()->smartforge->add_column('member_groups', $v['field'], $v['after_field']);
		}

		ee()->db->query("ALTER TABLE `exp_member_groups` MODIFY COLUMN `can_access_modules` CHAR(1) NOT NULL DEFAULT 'n' AFTER `can_access_addons`");

		$add_columns = array(
			array(
				'field'			=> array('can_access_extensions'	=> array('type' => 'char',	'constraint' => 1,	'null' => FALSE,	'default' => 'n')),
				'after_field'	=> 'can_access_modules',
			),
			array(
				'field'			=> array('can_access_accessories'	=> array('type' => 'char',	'constraint' => 1,	'null' => FALSE,	'default' => 'n')),
				'after_field'	=> 'can_access_extensions',
			),
			array(
				'field'			=> array('can_access_plugins'		=> array('type' => 'char',	'constraint' => 1,	'null' => FALSE,	'default' => 'n')),
				'after_field'	=> 'can_access_accessories',
			),
			array(
				'field'			=> array('can_access_members'		=> array('type' => 'char',	'constraint' => 1,	'null' => FALSE,	'default' => 'n')),
				'after_field'	=> 'can_access_plugins',
			),
			array(
				'field'			=> array('can_access_sys_prefs'		=> array('type' => 'char',	'constraint' => 1,	'null' => FALSE,	'default' => 'n')),
				'after_field'	=> 'can_access_admin',
			),
			array(
				'field'			=> array('can_access_content_prefs'	=> array('type' => 'char',	'constraint' => 1,	'null' => FALSE,	'default' => 'n')),
				'after_field'	=> 'can_access_sys_prefs',
			),
			array(
				'field'			=> array('can_access_tools'			=> array('type' => 'char',	'constraint' => 1,	'null' => FALSE,	'default' => 'n')),
				'after_field'	=> 'can_access_content_prefs',
			),
			array(
				'field'			=> array('can_access_utilities'		=> array('type' => 'char',	'constraint' => 1,	'null' => FALSE,	'default' => 'n')),
				'after_field'	=> 'can_access_comm',
			),
			array(
				'field'			=> array('can_access_data'			=> array('type' => 'char',	'constraint' => 1,	'null' => FALSE,	'default' => 'n')),
				'after_field'	=> 'can_access_utilities',
			),
			array(
				'field'			=> array('can_access_logs'			=> array('type' => 'char',	'constraint' => 1,	'null' => FALSE,	'default' => 'n')),
				'after_field'	=> 'can_access_data',
			),
			array(
				'field'			=> array('can_admin_design'			=> array('type' => 'char',	'constraint' => 1,	'null' => FALSE,	'default' => 'n')),
				'after_field'	=> 'can_admin_weblogs',
			)
		);

		foreach ($add_columns as $v)
		{
			ee()->smartforge->add_column('member_groups', $v['field'], $v['after_field']);
		}

		return 'database_changes_weblog';
	}

	// ------------------------------------------------------------------------

	public function database_changes_weblog()
	{
		ee()->progress->update_state("Updating weblog tables");

		$has_duplicates = ( ! isset($this->config['table_duplicates'])) ? array() : explode('|', $this->config['table_duplicates']);

		// If there is no action id, add it
		$values = array(
			'class'		=> 'Jquery',
			'method'	=> 'output_javascript'
		);

		ee()->smartforge->insert_set('actions', $values, $values);


		$data = array(
			'template_type'		=> 'feed'
		);

		ee()->db->where('template_type', 'rss');
		ee()->db->update('templates', $data);

		// Channel fields can now have content restrictions
		ee()->smartforge->add_column(
			'weblog_fields',
			array(
				'field_content_type' => array(
					'type'			=> 'varchar',
					'constraint'	=> 20,
					'null'			=> FALSE,
					'default'		=> 'any'
				)
			)
		);

		// get rid of 'blog_encoding from exp_weblogs' - everything's utf-8 now
		ee()->smartforge->drop_column('weblogs', 'blog_encoding');

		// HTML buttons now have an identifying classname
		ee()->smartforge->add_column(
			'html_buttons',
			array(
				'classname' => array(
					'type'			=> 'varchar',
					'constraint'	=> 20,
					'null'			=> TRUE
				)
			)
		);

		// The sites table now stores bootstrap file checksums
		ee()->smartforge->add_column(
			'sites',
			array(
				'site_bootstrap_checksums' => array(
					'type'	=> 'text',
					'null'	=> FALSE
				)
			)
		);

		// insert default buttons
		include(EE_APPPATH.'config/html_buttons.php');

		// Remove EE 1.6.X default button set
		ee()->db->delete('html_buttons', array('member_id' => 0));

		$site_query = ee()->db->query("SELECT site_id FROM `exp_sites`");

		$Q = array();

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

		// Run the queries
		$this->_run_queries('Updating weblog tables', $Q);

		// increase path fields to 150 characters
		ee()->smartforge->modify_column(
			'upload_prefs',
			array(
				'server_path' => array(
					'name'			=> 'server_path',
					'type'			=> 'varchar',
					'constraint'	=> 150,
					'null'			=> FALSE,
					'default'		=> ''
				)
			)
		);

		ee()->smartforge->modify_column(
			'message_attachments',
			array(
				'attachment_location' => array(
					'name'			=> 'attachment_location',
					'type'			=> 'varchar',
					'constraint'	=> 150,
					'null'			=> FALSE,
					'default'		=> ''
				)
			)
		);

		// drop trackback related fields
		ee()->smartforge->drop_column('stats', 'total_trackbacks');
		ee()->smartforge->drop_column('stats', 'last_trackback_date');
		ee()->smartforge->drop_column('weblogs', 'total_trackbacks');
		ee()->smartforge->drop_column('weblogs', 'last_trackback_date');
		ee()->smartforge->drop_column('weblogs', 'enable_trackbacks');
		ee()->smartforge->drop_column('weblogs', 'trackback_use_url_title');
		ee()->smartforge->drop_column('weblogs', 'trackback_max_hits');
		ee()->smartforge->drop_column('weblogs', 'trackback_field');
		ee()->smartforge->drop_column('weblogs', 'deft_trackbacks');
		ee()->smartforge->drop_column('weblogs', 'trackback_system_enabled');
		ee()->smartforge->drop_column('weblogs', 'show_trackback_field');
		ee()->smartforge->drop_column('weblogs', 'trackback_use_captcha');
		ee()->smartforge->drop_column('weblogs', 'tb_return_url');
		ee()->smartforge->drop_column('weblog_titles', 'allow_trackbacks');
		ee()->smartforge->drop_column('weblog_titles', 'trackback_total');
		ee()->smartforge->drop_column('weblog_titles', 'sent_trackbacks');
		ee()->smartforge->drop_column('weblog_titles', 'recent_trackback_date');

		ee()->dbforge->drop_table('trackbacks');

		// Add primary keys as needed for normalization of all tables
		// Can't use SmartForge since DB Forge doesn't support FIRST.

		ee()->smartforge->drop_key('field_formatting', 'field_id');

		$fields = array(
			// 'table_name' => 'field_name'
			'throttle'			=> 'throttle_id',
			'stats'				=> 'stat_id',
			'online_users'		=> 'online_id',
			'security_hashes'	=> 'hash_id',
			'password_lockout'	=> 'lockout_id',
			'reset_password'	=> 'reset_id',
			'field_formatting'	=> 'formatting_id',
		);

		$Q = array();

		foreach ($fields as $k => $v)
		{
			// Check to make sure the table exists and the field doesn't yet.
			if (ee()->db->table_exists($k) AND  ! ee()->db->field_exists($v, $k))
			{
				$Q[] = "ALTER TABLE `exp_{$k}` ADD COLUMN `{$v}` int(10) UNSIGNED AUTO_INCREMENT NOT NULL PRIMARY KEY FIRST";
			}
		}

		// Run the queries
		$this->_run_queries('Updating weblog tables', $Q);

		ee()->smartforge->drop_key('email_cache_mg', 'cache_id');
		ee()->smartforge->add_key('email_cache_mg', array('cache_id', 'group_id'), 'PRIMARY');

		ee()->smartforge->drop_key('email_cache_ml', 'cache_id');
		ee()->smartforge->add_key('email_cache_ml', array('cache_id', 'list_id'), 'PRIMARY');

		ee()->smartforge->drop_key('member_homepage', 'member_id');
		ee()->smartforge->add_key('member_homepage', 'member_id', 'PRIMARY');

		ee()->smartforge->drop_key('member_groups', 'group_id');
		ee()->smartforge->drop_key('member_groups', 'site_id');
		ee()->smartforge->add_key('member_groups', array('group_id', 'site_id'), 'PRIMARY');

		ee()->smartforge->drop_key('weblog_member_groups', 'group_id');
		ee()->smartforge->add_key('weblog_member_groups', array('group_id', 'weblog_id'), 'PRIMARY');

		ee()->smartforge->drop_key('module_member_groups', 'group_id');
		ee()->smartforge->add_key('module_member_groups', array('group_id', 'module_id'), 'PRIMARY');

		ee()->smartforge->drop_key('template_member_groups', 'group_id');
		ee()->smartforge->add_key('template_member_groups', array('group_id', 'template_group_id'), 'PRIMARY');

		ee()->smartforge->drop_key('member_data', 'member_id');
		ee()->smartforge->add_key('member_data', 'member_id', 'PRIMARY');

		ee()->smartforge->drop_key('weblog_data', 'entry_id');
		ee()->smartforge->add_key('weblog_data', 'entry_id', 'PRIMARY');

		ee()->smartforge->add_key('entry_ping_status', array('entry_id', 'ping_id'), 'PRIMARY');

		ee()->smartforge->add_key('status_no_access', array('status_id', 'member_group'), 'PRIMARY');

		if ( ! in_array('category_posts', $has_duplicates))
		{
			ee()->smartforge->drop_key('category_posts', 'entry_id');
			ee()->smartforge->drop_key('category_posts', 'cat_id');
		}

		ee()->smartforge->add_key('category_posts', array('entry_id', 'cat_id'), 'PRIMARY');

		ee()->smartforge->drop_key('template_no_access', 'template_id');
		ee()->smartforge->add_key('template_no_access', array('template_id', 'member_group'), 'PRIMARY');

		ee()->smartforge->add_key('upload_no_access', array('upload_id', 'member_group'), 'PRIMARY');

		if ( ! in_array('message_folders', $has_duplicates))
		{
			ee()->smartforge->drop_key('message_folders', 'member_id');
		}

		ee()->smartforge->add_key('message_folders', 'member_id', 'PRIMARY');

		// Add default values for a few columns and switch some to NULL
		ee()->progress->update_state("Updating weblog tables");

		ee()->smartforge->modify_column(
			'templates',
			array(
				'template_data'		=> array('type' => 'mediumtext',	'null' => TRUE),
				'template_notes'	=> array('type' => 'text',			'null' => TRUE),
				'last_author_id'	=> array('type' => 'int',			'constraint' => 10,	'null' => FALSE,	'default' => 0),
				'refresh'			=> array('type' => 'int',			'constraint' => 6,	'unsigned' => TRUE,	'null' => FALSE,	'default' => 0),
				'no_auth_bounce'	=> array('type' => 'varchar',		'constraint' => 50,	'null' => FALSE,	'default' => ''),
				'hits'				=> array('type' => 'int',			'constraint' => 10,	'unsigned' => TRUE,	'null' => FALSE, 'default' => 0)
			)
		);

		ee()->smartforge->modify_column(
			'member_groups',
			array(
				'mbr_delete_notify_emails'	=> array('type' => 'varchar',	'constraint' => 255,	'null' => TRUE)
			)
		);

		ee()->smartforge->modify_column(
			'weblog_fields',
			array(
				'field_pre_field_id'	=> array('type' => 'int',	'constraint' => 6,	'unsigned' => TRUE,	'null' => TRUE),
				'recent_comment_date'	=> array('type' => 'int',	'constraint' => 10,	'null' => TRUE)
			)
		);

		ee()->smartforge->modify_column(
			'sites',
			array(
				'site_description'	=> array('type' => 'text',	'null' => TRUE)
			)
		);

		ee()->smartforge->modify_column(
			'category_groups',
			array(
				'can_edit_categories'	=> array('type' => 'text',	'null' => TRUE),
				'can_delete_categories'	=> array('type' => 'text',	'null' => TRUE)
			)
		);

		ee()->smartforge->modify_column(
			'categories',
			array(
				'cat_description'		=> array('type' => 'text',		'null' => TRUE),
				'cat_image'				=> array('type' => 'varchar',	'constraint' => 120,	'null' => TRUE)
			)
		);

		ee()->smartforge->modify_column(
			'upload_prefs',
			array(
				'max_size'			=> array('type' => 'varchar',	'constraint' => 16,		'null' => TRUE),
				'max_height'		=> array('type' => 'varchar',	'constraint' => 6,		'null' => TRUE),
				'max_width'			=> array('type' => 'varchar',	'constraint' => 6,		'null' => TRUE),
				'properties'		=> array('type' => 'varchar',	'constraint' => 120,	'null' => TRUE),
				'pre_format'		=> array('type' => 'varchar',	'constraint' => 120,	'null' => TRUE),
				'post_format'		=> array('type' => 'varchar',	'constraint' => 120,	'null' => TRUE),
				'file_properties'	=> array('type' => 'varchar',	'constraint' => 120,	'null' => TRUE),
				'file_pre_format'	=> array('type' => 'varchar',	'constraint' => 120,	'null' => TRUE),
				'file_post_format'	=> array('type' => 'varchar',	'constraint' => 120,	'null' => TRUE)
			)
		);

		ee()->smartforge->modify_column(
			'weblog_fields',
			array(
				'field_instructions'	=> array('type' => 'text',		'null' => TRUE),
				'field_pre_field_id'	=> array('type' => 'int',		'constraint' => 6,	'unsigned' => TRUE,	'null' => TRUE),
				'field_maxl'			=> array('type' => 'smallint',	'constraint' => 3,	'null' => TRUE)
			)
		);

		ee()->smartforge->modify_column(
			'weblog_titles',
			array(
				'forum_topic_id'		=> array('type' => 'int',	'constraint' => 10,	'unsigned' => TRUE,	'null' => TRUE),
				'recent_comment_date'	=> array('type' => 'int',	'constraint' => 10,	'null' => TRUE)
			)
		);

		ee()->smartforge->modify_column(
			'weblogs',
			array(
				'cat_group'				=> array('type' => 'varchar',	'constraint' => 255,	'null' => TRUE),
				'status_group'			=> array('type' => 'int',		'constraint' => 4,		'unsigned' => TRUE,	'null' => TRUE),
				'field_group'			=> array('type' => 'int',		'constraint' => 4,		'unsigned' => TRUE,	'null' => TRUE),
				'search_excerpt'		=> array('type' => 'int',		'constraint' => 4,		'unsigned' => TRUE,	'null' => TRUE),
				'deft_category'			=> array('type' => 'varchar',	'constraint' => 60,		'null' => TRUE),
				'comment_url'			=> array('type' => 'varchar',	'constraint' => 80,		'null' => TRUE),
				'comment_max_chars'		=> array('type' => 'int',		'constraint' => 5,		'unsigned' => TRUE,	'null' => TRUE,	'default' => 5000),
				'comment_notify_emails'	=> array('type' => 'varchar',	'constraint' => 255,	'null' => TRUE),
				'search_results_url'	=> array('type' => 'varchar',	'constraint' => 80,		'null' => TRUE),
				'ping_return_url'		=> array('type' => 'varchar',	'constraint' => 80,		'null' => TRUE),
				'rss_url'				=> array('type' => 'varchar',	'constraint' => 80,		'null' => TRUE)
			)
		);

		ee()->smartforge->drop_column('weblogs', 'enable_qucksave_versioning');

		ee()->progress->update_state("Updating weblog tables");

		// Remove trackback actions
		ee()->db->delete('actions', array('class' => 'Trackback'));
		ee()->db->delete('actions', array('class' => 'Trackback_CP'));

		// Update CP action names
		$query = ee()->db->select('action_id, class')->get('actions');

		if ($query->num_rows() > 0)
		{
			foreach ($query->result() as $row)
			{
				if (substr($row->class, -3) == '_CP')
				{
					ee()->db->set('class', substr($row->class, 0, -3).'_mcp');
					ee()->db->where('action_id', $row->action_id);
					ee()->db->update('actions');
				}
			}
		}

		ee()->progress->update_state("Installing default Accessories");
		ee()->_install_accessories();

		if ( ! empty($has_duplicates))
		{
			ee()->config->_update_config(array(), array('table_duplicates' => ''));
		}

		// weblogs are channels!
		return 'update_custom_fields';
	}

	// ------------------------------------------------------------------------

	public function update_custom_fields()
	{
		ee()->progress->update_state("Updating custom field tables");

		// Update category custom fields to allow null
		$query = ee()->db->select('field_id')->get('category_fields');

		if ($query->num_rows() > 0)
		{
			foreach ($query->result() as $row)
			{
				ee()->smartforge->modify_column(
					'category_field_data',
					array(
						'field_id_'.$row->field_id => array('type' => 'text',		'null' => TRUE),
						'field_ft_'.$row->field_id => array('type' => 'varchar',	'constraint' => 40,	'null' => TRUE,	'default' => 'none')
					)
				);
			}
		}

		// Update custom fields to allow null
		$query = ee()->db->select('field_id, field_type')->get('weblog_fields');

		if ($query->num_rows() > 0)
		{
			foreach ($query->result() as $row)
			{
				if ($row->field_type == 'date' OR $row->field_type == 'rel')
				{
					ee()->smartforge->modify_column(
						'weblog_data',
						array(
							'field_id_'.$row->field_id => array('type' => 'int',	'constraint' => 10,	'null' => FALSE,	'default' => 0)
						)
					);

					if ($row->field_type == 'date')
					{
						ee()->smartforge->modify_column(
							'weblog_data',
							array(
								'field_dt_'.$row->field_id => array('type' => 'varchar',	'constraint' => 8,	'null' => NULL)
							)
						);
					}
				}
				else
				{
					ee()->smartforge->modify_column(
						'weblog_data',
						array(
							'field_id_'.$row->field_id => array('type' => 'text',	'null' => NULL)
						)
					);
				}
			}
		}

		return 'resync_member_groups';
	}

	// ------------------------------------------------------------------------

	public function resync_member_groups()
	{
		ee()->progress->update_state("Synchronizing member groups");

		//  Update access priveleges for 2.0
		// resync member groups.  In 1.x, a bug existed where deleting a member group would only delete it from the currently logged in site,
		// leaving orphaned member groups in the member groups table.
		$query = ee()->db->select('group_id, site_id, can_access_publish, can_access_edit, can_access_modules, can_admin_utilities, can_admin_members, can_admin_preferences, can_access_admin, can_access_comm')->get('member_groups');

		$groups = array();

		foreach ($query->result() as $row)
		{
			$new_privs = array();

			if ($row->can_admin_utilities == 'y')
			{
				$new_privs['can_access_addons']		= 'y';
				$new_privs['can_access_extensions']	= 'y';
				$new_privs['can_access_plugins']	= 'y';
				$new_privs['can_access_tools']		= 'y';
				$new_privs['can_access_utilities']	= 'y';
				$new_privs['can_access_data']		= 'y';
				$new_privs['can_access_logs']		= 'y';
			}
			elseif ($row->can_access_comm == 'y')
			{
				$new_privs['can_access_tools']	= 'y';
			}

			if ($row->can_access_modules == 'y')
			{
				$new_privs['can_access_addons']	= 'y';
			}

			if ($row->can_access_publish == 'y' OR $row->can_access_edit == 'y')
			{
				$new_privs['can_access_content']	= 'y';
			}

			if ($row->can_admin_members == 'y')
			{
				$new_privs['can_access_members']	= 'y';
			}

			if ($row->can_admin_preferences == 'y')
			{
				$new_privs['can_access_sys_prefs']	= 'y';
				$new_privs['can_admin_design']		= 'y';
			}

			if ($row->can_access_admin == 'y')
			{
				$new_privs['can_access_content_prefs']	= 'y';
			}

			if ($row->group_id == 1)
			{
				$new_privs['can_access_accessories']	= 'y';
				$new_privs['can_access_files']			= 'y';
				$new_privs['can_edit_categories']		= 'y';
				$new_privs['can_delete_categories']		= 'y';
			}

			if ( ! empty($new_privs))
			{
				ee()->db->set($new_privs);
				ee()->db->where('group_id', $row->group_id);
				ee()->db->update('member_groups');
			}

			$groups[$row->group_id][] = $row->site_id;
		}

		$query = ee()->db->select('site_id')->get('sites');

		foreach ($query->result() as $row)
		{
			foreach ($groups as $group_id => $group_site_ids)
			{
				if ( ! in_array($row->site_id, $group_site_ids))
				{
					// vanquish!
					ee()->db->delete('member_groups', array('group_id' => $group_id));
				}
			}
		}

		return 'drop_member_group_columns';
	}

	// ------------------------------------------------------------------------

	public function drop_member_group_columns()
	{
		ee()->smartforge->drop_column('member_groups', 'can_admin_preferences');
		ee()->smartforge->drop_column('member_groups', 'can_admin_utilities');

		return 'convert_fresh_variables';
	}

	// ------------------------------------------------------------------------

	public function convert_fresh_variables()
	{
		// port over old Fresh Variables to Snippets?
		ee()->progress->update_state('Checking for Fresh Variables');

		ee()->db->select('settings');
		ee()->db->where('class', 'Fresh_variables');
		$query = ee()->db->get('extensions', 1);

		if ($query->num_rows() > 0 && $query->row('settings') != '')
		{
			ee()->progress->update_state("Converting Fresh Variables");

			// Load the string helper
			ee()->load->helper('string');

			$snippets = strip_slashes(unserialize($query->row('settings')));

			foreach ($snippets as $site_id => $vars)
			{
				foreach ($vars as $var)
				{
					ee()->progress->update_state('Adding Snippet: '.$var['var_name']);

					$data = array(
						'site_id'			=> ($site_id == 'all') ? 0 : $site_id,
						'snippet_name'		=> $var['var_name'],
						'snippet_contents'	=> $var['var_value']
					);

					ee()->smartforge->insert_set('snippets', $data, $data);
				}
			}

			unset($snippets);

			ee()->progress->update_state('Deleting Fresh Variables');

			// uninstall Fresh Variables
			ee()->db->delete('extensions', array('class' => 'Fresh_variables'));

			$query = ee()->db->select('module_id')
				->where('module_name', 'Fresh_variables')
				->get('modules');

			ee()->db->delete('module_member_groups', array('module_id' => $query->row('module_id')));
			ee()->db->delete('modules', array('module_name' => 'Fresh_variables'));
			ee()->db->delete('actions', array('class' => 'Fresh_variables'));
		}

		return 'weblog_terminology_changes';
	}

	// ------------------------------------------------------------------------

	public function weblog_terminology_changes()
	{
		ee()->progress->update_state("Replacing weblog with channel.");

		ee()->smartforge->modify_column(
			'sites',
			array(
				'site_weblog_preferences'	=> array('name' => 'site_channel_preferences',	'type' => 'text',	'null' => FALSE)
			)
		);

		ee()->smartforge->modify_column(
			'member_groups',
			array(
				'can_admin_weblogs'	=> array('name' => 'can_admin_channels',	'type' => 'char',	'constraint' => 1,	'null' => FALSE,	'default' => 'n')
			)
		);

		ee()->smartforge->modify_column(
			'weblog_member_groups',
			array(
				'weblog_id'	=> array('name' => 'channel_id',	'type' => 'int',	'constraint' => 6,	'unsigned' => TRUE,	'null' => FALSE)
			)
		);

		ee()->smartforge->rename_table('weblog_member_groups', 'channel_member_groups');

		ee()->smartforge->modify_column(
			'weblogs',
			array(
				'weblog_id'					=> array('name' => 'channel_id',					'type' => 'int',		'constraint' => 6,		'unsigned' => TRUE,	'null' => FALSE,	'auto_increment' => TRUE),
				'blog_name'					=> array('name' => 'channel_name',					'type' => 'varchar',	'constraint' => 40,		'null' => FALSE),
				'blog_title'				=> array('name' => 'channel_title',					'type' => 'varchar',	'constraint' => 100,	'null' => FALSE),
				'blog_url'					=> array('name' => 'channel_url',					'type' => 'varchar',	'constraint' => 100,	'null' => FALSE),
				'blog_description'			=> array('name' => 'channel_description',			'type' => 'varchar',	'constraint' => 255,	'null' => TRUE),
				'blog_lang'					=> array('name' => 'channel_lang',					'type' => 'varchar',	'constraint' => 12,		'null' => FALSE),
				'weblog_max_chars'			=> array('name' => 'channel_max_chars',				'type' => 'int',		'constraint' => 5,		'unsigned' => TRUE,	'null' => TRUE),
				'weblog_notify'				=> array('name' => 'channel_notify',				'type' => 'char',		'constraint' => 1,		'null' => FALSE,	'default' => 'n'),
				'weblog_require_membership'	=> array('name' => 'channel_require_membership',	'type' => 'char',		'constraint' => 1,		'null' => FALSE,	'default' => 'y'),
				'weblog_html_formatting'	=> array('name' => 'channel_html_formatting', 		'type' => 'char',		'constraint' => 4,		'null' => FALSE,	'default' => 'all'),
				'weblog_allow_img_urls'		=> array('name' => 'channel_allow_img_urls',		'type' => 'char',		'constraint' => 1,		'null' => FALSE,	'default' => 'y'),
				'weblog_auto_link_urls'		=> array('name' => 'channel_auto_link_urls',		'type' => 'char',		'constraint' => 1,		'null' => FALSE,	'default' => 'y'),
				'weblog_notify_emails'		=> array('name' => 'channel_notify_emails',			'type' => 'varchar',	'constraint' => 255,	'null' => TRUE)
			)
		);

		ee()->smartforge->rename_table('weblogs', 'channels');

		ee()->smartforge->modify_column(
			'weblog_titles',
			array(
				'weblog_id'	=> array('name' => 'channel_id',	'type' => 'int',	'constraint' => 4,	'unsigned' => TRUE,	'null' => FALSE)
			)
		);

		ee()->smartforge->rename_table('weblog_titles', 'channel_titles');

		ee()->smartforge->modify_column(
			'entry_versioning',
			array(
				'weblog_id'	=> array('name' => 'channel_id',	'type' => 'int',	'constraint' => 4,	'unsigned' => TRUE,	'null' => FALSE)
			)
		);

		ee()->smartforge->modify_column(
			'weblog_fields',
			array(
				'field_pre_blog_id'	=> array('name' => 'field_pre_channel_id',	'type' => 'int',		'constraint' => 6,	'unsigned' => TRUE,	'null' => TRUE),
				'field_related_to'	=> array('name' => 'field_related_to',		'type' => 'varchar',	'constraint' => 12,	'null' => FALSE,	'default' => 'channel')
			)
		);

		ee()->db->set('field_related_to', 'channel');
		ee()->db->where('field_related_to', 'blog');
		ee()->db->update('weblog_fields');

		ee()->smartforge->modify_column(
			'weblog_data',
			array(
				'weblog_id'	=> array('name' => 'channel_id',	'type' => 'int',	'constraint'	=> 4,	'unsigned' => TRUE,	'null' => FALSE)
			)
		);

		$template_replacements = array(
			'weblog:weblog_name'			=> 'channel:channel_name',
			'exp:weblog'					=> 'exp:channel',
			'{assign_variable:'				=> '{preload_replace:',	// this is necessary before the following query
			'{preload_replace:my_weblog='	=> '{preload_replace:my_channel=',
			'{my_weblog}'					=> '{my_channel}',
			'{weblog}'						=> '{channel}',
			'weblog_'						=> 'channel_',
			'_weblog'						=> '_channel',
			'weblog='						=> 'channel=',
			'{blog_title}'					=> '{channel_title}',
			'{blog_description}'			=> '{channel_description}',
			'{blog_encoding}'				=> '{channel_encoding}',
			'{blog_lang}'					=> '{channel_lang}',
			'{blog_url}'					=> '{channel_url}',
		);

		foreach ($template_replacements as $k => $v)
		{
			ee()->db->set('template_data', "REPLACE(`template_data`, '{$k}', '{$v}')", FALSE);
			ee()->db->update('templates');
		}

		ee()->db->set('module_name', 'Channel');
		ee()->db->where('module_name', 'Weblog');
		ee()->db->update('modules');

		return 'rename_weblog_tables';
	}

	// ------------------------------------------------------------------------

	public function rename_weblog_tables()
	{
		ee()->smartforge->rename_table('weblog_fields', 'channel_fields');
		ee()->smartforge->rename_table('weblog_data', 'channel_data');

		// Finished
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

		$tables = ee()->db->list_tables(TRUE);

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
			'exp_stats'						=> array('last_entry_date', 'last_visitor_date', 'most_visitor_date', 'last_cache_clear', 'last_forum_post_date', 'last_comment_date'),
			'exp_templates'					=> array('edit_date'),
			'exp_throttle'					=> array('last_activity'),
			'exp_updated_site_pings'		=> array('ping_date'),
			'exp_weblog_data'				=> array(),
			'exp_weblog_titles'				=> array('entry_date', 'expiration_date', 'comment_expiration_date', 'recent_comment_date'),
			'exp_weblogs'					=> array('last_entry_date', 'last_comment_date'),
			'exp_wiki_page'					=> array('last_updated'),
			'exp_wiki_revisions'			=> array('revision_date'),
			'exp_wiki_uploads'				=> array('upload_date'),
		);

		// Also find all custom fields that are date fields as well
		$query = ee()->db->query("SELECT field_id FROM exp_weblog_fields WHERE field_type = 'date'");

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
			$query = ee()->db->query("SHOW FIELDS FROM `".ee()->db->escape_str($table)."`");

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
			$table = ee()->db->escape_str($table);

			foreach($fields as $field)
			{
				$field = ee()->db->escape_str($field);

				// Compensate for 1.x's $LOC->now DST behavior by adding an hour
				// to all dates that the server considers to have been in DST

				if (isset($table_keys[$table]))
				{
					$count = ee()->db->count_all($table);

					// Split up into 50,000 records per update so we don't
					// run mysql into the ground

					for($i = 0; $i <= $count; $i = $i + 50000)
					{
						ee()->progress->update_state("Searching `{$table}.{$field}` for DST discrepancies ({$i} / {$count})");

						$query = ee()->db->query("SELECT `{$field}`, `".ee()->db->escape_str($table_keys[$table])."`
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
								ee()->progress->update_state("Generating queries to compensate for DST discrepancies in `{$table}` ({$tot} records)");

								// add one hour to the field we're converting, for all the
								// rows we gathered above ($dst_dates == array of primary keys)

								$conversion_queries[] = "UPDATE `{$table}` SET `{$field}` = `{$field}` + 3600
									WHERE `".ee()->db->escape_str($table_keys[$table])."` IN ('".implode("','", $dst_dates)."');";
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
			ee()->progress->update_state("{$summary} (Query {$num} of {$count})");

			ee()->db->query($sql);
		}
	}

	// ------------------------------------------------------------------------

	private function _dupe_check()
	{
		$has_duplicates = array();

		// Check whether we need to run duplicate record clean up
		$query = ee()->db->query("SELECT `upload_id`, `member_group`, count(`member_group`) FROM `exp_upload_no_access` GROUP BY `upload_id`, `member_group` HAVING COUNT(`member_group`) > 1");

		if ($query->num_rows() > 0)
		{
			$has_duplicates[] = 'upload_no_access';
		}

		$query = ee()->db->query("SELECT `member_id`, count(`member_id`) FROM `exp_message_folders` GROUP BY `member_id` HAVING COUNT(`member_id`) > 1");

		if ($query->num_rows() > 0)
		{
			$has_duplicates[] = 'message_folders';
		}

		$query = ee()->db->query("SELECT `entry_id`, `cat_id`, count(`cat_id`) FROM `exp_category_posts` GROUP BY `entry_id`, `cat_id` HAVING count(`cat_id`) > 1");

		if ($query->num_rows() > 0)
		{
			$has_duplicates[] = 'category_posts';
		}

		if ( ! empty($has_duplicates))
		{
			ee()->config->_update_config(array('table_duplicates' => implode('|', $has_duplicates)));
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
		$query = ee()->db->query("SHOW TABLE STATUS FROM `".ee()->db->database."`");

		$totsize = 0;
		$records = 0;

		$prefix_len = strlen(ee()->db->dbprefix);

		foreach ($query->result_array() as $row)
		{
			if (strncmp($row['Name'], ee()->db->dbprefix, $prefix_len) != 0)
			{
				continue;
			}

			$totsize += $row['Data_length'] + $row['Index_length'];
		}

		return round($totsize / 1048576);
	}

	// --------------------------------------------------------------------

	/**
	 * Changes column type for the `site_system_preferences` column in
	 * `sites` from TEXT to MEDIUMTEXT
	 */
	private function _change_site_preferences_column_type()
	{
		ee()->smartforge->modify_column(
			'sites',
			array(
				'site_system_preferences' => array(
					'name' => 'site_system_preferences',
					'type' => 'mediumtext'
				)
			)
		);
	}


	// --------------------------------------------------------------------

}
/* END CLASS */

/* End of file ud_200.php */
/* Location: ./system/expressionengine/installer/updates/ud_200.php */

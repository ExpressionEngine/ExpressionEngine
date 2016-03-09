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
 * ExpressionEngine Logging Class
 *
 * @package		ExpressionEngine
 * @subpackage	Control Panel
 * @category	Control Panel
 * @author		EllisLab Dev Team
 * @link		https://ellislab.com
 */

class EE_Logger {

	protected $_dev_log_hashes = array();
	private $db;

	/**
	 * Generates or returns the logger DB as to not interfere with other Active
	 * Record queries
	 * @return CI_DB The database object
	 */
	private function logger_db()
	{
		if ( ! isset($this->db))
		{
			$db = clone ee()->db;
			$db->_reset_select();
			$db->_reset_write();
			$db->flush_cache();
			$this->db = $db;
		}

		return $this->db;
	}

	// --------------------------------------------------------------------

	/**
	 * Log an action
	 *
	 * @access	public
	 * @param	string	action
	 */
	function log_action($action = '')
	{
		if (is_array($action))
		{
			$action = implode("\n", $action);
		}

		if (trim($action) == '')
		{
			return;
		}

		$this->logger_db()->query(
			$this->logger_db()->insert_string(
				'exp_cp_log',
				array(
					'member_id'	=> ee()->session->userdata('member_id'),
					'username'	=> ee()->session->userdata['username'],
					'ip_address'=> ee()->input->ip_address(),
					'act_date'	=> ee()->localize->now,
					'action'	=> $action,
					'site_id'	=> ee()->config->item('site_id')
				)
			)
		);
	}

	// --------------------------------------------------------------------

	/**
	 * Log an item in the Developer Log
	 *
	 * @param	mixed $data String containing log message, or array of data
	 *		such as information for a deprecation warning.
	 * @param	bool $update If set to TRUE, function will not add the log
	 *		item if one like it already exists. It will instead set the
	 *		viewed status to unviewed and update the timestamp on the
	 *		existing log item.
	 * @param	int $expires If $update set to TRUE, $expires is the amount
	 *		of time in seconds to have elapsed from the initial logging to
	 *		mark as unread and alert Super Admin again. For example, an item
	 *		is logged with an expires of 3600 seconds. If the developer
	 *		function is called with the same data within that 3600 seconds,
	 *		it will hold off displaying a notice to the Super Admin until
	 *		the developer function is called again after the 3600 seconds
	 *		are up. This is designed to make log item alerts less annoying
	 *		to the user.
	 * @return	int ID of inserted or updated record
	 */
	public function developer($data, $update = FALSE, $expires = 0)
	{
		// Grab previously-logged items upfront and cache
		if (empty($this->_dev_log_hashes))
		{
			// Order by timestamp to store only the latest timestamp in the
			// cache array
			$rows = $this->logger_db()->select('hash, timestamp')
				->order_by('timestamp', 'asc')
				->get('developer_log')
				->result_array();

			foreach ($rows as $row)
			{
				$this->_dev_log_hashes[$row['hash']] = $row['timestamp'];
			}
		}

		$log_data = array();

		// If we were passed an array, place its contents to $log_data
		if (is_array($data))
		{
			$log_data = $data;
		}
		// Otherwise it's probably a string, stick it in the 'description' field
		else
		{
			$log_data['description'] = $data;
		}

		// Get a hash of the data to see if we've aleady logged this
		$hash = md5(serialize($log_data));

		// Load Localize in case this is being called via the Javascript
		// controller where full EE bootstrapping hasn't run
		ee()->load->library('localize');

		// If this log is not to be duplicated and it already exists in the DB
		if ($update && isset($this->_dev_log_hashes[$hash]))
		{
			// If $expires is set, only update item if the duplicate is old enough
			if (ee()->localize->now - $expires > $this->_dev_log_hashes[$hash])
			{
				// There may be multiple items with the same hash for if a log item
				// was previously set not to update, so update based on timestamp too
				$this->logger_db()->where(
					array(
						'hash'		=> $hash,
						'timestamp' => $this->_dev_log_hashes[$hash]
					)
				);

				// Set log item as unviewed and update the timestamp
				$this->logger_db()->update('developer_log',
					array(
						'viewed'	=> 'n',
						'timestamp' => ee()->localize->now
					)
				);
			}

			return;
		}

		// If we got here, we're inserting a new item into the log
		$log_data['timestamp'] = ee()->localize->now;
		$log_data['hash'] = $hash;

		$this->logger_db()->insert('developer_log', $log_data);

		// Add to the hash cache so we don't have to requery
		$this->_dev_log_hashes[$hash] = $log_data['timestamp'];

		return $log_data;
	}

	// --------------------------------------------------------------------

	/**
	 * Log a function as deprecated
	 *
	 * This function is to be called from a function that we plan to
	 * deprecate. The only parameter passed is the version number the
	 * function was deprecated in order to pass that along to the user.
	 * Other information, such as the actual name of the function and
	 * where it was called from is determined by PHP's debug_backtrace
	 * function.
	 *
	 * From there, the use of the deprecated method is logged in the
	 * developer log for Super Admin review.
	 *
	 * This not public to third-party developers.
	 *
	 * @param	string $version Version function was deprecated
	 * @param	string $use_instead Function to use instead, if applicable
	 * @return	void
	 */
	function deprecated($version = NULL, $use_instead = NULL)
	{
		ee()->load->helper('array');

		$backtrace = debug_backtrace();

		// find the call site
		$callee = element(1, $backtrace, array());

		// make sure the items are set
		$line = element('line', $callee, 0);
		$file = element('file', $callee, '');
		$function = element('function', $callee, '');

		// if we're inside the system folder we don't care about the parent path
		$file = str_replace(APPPATH, 'system/expressionengine/', $file);

		// Information we are capturing from the incident
		$deprecated = array(
			'function'			=> $function.'()',				// Name of deprecated function
			'line'				=> $line,						// Line where 'function' was called
			'file'				=> $file,						// File where 'function' was called
			'deprecated_since'	=> $version,					// Version function was deprecated
			'use_instead'		=> ( ! empty($use_instead))		// Function to use instead
				? htmlentities($use_instead) : NULL
		);

		// On page requests we need to check a bunch of other stuff
		if (REQ == 'PAGE')
		{
			foreach ($backtrace as $i => $call)
			{
				if (isset($backtrace[$i + 1]))
				{
					$next = $backtrace[$i + 1];

					if (is_a(element('object', $next, ''), 'EE_Template') && element('function', $next) == 'process_tags')
					{
						// found our parent tag
						$addon_module = element('class', $call, '');
						$addon_method = element('function', $call, '');

						$deprecated += compact('addon_module', 'addon_method');

						// grab our full tag name
						$template_obj = $next['object'];
						$addon_tag = $template_obj->tagproper;

						$deprecated += array(
							'template_id' => $template_obj->template_id,
							'template_group' => $template_obj->group_name,
							'template_name' => $template_obj->template_name
						);

						// check in snippets
						$global_vars = ee()->config->_global_vars;

						$regex = '/'.preg_quote($addon_tag, '/').'/';
						$matched = preg_grep($regex, $global_vars);

						// Found in a snippet
						if (count($matched))
						{
							$matched = array_keys($matched);
							$deprecated += array('snippets' => implode('|', $matched));
						}

						break;
					}
				}
			}
		}

		// Only bug the user about this again after a week, or 604800 seconds
		$deprecation_log = $this->developer($deprecated, TRUE, 604800);
		$this->show_flashdata($deprecation_log);
	}

	// --------------------------------------------------------------------

	/**
	 * Log an extension hook as deprecated
	 *
	 * This method is to be called when a deprecated extension hook is
	 * activated. The original hook name must be passed, and optionally
	 * the version it was deprecated in, and what hook to use instead.
	 *
	 * From there, the use of the deprecated hook is logged in the
	 * developer log for Super Admin review.
	 *
	 * @param	string	$hook - the name of the deprecated hook
	 * @param	string	$version (optional) - the version number it was deprecated in
	 * @param	string	$use_instead (optional) - the name of the hook to use instead
	 * @return	void
	 **/
	public function deprecated_hook($hook, $version = NULL, $use_instead = NULL)
	{
		$hook_details = ee()->extensions->get_active_hook_info($hook);

		if ($hook_details === FALSE)
		{
			return FALSE;
		}

		// potentially many extensions using this hook
		$in_use = array();
		foreach ($hook_details as $priority => $extensions)
		{
			foreach ($extensions as $class => $details)
			{
				// 0 is the method name, 1 is the settings, 2 is the version number
				$in_use[] = $class.'::'.$details[0].'()';
			}
		}

		ee()->lang->loadfile('tools');
		$description = sprintf(lang('deprecated_hook'), '<br /><li>'.implode('</li><li>', $in_use).'</li>');

		if ( ! empty($version))
		{
			$description .= '<br />'.sprintf(lang('deprecated_since'), $version);
		}

		if ( ! empty($use_instead))
		{
			$description .= NBS.sprintf(lang('deprecated_use_instead'), $use_instead);
		}

		// Only bug the user about this again after a week, or 604800 seconds
		$deprecation_log = $this->developer($description, TRUE, 604800);
		$this->show_flashdata($deprecation_log);
	}

	// --------------------------------------------------------------------

	/**
	 * Show Flashdata
	 *
	 * Shows and stores flashdata if we are in the CP, and only to Super Admins
	 *
	 * @param	array	$deprecation_log - array, returned by $this->developer()
	 * @return	void
	 **/
	private function show_flashdata($deprecation_log)
	{
		if (REQ == 'CP' && isset(ee()->session) && ee()->session instanceof EE_Session
			&& ee()->session->userdata('group_id') == 1)
		{
			ee()->lang->loadfile('tools');

			// Set JS globals for "What does this mean?" modal
			ee()->javascript->set_global(
				array(
					'developer_log' => array(
						'dev_log_help'			=> lang('dev_log_help'),
						'deprecation_meaning'	=> lang('deprecated_meaning')
					)
				)
			);

			if (isset($deprecation_log['updated']))
			{
				ee()->session->set_flashdata(
					'message_error',
					lang('deprecation_detected').'<br />'.
						'<a href="'.BASE.AMP.'C=tools_logs'.AMP.'M=view_developer_log">'.lang('dev_log_view_report').'</a>
						'.lang('or').' <a href="#" class="deprecation_meaning">'.lang('dev_log_help').'</a>'
				);
			}
		}
	}

	// --------------------------------------------------------------------

	/**
	 * Deprecate a template tag and replace it in templates and snippets
	 *
	 * @param  String $message     The message to send to the developer log,
	 *                             uses developer() not deprecated()
	 * @param  String $regex       Regular expression to run through
	 *                             preg_replace
	 * @param  String $replacement Replacement to pass to preg_replace
	 * @return void
	 */
	public function deprecate_template_tag($message, $regex, $replacement)
	{
		ee()->load->model('template_model');
		$templates = ee()->template_model->fetch_last_edit(array(), TRUE);

		$changed = 0;

		foreach ($templates as $template)
		{
			$old_template_data = $template->template_data;

			// Find and replace the tags
			$template->template_data = preg_replace(
				$regex,
				$replacement,
				$template->template_data
			);

			// Only save if the template data changed
			if ($old_template_data != $template->template_data)
			{
				// Keep track of how many changed templates we have
				// so we know whether or not to bother the user with
				// a deprecation notification
				$changed++;

				// save the template
				ee()->template_model->save_entity($template);
			}
		}

		// Update snippets
		ee()->load->model('snippet_model');
		$snippets = ee()->snippet_model->fetch();

		foreach ($snippets as $snippet)
		{
			$old_snippet_contents = $snippet->snippet_contents;

			$snippet->snippet_contents = preg_replace(
				$regex,
				$replacement,
				$snippet->snippet_contents
			);

			// Only save if the snippet data changed
			if ($old_snippet_contents != $snippet->snippet_contents)
			{
				$changed++;

				ee()->snippet_model->save($snippet);
			}
		}

		// Update current tagdata if running outside the updater
		if (isset(ee()->TMPL) && isset(ee()->TMPL->tagdata))
		{
			ee()->TMPL->tagdata = preg_replace(
				$regex,
				$replacement,
				ee()->TMPL->tagdata
			);
		}

		// Only log the change if changes were made
		if ($changed > 0)
		{
			$this->developer($message, TRUE, 604800);
		}
	}

	// --------------------------------------------------------------------

	/**
	 * Deprecate tags within specialty templates (forum, profile, wiki)
	 *
	 * @param  String $message     The message to send to the developer log,
	 *                             uses developer() not deprecated()
	 * @param  String $regex       Regular expression to run through
	 *                             preg_replace
	 * @param  String $replacement Replacement to pass to preg_replace
	 * @param  String $specific_template     Filename of specific template to
	 *                                       deprecate in
	 * @return void
	 */
	public function deprecate_specialty_template_tag($message, $regex, $replacement, $specific_template = '')
	{
		ee()->load->helper(array('directory', 'file'));
		$results = array();

		foreach (array('forum', 'wiki', 'profile') as $type)
		{
			if (is_dir($current_path = PATH_THEMES.$type.'_themes/'))
			{
				$results[$type] = $this->_update_specialty_template(
					directory_map($current_path),
					$current_path,
					$regex,
					$replacement,
					$specific_template
				);
			}
		}

		if (strpos(json_encode($results), 'true') !== FALSE)
		{
			$this->developer($message, TRUE, 604800);
		}
	}

	// -------------------------------------------------------------------------

	/**
	 * Update specialty templates given an array of specialty templates from
	 * directory_map
	 * @param  Mixed  $filename    Filename to replace or directory_map listing
	 *                             (or section of listing)
	 * @param  String $path        Full path to where $filename exists
	 * @param  String $regex       Regular expression to run through
	 *                             preg_replace
	 * @param  String $replacement Replacement to pass to preg_replace
	 * @param  String $specific_template     Filename of specific template to
	 *                                       deprecate in
	 * @return void
	 */
	private function _update_specialty_template($filename, $path, $regex, $replacement, $specific_template)
	{
		if (is_array($filename))
		{
			foreach ($filename as $current_directory => $file)
			{
				// Only append $current_directory if it's not numeric
				$recursive_path = ( ! is_numeric($current_directory)) ? $path.$current_directory.'/' : $path;
				$filename[$current_directory] = $this->_update_specialty_template($file, $recursive_path, $regex, $replacement, $specific_template);
			}
			return $filename;
		}

		// Figure out if this is .html, .css, .feed, or .xml
		$full_filename = $path.$filename;
		$pathinfo = pathinfo($full_filename);

		if (($specific_template == ''
			OR $specific_template == $filename)
			&& in_array($pathinfo['extension'], array('html', 'css', 'feed', 'xml'))
			&& ($file_contents = read_file($full_filename))
			&& preg_match($regex, $file_contents))
		{
			write_file(
				$full_filename,
				preg_replace(
					$regex,
					$replacement,
					$file_contents
				)
			);

			return TRUE;
		}

		return FALSE;
	}

	// --------------------------------------------------------------------

	/**
	 * Log a message in the Updater log.
	 *
	 * @access	public
	 * @param	string		Message to add to the log.
	 * @param	bool			If TRUE, add backtrace info to the log.
	 * @return	void
	 */
	public function updater($log_message, $exception = FALSE)
	{
		$this->_setup_update_log();

		$data = array(
			 'timestamp'	=> ee()->localize->now,
			 'message'		=> $log_message,
		);

		if ($exception === TRUE)
		{
			$backtrace		= element(1, debug_backtrace(FALSE));

			$data['method']	= $backtrace['class'].'::'.$backtrace['function'];
			$data['line']	= $backtrace['line'];
			$data['file']	= $backtrace['file'];
		}

		$this->logger_db()->insert('update_log', $data);
	}

	// --------------------------------------------------------------------

	/**
	 * Create the update_log table if it doesn't already exist. Must be done
	 * here rather than through the usual Updater since we need it available
	 * when the Updater begins. Only creates the table if it doesn't already
	 * exist.
	 *
	 * @access	private
	 * @return	bool
	 */
	private function _setup_update_log()
	{
		$table = 'update_log';

		// Clear table caches
		ee()->db->data_cache = array();

		// Using normal ee()->db here since we need to see if this table was
		// created using the normal DB object
		if ( ! ee()->db->table_exists($table))
		{
			ee()->load->dbforge();

			$fields = array(
				'log_id' => array(
					'type'				=> 'int',
					'constraint'		=> 10,
					'unsigned'			=> TRUE,
					'auto_increment'	=> TRUE
				),
				'timestamp' => array(
					'type'				=> 'int',
					'constraint'		=> 10,
					'unsigned'			=> TRUE
				),
				'message' => array(
					'type'				=> 'text',
					'null'				=> TRUE
				),
				'method' => array(
					'type'				=> 'varchar',
					'constraint'		=> 100,
					'null'				=> TRUE
				),
				'line' => array(
					'type'				=> 'int',
					'constraint'		=> 10,
					'unsigned'			=> TRUE,
					'null'				=> TRUE
				),
				'file' => array(
					'type'				=> 'varchar',
					'constraint'		=> 255,
					'null'				=> TRUE
				)
			);

			ee()->dbforge->add_field($fields);
			ee()->dbforge->add_key('log_id', TRUE);

			ee()->dbforge->create_table($table);
		}
	}
}
// END CLASS

// EOF

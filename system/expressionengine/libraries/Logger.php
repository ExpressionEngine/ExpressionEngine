<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
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
 * ExpressionEngine Logging Class
 *
 * @package		ExpressionEngine
 * @subpackage	Control Panel
 * @category	Control Panel
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */

class EE_Logger {

	protected $_dev_log_hashes = array();

	/**
	 * Constructor
	 *
	 * @access	public
	 */
	function __construct()
	{
		$this->EE =& get_instance();
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

		ee()->db->query(
			ee()->db->insert_string(
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
			$rows = ee()->db->select('hash, timestamp')
				->order_by('timestamp', 'asc')
				->get('developer_log')
				->result_array();

			foreach ($rows as $row)
			{
				$this->_dev_log_hashes[$row['hash']] = $row['timestamp'];
			}
		}

		$log_data = array();

		// If we were passed an array, add its contents to $log_data
		if (is_array($data))
		{
			$log_data = array_merge($log_data, $data);
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
				ee()->db->where(
					array(
						'hash'		=> $hash,
						'timestamp' => $this->_dev_log_hashes[$hash]
					)
				);

				// Set log item as unviewed and update the timestamp
				ee()->db->update('developer_log',
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

		ee()->db->insert('developer_log', $log_data);

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

		// Show and store flashdata only if we're in the CP, and only to Super Admins
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
	 * Builds deprecation notice language based on data given
	 *
	 * @param	array $deprecated Data array of deprecated function details
	 * @return	string Message constructed from language keys describing function deprecation
	 */
	function build_deprecation_language($deprecated)
	{
		ee()->lang->loadfile('tools');

		if ( ! isset($deprecated['function']))
		{
			return $deprecated['description'];
		}

		// "Deprecated function %s called"
		$message = sprintf(lang('deprecated_function'), $deprecated['function']);

		// "in %s on line %d."
		if (isset($deprecated['file']) && isset($deprecated['line']))
		{
			$message .= NBS.sprintf(lang('deprecated_on_line'), $deprecated['file'], $deprecated['line']);
		}

		// "from template tag: %s in template %s"
		if (isset($deprecated['addon_module']) && isset($deprecated['addon_method']))
		{
			$message .= '<br />';
			$message .= sprintf(
				lang('deprecated_template'),
				'<code>exp:'.strtolower($deprecated['addon_module']).':'.$deprecated['addon_method'].'</code>',
				'<a href="'.BASE.AMP.'C=design'.AMP.'M=edit_template'.AMP.'id='.$deprecated['template_id'].'">'.$deprecated['template_group'].'/'.$deprecated['template_name'].'</a>'
			);

			if ($deprecated['snippets'])
			{
				$snippets = explode('|', $deprecated['snippets']);

				foreach ($snippets as &$snip)
				{
					$snip = '<a href="'.BASE.AMP.'C=design'.AMP.'M=snippets_edit'.AMP.'snippet='.$snip.'">{'.$snip.'}</a>';
				}

				$message .= '<br />';
				$message .= sprintf(lang('deprecated_snippets'), implode(', ', $snippets));
			}
		}

		if (isset($deprecated['deprecated_since'])
			|| isset($deprecated['deprecated_use_instead']))
		{
			// Add a line break if there is additional information
			$message .= '<br />';

			// "Deprecated since %s."
			if (isset($deprecated['deprecated_since']))
			{
				$message .= sprintf(lang('deprecated_since'), $deprecated['deprecated_since']);
			}

			// "Use %s instead."
			if (isset($deprecated['use_instead']))
			{
				$message .= NBS.sprintf(lang('deprecated_use_instead'), $deprecated['use_instead']);
			}
		}

		return $message;
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
		$this->_setup_log();

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

		ee()->db->insert('update_log', $data);
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
	private function _setup_log()
	{
		$table = 'update_log';

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

/* End of file Logger.php */
/* Location: ./system/expressionengine/libraries/Logger.php */

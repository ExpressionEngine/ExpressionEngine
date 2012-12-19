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
 * ExpressionEngine Logging Class
 *
 * @package		ExpressionEngine
 * @subpackage	Control Panel
 * @category	Control Panel
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */
 
class EE_Logger {

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
												
		$this->EE->db->query(
			$this->EE->db->insert_string(
				'exp_cp_log',
				array(
					'member_id'	=> $this->EE->session->userdata('member_id'),
					'username'	=> $this->EE->session->userdata['username'],
					'ip_address'=> $this->EE->input->ip_address(),
					'act_date'	=> $this->EE->localize->now,
					'action'	=> $action,
					'site_id'	=> $this->EE->config->item('site_id')
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
		
		// If this log is not to be duplicated
		if ($update)
		{
			// Look to see if this exact log data is already in the database
			$this->EE->db->where($log_data);
			$this->EE->db->order_by('log_id', 'desc');
			$duplicate = $this->EE->db->get('developer_log')->row_array();
			
			if (count($duplicate))
			{
				// If $expires is set, only update item if the duplicate is old enough
				if ($this->EE->localize->now - $expires > $duplicate['timestamp'])
				{
					// Set log item as unviewed and update the timestamp
					$duplicate['viewed'] = 'n';
					$duplicate['timestamp'] = $this->EE->localize->now;
					
					$this->EE->db->where('log_id', $duplicate['log_id']);
					$this->EE->db->update('developer_log', $duplicate);
					
					$duplicate['updated'] = TRUE;
				}
				
				return $duplicate;
			}
		}
		
		// If we got here, we're inserting a new item into the log
		$log_data['timestamp'] = $this->EE->localize->now;
		
		$this->EE->db->insert('developer_log', $log_data);
		
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
		// debug_backtrace() will tell us what method is deprecated and what called it
		$backtrace = debug_backtrace();
		
		// Make sure the keys exist
		$function = (isset($backtrace[1]['function'])) ? $backtrace[1]['function'] : '';
		$line = (isset($backtrace[1]['line'])) ? $backtrace[1]['line'] : 0;
		$file = (isset($backtrace[1]['file'])) ? $backtrace[1]['file'] : '';
		
		// Information we are capturing from the incident
		$deprecated = array(
			'function'			=> $function.'()',	// Name of deprecated function
			'line'				=> $line,			// Line where 'function' was called
			'file'				=> $file,			// File where 'function' was called 
			'deprecated_since'	=> $version,		// Version function was deprecated
			'use_instead'		=> $use_instead		// Function to use instead
		);
		
		// Only bug the user about this again after a week, or 604800 seconds
		$deprecation_log = $this->developer($deprecated, TRUE, 604800);
		
		// Show and store flashdata only if we're in the CP, and only to Super Admins
		if (REQ == 'CP' AND $this->EE->session->userdata('group_id') == 1)
		{
			$this->EE->lang->loadfile('tools');
			
			// Set JS globals for "What does this mean?" modal
			$this->EE->javascript->set_global(
				array(
					'developer_log' => array(
						'dev_log_help'			=> lang('dev_log_help'),
						'deprecation_meaning'	=> lang('deprecated_meaning')
					)
				)
			);
			
			if (isset($deprecation_log['updated']))
			{
				$this->EE->session->set_flashdata(
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
		$this->EE->lang->loadfile('tools');
		
		// "The system has detected an add-on that is using outdated code..." and "What does this mean?" link
		$message = lang('deprecation_detected').NBS.'<a href="#" class="deprecation_meaning">'.lang('dev_log_help').'</a><br />';
		
		// "Deprecated function %s called"
		$message .= sprintf(lang('deprecated_function'), $deprecated['function']);
		
		// "in %s on line %d."
		if (isset($deprecated['file']) && isset($deprecated['line']))
		{
			$message .= NBS.sprintf(lang('deprecated_on_line'), $deprecated['file'], $deprecated['line']);
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
}
// END CLASS

/* End of file Logger.php */
/* Location: ./system/expressionengine/libraries/Logger.php */
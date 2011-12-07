<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
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
 * ExpressionEngine Logging Class
 *
 * @package		ExpressionEngine
 * @subpackage	Control Panel
 * @category	Control Panel
 * @author		ExpressionEngine Dev Team
 * @link		http://expressionengine.com
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
	 * @param string $version Version function was deprecated
	 * @return void
	 */
	function deprecated($version = NULL)
	{
		// debug_backtrace() will tell us what method is deprecated and what called it
		$backtrace = debug_backtrace();
		
		// Explaination of below array indicies:
		// Index 0: deprecated function (this one)
		// Index 1: function that called deprecated(), i.e. the function that is deprecated
		// Index 2: function that called the function that is deprecated
		
		// Information we are capturing from the incident
		$deprecated = array(
			'function'			=> $backtrace[1]['function'],	// Name of deprecated function
			'called_by'			=> $backtrace[2]['function'],	// Name of function where 'function' was called
			'line'				=> $backtrace[1]['line'],		// Line where 'function' was called
			'file'				=> $backtrace[1]['file'],		// File where 'function' was called 
			'deprecated_since'	=> $version						// Version function was deprecated
		);
		
		$this->developer($deprecated, TRUE);
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
	 * @return	int ID of inserted or updated record
	 */
	public function developer($data, $update = FALSE)
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
			$duplicates = $this->EE->db->get('developer_log')->row_array();
			
			if (count($duplicates))
			{
				// Set log item as unviewed and update the timestamp
				$duplicates['viewed'] = 'n';
				$duplicates['timestamp'] = $this->EE->localize->now;
				
				$this->EE->db->where('log_id', $duplicates['log_id']);
				$this->EE->db->update('developer_log', $duplicates);
				
				return $duplicates['log_id'];
			}
		}
		
		// If we got here, we're inserting a new item into the log
		$log_data['timestamp'] = $this->EE->localize->now;
		
		$this->EE->db->insert('developer_log', $log_data);
		
		return $this->EE->db->insert_id();
	}
}
// END CLASS

/* End of file Logger.php */
/* Location: ./system/expressionengine/libraries/Logger.php */
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
	
	/**
	 * Log a function as deprecated
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
		
		$deprecated = array(
			'function'	=> $backtrace[1]['function'],
			'called_by'	=> $backtrace[2]['function'],
			'line'		=> $backtrace[1]['line'], // Line where 'function' was called
			'file'		=> $backtrace[1]['file'], // File where 'function' was called 
			'since'		=> $version
		);
		
		echo 'Deprecated method "'.$deprecated['function']
			.'" called by function "'.$deprecated['called_by']
			.'" on line '.$deprecated['line']
			.' of '.$deprecated['file'] . '<br />';
	}
}
// END CLASS

/* End of file Logger.php */
/* Location: ./system/expressionengine/libraries/Logger.php */
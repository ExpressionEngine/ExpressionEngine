<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
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
 * ExpressionEngine Logging Class
 *
 * @package		ExpressionEngine
 * @subpackage	Control Panel
 * @category	Control Panel
 * @author		ExpressionEngine Dev Team
 * @link		http://expressionengine.com
 */
 
class EE_Logger {


	function EE_Logger()
	{
		// Make a local reference to the ExpressionEngine super object
		$this->EE =& get_instance();
	}


	/** -------------------------------------
	/**  Log an action
	/** -------------------------------------*/
	function log_action($action = '')
	{
		if ($action == '')
		{
			return;
		}
				
		if (is_array($action))
		{
			if (count($action) == 0)
			{
				return;
			}
		
			$msg = '';
		
			foreach ($action as $val)
			{
				$msg .= $val."\n";	
			}
			
			$action = $msg;
		}
												
		$this->EE->db->query(
					 $this->EE->db->insert_string(
											'exp_cp_log',
				
											array(
													'member_id'  => $this->EE->session->userdata('member_id'),
													'username'	=> $this->EE->session->userdata['username'],
													'ip_address' => $this->EE->input->ip_address(),
													'act_date'	=> $this->EE->localize->now,
													'action'	 => $action,
													'site_id'	 => $this->EE->config->item('site_id')
												 )
											)
					);	
	}



}
// END CLASS

/* End of file Logger.php */
/* Location: ./system/expressionengine/libraries/Logger.php */
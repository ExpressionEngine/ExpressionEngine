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
 * ExpressionEngine Core Input Class
 *
 * @package		ExpressionEngine
 * @subpackage	Core
 * @category	Core
 * @author		ExpressionEngine Dev Team
 * @link		http://expressionengine.com
 */
class EE_Input extends CI_Input {

	var $SID = ''; // Session ID extracted from the URI segments


	// --------------------------------------------------------------------
	
	/**
	 * Fetch an item from the COOKIE array
	 *
	 * This method overrides the one in the CI class since EE cookies have a particular prefix
	 *
	 * @access	public
	 * @param	string
	 * @return	string
	 */
	function cookie($index = '')
	{
		$EE =& get_instance();
		
		$prefix = ( ! $EE->config->item('cookie_prefix')) ? 'exp_' : $EE->config->item('cookie_prefix').'_';
		
		return ( ! isset($_COOKIE[$prefix.$index]) ) ? FALSE : stripslashes($_COOKIE[$prefix.$index]);
	}

	// --------------------------------------------------------------------
		
	/**
	 * Filter GET Data
	 *
	 * Filters GET data for security
	 *
	 * @access	public
	 * @param	string
	 * @return	void
	 */
	function filter_get_data($request_type = 'PAGE')
	{
		$EE =& get_instance();

		$filter_keys = TRUE;
	
		if ($request_type == 'CP' && isset($_GET['BK']) && isset($_GET['channel_id']) && isset($_GET['title']) && $EE->session->userdata['admin_sess'] == 1)
		{
			if (in_array($EE->input->get_post('channel_id'), $EE->functions->fetch_assigned_channels()))
			{			
				$filter_keys = FALSE;
			}		
		}
	
		if (isset($_GET))
		{
			foreach($_GET as $key => $val)
			{
				if ($filter_keys == TRUE)
				{
					if (is_array($val))
					{
						exit('Invalid GET Data - Array');
					}
					elseif (preg_match("#(;|\?|exec\s*\(|system\s*\(|passthru\s*\(|cmd\s*\(|[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3})#i", $val))
					{
						exit('Invalid GET Data');
					}   
				}
			}	
		}	
	}

	// --------------------------------------------------------------------
	
}
// END CLASS

/* End of file EE_Input.php */
/* Location: ./system/expressionengine/libraries/EE_Input.php */
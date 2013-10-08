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
 * ExpressionEngine Core Input Class
 *
 * @package		ExpressionEngine
 * @subpackage	Core
 * @category	Core
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
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
	 * @param	bool
	 * @return	string
	 */
	function cookie($index = '', $xss_clean = FALSE)
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

		/*
 		* --------------------------------------------------------------------
 		*  Is the request a URL redirect redirect?  Moved from the index so we can have config variables!
 		* --------------------------------------------------------------------
 		*
 		* All external links that appear in the ExpressionEngine control panel
 		* are redirected to this index.php file first, before being sent to the
 		* final destination, so that the location of the control panel will not 
 		* end up in the referrer logs of other sites.
 		*
 		*/	

		if (isset($_GET['URL'])) 
		{ 
			if ( ! file_exists(APPPATH.'libraries/Redirect.php'))
			{
				exit('Some components appear to be missing from your ExpressionEngine installation.');	
			}
			
			require(APPPATH.'libraries/Redirect.php');

			exit();  // We halt system execution since we're done
		}		

		$filter_keys = TRUE;
	
		if ($request_type == 'CP'
			&& isset($_GET['BK'])
			&& isset($_GET['channel_id'])
			&& isset($_GET['title'])
			&& $EE->session->userdata('admin_sess') == 1)
		{
			if (in_array($EE->input->get_post('channel_id'), $EE->functions->fetch_assigned_channels()))
			{			
				$filter_keys = FALSE;
			}		
		}
	
		if (isset($_GET) && $filter_keys == TRUE)
		{
			foreach($_GET as $key => $val)
			{
				$clean = $this->_clean_get_input_data($val);	
				
				if ( ! $clean)
				{
					// Only notify super admins of the offending data
					if ($EE->session->userdata('group_id') == 1)
					{
						$data = ((int) config_item('debug') == 2) ? '<br>'.htmlentities($val) : '';
							
						set_status_header(503);
						exit(sprintf("Invalid GET Data %s", $data));
					}
					// Otherwise, handle it more gracefully and just unset the variable
					else
					{
						unset($_GET[$key]);
					}
				}				
			}
		}	
	}

	// --------------------------------------------------------------------

	/**
	 * Remove session ID from string
	 *
	 * This function is used mainly by the Input class to strip
	 * session IDs if they are used in public pages.
	 *
	 * @param	string
	 * @return	string
	 */	
	public function remove_session_id($str)
	{
		return preg_replace("#S=.+?/#", "", $str);
	}

	// --------------------------------------------------------------------
	
	/**
	 * Extend _sanitize_globals to allow css
	 *
	 * For action requests we need to fully allow GET variables, so we set
	 * an exception in EE_Config. For css, we only need that one and it's a
	 * path, so we'll do some stricter cleaning.
	 *
	 * @param	string
	 * @return	string
	 */
	function _sanitize_globals()
	{
		$_css = $this->get('css');
		
		parent::_sanitize_globals();
		
		if ($_css)
		{
			$_GET['css'] = remove_invisible_characters($_css);
		}
	}

	// --------------------------------------------------------------------
	
	/**
	 * Clean GET data
	 *
	 * If the GET value is disallowed, we show an error to superadmins
	 * For non-super, we unset the variable and let them go on their merry way
	 *
	 * @param	string Variable's key
	 * @param	mixed Variable's value- may be string or array
	 * @return	string
	 */
	function _clean_get_input_data($str)
	{
		if (is_array($str))
		{
			foreach ($str as $k => $v)
			{
				$out = $this->_clean_get_input_data($v);
				
				if ($out == FALSE)
				{
					return FALSE;
				}
			}

			return TRUE;
		}

		if (preg_match("#(;|exec\s*\(|system\s*\(|passthru\s*\(|cmd\s*\()#i", $str))
		{
			return FALSE;
		}
		
		return TRUE;
	}
}
// END CLASS

/* End of file EE_Input.php */
/* Location: ./system/expressionengine/libraries/EE_Input.php */
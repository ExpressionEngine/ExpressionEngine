<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		ExpressionEngine Dev Team
 * @copyright	Copyright (c) 2003 - 2010, EllisLab, Inc.
 * @license		http://expressionengine.com/docs/license.html
 * @link		http://expressionengine.com
 * @since		Version 2.0
 * @filesource
 */
 
// ------------------------------------------------------------------------

/**
 * ExpressionEngine Form Validation Class
 *
 * @package		ExpressionEngine
 * @subpackage	Libraries
 * @category	Validation
 * @author		ExpressionEngine Dev Team
 * @link		http://expressionengine.com
 */
class EE_Form_validation extends CI_Form_validation {
	
	var $EE;
	var $old_values = array();

	/**
	 * Constructor
	 *
	 * @access	public
	 */	
	function EE_Form_validation($rules = array())
	{	
		parent::CI_Form_validation($rules);
		$this->EE =& get_instance();
		
		// @todo this is a hack to get around the callback scope issues when inside an mcp file
		if ($this->EE->input->get('C') == 'addons_modules' &&
			$this->EE->input->get('M') == 'show_module_cp' &&
			isset($this->EE->_mcp_reference)
		)
		{
			$this->EE->_mcp_reference->lang =& $this->CI->lang;
			$this->CI =& $this->EE->_mcp_reference;
		}
	}
	
	// --------------------------------------------------------------------

	/**
	 * Validate Username
	 *
	 * Calls the custom field validation
	 *
	 * @access	public
	 * @param	string
	 * @param	string	update / new
	 * @return	bool
	 */
	function call_field_validation($data, $field_id)
	{
		$this->CI->api_channel_fields->setup_handler($field_id);
		$err = $this->CI->api_channel_fields->apply('validate', array($data));
		
		if ($err !== TRUE && $err != '')
		{
			$this->set_message('call_field_validation', $err);
			return FALSE;
		}
		
		return TRUE;
	}
	
	// --------------------------------------------------------------------

	/**
	 * Validate Username
	 *
	 * Checks if the submitted username is valid
	 *
	 * @access	public
	 * @param	string
	 * @param	string	update / new
	 * @return	bool
	 */
	function valid_username($str, $type)
	{
		if ( ! $type)
		{
			$type = 'update';
		}
		
		// Is username formatting correct?

		// Reserved characters:  |  "  '  ! < > { }
	
		if (preg_match("/[\|'\"!<>\{\}]/", $str))
		{
			$this->set_message('valid_username', $this->EE->lang->line('invalid_characters_in_username'));      
			return FALSE;
		}					
		
		
		// Is username min length correct?
		
		$len = $this->EE->config->item('un_min_len');
	
		if (strlen($str) < $len)
		{
			$this->set_message('valid_username', str_replace('%x', $len, $this->EE->lang->line('username_too_short')));      
			return FALSE;
		}					


		// Is username max length correct?
		
		if (strlen($str) > 32)
		{
			$this->set_message('valid_username', $this->EE->lang->line('username_password_too_long'));      
			return FALSE;
		}

		if ($current = $this->old_value('username'))
		{
			if ($current != $str)
			{
				$type = 'new';
			}
		}

		if ($type == 'new')
		{
			// Is username banned?

			if ($this->EE->session->ban_check('username', $str))
			{
				$this->set_message('valid_username', $this->EE->lang->line('username_taken'));      
				return FALSE;
			}


			// Is username taken?
			
			$this->EE->db->where('username', $str);
			$count = $this->EE->db->count_all_results('members');
							  
			if ($count > 0)
			{
				$this->set_message('valid_username', $this->EE->lang->line('username_taken'));      
				return FALSE;
			}
		}
		
		return TRUE;
	}
	
	// --------------------------------------------------------------------

	/**
	 * Validate Screen Name
	 *
	 * Checks if the submitted screen name is valid
	 *
	 * @access	public
	 * @param	string
	 * @param	string	update / new
	 * @return	bool
	 */
	function valid_screen_name($str, $type)
	{
		if ($str == '')
		{
			return TRUE;
		}
		
		if ( ! $type)
		{
			$type = 'update';
		}
		
		if (preg_match('/[\{\}<>]/', $str)) 
		{
			$this->set_message('valid_screen_name', $this->EE->lang->line('disallowed_screen_chars'));      
			return FALSE;
		}

		if ($current = $this->old_value('screen_name'))
		{
			if ($current != $str)
			{
				$type = 'new';
			}
		}
	
		if ($type == 'new')
		{
			// Is screen name banned?
		
			if ($this->EE->session->ban_check('screen_name', $str) OR trim(preg_replace("/&nbsp;*/", '', $str)) == '')
			{
				$this->set_message('valid_screen_name', $this->EE->lang->line('screen_name_taken'));      
				return FALSE;
			}

			// Is screen name taken?

			if (strtolower($current) != strtolower($str))
			{
				$this->EE->db->where('screen_name', $str);
				$count = $this->EE->db->count_all_results('members');
		
				if ($count > 0)
				{							
					$this->set_message('valid_screen_name', $this->EE->lang->line('screen_name_taken'));      
					return FALSE;
				}
			}
		}
		
		return TRUE;
	}
	
	// --------------------------------------------------------------------

	/**
	 * Validate Password
	 *
	 * Checks if the submitted password is valid
	 *
	 * @access	public
	 * @param	string
	 * @param	string	username field post key
	 * @return	bool
	 */
	function valid_password($str, $username_field)
	{
		if ( ! $username_field)
		{
			$username_field = 'username';
		}
		
		// Is password min length correct?
		
		$len = $this->EE->config->item('pw_min_len');
	
		if (strlen($str) < $len)
		{
			$this->set_message('valid_password', str_replace('%x', $len, $this->EE->lang->line('password_too_short')));      
			return FALSE;
		}
		
		
		// Is password max length correct?
		
		if (strlen($str) > 32)
		{
			$this->set_message('valid_password', $this->EE->lang->line('username_password_too_long'));
		}		


		// Is password the same as username?
		// - We check for a reversed password as well

		$username = $_POST[$username_field];

		//  Make UN/PW lowercase for testing

		$lc_user = strtolower($username);
		$lc_pass = strtolower($str);
		$nm_pass = strtr($lc_pass, 'elos', '3105');


		if ($lc_user == $lc_pass OR $lc_user == strrev($lc_pass) OR $lc_user == $nm_pass OR $lc_user == strrev($nm_pass))
		{
			$this->set_message('valid_password', $this->EE->lang->line('password_based_on_username'));
			return FALSE;
		}		

		
		// Are secure passwords required?

		if ($this->EE->config->item('require_secure_passwords') == 'y')
		{
			$count = array('uc' => 0, 'lc' => 0, 'num' => 0);
						
			$pass = preg_quote($str, "/");

			$len = strlen($pass);

			for ($i = 0; $i < $len; $i++)
			{
				$n = substr($pass, $i, 1);

				if (preg_match("/^[[:upper:]]$/", $n))
				{
					$count['uc']++;
				}
				elseif (preg_match("/^[[:lower:]]$/", $n))
				{
					$count['lc']++;
				}
				elseif (preg_match("/^[[:digit:]]$/", $n))
				{
					$count['num']++;
				}
			}
			
			foreach ($count as $val)
			{
				if ($val == 0)
				{
					$this->set_message('valid_password', $this->EE->lang->line('not_secure_password'));
					return FALSE;
				}
			}
		}
		
		
		// Does password exist in dictionary?

		if ($this->_lookup_dictionary_word($lc_pass) == TRUE)
		{
			$this->set_message('valid_password', $this->EE->lang->line('password_in_dictionary'));      
			return FALSE;
		}
		
		return TRUE;
	}
	
	// --------------------------------------------------------------------

	/**
	 * Validate Email
	 *
	 * Checks if the submitted email is valid
	 *
	 * @access	public
	 * @param	string
	 * @param	string	update / new
	 * @return	bool
	 */
	function valid_user_email($str, $type)
	{
		if ( ! $type)
		{
			$type = 'update';
		}
		
		// Is email valid?

		if ( ! $this->valid_email($str))
		{
			$this->set_message('valid_email', $this->EE->lang->line('invalid_email_address'));      
			return FALSE;
		}
		

		if ($current = $this->old_value('email'))
		{
			if ($current != $str)
			{
				$type = 'new';
			}
		}

		if ($type == 'new')
		{
			// Is email banned?
		
			if ($this->EE->session->ban_check('email', $str))
			{
				$this->set_message('valid_email', $this->EE->lang->line('email_taken'));      
				return FALSE;
			}


			// Do we allow multiple identical emails?
			
			if ($this->EE->config->item('allow_multi_emails') == 'n')
			{
				$this->EE->db->where('email', $str);
				$count = $this->EE->db->count_all_results('members');

				if ($count > 0)
				{							
					$this->set_message('valid_email', $this->EE->lang->line('email_taken'));      
					return FALSE;
				}
			}
		}
		
		return TRUE;
	}
	
	// --------------------------------------------------------------------

	/**
	 * Set old value
	 *
	 * Required for some rules to exclude current value from the
	 * *exists* checks (email, username, screen name)
	 *
	 * @access	public
	 * @param	mixed
	 * @return	void
	 */
	function set_old_value($key, $val = '')
	{
		if ( ! is_array($key))
		{
			$this->old_values[$key] = $val;
		}
		else
		{
			$this->old_values = array_merge ($this->old_values, $key);
		}
	}
	
	// --------------------------------------------------------------------

	/**
	 * Get old value
	 *
	 * @access	public
	 * @param	string
	 * @return	string
	 */
	function old_value($key)
	{
		// @todo usage of this in the method should really be based on the field
		// name, not a static keyword (as it is now). Problem is we don't
		// have access to the field name .... grr...
		return (isset($this->old_values[$key])) ? $this->old_values[$key] : '';
	}
	
	// --------------------------------------------------------------------

	/**
	 * Prep a list
	 *
	 * Unifies spaces/newlines/commas/pipes to $delim
	 *
	 * @access	public
	 * @param	string
	 * @return	string
	 */
	function prep_list($str, $delim = " ")
	{
		$str = trim($str);
		
		if ($delim == " ")
		{
			$str = preg_replace("/\t+/", " ", $str);
			$str = preg_replace("/\s+/", " ", $str);
			$str = preg_replace("/[,|]+/", " ", $str);
			$str = str_replace(array("\r\n", "\r", "\n"), " ", $str);
		}
		else
		{
			$str = preg_replace("/[\s,|]+/", $delim, $str);
			$str = trim($str, $delim);
		}

		return $str;
	}
	
	// --------------------------------------------------------------------

	/**
	 * Enum
	 *
	 * Check if a value is in a set
	 *
	 * @access	public
	 * @param	string
	 * @return	string
	 */
	function enum($str, $opts)
	{
		// @todo lang!
		$this->set_message('enum', 'The option you selected is not valid.');

		$opts = explode(',', $opts);
		return in_array($str, $opts);
	}
	
	// --------------------------------------------------------------------

	/**
	 * Lookup Dictionary Word
	 *
	 * Checks if a word is in the dictionary
	 *
	 * @access	private
	 * @param	string
	 * @param	string	update / new
	 * @return	bool
	 */
	function _lookup_dictionary_word($target)
	{
		if ($this->EE->config->item('allow_dictionary_pw') == 'y' OR $this->EE->config->item('name_of_dictionary_file') == '')
		{
			return FALSE;
		}
				
		$path = $this->EE->functions->remove_double_slashes(PATH_DICT.$this->EE->config->item('name_of_dictionary_file'));
		
		if ( ! file_exists($path))
		{
			return FALSE;
		}
		
		$word_file = file($path);

		foreach ($word_file as $word)
		{ 
		 	if (trim(strtolower($word)) == $target)
		 	{
				return TRUE;
			}
		}
		
		return FALSE;
	}

}
/* End of file EE_Form_validation.php */
/* Location: ./system/expressionengine/libraries/EE_Form_validation.php */
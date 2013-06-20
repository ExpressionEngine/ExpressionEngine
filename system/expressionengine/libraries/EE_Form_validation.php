<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
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
 * ExpressionEngine Form Validation Class
 *
 * @package		ExpressionEngine
 * @subpackage	Libraries
 * @category	Validation
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */
class EE_Form_validation extends CI_Form_validation {

	var $old_values = array();
	var $_fieldtype = NULL;

	/**
	 * Constructor
	 *
	 * @access	public
	 */	
	function __construct($rules = array())
	{	
		parent::__construct($rules);
		
		if ($this->CI->input->get('C') == 'addons_modules' &&
			$this->CI->input->get('M') == 'show_module_cp' &&
			isset($this->CI->_mcp_reference)
		)
		{
			$this->CI->_mcp_reference->lang =& $this->CI->lang;
			$this->CI->_mcp_reference->input =& $this->CI->input;
			$this->CI->_mcp_reference->security =& $this->CI->security;
			$this->CI =& $this->CI->_mcp_reference;
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
		$error = '';
		$value = TRUE;
		
		$exists = $this->CI->api_channel_fields->setup_handler($field_id);
        
        if ( ! $exists)
        {
            return TRUE;
        }

		$res = $this->CI->api_channel_fields->apply('validate', array($data));
		
		if (is_array($res))
		{
			// Overwrites $error and $value if they're set
			// array('error' => ..., 'value' => ...)
			
			extract($res);
		}
		else
		{
			$error = $res;
		}
		
		if ($error !== TRUE && $error != '')
		{
			$this->set_message('call_field_validation', $error);
			return FALSE;
		}
		
		return $value;
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
		
		$str = trim_nbs($str);
		
		// Is username formatting correct?
		// Reserved characters:  |  "  '  ! < > { }
		if (preg_match("/[\|'\"!<>\{\}]/", $str))
		{
			$this->set_message('valid_username', $this->CI->lang->line('invalid_characters_in_username'));      
			return FALSE;
		}
		
		// Is username min length correct?		
		$len = $this->CI->config->item('un_min_len');
	
		if (strlen($str) < $len)
		{
			$this->set_message('valid_username', str_replace('%x', $len, $this->CI->lang->line('username_too_short')));      
			return FALSE;
		}

		// Is username max length correct?
		if (strlen($str) > 50)
		{
			$this->set_message('valid_username', $this->CI->lang->line('username_password_too_long'));      
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

			if ($this->CI->session->ban_check('username', $str))
			{
				$this->set_message('valid_username', $this->CI->lang->line('username_taken'));      
				return FALSE;
			}


			// Is username taken?
			
			$this->CI->db->where('username', $str);
			$count = $this->CI->db->count_all_results('members');
							  
			if ($count > 0)
			{
				$this->set_message('valid_username', $this->CI->lang->line('username_taken'));      
				return FALSE;
			}
		}
		
		return $str;
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
			$this->set_message('valid_screen_name', $this->CI->lang->line('disallowed_screen_chars'));      
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
		
			if ($this->CI->session->ban_check('screen_name', $str) OR trim(preg_replace("/&nbsp;*/", '', $str)) == '')
			{
				$this->set_message('valid_screen_name', $this->CI->lang->line('screen_name_taken'));      
				return FALSE;
			}

			// Is screen name taken?

			if (strtolower($current) != strtolower($str))
			{
				$this->CI->db->where('screen_name', $str);
				$count = $this->CI->db->count_all_results('members');
		
				if ($count > 0)
				{							
					$this->set_message('valid_screen_name', $this->CI->lang->line('screen_name_taken'));      
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
		
		$len = $this->CI->config->item('pw_min_len');
	
		if (strlen($str) < $len)
		{
			$this->set_message('valid_password', str_replace('%x', $len, $this->CI->lang->line('password_too_short')));      
			return FALSE;
		}
		
		
		// Is password max length correct?
		
		if (strlen($str) > 40)
		{
			$this->set_message('valid_password', $this->CI->lang->line('username_password_too_long'));
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
			$this->set_message('valid_password', $this->CI->lang->line('password_based_on_username'));
			return FALSE;
		}		

		
		// Are secure passwords required?

		if ($this->CI->config->item('require_secure_passwords') == 'y')
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
					$this->set_message('valid_password', $this->CI->lang->line('not_secure_password'));
					return FALSE;
				}
			}
		}
		
		
		// Does password exist in dictionary?

		if ($this->_lookup_dictionary_word($lc_pass) == TRUE)
		{
			$this->set_message('valid_password', $this->CI->lang->line('password_in_dictionary'));      
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
		
		$str = trim_nbs($str);
		
		// Is email valid?

		if ( ! $this->valid_email($str))
		{
			$this->set_message('valid_user_email', $this->CI->lang->line('invalid_email_address'));      
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
		
			if ($this->CI->session->ban_check('email', $str))
			{
				$this->set_message('valid_user_email', $this->CI->lang->line('email_taken'));      
				return FALSE;
			}


			// Duplicate emails?
			
			$this->CI->db->where('email', $str);
			$count = $this->CI->db->count_all_results('members');

			if ($count > 0)
			{							
				$this->set_message('valid_user_email', $this->CI->lang->line('email_taken'));      
				return FALSE;
			}
		}
		
		return $str;
	}
	
	// --------------------------------------------------------------------

	/**
	 * Check to see if a date is valid by passing it to strtotime()
	 * @param  String $date Date value to validate
	 * @return Boolean      TRUE if it's a date, FALSE otherwise
	 */
	public function valid_date($date)
	{
		return (strtotime($date) !== FALSE);
	}

	// --------------------------------------------------------------------

	/**
	 * Deprecated method from SafeCracker, added for one version
	 * @deprecated 2.5
	 * @param  String $date Date value to validate
	 * @return Boolean      TRUE if it's a date, FALSE otherwise
	 */
	public function valid_ee_date($date)
	{
		$this->CI->load->library('logger');
		$this->CI->logger->deprecated('2.5', 'valid_date');
		return $this->valid_date($date);
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
		return (isset($this->old_values[$key])) ? $this->old_values[$key] : '';
	}

	// --------------------------------------------------------------------

	/**
	 * Sets additional object to check callbacks against such as fieldtypes
	 * to allow third-party fieldtypes to validate their settings forms
	 *
	 * @param	object 	Fieldtype to check callbacks against
	 * @return	void
	 */
	public function set_fieldtype($fieldtype)
	{
		$this->_fieldtype = $fieldtype;
	}

	// --------------------------------------------------------------------

	/**
	 * Get the value from a form
	 *
	 * Permits you to repopulate a form field with the value it was submitted
	 * with, or, if that value doesn't exist, with the default
	 *
	 * Overrides parent to also check POST
	 *
	 * @access	public
	 * @param	string	the field name
	 * @param	string
	 * @return	void
	 */
	function set_value($field = '', $default = '')
	{
		if ( ! isset($this->_field_data[$field]))
		{
			if (isset($_POST[$field]))
			{
				return form_prep($_POST[$field], $field);
			}

			return $default;
		}

		return $this->_field_data[$field]['postdata'];
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
		$this->set_message('enum', 'The option you selected is not valid.');

		$opts = explode(',', $opts);
		return in_array($str, $opts);
	}
	
	// --------------------------------------------------------------------


	/**
	 * Executes the Validation routines
	 *
	 * This is almost a direct copy out of the CI Form_validation lib.
	 * however there are a couple of differences in order to work with EE.
	 *
	 * @param 	protected 
	 * @param	array
	 * @param	array
	 * @param	mixed
	 * @param	integer
	 * @return	mixed
	 */	
	function _execute($row, $rules, $postdata = NULL, $cycles = 0)
	{
		// If the $_POST data is an array we will run a recursive call
		if (is_array($postdata))
		{ 
			foreach ($postdata as $key => $val)
			{
				$this->_execute($row, $rules, $val, $cycles);
				$cycles++;
			}
			
			return;
		}
		
		// --------------------------------------------------------------------

		// If the field is blank, but NOT required, no further tests are necessary
		$callback = FALSE;
		$ee_hack = FALSE;
	
		if ( ! in_array('required', $rules) AND is_null($postdata))
		{
			// Before we bail out, does the rule contain a callback?
			if (preg_match("/(callback_\w+)/", implode(' ', $rules), $match))
			{
				$callback = TRUE;
				$rules = (array('1' => $match[1]));
			}
			elseif (preg_match("/(call_field_validation\[.*?\])/", implode(' ', $rules), $match))
			{
				$ee_hack = TRUE;
				$rules = array($match[1]);
			}
			else
			{
				return;
			}
		}

		// --------------------------------------------------------------------
		
		// Isset Test. Typically this rule will only apply to checkboxes.
		if (is_null($postdata) AND $callback == FALSE AND $ee_hack == FALSE)
		{
			if (in_array('isset', $rules, TRUE) OR in_array('required', $rules))
			{
				// Set the message type
				$type = (in_array('required', $rules)) ? 'required' : 'isset';
			
				if ( ! isset($this->_error_messages[$type]))
				{
					if (FALSE === ($line = $this->CI->lang->line($type)))
					{
						$line = 'The field was not set';
					}							
				}
				else
				{
					$line = $this->_error_messages[$type];
				}
				
				// Build the error message
				$message = sprintf($line, $this->_translate_fieldname($row['label']));

				// Save the error message
				$this->_field_data[$row['field']]['error'] = $message;
				
				if ( ! isset($this->_error_array[$row['field']]))
				{
					$this->_error_array[$row['field']] = $message;
				}
			}
					
			return;
		}

		// --------------------------------------------------------------------

		// Cycle through each rule and run it
		foreach ($rules As $rule)
		{
			$_in_array = FALSE;
			
			// We set the $postdata variable with the current data in our master array so that
			// each cycle of the loop is dealing with the processed data from the last cycle
			if ($row['is_array'] == TRUE AND is_array($this->_field_data[$row['field']]['postdata']))
			{
				// We shouldn't need this safety, but just in case there isn't an array index
				// associated with this cycle we'll bail out
				if ( ! isset($this->_field_data[$row['field']]['postdata'][$cycles]))
				{
					continue;
				}
			
				$postdata = $this->_field_data[$row['field']]['postdata'][$cycles];
				$_in_array = TRUE;
			}
			else
			{
				$postdata = $this->_field_data[$row['field']]['postdata'];
			}

			// --------------------------------------------------------------------
	
			// Is the rule a callback?			
			$callback = FALSE;
			if (substr($rule, 0, 9) == 'callback_')
			{
				$rule = substr($rule, 9);
				$callback = TRUE;
			}
			
			// Strip the parameter (if exists) from the rule
			// Rules can contain a parameter: max_length[5]
			$param = FALSE;
			if (preg_match("/(.*?)\[(.*?)\]/", $rule, $match))
			{
				$rule	= $match[1];
				$param	= $match[2];
			}

			// Call the function that corresponds to the rule
			if ($callback === TRUE)
			{
				// Check the controller for the callback first
				if (method_exists($this->CI, $rule))
				{
					$object = $this->CI;
				}
				// Check fieldtype for the callback
				elseif (method_exists($this->_fieldtype, $rule))
				{ 
					$object = $this->_fieldtype;
				}
				else
				{
					continue;
				}
				
				// Run the function and grab the result
				$result = $object->$rule($postdata, $param);

				// Re-assign the result to the master data array
				if ($_in_array == TRUE)
				{
					$this->_field_data[$row['field']]['postdata'][$cycles] = (is_bool($result)) ? $postdata : $result;
				}
				else
				{
					$this->_field_data[$row['field']]['postdata'] = (is_bool($result)) ? $postdata : $result;
				}
			
				// If the field isn't required and we just processed a callback we'll move on...
				if ( ! in_array('required', $rules, TRUE) AND $result !== FALSE)
				{
					continue;
				}
			}
			else
			{				
				if ( ! method_exists($this, $rule))
				{
					// If our own wrapper function doesn't exist we see if a native PHP function does. 
					// Users can use any native PHP function call that has one param.
					if (function_exists($rule))
					{
						$result = $rule($postdata);
											
						if ($_in_array == TRUE)
						{
							$this->_field_data[$row['field']]['postdata'][$cycles] = (is_bool($result)) ? $postdata : $result;
						}
						else
						{
							$this->_field_data[$row['field']]['postdata'] = (is_bool($result)) ? $postdata : $result;
						}
					}
										
					continue;
				}

				$result = $this->$rule($postdata, $param);

				if ($_in_array == TRUE)
				{
					$this->_field_data[$row['field']]['postdata'][$cycles] = (is_bool($result)) ? $postdata : $result;
				}
				else
				{
					$this->_field_data[$row['field']]['postdata'] = (is_bool($result)) ? $postdata : $result;
				}
			}
							
			// Did the rule test negatively?  If so, grab the error.
			if ($result === FALSE)
			{			
				if ( ! isset($this->_error_messages[$rule]))
				{
					if (FALSE === ($line = $this->CI->lang->line($rule)))
					{
						$line = 'Unable to access an error message corresponding to your field name.';
					}						
				}
				else
				{
					$line = $this->_error_messages[$rule];
				}
				
				// Is the parameter we are inserting into the error message the name
				// of another field?  If so we need to grab its "field label"
				if (isset($this->_field_data[$param]) AND isset($this->_field_data[$param]['label']))
				{
					$param = $this->_translate_fieldname($this->_field_data[$param]['label']);
				}
				
				// Build the error message
				$message = sprintf($line, $this->_translate_fieldname($row['label']), $param);

				// Save the error message
				$this->_field_data[$row['field']]['error'] = $message;
				
				if ( ! isset($this->_error_array[$row['field']]))
				{
					$this->_error_array[$row['field']] = $message;
				}
				
				return;
			}
		}
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
		if ($this->CI->config->item('allow_dictionary_pw') == 'y' OR $this->CI->config->item('name_of_dictionary_file') == '')
		{
			return FALSE;
		}
				
		$path = reduce_double_slashes(PATH_DICT.$this->CI->config->item('name_of_dictionary_file'));
		
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

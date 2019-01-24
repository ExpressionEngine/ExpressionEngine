<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2019, EllisLab Corp. (https://ellislab.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

/**
 * Core Validation
 */
class EE_Validate {

	var $member_id			= '';
	var $val_type			= 'update';
	var $fetch_lang 		= TRUE;
	var $require_cpw 		= FALSE;
	var $username			= '';
	var	$cur_username		= '';
	var $screen_name		= '';
	var $cur_screen_name	= '';
	var $password			= '';
	var	$password_confirm	= '';
	var $email				= '';
	var $cur_email			= '';
	var $errors 			= array();
	var $enable_log			= FALSE;
	var $log_msg			= array();

	/**
	 * Construct
	 */
	function __construct($data = '')
	{
		$vars = array(
				'member_id', 'username', 'cur_username', 'screen_name',
				'cur_screen_name', 'password', 'password_confirm',
				'cur_password', 'email', 'cur_email'
			);

		if (is_array($data))
		{
			foreach ($vars as $val)
			{
				$this->$val	= (isset($data[$val])) ? $data[$val] : '';
			}
		}

		if (isset($data['fetch_lang']))		$this->fetch_lang 	= $data['fetch_lang'];
		if (isset($data['require_cpw']))	$this->require_cpw 	= $data['require_cpw'];
		if (isset($data['enable_log']))		$this->enable_log 	= $data['enable_log'];
		if (isset($data['val_type']))		$this->val_type 	= $data['val_type'];
		if ($this->fetch_lang == TRUE)		ee()->lang->loadfile('myaccount');
		if ($this->require_cpw == TRUE)		$this->password_safety_check();
	}

	/**
	 * Password safety check
	 *
	 */
	function password_safety_check()
	{
		if ($this->cur_password == '')
		{
			return $this->errors[] = ee()->lang->line('missing_current_password');
		}

		ee()->load->library('auth');

		// Get the users current password
		$pq = ee()->db->select('password, salt')
			->get_where('members', array(
				'member_id' => (int) ee()->session->userdata('member_id')
			));

		if ( ! $pq->num_rows())
		{
			$this->errors[] = ee()->lang->line('invalid_password');
		}

		$passwd = ee()->auth->hash_password($this->cur_password, $pq->row('salt'));

		if ( ! isset($passwd['salt']) OR ($passwd['password'] != $pq->row('password')))
		{
			$this->errors[] = ee()->lang->line('invalid_password');
		}
	}

	/**
	 * Validate Username
	 */
	function validate_username()
	{
		$type = $this->val_type;

		// Is username missing?
		if ($this->username == '')
		{
			return $this->errors[] = ee()->lang->line('missing_username');
		}

		// Is username formatting correct?
		// Reserved characters:  |  "  '  !
		if (preg_match("/[\|'\"!<>\{\}]/", $this->username))
		{
			$this->errors[] = ee()->lang->line('invalid_characters_in_username');
		}

		// Is username min length correct?
		$len = ee()->config->item('un_min_len');

		if (strlen($this->username) < $len)
		{
			$this->errors[] = sprintf(lang('username_too_short'), $len);
		}

		// Is username max length correct?
		if (strlen($this->username) > USERNAME_MAX_LENGTH)
		{
			$this->errors[] = ee()->lang->line('username_too_long');
		}

		// Set validation type
		if ($this->cur_username != '')
		{
			if ($this->cur_username != $this->username)
			{
				$type = 'new';

				if ($this->enable_log == TRUE)
				{
					$this->log_msg[] = ee()->lang->line('username_changed').NBS.NBS.$this->username;

				}
			}
		}

		if ($type == 'new')
		{
			// Is username banned?
			if (ee()->session->ban_check('username', $this->username))
			{
				$this->errors[] = ee()->lang->line('username_taken');
			}

			// Is username taken?
			ee()->db->from('members');
			ee()->db->where('username = LOWER('.ee()->db->escape($this->username).')', NULL, FALSE);
			ee()->db->where('LOWER(username) = '.ee()->db->escape(strtolower($this->username)), NULL, FALSE);
			$count = ee()->db->count_all_results();

			if ($count  > 0)
			{
				$this->errors[] = ee()->lang->line('username_taken');
			}
		}
	}

	/**
	 * Validate screen name
	 */
	function validate_screen_name()
	{
		if ($this->screen_name == '')
		{
			if ($this->username == '')
			{
				return $this->errors[] = ee()->lang->line('missing_username');
			}

			return $this->screen_name = $this->username;
		}

		if (preg_match('/[\{\}<>]/', $this->screen_name))
		{
			return $this->errors[] = ee()->lang->line('disallowed_screen_chars');
		}

		if (strlen($this->screen_name) > USERNAME_MAX_LENGTH)
		{
			return $this->errors[] = ee()->lang->line('screenname_too_long');
		}

		/** -------------------------------------
		/**  Is screen name banned?
		/** -------------------------------------*/

		if (ee()->session->ban_check('screen_name', $this->screen_name) OR trim(preg_replace("/&nbsp;*/", '', $this->screen_name)) == '')
		{
			return $this->errors[] = ee()->lang->line('screen_name_taken');
		}
	}

	/**
	 * Validate Password
	 *
	 * @return 	mixed 	array on failure, void on success
	 */
	function validate_password()
	{
		/** ----------------------------------
		/**  Is password missing?
		/** ----------------------------------*/

		if ($this->password == '' AND $this->password_confirm == '')
		{
			return $this->errors[] = ee()->lang->line('missing_password');
		}

		/** -------------------------------------
		/**  Is password min length correct?
		/** -------------------------------------*/

		$len = ee()->config->item('pw_min_len');

		if (strlen($this->password) < $len)
		{
			return $this->errors[] = sprintf(lang('password_too_short'), $len);
		}

		/** -------------------------------------
		/**  Is password max length correct?
		/** -------------------------------------*/
		if (strlen($this->password) > PASSWORD_MAX_LENGTH)
		{
			return $this->errors[] = ee()->lang->line('password_too_long');
		}

		/** -------------------------------------
		/**  Is password the same as username?
		/** -------------------------------------*/
		// We check for a reversed password as well

		//  Make UN/PW lowercase for testing

		$lc_user = strtolower($this->username);
		$lc_pass = strtolower($this->password);
		$nm_pass = strtr($lc_pass, 'elos', '3105');


		if ($lc_user == $lc_pass OR $lc_user == strrev($lc_pass) OR $lc_user == $nm_pass OR $lc_user == strrev($nm_pass))
		{
			return $this->errors[] = ee()->lang->line('password_based_on_username');
		}

		/** -------------------------------------
		/**  Do Password and confirm match?
		/** -------------------------------------*/

		if ($this->password != $this->password_confirm)
		{
			return $this->errors[] = ee()->lang->line('missmatched_passwords');
		}

		/** -------------------------------------
		/**  Are secure passwords required?
		/** -------------------------------------*/
		if (ee()->config->item('require_secure_passwords') == 'y')
		{
			$count = array('uc' => 0, 'lc' => 0, 'num' => 0);

			$pass = preg_quote($this->password, "/");

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
					return $this->errors[] = ee()->lang->line('not_secure_password');
				}
			}
		}


		/** -------------------------------------
		/**  Does password exist in dictionary?
		/** -------------------------------------*/
		if ($this->lookup_dictionary_word($lc_pass) == TRUE)
		{
			$this->errors[] = ee()->lang->line('password_in_dictionary');
		}
	}

	/**
	 * Validate Email
	 *
	 *
	 * @return 	mixed 	array on failure, void on success
	 */
	function validate_email()
	{
		$type = $this->val_type;

		/** -------------------------------------
		/**  Is email missing?
		/** -------------------------------------*/

		if ($this->email == '')
		{
			return $this->errors[] = ee()->lang->line('missing_email');
		}

		/** -------------------------------------
		/**  Is email valid?
		/** -------------------------------------*/

		ee()->load->helper('email');

		if ( ! valid_email($this->email))
		{
			return $this->errors[] = ee()->lang->line('invalid_email_address');
		}

		/** -------------------------------------
		/**  Set validation type
		/** -------------------------------------*/

		if ($this->cur_email != '')
		{
			if ($this->cur_email != $this->email)
			{
				if ($this->enable_log == TRUE)
				{
					$this->log_msg = ee()->lang->line('email_changed').NBS.NBS.$this->email;
				}

				$type = 'new';
			}
		}

		if ($type == 'new')
		{
			/** -------------------------------------
			/**  Is email banned?
			/** -------------------------------------*/
			if (ee()->session->ban_check('email', $this->email))
			{
				return $this->errors[] = ee()->lang->line('email_taken');
			}

			/** -------------------------------------
			/**  Duplicate emails?
			/** -------------------------------------*/
			if ( ! ee('Validation')->check('uniqueEmail', $this->email))
			{
				$this->errors[] = ee()->lang->line('email_taken');
			}
		}
	}

	/**
	 * Show Errors
	 *
	 * @return 	string
	 */
	function show_errors()
	{
		 if (count($this->errors) > 0)
		 {
			$msg = '';

			foreach($this->errors as $val)
			{
				$msg .= $val.'<br />';
			}

			return $msg;
		 }
	}

  	/**
	 * Lookup word in dictionary file
	 *
	 * @param 	string
	 * @return 	boolean
	 */
	function lookup_dictionary_word($target)
	{
		if (ee()->config->item('allow_dictionary_pw') == 'y' OR ee()->config->item('name_of_dictionary_file') == '')
		{
			return FALSE;
		}

		$path = reduce_double_slashes(PATH_DICT.ee()->config->item('name_of_dictionary_file'));

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
// END CLASS

// EOF

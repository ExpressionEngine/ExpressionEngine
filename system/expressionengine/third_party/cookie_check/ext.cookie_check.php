<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Cookie_check_ext {

	var $name = 'Cookie Check';
	var $version = '1.0';
	var $settings_exist = 'n';
	var $docs_url = 'http://expressionengine.com/user_guide/modules/cookie_check/index.html';
	var $required_by = array('module');

	private $EE;
	
	/**
	 * Constructor
	 */
	function __construct()
	{
		$this->EE =& get_instance();
	}

	// --------------------------------------------------------------------

	/**
	 * Checks if cookies are allowed- if not, deletes EE cookies
	 */
	function check_cookie_permission($data)
	{
		if ($this->EE->input->cookie('cookies_allowed'))
		{
			return;
		}

		// If they are setting cookies allowed- allow it
		if ($data['name'] == 'cookies_allowed')
		{
			return;
		}

		// This is a bit awkward, but it allows us to let them accept cookies from the
		// login pages.  The way cookies work, it's the only method I see.
		if ($this->EE->input->post('cookie_consent') == 'y')
		{
			return;
		}

		$time = time();


		// If they are unsetting a cookie- allow it
		if ($data['expire'] != 0 && $data['expire'] < $time)
		{
			return;
		}

		$this->EE->extensions->end_script = TRUE;

		$expire = $time - 86500;
		$prefix_length = strlen($data['prefix']);

		// Clear existing cookies
		// Note- could use function set_cookie, but this is leaner.
		// Why calculate all the variables again?
		foreach($_COOKIE as $name => $value)
		{
			if (strncmp($name, $data['prefix'], $prefix_length) == 0)
			{
				setcookie($name, FALSE, $expire, 
					$data['path'], $data['domain'], $data['secure_cookie']);
			}
		}

		return FALSE;
	}

	// --------------------------------------------------------------------

	/**
	 * Require cookie consent for Frontend login
	 */
	function front_login_cookie_required()
	{
		if ($this->_form_submission_check())
		{
			return;
		}

		$this->EE->extensions->end_script = TRUE;

		// I do not love this method of outputting the error
		// @todo- rewrite members!

		$this->EE->output->show_user_error('general', lang('cookie_consent_required'));		
	}

	// --------------------------------------------------------------------

	/**
	 * Require cookie consent for Frontend registration
	 */
	function front_register_cookie_required($errors)
	{
		if ($this->_form_submission_check() == FALSE)
		{
			$errors[] = lang('cookie_consent_required');
		}

		return $errors;		
	}


	// --------------------------------------------------------------------

	/**
	 * Require cookie consent for CP login
	 */
	function cp_login_cookie_required($data)
	{
		if ($this->_form_submission_check())
		{
			return;
		}

		// Bleh- errors here are displayed using flash data
		// Which yes- requires cookies be enabled
		// Thus we have to fall back on yee old style error display

		$this->EE->extensions->end_script = TRUE;

		$this->EE->output->show_user_error('general', lang('cookie_consent_required'));	
	}



	// --------------------------------------------------------------------

	/**
	 * Handles form submission to see if cookies are allowed
	 */
	function _form_submission_check()
	{
		if ($this->EE->input->cookie('cookies_allowed'))
		{
			return TRUE;
		}

		if ($this->EE->input->post('cookie_consent') == 'y')
		{
			$expires = 60*60*24*365;  // 1 year

			$this->EE->functions->set_cookie('cookies_allowed', 'y', $expires);

			return TRUE;
		}

		return FALSE;		
	}


	// --------------------------------------------------------------------

	/**
	 * Frontend login form and registration form access check
	 * todo - I find this annoying so remove?
	 */
	function front_form_access_check($data)
	{
		if ($this->EE->input->cookie('cookies_allowed'))
		{
			return;
		}

		if ($data['request'] != 'login' && $data['request'] != 'register')
		{
			return;
		}

		$this->send_rejection_notice($data['request']);

	}

	// --------------------------------------------------------------------

	/**
	 * Backend login form access check
	 * todo - I find this annoying so remove?	 
	 */
	function cp_form_access_check($data)
	{
		// ack- um.....

		//$this->send_rejection_notice($data['request']);

	}


	// --------------------------------------------------------------------

	/**
	 * Show no access message
	 * todo - goes along with the annoying bits
	 */
	function send_rejection_notice($type)
	{
		$data = array(
			'title' 	=> $this->EE->lang->line('cookies_required'),
			'heading'	=> $this->EE->lang->line('cookies_required'),
			'content'	=> $this->EE->lang->line('cookies_required_descrption'),
			'redirect'	=> $return_link,
			'link'		=> array($return_link, $this->EE->lang->line('cookies_return_to_home'))
		);

		$this->EE->output->show_message($data);	
	}

	// --------------------------------------------------------------------

	/**
	 * Add some variables to the frontend member registration form (member_member_register_form_end)
	 * todo - can nuke this if we add form_finalize hook
	 */
	function modify_member_registration_form($data, $reg_form)
	{
		return $this->_parse_member_forms($reg_form);	
	}


	// --------------------------------------------------------------------

	/**
	 * Add some variables to the frontend member login form (member_member_register_form_end)
	 * todo - can nuke this if we add form_finalize hook
	 */
	function modify_member_login_form($data, $login_form)
	{
	
		return $this->_parse_member_forms($login_form);
	}

	// --------------------------------------------------------------------

	/**
	 * Parse member forms variables
	 */
	function _parse_member_forms($form)
	{
		$this->EE->lang->loadfile('cookie_check');

		$cookies_allowed = ($this->EE->input->cookie('cookie_consent') == 'y') ? TRUE : FALSE;

		// {if cookies_required}
		if (preg_match("/{if cookies_required}(.+?){\/if}/s", $form, $match))
		{
			if ($cookies_allowed)
			{
				$form = preg_replace("/{if cookies_required}.+?{\/if}/s", "", $form); 
			}
			else
			{	

				$form = preg_replace("/{if cookies_required}.+?{\/if}/s", $match['1'], $form);
			}

		}

		$checkbox_setting = ($cookies_allowed) ? 'checked="checked"' : ''; 

		$form = str_replace('{cookie_consent_setting}', $checkbox_setting, $form);

		return $form;
	}

	// --------------------------------------------------------------------

	/**
	 * Modify Frontend login and registration forms
	 */
	function modify_module_forms($data, $form)
	{
		$valid_forms = array('register_member', 'member_login');

		$act_class = (isset($data['hidden_fields']['ACT'])) ? $data['hidden_fields']['ACT'] : FALSE;

		if ( ! $act_class)
		{
			return $form;
		}

		foreach ($valid_forms as $method)
		{
			// Quick check
			if (strpos($act_class, $method) !== FALSE)
			{
				// Strict check
				$method_seg = array_pop(explode(':', $act_class));

				if ($method_seg == $method.RD)
				{
					return $this->_parse_member_forms($form);
				}
			}
		}
		return $form;
	}

	// --------------------------------------------------------------------

	/**
	 * Activate Extension
	 */
	function activate_extension()
	{
		return TRUE;
	}

	// --------------------------------------------------------------------

	/**
	 * Update Extension
	 */
	function update_extension($current = FALSE)
	{


		return TRUE;
	}

	// --------------------------------------------------------------------

	/**
	 * Disable Extension
	 */
	function disable_extension()
	{
		show_error('This extension is automatically deleted with the Cookie module');
	}
	
		// --------------------------------------------------------------------

	/**
	 * Disable Extension
	 */
	function uninstall_extension()
	{
		return TRUE;

	}
	
}

/* End of file ext.cookie_check.php */
/* Location: ./system/expressionengine/extensions/ext.cookie_check.php */
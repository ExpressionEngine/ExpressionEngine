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
	 * Make the module work in the forum
	 */
	function forum_add_template($which, $classname)
	{
		if ($which == 'cookie_check_message')
		{
			$classname = 'cookie_check';
		}

		return $classname;
	}

	// --------------------------------------------------------------------

	/**
	 * Make the module work in the forum
	 */
	function parse_forum_template($obj, $function, $template)
	{
		// Check for tags
		if ($function != 'cookie_check_message')
		{
			return $template;
		}

		$cookies_allowed = ($this->EE->input->cookie('cookies_allowed')) ? TRUE : FALSE;

		if ($cookies_allowed)
		{
			$template = $obj->deny_if('cookies_not_allowed', $template);
			$template = $obj->allow_if('cookies_allowed', $template);
		}
		else
		{
			$template = $obj->allow_if('cookies_not_allowed', $template);
			$template = $obj->deny_if('cookies_allowed', $template);
		}

		$allowed_link = $this->cookies_allowed_link();
		$clear_link = $this->clear_cookies_link('ee');

		$vars = array('{cookies_allowed_link}', '{clear_cookies_link}');
		$values = array($allowed_link, $clear_link);
		$template = str_replace($vars, $values, $template);

		return $template;
	}


	// --------------------------------------------------------------------

	/**
	 * Create cookies allowed link
	 */
	function cookies_allowed_link()
	{
		$link = $this->EE->functions->fetch_site_index(0, 0).QUERY_MARKER.'ACT='
			.$this->EE->functions->fetch_action_id('Cookie_check', 'set_cookies_allowed');

		$link .= AMP.'RET='.$this->EE->uri->uri_string();	
		
		return $link;		
	}

	// --------------------------------------------------------------------

	/**
	 * Create the 'clear cookies' link
	 */
	function clear_cookies_link($clear_all)
	{
		$link = $this->EE->functions->fetch_site_index(0, 0).QUERY_MARKER.'ACT='
			.$this->EE->functions->fetch_action_id('Cookie_check', 'clear_ee_cookies');

		$link .= AMP.'CLEAR='.$clear_all;			

		$link .= AMP.'RET='.$this->EE->uri->uri_string();	
		
		return $link;		
	}	

// @todo nuke above 2 functions- move to lib???



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
	function front_register_cookie_required($obj)
	{
		if ($this->_form_submission_check() == FALSE)
		{
			$obj->errors[] = lang('cookie_consent_required');
		}

		return;		
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
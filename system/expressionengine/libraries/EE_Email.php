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
 * ExpressionEngine Core Email Class
 *
 * @package		ExpressionEngine
 * @subpackage	Core
 * @category	Core
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */
class EE_Email extends CI_Email {


	/**
	 * Constructor
	 */	
	function __construct($init = TRUE)
	{
		parent::__construct();

		if ($init != TRUE)
			return;

		// Make a local reference to the ExpressionEngine super object
		$this->EE =& get_instance();
			
		$this->EE_initialize();		
	}	

	// --------------------------------------------------------------------

	/**
	 * Set config values
	 *
	 * @access	private
	 * @return	void
	 */
	function EE_initialize()
	{	
		$config = array(
						'protocol'		=> ( ! in_array( $this->EE->config->item('mail_protocol'), $this->_protocols)) ? 'mail' : $this->EE->config->item('mail_protocol'),
						'charset'		=> ($this->EE->config->item('email_charset') == '') ? 'utf-8' : $this->EE->config->item('email_charset'),
						'smtp_host'		=> $this->EE->config->item('smtp_server'),
						'smtp_user'		=> $this->EE->config->item('smtp_username'),
						'smtp_pass'		=> $this->EE->config->item('smtp_password')
						);
		
		/* -------------------------------------------
		/*	Hidden Configuration Variables
		/*	- email_newline => Default newline.
		/*  - email_crlf => CRLF used in quoted-printable encoding
		/*  - email_smtp_port => SMTP Port
		/*  - email_smtp_crypto => Cryptographic protocol (Secure Sockets Layer or Transport Layer Security allowed) 
        /* -------------------------------------------*/
		
		if ($this->EE->config->item('email_newline') !== FALSE)
		{
			$config['newline'] = $this->EE->config->item('email_newline');
		}
		
		if ($this->EE->config->item('email_crlf') !== FALSE)
		{
			$config['crlf'] = $this->EE->config->item('email_crlf');
		}

		if ($this->EE->config->item('email_smtp_port') !== FALSE)
		{
			$config['smtp_port'] = $this->EE->config->item('email_smtp_port');
		}
				
		if ($this->EE->config->item('email_smtp_crypto') == 'tls' OR $this->EE->config->item('email_smtp_crypto') == 'ssl')
		{
			$config['smtp_crypto'] = $this->EE->config->item('email_smtp_crypto');
		}
		
		$this->useragent = APP_NAME.' '.APP_VER;		

		$this->initialize($config);
	}
	
	// --------------------------------------------------------------------

	/**
	 * Set the email message
	 *
	 * EE uses action ID's so we override the messsage() function 	 
	 *
	 * @access	public
	 * @return	void
	 */	 
	function message($body, $alt = '')
	{
		$body = $this->EE->functions->insert_action_ids($body);
	
		if ($alt != '')
		{
			$this->set_alt_message($this->EE->functions->insert_action_ids($alt));
		}
				
		$this->_body = stripslashes(rtrim(str_replace("\r", "", $body)));
		return $this;
	}
	
	// --------------------------------------------------------------------
	
	/**
	 * Override _spool_email so we can provide a hook
	 */
	function _spool_email()
	{
		// ------------------------------------------------------
		// 'email_send' hook.
		//  - Optionally modifies and overrides sending of email.
		//
		if ($this->EE->extensions->active_hook('email_send') === TRUE)
		{
			$ret = $this->EE->extensions->call(
				'email_send',
				array(
					'headers'		=> &$this->_headers,
					'header_str'	=> &$this->_header_str,
					'recipients'	=> &$this->_recipients,
					'cc_array'		=> &$this->_cc_array,
					'bcc_array'		=> &$this->_bcc_array,
					'subject'		=> &$this->_subject,
					'finalbody'		=> &$this->_finalbody
				)
			);
			
			if ($this->EE->extensions->end_script === TRUE)
			{
				return $ret;
			}
		}
		//
		// ------------------------------------------------------
		
		return parent::_spool_email();
	}
	
	// --------------------------------------------------------------------

	/**
	 * Mime Types
	 *
	 * @param	string
	 * @return	string
	 */
	protected function _mime_types($ext = '')
	{
		static $mimes;

		$ext = strtolower($ext);

		if ( ! is_array($mimes))
		{
			include(APPPATH.'config/mimes.php');			
		}

		if (isset($mimes[$ext]))
		{
			return is_array($mimes[$ext])
				? current($mimes[$ext])
				: $mimes[$ext];
		}

		return 'application/x-unknown-content-type';
	}

	// --------------------------------------------------------------------
	
	/**
	 * Removed from CI, here temporarily during deprecation period
	 */
	function _get_ip()
	{
		$this->EE->load->library('logger');
		$this->EE->logger->deprecated('2.6', 'Email::_get_ip()');

		// Why was this ever here?
		return $this->EE->input->ip_address();
	}

	// --------------------------------------------------------------------
	
	/**
	 * Removed from CI, here temporarily during deprecation period
	 */
	function _set_header($header, $value)
	{
		$this->EE->load->library('logger');
		$this->EE->logger->deprecated('2.6', 'Email::_set_header()');

		$this->set_header($header, $value);
	}
}
// END CLASS

/* End of file EE_Email.php */
/* Location: ./system/expressionengine/libraries/EE_Email.php */
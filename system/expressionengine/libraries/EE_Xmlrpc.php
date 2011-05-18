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
 * ExpressionEngine Core XMLRPC Class
 *
 * @package		ExpressionEngine
 * @subpackage	Core
 * @category	Core
 * @author		ExpressionEngine Dev Team
 * @link		http://expressionengine.com
 */
class EE_Xmlrpc extends CI_Xmlrpc {

	/**
	 * Constructor
	 */	
	function __construct($init = TRUE)
	{
		parent::__construct();

		if ($init != TRUE)
		{
			return;
		}

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
    function weblogs_com_ping($server, $port=80, $name, $blog_url, $rss_url = '')
    {
		$this->server($server, $port);
		$this->timeout(5);
		
		if (stristr($server, 'ping.expressionengine.com') === FALSE)
		{
			if ($rss_url != '')
			{
				$this->method('weblogUpdates.extendedPing');
				$this->request(array(
					$name,
					$blog_url,
					$this->EE->config->item('site_index'),
					$rss_url
				));
				
				if ( ! $this->EE->xmlrpc->send_request())
				{
					$this->method('weblogUpdates.ping');
					$this->request(array(
						$name,
						$blog_url
					));
				}
				else
				{
					return TRUE;
				}
			}
			else
			{
				$this->method('weblogUpdates.ping');
				$this->request(array(
					$name,
					$blog_url
				));
			}
		}
		else
		{
			if ( ! $license = $this->EE->config->item('license_number'))
			{
				$this->EE->lang->loadfile('xmlrpc');
				$this->error = $this->EE->lang->line('invalid_license');
				return $this->display_error();
			}
			
			$this->method('ExpressionEngine.ping');
			$this->request(array(
				$name,
				$blog_url,
				$license
			));
		}
		
		if ( ! $this->send_request())
		{
			return $this->display_error();
		}
		
		return TRUE;
    }
}
// END CLASS

/* End of file EE_Xmlrpc.php */
/* Location: ./system/expressionengine/libraries/EE_Xmlrpc.php */
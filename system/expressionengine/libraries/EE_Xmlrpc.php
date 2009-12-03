<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		ExpressionEngine Dev Team
 * @copyright	Copyright (c) 2003 - 2009, EllisLab, Inc.
 * @license		http://expressionengine.com/docs/license.html
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
	function EE_Xmlrpc($init = TRUE)
	{
		parent::CI_Xmlrpc();

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
		if (stristr($server, 'ping.pmachine.com') !== FALSE)
		{
			$server = str_replace('ping.pmachine.com', 'ping.expressionengine.com', $server);
		}
		
		/* @todo Weblogs.com Fixeroo ?
		
		// $server = "rpc.weblogs.com/RPC2/";
		if (substr($server, 0, 4) != "http") $server = "http://".$server; 
		
		$parts = parse_url($server);
		
		if (isset($parts['path']) && $parts['path'] == "/RPC2/")
		{
			$path = str_replace('/RPC2/', '/RPC2', $parts['path']);
		}
		else
		{
			$path = (!isset($parts['path'])) ? '/' : $parts['path'];
		}
		*/
		
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
				
				if ( ! $this->xmlrpc->send_request())
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
				// @todo return 'Invalid License';
			}
			
			$this->method('Expressionengine.ping');
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
<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Checks two IP addresses to see if they match up to a certain accuracy
 * @param  String 	$current_ip 	The current IP to comparre
 * @param  String 	$stored_ip  	The stored IP to compare
 * @param  Int 		$accuracy   	The number of octets to check
 * @return Boolean, true if they are equivalent up to the desired accuracy
 */
function ip_accuracy_check($current_ip, $stored_ip, $accuracy)
{
	$current_ip = explode('.', $current_ip);
	$stored_ip = explode('.', $stored_ip);
	
	for ($octet = 0; $octet < $accuracy; $octet++)
	{ 
		if ($current_ip[$octet] != $stored_ip[$octet])
		{
			return false;
		}
	}

	return true;
}

// -----------------------------------------------------------------------------

/**
 * Returns abbreviated IP address with a certain number of octets
 * @param  String 	$ip_address 	The IP address to abbreviate
 * @param  Int 		$accuracy   	The number of octets to return
 * @return String, the abbreviated IP address
 */
function ip_accuracy_like($ip_address, $accuracy)
{
	$current_ip = explode('.', $ip_address);
	$like_ip = array();
	
	for ($octet = 0; $octet < $accuracy; $octet++)
	{ 
		$like_ip[] = $current_ip[$octet];
	}

	return implode('.', $like_ip);
}

// -----------------------------------------------------------------------------

/**
 * Get the current IP accuracy as defined in config
 * @return Int, the current level of IP accuracy
 */
function get_ip_accuracy()
{
	$EE =& get_instance();

	return (is_numeric($EE->config->item('session_ip_accuracy'))) ? 
		(int) $EE->config->item('session_ip_accuracy') : 
		4;
}

/* End of file ip_helper.php */
/* Location: ./system/expressionengine/helpers/ip_helper.php */
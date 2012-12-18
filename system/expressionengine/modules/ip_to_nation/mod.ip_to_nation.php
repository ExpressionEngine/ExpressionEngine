<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2012, EllisLab, Inc.
 * @license		http://ellislab.com/expressionengine/user-guide/license.html
 * @link		http://ellislab.com
 * @since		Version 2.0
 * @filesource
 */
 
// ------------------------------------------------------------------------

/**
 * ExpressionEngine Ip to Nation Module
 *
 * @package		ExpressionEngine
 * @subpackage	Modules
 * @category	Modules
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */

class Ip_to_nation {

	var $return_data = '';

	function __construct()
	{
		$this->EE =& get_instance();
	}

	// ----------------------------------------------------------------------

	/**
	 * World flags
	 */
	function world_flags($ip = '')
	{
		if ($ip == '')
		{
			$ip = $this->EE->TMPL->tagdata;
		}

		$ip = trim($ip);

		if ( ! $this->EE->input->valid_ip($ip))
		{
			$this->return_data = $ip;
			return;
		}

		$this->EE->load->model('ip_to_nation_data', 'ip_data');

		$c_code = $this->EE->ip_data->find($ip);

		if ( ! $c_code)
		{
			$this->return_data = $ip;
			return;
		}

		$country = $this->get_country($c_code);

		if ($this->EE->TMPL->fetch_param('type') == 'text')
		{
			$this->return_data = $country;
		}
		else
		{
			$this->return_data = '<img src="'.$this->EE->TMPL->fetch_param('image_url').'flag_'.$c_code.'.gif" width="18" height="12" alt="'.$country.'" title="'.$country.'" />';
		}

		return $this->return_data;
	}

	// ----------------------------------------------------------------------
	
	/**
	 * Countries
	 */
	function get_country($which = '')
	{
		if ( ! isset($this->EE->session->cache['ip_to_nation']['countries']))
		{
			if ( ! include_once(APPPATH.'config/countries.php'))
			{
				$this->EE->TMPL->log_item("IP to Nation Module Error: Countries library file not found");
				return 'Unknown';
			}

			$this->EE->session->cache['ip_to_nation']['countries'] = $countries;
		}

		if ( ! isset($this->EE->session->cache['ip_to_nation']['countries'][$which]))
		{
			return 'Unknown';
		}

		return $this->EE->session->cache['ip_to_nation']['countries'][$which];
	}
}
// END CLASS

/* End of file mod.ip_to_nation.php */
/* Location: ./system/expressionengine/modules/ip_to_nation/mod.ip_to_nation.php */
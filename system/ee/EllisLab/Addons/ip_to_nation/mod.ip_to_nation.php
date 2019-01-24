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
 * Ip to Nation Module
 */
class Ip_to_nation {

	var $return_data = '';

	/**
	 * World flags
	 */
	function world_flags($ip = '')
	{
		if ($ip == '')
		{
			$ip = ee()->TMPL->tagdata;
		}

		$ip = trim($ip);

		if ( ! ee()->input->valid_ip($ip))
		{
			$this->return_data = $ip;
			return;
		}

		ee()->load->model('ip_to_nation_data', 'ip_data');

		$c_code = ee()->ip_data->find($ip);

		if ( ! $c_code)
		{
			$this->return_data = $ip;
			return;
		}

		$country = $this->get_country($c_code);

		if (ee()->TMPL->fetch_param('type') == 'text')
		{
			$this->return_data = $country;
		}
		else
		{
			$this->return_data = '<img src="'.ee()->TMPL->fetch_param('image_url').'flag_'.$c_code.'.gif" width="18" height="12" alt="'.$country.'" title="'.$country.'" />';
		}

		return $this->return_data;
	}

	/**
	 * Countries
	 */
	function get_country($which = '')
	{
		if ( ! isset(ee()->session->cache['ip_to_nation']['countries']))
		{
			$conf = ee()->config->loadFile('countries');
			ee()->session->cache['ip_to_nation']['countries'] = $conf['countries'];
		}

		if ( ! isset(ee()->session->cache['ip_to_nation']['countries'][$which]))
		{
			return 'Unknown';
		}

		return ee()->session->cache['ip_to_nation']['countries'][$which];
	}
}
// END CLASS

// EOF

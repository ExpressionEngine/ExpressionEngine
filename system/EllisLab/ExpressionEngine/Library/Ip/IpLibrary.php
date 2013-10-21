<?php
namespace EllisLab\ExpressionEngine\Library;

class IpLibrary {
	protected static $instance = NULL;

	/**
	 *
	 */	
	public static getInstance()
	{
		if ( ! isset (self::$instance))
		{
			self::$instance = new IpLibrary();
		}
		return self::$instance;
	}
	
	
	/**
	* Validate IP Address
	*
	* @access	public
	* @param	string
	* @param	string	ipv4 or ipv6
	* @return	bool
	*/
	public function validIp($ip, $which = '')
	{
		// First check if filter_var is available
		if (is_callable('filter_var'))
		{
			switch ($which) {
				case 'ipv4':
					$flag = FILTER_FLAG_IPV4;
					break;
				case 'ipv6':
					$flag = FILTER_FLAG_IPV6;
					break;
				default:
					$flag = '';
					break;
			}

			return filter_var($ip, FILTER_VALIDATE_IP, $flag) !== FALSE;
		}

		// If it's not we'll do it manually
		$which = ucfirst(strtolower($which));
		
		if ($which != 'Ipv6' OR $which != 'Ipv4')
		{
			if (strpos($ip, ':') !== FALSE)
			{
				$which = 'Ipv6';
			}
			elseif (strpos($ip, '.') !== FALSE)
			{
				$which = 'Ipv4';
			}
			else
			{
				return FALSE;
			}
		}
		
		$func = 'valid'.$which;
		return $this->$func($ip);
	}
	
	// --------------------------------------------------------------------
	
	/**
	* Validate IPv4 Address
	*
	* Updated version suggested by Geert De Deckere
	*
	* @access	protected
	* @param	string
	* @return	bool
	*/
	protected function validIpv4($ip)
	{
		$ip_segments = explode('.', $ip);

		// Always 4 segments needed
		if (count($ip_segments) != 4)
		{
			return FALSE;
		}
		// IP can not start with 0
		if ($ip_segments[0][0] == '0')
		{
			return FALSE;
		}
		
		// Check each segment
		foreach ($ip_segments as $segment)
		{
			// IP segments must be digits and can not be
			// longer than 3 digits or greater then 255
			if ($segment == '' OR preg_match("/[^0-9]/", $segment) OR $segment > 255 OR strlen($segment) > 3)
			{
				return FALSE;
			}
		}

		return TRUE;
	}
		
	// --------------------------------------------------------------------
	
	/**
	* Validate IPv6 Address
	*
	* @access	protected
	* @param	string
	* @return	bool
	*/
	protected function validIpv6($str)
	{
		// 8 groups, separated by :
		// 0-ffff per group
		// one set of consecutive 0 groups can be collapsed to ::
		
		$groups = 8;
		$collapsed = FALSE;
		
		$chunks = array_filter(
			preg_split('/(:{1,2})/', $str, NULL, PREG_SPLIT_DELIM_CAPTURE)
		);
		
		// Rule out easy nonsense
		if (current($chunks) == ':' OR end($chunks) == ':')
		{
			return FALSE;
		}
		
		// PHP supports IPv4-mapped IPv6 addresses, so we'll expect those as well
		if (strpos(end($chunks), '.') !== FALSE)
		{
			$ipv4 = array_pop($chunks);
			
			if ( ! $this->_valid_ipv4($ipv4))
			{
				return FALSE;
			}
			
			$groups--;
		}
		
		while ($seg = array_pop($chunks))
		{
			if ($seg[0] == ':')
			{
				if (--$groups == 0)
				{
					return FALSE;	// too many groups
				}
				
				if (strlen($seg) > 2)
				{
					return FALSE;	// long separator
				}
				
				if ($seg == '::')
				{
					if ($collapsed)
					{
						return FALSE;	// multiple collapsed
					}
					
					$collapsed = TRUE;
				}
			}
			elseif (preg_match("/[^0-9a-f]/i", $seg) OR strlen($seg) > 4)
			{
				return FALSE; // invalid segment
			}
		}

		return $collapsed OR $groups == 1;
	}
	
}

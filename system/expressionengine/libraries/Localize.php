<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		ExpressionEngine Dev Team
 * @copyright	Copyright (c) 2003 - 2010, EllisLab, Inc.
 * @license		http://expressionengine.com/user_guide/license.html
 * @link		http://expressionengine.com
 * @since		Version 2.0
 * @filesource
 */
 
// ------------------------------------------------------------------------

/**
 * ExpressionEngine Core Localization Class
 *
 * @package		ExpressionEngine
 * @subpackage	Core
 * @category	Core
 * @author		ExpressionEngine Dev Team
 * @link		http://expressionengine.com
 */
class EE_Localize {
  
	var $server_now			= '';	// Local server time
	var $now				= '';  // Local server time as GMT  
	var $ctz				=  0;  // Current user's timezone setting
	var $zones				= array();
 	var $cached				= array();
	var $format				= array('DATE_ATOM'		=>	'%Y-%m-%dT%H:%i:%s%Q',
									'DATE_COOKIE'	=>	'%l, %d-%M-%y %H:%i:%s UTC',
									'DATE_ISO8601'	=>	'%Y-%m-%dT%H:%i:%s%O',
									'DATE_RFC822'	=>	'%D, %d %M %y %H:%i:%s %O',
									'DATE_RFC850'	=>	'%l, %d-%M-%y %H:%m:%i UTC',
									'DATE_RFC1036'	=>	'%D, %d %M %y %H:%i:%s %O',
									'DATE_RFC1123'	=>	'%D, %d %M %Y %H:%i:%s %O',
									'DATE_RFC2822'	=>	'%D, %d %M %Y %H:%i:%s %O',
									'DATE_RSS'		=>	'%D, %d %M %Y %H:%i:%s %O',
									'DATE_W3C'		=>	'%Y-%m-%dT%H:%i:%s%Q'
									);
  
	/**
	 * Constructor
	 */	  
	function __construct()
	{
		// Make a local reference to the ExpressionEngine super object
		$this->EE =& get_instance();

		// Fetch the current local server time and convert it to GMT
		$this->server_now	= time();
		$this->now			= $this->set_gmt($this->server_now); 
		$this->zones		= $this->zones();
	}
	
	// --------------------------------------------------------------------

	/**
	 *  Set GMT time
	 *
	 * Takes a Unix timestamp as input and returns it as GMT
	 *
	 * @access	public
	 * @param	string
	 * @return	string
	 */	
	function set_gmt($now = '')
	{	
		if ($now == '')
		{
			$now = time(); 
		}
			
		$time = gmmktime( gmdate("H", $now),
						 gmdate("i", $now),
						 gmdate("s", $now),
						 gmdate("m", $now),
						 gmdate("d", $now),
						 gmdate("Y", $now),
						 -1	// this must be explicitly set or some FreeBSD servers behave erratically
						);

		// mktime() has a bug that causes it to fail during the DST "spring forward gap"
		// when clocks are offset an hour forward (around April 4).  Instead of returning a valid
		// timestamp, it returns -1.  Basically, mktime() gets caught in purgatory, not 
		// sure if DST is active or not.  As a work-around for this we'll test for "-1",
		// and if present, return the current time.  This is not a great solution, as this time
		// may not be what the user intended, but it's preferable than storing -1 as the timestamp, 
		// which correlates to: 1969-12-31 16:00:00. 

		if ($time == -1)
		{
			return $this->set_gmt();
		}
		else
		{
			return $time;
		}
	}	

	// --------------------------------------------------------------------

	/**
	 *   Convert a MySQL timestamp to GMT
	 *
	 * @access	public
	 * @param	string
	 * @return	string
	 */	
	function timestamp_to_gmt($str = '')
	{		
		// We'll remove certain characters for backward compatibility
		// since the formatting changed with MySQL 4.1
		// YYYY-MM-DD HH:MM:SS
		
		$str = str_replace('-', '', $str);
		$str = str_replace(':', '', $str);
		$str = str_replace(' ', '', $str);
		
		// YYYYMMDDHHMMSS

		return  $this->set_gmt( gmmktime( substr($str,8,2),
										substr($str,10,2),
										substr($str,12,2),
										substr($str,4,2),
										substr($str,6,2),
										substr($str,0,4)
									  )
								);
	}
	
	
	// --------------------------------------------------------------------

	/**
	 *   Set localized time
	 *
	 * Converts GMT time to the localized values of the current logged-in user
	 *
	 * @access	public
	 * @param	string
	 * @param	string
	 * @param	string
	 * @return	string
	 */
	function set_localized_time($now = '', $timezone = '', $dst = '')
	{
		if ($now == '')
		{
			$now = $this->now;
		}
				
		// This lets us use a different timezone then the logged in user to calculate a time.
		// Right now we only use this to show the local time of other users
		if ($timezone == '')
		{	
			$timezone = $this->EE->session->userdata['timezone'];
		}

		// If the current user has not set localization preferences
		// we'll instead use the master server settings
		if ($timezone == '')
		{
			return $this->set_server_time($now);
		}

		// $now = $this->now + ($this->zones[$timezone] * 3600);
		$now += $this->zones[$timezone] * 3600;

		if ($dst == '')
		{
			$dst = $this->EE->session->userdata('daylight_savings');
		}
		
		if ($dst == 'y')
		{
			$now += 3600;
		}
		
		return $this->set_server_offset($now);
	}
	
	// --------------------------------------------------------------------

	/**
	 * Set localized server time
	 *
	 * Converts GMT time to the localized server timezone
	 *
	 * @access	public
	 * @param	string
	 * @return	string
	 */
	function set_server_time($now = '')
	{
		if ($now == '')
		{
			$now = $this->now;
		}
		
		if ($tz = $this->EE->config->item('server_timezone'))
		{
			$now += $this->zones[$tz] * 3600;
		}
		
		if ($this->EE->config->item('daylight_savings') == 'y')
		{
			$now += 3600;
		}
		
		$now = $this->set_server_offset($now);
				
		return $now;
	}
	
	// --------------------------------------------------------------------

	/**
	 *   Set server offset
	 *
	 * Takes a Unix timestamp as input and adds/subtracts the number of 
	 * minutes specified in the master server time offset preference
	 *
	 * The optional second parameter lets us reverse the offset (positive number becomes negative)
	 * We use the second parameter with set_localized_offset()
	 *
	 * @access	public
	 * @param	string
	 * @return	string
	 */
	function set_server_offset($time, $reverse = 0)
	{
		$offset = ( ! $this->EE->config->item('server_offset')) ? 0 : $this->EE->config->item('server_offset') * 60;
		
		if ($offset == 0)
		{
			return $time;
		}
		
		if ($reverse == 1)
		{
			$offset = $offset * -1;
		}
		
		$time += $offset;
		
		return $time;
	}

	// --------------------------------------------------------------------

	/**
	 *   Set localized offset
	 *
	 * This function lets us calculate the time difference between the
	 * timezone of the current user and the timezone of the server hosting
	 * the site.  It solves a dilemma we face when using functions like mktime()
	 * which base their output on the server's timezone.  When a channel entry is
	 * submitted, the entry date is converted to a Unix timestamp.  But since
	 * the user submitting the entry might not be in the same timezone as the 
	 * server we need to offset the timestamp to reflect this difference.
	 *
	 * @access	public
	 * @return	void
	 */	
	function set_localized_offset()
	{
		$offset = 0;
				
		if ($this->EE->session->userdata['timezone'] == '')
		{
			if ($tz = $this->EE->config->item('server_timezone'))
			{
				$offset += $this->zones[$tz];
			}
			
			if ($this->EE->config->item('daylight_savings') == 'y')
			{
				$offset += 1;
			}
		}
		else
		{			 
			$offset += $this->zones[$this->EE->session->userdata['timezone']];  
			 
			if ($this->EE->session->userdata['daylight_savings'] == 'y')
			{
				$offset += 1;
			}
		} 
				
		// Grab local time	
		$time = $this->server_now;
		
		// Determine the number of seconds between the local time and GMT
		$time -= $this->now;
		
		// Offset this number based on the server offset (if it exists)
		$time = $this->set_server_offset($time, 1);
		
		// Divide by 3600, making our offset into hours
		$time = $time/3600;
		
		// add or subtract it from our timezone offset
		$offset -= $time;
		
		// Multiply by -1 to invert the value (positive becomes negative and vice versa)
		$offset = $offset * -1;
		
		// Convert it to seconds
		if ($offset != 0)
			$offset = $offset * (60 * 60);
		
		return $offset;
	}
	
	// --------------------------------------------------------------------

	/**
	 * Human-readable time
	 *
	 * Formats Unix/GMT timestamp to the following format: 2003-08-21 11:35 PM
	 *
	 * Will also switch to Euro time based on the user preference
	 *
	 * @access	public
	 * @param	string
	 * @param	bool
	 * @param	bool
	 * @return	string
	 */
	function set_human_time($now = '', $localize = TRUE, $seconds = FALSE)
	{
		/* -------------------------------------------
		/*	Hidden Configuration Variables
		/*	- include_seconds => Determines whether to include seconds in our human time.
		/* -------------------------------------------*/		
		
		if (func_num_args() != 3 && $this->EE->config->item('include_seconds') == 'y')
		{
			$seconds = TRUE;
		}
		
		$fmt = ($this->EE->session->userdata['time_format'] != '') ? $this->EE->session->userdata['time_format'] : $this->EE->config->item('time_format');
	
		if ($localize)
		{
			$now = $this->set_localized_time($now);
		}
			
		$r  = gmdate('Y', $now).'-'.gmdate('m', $now).'-'.gmdate('d', $now).' ';
			
		if ($fmt == 'us')
		{
			$r .= gmdate('h', $now).':'.gmdate('i', $now);
		}
		else
		{
			$r .= gmdate('H', $now).':'.gmdate('i', $now);
		}
		
		if ($seconds)
		{
			$r .= ':'.gmdate('s', $now);
		}
		
		if ($fmt == 'us')
		{
			$r .= ' '.gmdate('A', $now);
		}
			
		return $r;
	}
	
	// --------------------------------------------------------------------

	/**
	 * Convert "human" date to GMT
	 *
	 * Converts the human-readable date used in the channel entry 
	 * submission page back to Unix/GMT
	 *
	 * @access	public
	 * @param	string
	 * @return	int
	 */
	function convert_human_date_to_gmt($datestr = '')
	{
		if ($datestr == '')
		{
			return FALSE;			
		}
					
			$datestr = trim($datestr);
			
			$datestr = preg_replace('/\040+/', ' ', $datestr);
			
			if ( ! preg_match('/^[0-9]{2,4}\-[0-9]{1,2}\-[0-9]{1,2}\s[0-9]{1,2}:[0-9]{1,2}(?::[0-9]{1,2})?(?:\s[AP]M)?$/i', $datestr))
			{
				return $this->EE->lang->line('invalid_date_formatting');
			}
			
			$split = explode(' ', $datestr);

			$ex = explode("-", $split[0]);			
			
			$year  = (strlen($ex[0]) == 2) ? '20'.$ex[0] : $ex[0];
			$month = (strlen($ex[1]) == 1) ? '0'.$ex[1]  : $ex[1];
			$day	= (strlen($ex[2]) == 1) ? '0'.$ex[2]  : $ex[2];

			$ex = explode(":", $split[1]); 
			
			$hour = (strlen($ex[0]) == 1) ? '0'.$ex[0] : $ex[0];
			$min  = (strlen($ex[1]) == 1) ? '0'.$ex[1] : $ex[1];

			// I'll explain later
			$fib_seconds = FALSE;
			
			if (isset($ex[2]) && preg_match('/[0-9]{1,2}/', $ex[2]))
			{
				$sec = sprintf('%02d', $ex[2]);
			}
			else
			{
	        	// Unless specified, seconds get set to zero.
				// $sec = '00'; 
				// The above doesn't make sense to me, and can cause entries submitted within the same
				// minute to have identical timestamps, so I'm reverting to an older behavior - D'Jones
				// *********************************************************************************************
				// I now see what Paul was initially avoiding.  So, here's the dealio and how we'll address it:
				// Since the seconds were not specified, we're going to fib and roll back one second, otherwise
				// the submitted entry will be considered to not be < $this->now and will not be displayed on
				// the page request that creates it, a common scenario when submitting entries via a SAEF.
				// So we'll set a flag, and adjust the time by one second after the timestamp is generated.
				// If we do it here, we'd have to step backwards through minutes and hours and days etc. to
				// check if each needs to roll back, for dates like January 1, 1990 12:00:00
				$sec = date('s', $this->now);
				$fib_seconds = TRUE;
			}
			
			if (isset($split[2]))
			{
				$ampm = strtolower($split[2]);
				
				if (substr($ampm, 0, 1) == 'p' AND $hour < 12)
					$hour = $hour + 12;
					
				if (substr($ampm, 0, 1) == 'a' AND $hour == 12)
					$hour =  '00';
					
				if (strlen($hour) == 1)
					$hour = '0'.$hour;
			}

		if ($year < 1902 OR $year > 2037)			
		{
			return $this->EE->lang->line('date_outside_of_range');
		}
				
		$time = $this->set_gmt(gmmktime($hour, $min, $sec, $month, $day, $year));

		// Are we fibbing?
		if ($fib_seconds === TRUE)
		{
			$time = $time - 1;
		}
		
		// Offset the time by one hour if the user is submitting a date
		// in the future or past so that it is no longer in the same
		// Daylight saving time.	
		if (date("I", $this->now))
		{
			if ( ! date("I", $time))
			{
				$time -= 3600;			
			}
		}
		else
		{
			if (date("I", $time))
			{
				$time += 3600;			
			}
		}

		$time += $this->set_localized_offset();

		return $time;	  
	}
	
	// --------------------------------------------------------------------

	/**
	 *   Simple Offset
	 *
	 * This allows a timestamp to be offset by the submitted timezone.
	 * Currently this is only used in the PUBLISH page
	 *
	 * @access	public
	 * @param	string
	 * @param	string
	 * @return	int
	 */
	function simpl_offset($time = '', $timezone = '')
	{
		$time += $this->zones[$timezone] * 3600;

		if ($this->EE->session->userdata('daylight_savings') == 'y')
		{
			$time += 3600;
		}
	
		return $time;
	}
	
	// --------------------------------------------------------------------

	/**
	 * Offset Entry DST
	 *
	 * DEPRECATED
	 *
	 * This adds/subtracts an hour if the submitted entry
	 * has the "honor DST setting" clicked
	 *
	 * @access	public
	 * @param	string
	 * @param	string
	 * @param	bool
	 * @return	int
	 */
	function offset_entry_dst($time = '', $dst_enabled = '', $add_time = TRUE)
	{
		return $time;
	}
	
	// --------------------------------------------------------------------

	/**
	 * Format timespan
	 *
	 * Returns a span of seconds in this format: 10 days 14 hours 36 minutes 47 seconds
	 *
	 * @access	public
	 * @param	string
	 * @return	string
	 */
	function format_timespan($seconds = '')
	{		
		// things can get really screwy if a negative number is passed, which can happen
		// in very rare load-balanced environments when the web servers' are not in
		// perfect sync with one another
		$seconds = abs($seconds);
		
		$seconds = ($seconds == '') ? 1 : $seconds;

		$str = '';
		
		$years = floor($seconds / 31536000);
		
		if ($years > 0)
		{		
			$str .= $years.' '.$this->EE->lang->line(($years	> 1) ? 'years' : 'year').', ';
		}	
		
		$seconds -= $years * 31536000;
		
		$months = floor($seconds / 2628000);
		
		if ($years > 0 OR $months > 0)
		{
			if ($months > 0)
			{		
				$str .= $months.' '.$this->EE->lang->line(($months	> 1) ? 'months'	: 'month').', ';
			}	
		
			$seconds -= $months * 2628000;
		}

		$weeks = floor($seconds / 604800);
		
		if ($years > 0 OR $months > 0 OR $weeks > 0)
		{
			if ($weeks > 0)
			{				
				$str .= $weeks.' '.$this->EE->lang->line(($weeks > 1) ? 'weeks' : 'week').', ';
			}
			
			$seconds -= $weeks * 604800;
		}			

		$days = floor($seconds / 86400);
		
		if ($months > 0 OR $weeks > 0 OR $days > 0)
		{
			if ($days > 0)
			{			
				$str .= $days.' '.$this->EE->lang->line(($days > 1) ? 'days' : 'day').', ';
			}
		
			$seconds -= $days * 86400;
		}
		
		$hours = floor($seconds / 3600);
		
		if ($days > 0 OR $hours > 0)
		{
			if ($hours > 0)
			{
				$str .= $hours.' '.$this->EE->lang->line(($hours > 1) ? 'hours' : 'hour').', ';
			}
			
			$seconds -= $hours * 3600;
		}
		
		$minutes = floor($seconds / 60);
		
		if ($days > 0 OR $hours > 0 OR $minutes > 0)
		{
			if ($minutes > 0)
			{		
				$str .= $minutes.' '.$this->EE->lang->line(($minutes	> 1) ? 'minutes' : 'minute').', ';
			}
			
			$seconds -= $minutes * 60;
		}
		
		if ($str == '')
		{
			$str .= $seconds.' '.$this->EE->lang->line(($seconds	> 1) ? 'seconds' : 'second').', ';
		}
				
		$str = substr(trim($str), 0, -1);
					
		return $str;
	}
	
	// --------------------------------------------------------------------

	/**
	 *   Fetch Date Params (via template parser)
	 *
	 * @access	public
	 * @param	string
	 * @return	string
	 */
	function fetch_date_params($datestr = '')
	{
		if ($datestr == '')
			return;

		if ( ! preg_match_all("/(%\S)/", $datestr, $matches))
				return;
				 
		return $matches[1];
	}
	
	// --------------------------------------------------------------------

	/**
	 *   Decode date string (via template parser)
	 *
	 * This function takes a string containing text and
	 * date codes and extracts only the codes.  Then,
	 * the codes are converted to their actual timestamp 
	 * values and the string is reassembled.
	 *
	 * @access	public
	 * @param	string
	 * @param	string
	 * @param	bool
	 * @return	string
	 */
	function decode_date($datestr = '', $unixtime = '', $localize = TRUE)
	{
		$prelocalized = FALSE;
		
		if ($datestr == '')
			return;
			
		if ($unixtime == 0)
			return '';

		if ( ! preg_match_all("/(%\S)/", $datestr, $matches))
				return;
		
		$gmt_tz_offsets = FALSE;
		
		if ($localize === TRUE)
		{
			$unixtime = $this->set_localized_time($unixtime);
			$prelocalized = TRUE;
		}

		foreach ($matches[1] as $val)
		{
			$datestr = str_replace($val, $this->convert_timestamp($val, $unixtime, FALSE, $prelocalized), $datestr);
		}
				 
		return $datestr;
	}
	
	// --------------------------------------------------------------------

	/**
	 * Localize month name
	 *
	 * Helper function used to translate month names.
	 *
	 * @access	public
	 * @param	string
	 * @return	string
	 */
	function localize_month($month = '')
	{	
		$months = array(
							'01' => array('Jan', 'January'),
							'02' => array('Feb', 'February'),
							'03' => array('Mar', 'March'),
							'04' => array('Apr', 'April'),
							'05' => array('May', 'May_l'),
							'06' => array('Jun', 'June'),
							'07' => array('Jul', 'July'),
							'08' => array('Aug', 'August'),
							'09' => array('Sep', 'September'),
							'10' => array('Oct', 'October'),
							'11' => array('Nov', 'November'),
							'12' => array('Dec', 'December')
						);
						
		if (isset($months[$month]))
		{
			return $months[$month];
		}
	}

	// --------------------------------------------------------------------

	/**
	 * Convert timestamp codes
	 *
	 * All text codes are converted to the user-specified language.
	 *
	 * @access	public
	 * @param	string
	 * @param	string
	 * @param	bool
	 * @return	mixed
	 */
	function convert_timestamp($format = '', $time = '', $localize = TRUE, $prelocalized = FALSE)
	{	
		$return_str = FALSE;
		
		if ( ! is_array($format))
		{
			$format = array($format);
			$return_str = TRUE;
		}
		
		$localized_tz = ($prelocalized == TRUE) ? TRUE : $localize;
		
		$translate = (isset($this->EE->TMPL) && is_object($this->EE->TMPL) && $this->EE->TMPL->template_type == 'feed') ? FALSE : TRUE;
			
		if ($this->ctz == 0)
		{
			$this->ctz = $this->set_localized_timezone();
		}

		$time = ($localize == TRUE) ? $this->set_localized_time($time) : $time;
		
		$return = array();
				
		foreach($format as $which)
		{
			if (isset($this->cached[$time][$which]))
			{
				$return[] = $this->cached[$time][$which];
				continue;
			}
	  
			switch ($which)
			{
				case '%a': 	$var = ($translate === FALSE) ? gmdate('a', $time) : $this->EE->lang->line(gmdate('a', $time)); // am/pm
					break;
				case '%A': 	$var = ($translate === FALSE) ? gmdate('A', $time) : $this->EE->lang->line(gmdate('A', $time)); // AM/PM
					break;
				case '%B': 	$var = gmdate('B', $time);
					break;
				case '%d': 	$var = gmdate('d', $time);
					break;
				case '%D': 	$var = ($translate === FALSE) ? gmdate('D', $time) : $this->EE->lang->line(gmdate('D', $time)); // Mon, Tues
					break;
				case '%F': 	$may = (gmdate('F', $time) == 'May') ? gmdate('F', $time).'_l' : gmdate('F', $time);
							$var = ($translate === FALSE) ? gmdate('F', $time) : $this->EE->lang->line($may); // January, February
					break;
				case '%g': 	$var = gmdate('g', $time);
					break;
				case '%G': 	$var = gmdate('G', $time);
					break;
				case '%h': 	$var = gmdate('h', $time);
					break;
				case '%H': 	$var = gmdate('H', $time);
					break;
				case '%i': 	$var = gmdate('i', $time);
					break;
				case '%I': 	$var = ($localized_tz == TRUE) ? date('I', $time) : gmdate('I', $time);
					break;
				case '%j': 	$var = gmdate('j', $time);
					break;
				case '%l': 	$var = ($translate === FALSE) ? gmdate('l', $time) : $this->EE->lang->line(gmdate('l', $time)); // Monday, Tuesday
					break;
				case '%L': 	$var = gmdate('L', $time); 
					break;
				case '%m': 	$var = gmdate('m', $time);	
					break;
				case '%M': 	$var = ($translate === FALSE) ? gmdate('M', $time) : $this->EE->lang->line(gmdate('M', $time)); // Jan, Feb
					break;
				case '%n': 	$var = gmdate('n', $time);
					break;
				case '%O': 	$var = ($localized_tz == TRUE) ? date('O', $time) : gmdate('O', $time);
					break;
				case '%r': 	$var = ($translate === FALSE) ? gmdate('D', $time).gmdate(', d ', $time).gmdate('M', $time).gmdate(' Y H:i:s O', $time) : $this->EE->lang->line(gmdate('D', $time)).gmdate(', d ', $time).$this->EE->lang->line(gmdate('M', $time)).gmdate(' Y H:i:s O', $time);
					break;
				case '%s': 	$var = gmdate('s', $time);
					break;
				case '%S': 	$var = gmdate('S', $time);
					break;
				case '%t': 	$var = gmdate('t', $time);
					break;
				case '%T': 	$var = ($localized_tz == TRUE) ? $this->ctz : gmdate('T', $time);
					break;
				case '%U': 	$var = gmdate('U', $time);
					break;
				case '%w': 	$var = gmdate('w', $time);
					break;
				case '%W': 	$var = gmdate('W', $time);
					break;
				case '%y': 	$var = gmdate('y', $time);
					break;
				case '%Y': 	$var = gmdate('Y', $time);
					break;
				case '%Q':	$var = ($localized_tz == TRUE) ? $this->zone_offset($this->EE->session->userdata['timezone']) : '+00:00'; // equiv to date('P'), but P is not available in PHP < 5.1.3
					break;
				case '%z': 	$var = gmdate('z', $time);
					break;
				case '%Z':	$var = ($localized_tz == TRUE) ? date('Z', $time) : gmdate('Z', $time);
					break;
				default  :  $var = '';
					break;
			}
			
			$this->cached[$time][$which] = $var;
			
			$return[] = $var;
		}
		
		return ($return_str == TRUE) ? array_pop($return) : $return;
	}

	// --------------------------------------------------------------------

	/**
	 *  GMT Offset - Ouputs:  +01:00
	 *
	 * @access	public
	 * @param	string
	 * @return	int
	 */
	function zone_offset($tz = '')
	{
		if ($tz == '')
		{
			return '+00:00';
		}	
			
		$zone = trim($this->zones[$tz]);
		
		if ( ! strstr($zone, '.'))
		{
			$zone .= ':00';
		}
		
		$zone = str_replace(".5", ':30', $zone);
		
		if (substr($zone, 0, 1) != '-')
		{
			$zone = '+'.$zone;
		}
				
		$zone = preg_replace("/^(.{1})([0-9]{1}):(\d+)$/", "\\1D\\2:\\3", $zone);
		$zone = str_replace("D", '0', $zone);
	 
		return $zone;		
	}
	
	// --------------------------------------------------------------------

	/**
	 * Create timezone localization pull-down menu
	 *
	 * @access	public
	 * @param	string
	 * @return	string
	 */
	function timezone_menu($default = '')
	{		
		$r  = "<div class='default'>";
		$r .= "<select name='server_timezone' class='select'>";
		
		foreach ($this->zones as $key => $val)
		{
			$selected = ($default == $key) ? " selected='selected'" : '';

			$r .= "<option value='{$key}'{$selected}>".$this->EE->lang->line($key)."</option>\n";
		}

		$r .= "</select>";
		$r .= "</div>";

		return $r;
	}
	
	// --------------------------------------------------------------------
	
	/**
	 * Timezones
	 *
	 * This array is used to render the localization pull-down menu
	 *
	 * @access	public
	 * @return	array
	 */
	function zones()
	{
		// Note: Don't change the order of these even though 
		// some items appear to be in the wrong order
		return array( 
					'UM12'		=> -12,
					'UM11'		=> -11,
					'UM10'		=> -10,
					'UM95'		=> -9.5,
					'UM9'		=> -9,
					'UM8'		=> -8,
					'UM7'		=> -7,
					'UM6'		=> -6,
					'UM5'		=> -5,
					'UM45'		=> -4.5,
					'UM4'		=> -4,
					'UM35'		=> -3.5,
					'UM3'		=> -3,
					'UM2'		=> -2,
					'UM1'		=> -1,
					'UTC'		=> 0,
					'UP1'		=> +1,
					'UP2'		=> +2,
					'UP3'		=> +3,
					'UP35'		=> +3.5,
					'UP4'		=> +4,
					'UP45'		=> +4.5,
					'UP5'		=> +5,
					'UP55'		=> +5.5,
					'UP575'		=> +5.75,
					'UP6'		=> +6,
					'UP65'		=> +6.5,
					'UP7'		=> +7,
					'UP8'		=> +8,
					'UP875'		=> +8.75,
					'UP9'		=> +9,
					'UP95'		=> +9.5,
					'UP10'		=> +10,
					'UP105'		=> +10.5,
					'UP11'		=> +11,
					'UP115'		=> +11.5,
					'UP12'		=> +12,
					'UP1275'	=> +12.75,
					'UP13'		=> +13,
					'UP14'		=> +14
			);
	}
	
	// --------------------------------------------------------------------

	/**
	 * Set localized timezone
	 *
	 * @access	public
	 * @return	string
	 */
	function set_localized_timezone()
	{
        $zones = array(
						'UM12'	=> array('',	''),
						'UM11'	=> array('SST',	'SST'),
						'UM10'	=> array('HST',	'HST'),
						'UM95'	=> array('MART','MART'),
						'UM9'	=> array('AKST','AKDT'),					
						'UM8'	=> array('PST',	'PDT'),
						'UM7'	=> array('MST',	'MDT'),
						'UM6'	=> array('CST',	'CDT'),
						'UM5'	=> array('EST',	'EDT'),
						'UM45'	=> array('VET',	'VET'),
						'UM4'	=> array('AST',	'ADT'),
						'UM35'	=> array('NST',	'NDT'),
						'UM3'	=> array('ADT',	'ADT'),
						'UM2'	=> array('MAST','MAST'),
						'UM1'	=> array('AZOT','AZOT'),
						'UTC'	=> array('GMT',	'GMT'),
						'UP1'	=> array('MET',	'MET'),
						'UP2'	=> array('EET',	'EET'),
						'UP3'	=> array('BT', 	'BT'),
						'UP35'	=> array('IRT',	'IRT'),
						'UP4'	=> array('ZP4',	'ZP4'),
						'UP45'	=> array('AFT',	'AFT'),
						'UP5'	=> array('ZP5',	'ZP5'),
						'UP55'	=> array('IST',	'IDT'),
						'UP575'	=> array('NPT',	'NPT'),
						'UP6'	=> array('ZP6',	'ZP6'),
						'UP65'	=> array('BURT','BURT'),
						'UP7'	=> array('WAST','WADT'),
						'UP8'	=> array('WST','WDT'),
						'UP875'	=> array('CWST','CWDT'),
						'UP9'	=> array('JST',	'JDT'),
						'UP95'	=> array('CST',	'CDT'),
						'UP10'	=> array('AEST','AEDT'),
						'UP105'	=> array('LHST','LHST'),
						'UP11'	=> array('MAGT','MAGT'),
						'UP115'	=> array('NFT',	'NFT'),
						'UP12'	=> array('NZST','NZDT'),
						'UP1275'=> array('CHAST','CHAST'),
						'UP13'	=> array('PHOT','PHOT'),
						'UP14'	=> array('LINT','LINT')
                     );
				
		if ($this->EE->session->userdata['timezone'] == '')
		{
			$zone = $this->EE->config->item('server_timezone');
			$dst = ($this->EE->config->item('daylight_savings')  == 'y') ? TRUE : FALSE;
		}
		else
		{
			$zone = $this->EE->session->userdata['timezone'];
			$dst = ($this->EE->session->userdata['daylight_savings'] == 'y') ? TRUE : FALSE;
		}
		
		if (isset($zones[$zone]))
		{
			if ($dst == FALSE)
			{
				return $zones[$zone][0];		
			}
			else
			{
				return $zones[$zone][1];		
			}
		}
		
		return 'GMT';	
	} 
	
	// --------------------------------------------------------------------

	/**
	 * Fetch Days in Month
	 *
	 * Returns the number of days for the given month/year
	 * Takes leap years into consideration
	 *
	 * @access	public
	 * @param	string
	 * @param	string
	 * @return	int
	 */
	function fetch_days_in_month($month, $year)
	{
		$days_in_month	= array(31, 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31);
	
		if ($month < 1 OR $month > 12)
		{
			return 0;
		}
		
		if ($month == 2)
		{		
			if ($year % 400 == 0 OR ($year % 4 == 0 AND $year % 100 != 0))
			{
				return 29;
			}
		}
	
		return $days_in_month[$month - 1];
	}
	
	// --------------------------------------------------------------------

	/**
	 * Adjust Date
	 *
	 * This function is used by the calendar.  It verifies that
	 * the month/day are within the correct range and adjusts 
	 * if necessary.  For example:  Day 34 in Feburary would
	 * be adjusted to March 6th.
	 *
	 * @access	public
	 * @param	string
	 * @param	string
	 * @param	bool
	 * @return	array
	 */
	function adjust_date($month, $year, $pad = FALSE)
	{
		$date = array(); 
		
		$date['month']	= $month;
		$date['year']	= $year;
		
		while ($date['month'] > 12)
		{
			$date['month'] -= 12;
			$date['year']++;
		}
		
		while ($date['month'] <= 0)
		{
			$date['month'] += 12;
			$date['year']--;
		}
		
		if ($pad == TRUE AND strlen($date['month']) == 1)
		{
			$date['month'] = '0'.$date['month'];
		}
		
		return $date;
	}

}
// END CLASS

/* End of file Localize.php */
/* Location: ./system/expressionengine/libraries/Localize.php */
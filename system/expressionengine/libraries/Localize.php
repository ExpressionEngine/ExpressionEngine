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
 * ExpressionEngine Core Localization Class
 *
 * @package		ExpressionEngine
 * @subpackage	Core
 * @category	Core
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */
class EE_Localize {

	var $now				= '';  // Local server time as GMT
	var $ctz				=  0;  // Current user's timezone setting
	var $zones				= array();
 	var $cached				= array();
	var $format				= array('DATE_ATOM'		=>	'%Y-%m-%dT%H:%i:%s%Q',
									'DATE_COOKIE'	=>	'%l, %d-%M-%y %H:%i:%s UTC',
									'DATE_ISO8601'	=>	'%Y-%m-%dT%H:%i:%s%Q',
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

		$this->zones = $this->zones();

		// Fetch current Unix timestamp
		$this->now = time();

		// Apply server offset (in minutes) to $now
		if (($offset = $this->EE->config->item('server_offset'))
			&& is_numeric($offset))
		{
			$this->now += $offset * 60;
		}
	}

	// --------------------------------------------------------------------

	/**
	 * String to Timestamp
	 *
	 * Converts a human-readble date (and possibly time) to a Unix timestamp
	 * using the current member's locale
	 *
	 * @param	string	Human-readable date
	 * @return	mixed	int if successful, otherwise FALSE
	 */
	public function string_to_timestamp($human_string)
	{
		if (trim($human_string) == '')
		{
			return '';
		}

		$dt = $this->_datetime($human_string);

		return ($dt) ? $dt->format('U') : FALSE;
	}

	// --------------------------------------------------------------------

	/**
	 * Given an EE date format and a Unix timestamp, returns the human-readable
	 * date in the specified timezone or member's current timezone.
	 *
	 * @param	string	Date format, like "%D, %F %d, %Y - %g:%i:%s"
	 * @param	int		Unix timestamp
	 * @param	string	Timezone
	 * @return	string	Formatted date
	 */
	public function formatted_date($format, $timestamp = NULL, $timezone = NULL)
	{
		if ( ! ($dt = $this->_datetime($timestamp, $timezone)))
		{
			return FALSE;
		}

		// Match all EE date vars, which are essentially the normal PHP date
		// vars with a percent sign in front of them
		if ( ! preg_match_all("/(%\S)/", $format, $matches))
		{
			return $dt->format('U');
		}

		// Loop through matched date vars and replace them in the $format string
		foreach($matches[1] as $var)
		{
			$format = str_replace($var, $this->_date_string_for_variable($var, $dt), $format);
		}
		
		return $format;
	}

	// --------------------------------------------------------------------
	
	/**
	 * Given an EE date format and a Unix timestamp, returns the human-readable
	 * date in the specified timezone or member's current timezone.
	 *
	 * @param	string	Date format, like "%D, %F %d, %Y - %g:%i:%s"
	 * @param	int		Unix timestamp
	 * @param	string	Timezone
	 * @return	string	Formatted date
	 */
	private function _date_string_for_variable($var, $dt)
	{
		// These letters following a percent sign we will convert to their
		// matching PHP date variable value
		$allowed_date_vars = array(
			'a', 'A', 'B', 'd', 'D', 'F', 'g', 'G', 'h', 'H', 'i', 'I',
			'j', 'l', 'L', 'm', 'M', 'n', 'O', 'P', 'Q', 'r', 's', 'S',
			't', 'T', 'U', 'w', 'W', 'y', 'Y', 'z', 'Z'
		);
		
		// These date variables have month or day names and need to be ran
		// through the language library
		$translatable_date_vars = array(
			'a', 'A', 'D', 'F', 'l', 'M', 'r'
		);

		// If TRUE, the translatable date variables will be run through the
		// language library; this check has been brought over from legacy code
		$translate = ! (isset($this->EE->TMPL)
			&& is_object($this->EE->TMPL) 
			&& $this->EE->TMPL->template_type == 'feed');
		
		// Remove percent sign for easy comparing and passing to DateTime::format
		$date_var = str_replace('%', '', $var);
		
		if (in_array($date_var, $allowed_date_vars))
		{
			// Special cases
			switch ($date_var)
			{
				// F returns the full month name, but "May" is the same short
				// and long, so we need to catch it and modify the lang key so
				// the correct translation is returned
				case 'F':
					if ($dt->format('F') == 'May' && $translate)
					{
						return $this->EE->lang->line('May_l');
					}
					break;
				// Concatenate the RFC 2822 format with translations
				case 'r':
					if ($translate)
					{
						$rfc = $this->EE->lang->line($dt->format('D'));		// Thu
						$rfc .= $dt->format(', d ');						// , 21
						$rfc .= $this->EE->lang->line($dt->format('M'));	// Dec
						$rfc .= $dt->format(' Y H:i:s O'); 					// 2000 16:01:07 +0200

						return $rfc;
					}
					break;
				// Q was our replacement for P because P wasn't available < PHP 5.1.3,
				// so keep it around for backwards compatability
				case 'Q':
					$date_var = 'P';
					break;
			}
			
			// If it's translatable, return the value for the lang key,
			// otherwise send it straight to DateTime::format
			if ($translate && in_array($date_var, $translatable_date_vars))
			{
				return $this->EE->lang->line($dt->format($date_var));
			}
			else
			{
				return $dt->format($date_var);
			}
		}
		
		return $var;
	}

	// --------------------------------------------------------------------
	
	/**
	 * Returns a DateTime object for the current time and member timezone
	 * OR a specified time and timezone
	 *
	 * @param	string		Date string or Unix timestamp, current time used if NULL
	 * @param	string		Timezone to convert time to
	 * @return	datetime	DateTime object set to the given time and altered
	 * 						for server offset
	 */
	private function _datetime($date_string = NULL, $timezone = NULL)
	{
		// Get the member's timezone if one isn't specified
		if (empty($timezone))
		{
			$timezone = $this->EE->session->userdata('timezone');
		}
		
		try
		{
			$timezone = new DateTimeZone($this->_get_php_timezone($timezone));

			// If $date_string appears to be a Unix timestamp, prepend the
			// string with '@' so DateTime knows it's a timestamp; the
			// timezone parameter is ignored when a timestamp is passed,
			// so set it separately after instantiation
			if (is_numeric($date_string))
			{
				$dt = new DateTime('@'.$date_string);
				$dt->setTimezone($timezone);
			}
			// Otherwise, we must instantiate the DateTime object with the
			// correct DateTimeZone so that the date string passed in is
			// immediately interpreted using the member's timezone and not
			// the server timezone; otherwise, using setTimezone to set
			// the timezone later will transform the date
			else
			{
				$dt = new DateTime($date_string, $timezone);
			}
		}
		catch (Exception $e)
		{
			return FALSE;
		}

		// Apply server offset only
		if (empty($date_string)
			&& ($offset = $this->EE->config->item('server_offset'))
			&& is_numeric($offset))
		{
			$offset = ($offset > 0) ? '+'.$offset : $offset;
			$dt->modify($offset.' minutes');
		}

		return $dt;
	}

	// --------------------------------------------------------------------

	/**
	 * Convert a MySQL timestamp to GMT
	 *
	 * Legacy method to handle the edit_date column until it can be changed
	 * to use timestamps. DateTime won't be able to handle the date formats
	 * passed to this.
	 *
	 * @access	public
	 * @param	string
	 * @return	string
	 */
	public function timestamp_to_gmt($str = '')
	{
		// We'll remove certain characters for backward compatibility
		// since the formatting changed with MySQL 4.1
		// YYYY-MM-DD HH:MM:SS

		$str = str_replace('-', '', $str);
		$str = str_replace(':', '', $str);
		$str = str_replace(' ', '', $str);

		// YYYYMMDDHHMMSS

		return gmmktime(
			substr($str,8,2),
			substr($str,10,2),
			substr($str,12,2),
			substr($str,4,2),
			substr($str,6,2),
			substr($str,0,4)
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

		// Offset this number based on the server offset (if it exists)
		$time = $this->set_server_offset(0, 1);

		// Divide by 3600, making our offset into hours
		$time = $time/3600;

		// add or subtract it from our timezone offset
		$offset -= $time;

		// Multiply by -1 to invert the value (positive becomes negative and vice versa)
		$offset = $offset * -1;

		// Convert it to seconds
		if ($offset != 0)
		{
			$offset = $offset * (60 * 60);
		}

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

		$fmt = ($this->EE->session->userdata('time_format') != '')
			? $this->EE->session->userdata('time_format') : $this->EE->config->item('time_format');

		$format_string = '%Y-%m-%d';

		if ($fmt == 'us')
		{
			$format_string .= ' %h:%i';
		}
		else
		{
			$format_string .= ' %H:%i';
		}

		if ($seconds)
		{
			$format_string .= ':%s';
		}

		if ($fmt == 'us')
		{
			$format_string .= ' %A';
		}

		$timezone = ($localize) ? NULL : 'GMT';

		return $this->formatted_date($format_string, $now, $timezone);
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
		// TODO: deprecation notice
		
		return $this->string_to_timestamp($datestr);
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
		// TODO: Deprecate
		
		return $this->formatted_date($datestr, $unixtime);
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
		// TODO: Deprecate
		
		$return_array = FALSE;

		if (is_array($format) && isset($format[0]))
		{
			$return_array = TRUE;
			$format = $format[0];
		}

		$timezone = ($localize) ? NULL : 'GMT';

		$format = $this->_date_string_for_variable($format, $this->_datetime($time, $timezone));

		return ($return_array) ? array($format) : $format;
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
	 * Get PHP Timezone
	 *
	 * Returns the PHP timezone for a given EE-format timezone.
	 * For example, given "UM5" it returns "America/New_York"
	 *
	 * @access	private
	 * @param	string
	 * @return	string
	 */
	private function _get_php_timezone($zone = 'UTC')
	{
		$zones = array(
			'UM12'		=> 'Kwajalein', 					// -12
			'UM11'		=> 'Pacific/Midway', 				// -11
			'UM10'		=> 'Pacific/Honolulu', 				// -10
			'UM95'		=> 'Pacific/Marquesas',				// -9.5
			'UM9'		=> 'America/Anchorage', 			// -9
			'UM8'		=> 'America/Los_Angeles', 			// -8
			'UM7'		=> 'America/Denver', 				// -7
			'UM6'		=> 'America/Tegucigalpa', 			// -6
			'UM5'		=> 'America/New_York', 				// -5
			'UM45'		=> 'America/Caracas',				// -4.5
			'UM4'		=> 'America/Halifax', 				// -4
			'UM35'		=> 'America/St_Johns', 				// -3.5
			'UM3'		=> 'America/Argentina/Buenos_Aires',// -3
			'UM2'		=> 'Atlantic/South_Georgia', 		// -2
			'UM1'		=> 'Atlantic/Azores', 				// -1
			'UTC'		=> 'Europe/Dublin', 				// 0
			'UP1'		=> 'Europe/Belgrade', 				// +1
			'UP2'		=> 'Europe/Minsk', 					// +2
			'UP3'		=> 'Asia/Kuwait', 					// +3
			'UP35'		=> 'Asia/Tehran', 					// +3.5
			'UP4'		=> 'Asia/Muscat', 					// +4
			'UP45'		=> 'Asia/Kabul', 					// +4.5
			'UP5'		=> 'Asia/Yekaterinburg', 			// +5
			'UP55'		=> 'Asia/Kolkata',		 			// +5.5
			'UP575'		=> 'Asia/Katmandu', 				// +5.75
			'UP6'		=> 'Asia/Dhaka', 					// +6
			'UP65'		=> 'Asia/Rangoon', 					// +6.5
			'UP7'		=> 'Asia/Krasnoyarsk', 				// +7
			'UP8'		=> 'Asia/Brunei', 					// 8
			'UP875'		=> 'Australia/Eucla',				// +8.75
			'UP9'		=> 'Asia/Seoul', 					// +9
			'UP95'		=> 'Australia/Darwin', 				// +9.5
			'UP10'		=> 'Australia/Canberra', 			// +10
			'UP105'		=> 'Australia/Lord_Howe',			// +10.5
			'UP11'		=> 'Asia/Magadan', 					// +11
			'UP115'		=> 'Pacific/Norfolk',				// +11.5
			'UP12'		=> 'Pacific/Fiji', 					// +12
			'UP1275'	=> 'Pacific/Chatham',				// +12.75
			'UP13'		=> 'Pacific/Tongatapu', 			// +13
			'UP14'		=> 'Pacific/Kiritimati'				// +14
		);

		// Fall back to UTC if something went wrong
		if ( ! isset($zones[$zone]))
		{
			return 'UTC';
		}

		return $zones[$zone];
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
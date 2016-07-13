<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2016, EllisLab, Inc.
 * @license		https://expressionengine.com/license
 * @link		https://ellislab.com
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
 * @link		https://ellislab.com
 */
class Localize {

	public $now = '';  // Local server time as GMT accounting for server offset
	public $format = array(
		'DATE_ATOM'		=> '%Y-%m-%dT%H:%i:%s%Q',
		'DATE_COOKIE'	=> '%l, %d-%M-%y %H:%i:%s UTC',
		'DATE_ISO8601'	=> '%Y-%m-%dT%H:%i:%s%Q',
		'DATE_RFC822'	=> '%D, %d %M %y %H:%i:%s %O',
		'DATE_RFC850'	=> '%l, %d-%M-%y %H:%m:%i UTC',
		'DATE_RFC1036'	=> '%D, %d %M %y %H:%i:%s %O',
		'DATE_RFC1123'	=> '%D, %d %M %Y %H:%i:%s %O',
		'DATE_RFC2822'	=> '%D, %d %M %Y %H:%i:%s %O',
		'DATE_RSS'		=> '%D, %d %M %Y %H:%i:%s %O',
		'DATE_W3C'		=> '%Y-%m-%dT%H:%i:%s%Q'
	);

	// Cached timezone and country data, properties not to be accessed directly
	private $_countries = array();
	private $_timezones_by_country = array();

	/**
	 * Constructor
	 */
	function __construct()
	{
		// Fetch current Unix timestamp
		$this->now = time();

		// Apply server offset (in minutes) to $now
		if (($offset = ee()->config->item('server_offset'))
			&& is_numeric($offset))
		{
			$this->now += $offset * 60;
		}
	}

	// --------------------------------------------------------------------

	/**
	 * String to Timestamp
	 *
	 * Converts a human-readable date (and possibly time) to a Unix timestamp
	 * using the current member's locale
	 *
	 * @param	string	$human_string	Human-readable date
	 * @param	bool	$localized		Is the human date prelocalized?
	 * @param	string	$date_format	(optional) The date format to use when
	 *									parsing $human_string
	 * @return	mixed	int if successful, otherwise FALSE
	 */
	public function string_to_timestamp($human_string, $localized = TRUE, $date_format = NULL)
	{
		if (trim($human_string) == '')
		{
			return '';
		}

		$dt = $this->_datetime($human_string, $localized, $date_format);

		// A sanity-check fall back. If we were passed a date format but we
		// failed to parse the date, we'll try again, but without the format.
		// This mimics how we handled date input prior to 2.9.3.
		if ($date_format && ! $dt)
		{
			$dt = $this->_datetime($human_string, $localized);
		}

		return ($dt) ? $dt->format('U') : FALSE;
	}

	// --------------------------------------------------------------------

	/**
	 * Given an EE date format and a Unix timestamp, returns the human-readable
	 * date in the specified timezone or member's current timezone.
	 *
	 * @param	string	Date format, like "%D, %F %d, %Y - %g:%i:%s"
	 * @param	int		Unix timestamp
	 * @param	bool	Return date localized or not
	 * @return	string	Formatted date
	 */
	public function format_date($format, $timestamp = NULL, $localize = TRUE)
	{
		if ( ! ($dt = $this->_datetime($timestamp, $localize)))
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
	 * Given an date variable and a DateTime object, returns the associated
	 * formatting for the date variable and DateTime object
	 *
	 * @param	string		Date variable with percent sign prefix, like "%D"
	 * @param	datetime	DateTime object on which to call format()
	 * @return	string		Value of variable in DateTime object, translated
	 */
	private function _date_string_for_variable($var, $dt)
	{
		// These letters following a percent sign we will convert to their
		// matching PHP date variable value
		$allowed_date_vars = array(
			'a', 'A', 'B', 'c', 'd', 'D', 'e', 'F', 'g', 'G', 'h', 'H', 'i', 'I',
			'j', 'l', 'L', 'm', 'M', 'n', 'N', 'o', 'O', 'P', 'Q', 'r', 's', 'S',
			't', 'T', 'u', 'U', 'w', 'W', 'y', 'Y', 'z', 'Z'
		);

		// These date variables have month or day names and need to be ran
		// through the language library
		$translatable_date_vars = array(
			'a', 'A', 'D', 'F', 'l', 'M', 'r', 'S'
		);

		// If TRUE, the translatable date variables will be run through the
		// language library; this check has been brought over from legacy code
		$translate = ! (isset(ee()->TMPL)
			&& is_object(ee()->TMPL)
			&& ee()->TMPL->template_type == 'feed');

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
						return lang('May_l');
					}
					break;
				// Concatenate the RFC 2822 format with translations
				case 'r':
					if ($translate)
					{
						$rfc = lang($dt->format('D'));		// Thu
						$rfc .= $dt->format(', d ');						// , 21
						$rfc .= lang($dt->format('M'));	// Dec
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
				return lang($dt->format($date_var));
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
	 * Provides common date format for things like date fields, and takes
	 * into consideration the member's time_format preference
	 *
	 * Example: 2015-10-21 06:30 PM
	 *
	 * @param	int		Unix timestamp
	 * @param	bool	Localize to member's timezone or leave as GMT
	 * @param	bool	Include seconds in returned string or not
	 * @return	string	Formatted string
	 */
	public function human_time($timestamp = NULL, $localize = TRUE, $seconds = FALSE)
	{
		// Override the userdata/config with the parameter only if it was provided
		$include_seconds = ee()->session->userdata('include_seconds', ee()->config->item('include_seconds'));
		if (func_num_args() != 3 && $include_seconds == 'y')
		{
			$seconds = TRUE;
		}

		$format_string = $this->get_date_format($seconds);

		return $this->format_date($format_string, $timestamp, $localize);
	}

	// --------------------------------------------------------------------

	/**
	 * Provides the date format to use for calculating time (both input and output)
	 *
	 * @param	bool	Include seconds in the date format string or not
	 * @return	string	Date format string
	 */
	public function get_date_format($seconds = FALSE)
	{
		$include_seconds = ee()->session->userdata('include_seconds', ee()->config->item('include_seconds'));
		$date_format = ee()->session->userdata('date_format', ee()->config->item('date_format'));
		$time_format = ee()->session->userdata('time_format', ee()->config->item('time_format'));

		// Override the userdata/config with the parameter only if it was provided
		if (func_num_args() != 1 && $include_seconds == 'y')
		{
			$seconds = TRUE;
		}

		$seconds_format = $seconds ? ':%s' : '';

		$format_string = $date_format . ' ';
		if ($time_format == 24)
		{
			$format_string .= '%H:%i' . $seconds_format;
		}
		else
		{
			$format_string .= '%g:%i' . $seconds_format . ' %A';
		}

		return $format_string;
	}

	// --------------------------------------------------------------------

	/**
	 * Returns a DateTime object for the current time and member timezone
	 * OR a specified time and timezone
	 *
	 * @param	string		Date string or Unix timestamp, current time used if NULL
	 * @param	mixed		Bool: whether or not to localize to current member's
	 *                      timezone, or string of timezone to convert to
	 * @return	datetime	DateTime object set to the given time and altered
	 * 						for server offset
	 */
	private function _datetime($date_string = NULL, $timezone = TRUE, $date_format = NULL)
	{
		// Checking for ambiguous dates but only when we don't have a date
		// format.
		if ( ! $date_format)
		{
			if (preg_match('/\b\d{1,2}-\d{1,2}-\d{2}\b/', $date_string))
			{
				return FALSE;
			}
		}

		// Localize to member's timezone or leave as GMT
		if (is_bool($timezone))
		{
			$timezone = ($timezone) ? ee()->session->userdata('timezone', ee()->config->item('default_site_timezone')) : 'UTC';
		}

		// If timezone isn't known by PHP, it may be our legacy timezone
		// notation and needs to be converted
		if ( ! in_array($timezone, DateTimeZone::listIdentifiers()))
		{
			$timezone = $this->get_php_timezone($timezone);
		}

		try
		{
			$timezone = new DateTimeZone($timezone);
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
				// Attempt to use their date (and time) format
				if ( ! is_null($date_format))
				{
					$date_format = str_replace('%', '', $date_format);
					$dt = DateTime::createFromFormat($date_format, $date_string, $timezone);

					// In the case they just passed a date, we need to only use
					// their date format.
					if ( ! $dt) {
						$date_only_format = ee()->session->userdata(
							'date_format',
							ee()->config->item('date_format')
						);
						// The pipe makes sure all other time elements are
						// replaced by the unix epoch
						$date_only_format = str_replace('%', '', $date_only_format).'|';
						$dt = DateTime::createFromFormat(
							$date_only_format,
							$date_string,
							$timezone
						);
					}
				}

				// If there's no date format, or if the date format failed, toss
				// it back to PHP.
				// Using `date_create` instead of `new DateTime` to work around
				// a bug in php's usort (https://bugs.php.net/bug.php?id=50688).
				// Used by the table library to sort by date
				if (empty($dt))
				{
					$dt = date_create($date_string, $timezone);

					if ($dt === FALSE)
					{
						return FALSE;
					}
				}

				$dt = ( ! empty($dt)) ? $dt : date_create($date_string, $timezone);
			}
		}
		catch (Exception $e)
		{
			return FALSE;
		}

		// Apply server offset only
		if (empty($date_string)
			&& ($offset = ee()->config->item('server_offset'))
			&& is_numeric($offset))
		{
			$offset = ($offset > 0) ? '+'.$offset : $offset;
			$dt->modify($offset.' minutes');
		}

		return $dt;
	}

	// --------------------------------------------------------------------

	/**
	 * Generates an HTML menu of timezones
	 *
	 * @param	string	Default timezone selection
	 * @param	string	Name of dropdown form field element
	 * @return	string	HTML for dropdown list
	 */
	public function timezone_menu($default = NULL, $name = 'default_site_timezone')
	{
		// For the installer
		ee()->load->helper('language');

		// We only want timezones with these prefixes
		$continents = array('Africa', 'America', 'Antarctica', 'Arctic',
			'Asia', 'Atlantic', 'Australia', 'Europe', 'Indian', 'Pacific');

		$zones_by_country = $this->_get_timezones_by_country();
		$countries = $this->_get_countries();

		$timezones = array();

		foreach ($countries as $code => $country)
		{
			// If country code does not match any timezones, skip the loop
			if ( ! isset($zones_by_country[$code]))
			{
				continue;
			}

			// We'll store timezones for the current country here
			$local_zones = array();

			foreach ($zones_by_country[$code] as $zone)
			{
				// Explode ID by slashes while replacing underscores with spaces
				$zone_array = str_replace('_', ' ', explode('/', $zone));

				// Exclude deprecated PHP timezones
				if ( ! in_array($zone_array[0], $continents))
				{
					continue;
				}

				// Construct the localized zone name
				if (isset($zone_array[1]))
				{
					$zone_name = lang($zone_array[1]);

					if (isset($zone_array[2]))
					{
						$zone_name .= ' - ' . lang($zone_array[2]);
					}

					$local_zones[$zone] = $zone_name;
				}
			}

			// Sort timezones by their new names
			asort($local_zones);

			$timezones[$code] = $local_zones;
		}

		// Convert to JSON for fast switching of timezone dropdown
		$timezone_json = json_encode($timezones);

		$no_timezones_lang = lang('no_timezones');

		// Start the output with some javascript to handle the timezone
		// dropdown population based on the country dropdown
		$output = <<<EOF

			<script type="text/javascript">

				var timezones = $timezone_json

				function ee_tz_change(countryselect)
				{
					var timezoneselect = document.getElementById('timezone_select');
					var countrycode = countryselect.options[countryselect.selectedIndex].value;

					timezoneselect.options.length = 0;

					if (timezones[countrycode] == '' || timezones[countrycode] == undefined)
					{
						timezoneselect.add(new Option('$no_timezones_lang', ''));

						return;
					}

					for (var key in timezones[countrycode])
					{
						if (timezones[countrycode].hasOwnProperty(key))
						{
							timezoneselect.add(new Option(timezones[countrycode][key], key));
						}
					}
				}

			</script>
EOF;

		// Prepend to the top of countries dropdown with common country selections
		$countries = array_merge(
			array(
				lang('select_timezone'),
				'-------------',
				'us' => $countries['us'], // United States
				'gb' => $countries['gb'], // United Kingdom
				'au' => $countries['au'], // Australia
				'ca' => $countries['ca'], // Canada
				'fr' => $countries['fr'], // France
				'ie' => $countries['ie'], // Ireland
				'nz' => $countries['nz'], // New Zealand
				'-------------'
			),
			$countries
		);

		// Get ready to load preselected values into the dropdowns if one exists
		$selected_country = NULL;
		$timezone_prepopulated = array('' => lang('no_timezones'));

		if ( ! empty($default))
		{
			$timezone_ids = DateTimeZone::listIdentifiers();

			// If default selection isn't valid, it may be our legacy timezone format
			if ( ! in_array($default, $timezone_ids))
			{
				$default = $this->get_php_timezone($default);
			}

			$selected_country = $this->_get_country_for_php_timezone($default);

			// Preselect timezone if we got a valid country back
			if ($selected_country)
			{
				$timezone_prepopulated = $timezones[$selected_country];
			}
		}

		// Construct the form
		ee()->load->helper('form');
		$output .= form_dropdown('tz_country', $countries, $selected_country, 'onchange="ee_tz_change(this)"');
		$output .= '&nbsp;&nbsp;'; // NBS constant doesn't work in installer
		$output .= form_dropdown($name, $timezone_prepopulated, $default, 'id="timezone_select"');

		return $output;
	}

	// --------------------------------------------------------------------

	/**
	 * Loads countries config file and creates localized array of country
	 * codes corresponding to country names
	 *
	 * @access	private
	 * @param	boolean	Whether or not to return timezones mapped to
	 *				countries instead
	 * @return	string
	 */
	private function _get_countries($return_timezones = FALSE)
	{
		if ( ! empty($this->_countries) AND ! $return_timezones)
		{
			return $this->_countries;
		}

		$conf = ee()->config->loadFile('countries');
		$countries = $conf['countries'];
		$timezones = $conf['timezones'];

		if ($return_timezones)
		{
			return $timezones;
		}

		foreach ($countries as $code => $country)
		{
			$countries[$code] = lang($country);
		}

		$this->_countries = $countries;

		return $this->_countries;
	}

	// --------------------------------------------------------------------

	/**
	 * Creates and returns a cached array of timezones by country.
	 *
	 * @access	private
	 * @return	array 	Array of timezones by country code
	 */
	private function _get_timezones_by_country()
	{
		if ( ! empty($this->_timezones_by_country))
		{
			return $this->_timezones_by_country;
		}

		// PHP 5.3+
		if (defined('DateTimeZone::PER_COUNTRY'))
		{
			foreach ($this->_get_countries() as $code => $country)
			{
				$this->_timezones_by_country[$code] = DateTimeZone::listIdentifiers(
					DateTimeZone::PER_COUNTRY, strtoupper($code)
				);
			}
		}
		// < PHP 5.3
		else
		{
			$this->_timezones_by_country = $this->_get_countries(TRUE);
		}

		return $this->_timezones_by_country;
	}

	// --------------------------------------------------------------------

	/**
	 * Returns the country code for a given PHP timezone
	 *
	 * @access	private
	 * @param	string	PHP timezone
	 * @return	string	Two-letter country code for timezone
	 */
	private function _get_country_for_php_timezone($timezone)
	{
		foreach ($this->_get_timezones_by_country() as $code => $timezones)
		{
			if (in_array($timezone, $timezones))
			{
				return $code;
			}
		}

		return FALSE;
	}

	// --------------------------------------------------------------------

	/**
	 * Gets the PHP timezone for the legacy timezone format EE used to
	 * store timezones with which was based on offsets; for example, given
	 * "UM5", it returns "America/New_York"
	 *
	 * @access	public
	 * @param	string
	 * @return	string
	 */
	public function get_php_timezone($zone = 'UTC')
	{
		$zones = array(
			'UM12'		=> 'Kwajalein', 					// -12
			'UM11'		=> 'Pacific/Midway', 				// -11
			'UM10'		=> 'Pacific/Honolulu', 				// -10
			'UM95'		=> 'Pacific/Marquesas',				// -9.5
			'UM9'		=> 'America/Anchorage', 			// -9
			'UM8'		=> 'America/Los_Angeles', 			// -8
			'UM7'		=> 'America/Denver', 				// -7
			'UM6'		=> 'America/Chicago', 				// -6
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
	 * Reads the configured date format from either userdata or the site's
	 * config and returns the string needed to format the JS datepicker
	 * to match.
	 *
	 * @access public
	 * @return string The string needed for the 'dateFormat:' argument for
	 *                the jQuery datepicker plugin.
	 */
	public function datepicker_format()
	{
		$date_format = ee()->session->userdata('date_format', ee()->config->item('date_format'));

		// Days
		$date_format = str_replace('%d', 'dd', $date_format);
		$date_format = str_replace('%j', 'd', $date_format);

		// Months
		$date_format = str_replace('%m', 'mm', $date_format);
		$date_format = str_replace('%n', 'm', $date_format);

		// Years
		$date_format = str_replace('%Y', 'yy', $date_format);
		$date_format = str_replace('%y', 'y', $date_format);

		return $date_format;
	}
}
// END CLASS

// EOF

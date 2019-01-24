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
 * Relative_Date
 */
class Relative_date {

	public function create($timestamp, $reference = NULL)
	{
		return new Relative_Date_object($timestamp, $reference);
	}

}

/**
 * Relative date object created for each instance of pagination.
 */
class Relative_date_object {
	public $singular;
	public $less_than;
	public $about;
	public $past;
	public $future;

	private $_units				= array();
	private $_deltas			= array();
	private $_calculated_units	= array();
	private $_timestamp			= 0;
	private $_reference			= 0;
	private $_valid_units		= array('years', 'months', 'fortnights', 'weeks', 'days', 'hours', 'minutes', 'seconds');

	public function __construct($timestamp, $reference = NULL)
	{
		$this->singular = lang('singular');
		$this->less_than = lang('less_than');
		$this->about = lang('about');
		$this->past = lang('past');
		$this->future = lang('future');

		$this->_timestamp = (int) $timestamp;
		$this->_reference = is_numeric($reference) ? $reference : ee()->localize->now;

		// Initializing to NULL so as not to break the magic __get() method
		$this->_units = array(
			'years'      => NULL,
			'months'     => NULL,
			'fortnights' => NULL,
			'weeks'      => NULL,
			'days'       => NULL,
			'hours'      => NULL,
			'minutes'    => NULL,
			'seconds'    => NULL
		);
	}

	public function __get($name)
	{
		if (array_key_exists($name, $this->_units))
		{
			return $this->_units[$name];
		}

		if (in_array($name, array('timestamp', 'reference', 'valid_units')))
		{
			return $this->{'_'.$name};
		}

        $trace = debug_backtrace();
        trigger_error(
            'Undefined property via __get(): ' . $name .
            ' in ' . $trace[0]['file'] .
            ' on line ' . $trace[0]['line'],
            E_USER_NOTICE);
        return NULL;
	}

	/**
	 * This calculcates the amount of time that has elapsed between two dates
	 *
	 * @access	public
	 * @param	string[]	$units	An array of units (date parts) to calculate
	 * @return	void
	 */
	public function calculate($units = array('years', 'months', 'weeks', 'days', 'hours', 'minutes', 'seconds'))
	{
		if ( ! is_array($units))
		{
			throw new Exception('We need an array of units to calculate');
		}

		$this->_units = array(
			'years'      => 0,
			'months'     => 0,
			'fortnights' => 0,
			'weeks'      => 0,
			'days'       => 0,
			'hours'      => 0,
			'minutes'    => 0,
			'seconds'    => 0
		);

		$seconds = array(
			'years'      => 31536000,	// 365 * 24 * 60 * 60
			'months'     => 2628000,	// (365 * 24 * 60 * 60) / 12
			'fortnights' => 1209600,	// 14 * 24 * 60 * 60
			'weeks'      => 604800,		// 7 * 24 * 60 * 60
			'days'       => 86400,		// 24 * 60 * 60
			'hours'      => 3600,		// 60 * 60
			'minutes'    => 60,
			'seconds'    => 1
		);

		$delta = abs($this->_timestamp - $this->_reference);

		foreach ($this->_valid_units as $unit)
		{
			if (in_array($unit, $units))
			{
				$this->_calculated_units[] = $unit;
				$this->_units[$unit] = (int) floor($delta / $seconds[$unit]);
				$delta -= $this->_units[$unit] * $seconds[$unit];
				$this->_deltas[$unit] = $delta;
			}
		}
	}

	/**
	 * Generates a human readable relative date string
	 *  	e.g. 'a day'
	 *  	     '5 hours'
	 *
	 * @access	public
	 * @param	int		$depth	Determines how many date parts we use
	 * @return	string	A human readable relative date string
	 */
	public function render($depth = 1)
	{
		$rounded = FALSE;
		$units = $this->_units;

		$non_zero_units = array();
		foreach ($this->_calculated_units as $key)
		{
			if ($units[$key] > 0)
			{
				$non_zero_units[] = $key;
			}
		}

		// Check to see if we have a "less than" case
		if (empty($non_zero_units))
		{
			$unit = end($this->_calculated_units);
			reset($this->_calculated_units);

			$str = $this->less_than.' '.$this->singular.' '.lang(rtrim($unit, 's'));
		}
		else
		{
			if (is_numeric($depth) AND $depth > 0)
			{
				// Check to see if we need to round the smallest displayed unit
				if ($depth < count($non_zero_units))
				{
					$round_to = $non_zero_units[$depth - 1];

					// These are the number of seconds at which we will round up
					// based on the delta from the calculation
					$delta_thresholds = array(
						'years'      => 29808000,	// 345 days
						'months'     => 2160000,	// 25 days
						'fortnights' => 1036800,	// 12 days
						'weeks'      => 518400,		// 6 days
						'days'       => 79200,		// 22 hours
						'hours'      => 2700,		// 45 minutes
						'minutes'    => 45,			// 45 seconds
					);

					if ($this->_deltas[$round_to] >= $delta_thresholds[$round_to])
					{
						$units[$round_to]++;
						$rounded = TRUE;
					}
				}

				$non_zero_units = array_slice($non_zero_units, 0, $depth);
			}

			$display_units = array();
			foreach ($non_zero_units as $key)
			{
				if ($units[$key] == 1)
				{
					$display_units[] = $this->singular.' '.lang(rtrim($key, 's'));
				}
				elseif ($units[$key] > 1)
				{
					$display_units[] = $units[$key].' '.lang($key);
				}
			}

			// If we have more than one unit on display add an 'and' in for
			// grammar's sake
			if (count($display_units) > 1)
			{
				$i = count($display_units) - 1;
				$display_units[$i] = lang('and').' '.$display_units[$i];
			}

			// Add commas if we have more than 2 units to display
			if (count($display_units) > 2)
			{
				$str = implode(', ', $display_units);
			}
			else
			{
				$str = implode(' ', $display_units);
			}

			if ($rounded)
			{
				$str = $this->about.' '.$str;
			}
		}


		if ($this->_timestamp <= $this->_reference)
		{
			$str = str_replace('%s', $str, $this->past);
		}
		else
		{
			$str = str_replace('%s', $str, $this->future);
		}

		return $str;
	}

}
// END Relative_Date class

// EOF

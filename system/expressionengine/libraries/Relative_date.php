<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2013, EllisLab, Inc.
 * @license		http://ellislab.com/expressionengine/user-guide/license.html
 * @link		http://ellislab.com
 * @since		Version 2.8
 * @filesource
 */

// ------------------------------------------------------------------------

/**
 * ExpressionEngine Relative_Date Class
 *
 * @package		ExpressionEngine
 * @subpackage	Core
 * @category	Core
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */

class Relative_date {

	public function __construct()
	{
		$this->EE =& get_instance();
	}

	public function create($timestamp, $reference = NULL)
	{
		return new Relative_Date_object($timestamp, $reference);
	}

}

/**
 * Relative date object created for each instance of pagination.
 */
class Relative_date_object {
	public $singular 			= 'one';
	public $less_than 			= 'less than';
	public $past 				= '%s ago';
	public $future 				= 'in %s';

	private $_units				= array();
	private $_calculated_units	= array();
	private $_timestamp			= 0;
	private $_reference			= 0;
	private $_valid_units		= array('years', 'months', 'weeks', 'days', 'hours', 'minutes', 'seconds');

	public function __construct($timestamp, $reference = NULL)
	{
		$this->_timestamp = (int) $timestamp;
		$this->_reference = is_numeric($reference) ? $reference : ee()->localize->now;

		$this->_units = array(
			'years'   => NULL,
			'months'  => NULL,
			'weeks'   => NULL,
			'days'    => NULL,
			'hours'   => NULL,
			'minutes' => NULL,
			'seconds' => NULL
		);
	}

	// ------------------------------------------------------------------------

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

	// ------------------------------------------------------------------------

	/**
	 * This calculcates the amount of time that has elapsed between two dates
	 *
	 * @access	public
	 * @param	string[]	$units	An array of units (date parts) to calculate
	 * @return	void
	 */
	public function calculate($units = array('years', 'months', 'weeks', 'days', 'hours', 'minutes', 'seconds'))
	{
		$this->_units = array(
			'years'   => 0,
			'months'  => 0,
			'weeks'   => 0,
			'days'    => 0,
			'hours'   => 0,
			'minutes' => 0,
			'seconds' => 0
		);

		if ( ! is_array($units))
		{
			throw new Exception('We need an array of units to calculate');
		}

		$this->_calculated_units = $units;

		$delta = abs($this->_timestamp - $this->_reference);

		if (in_array('years', $units))
		{
			$this->_units['years'] = (int) floor($delta / 31536000);
			$delta -= $this->_units['years'] * 31536000;
		}

		if (in_array('months', $units))
		{
			$this->_units['months'] = (int) floor($delta / 2628000);
			$delta -= $this->_units['months'] * 2628000;
		}

		if (in_array('weeks', $units))
		{
			$this->_units['weeks'] = (int) floor($delta / 604800);
			$delta -= $this->_units['weeks'] * 604800;
		}

		if (in_array('days', $units))
		{
			$this->_units['days'] = (int) floor($delta / 86400);
			$delta -= $this->_units['days'] * 86400;
		}

		if (in_array('hours', $units))
		{
			$this->_units['hours'] = (int) floor($delta / 3600);
			$delta -= $this->_units['hours'] * 3600;
		}

		if (in_array('minutes', $units))
		{
			$this->_units['minutes'] = (int) floor($delta / 60);
			$delta -= $this->_units['minutes'] * 60;
		}

		if (in_array('seconds', $units))
		{
			$this->_units['seconds'] = $delta;
		}
	}

	// ------------------------------------------------------------------------

	/**
	 * Generates a human readable relative date string
	 *  	e.g. 'a day'
	 *  	     '5 hours'
	 *
	 * @access	public
	 * @param	string[]	$units	An array of units (date parts) to calculate
	 * @param	int			$depth	Determines how many date parts we use
	 * @return	string	A human readable relative date string
	 */
	public function render($depth = 1)
	{
		$units = array();

		// Generate the string from years to seconds
		foreach ($this->_valid_units as $key)
		{
			if ($this->_units[$key] == 1)
			{
				$units[] = $this->singular.' '.lang(rtrim($key, 's'));
			}
			elseif ($this->_units[$key] > 1)
			{
				$units[] = $this->_units[$key].' '.lang(rtrim($key));
			}
		}

		if (empty($units))
		{
			$unit = end($this->_calculated_units);
			reset($this->_calculated_units);

			$str = $this->less_than.' '.$this->singular.' '.lang(rtrim($unit, 's'));
		}
		else
		{
			if (is_numeric($depth))
			{
				$units = array_slice($units, 0, $depth);
			}

			if (count($units) > 1)
			{
				$i = count($units) - 1;
				$units[$i] = lang('and').' '.$units[$i];
			}

			$str = implode(', ', $units);

			if (count($units) < 3)
			{
				$str = str_replace(',', '', $str);
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

/* End of file Relative_Date.php */
/* Location: ./system/expressionengine/libraries/Relative_date.php */
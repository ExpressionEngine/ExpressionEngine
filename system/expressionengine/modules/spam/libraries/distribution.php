<?php
/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2014, EllisLab, Inc.
 * @license		http://ellislab.com/expressionengine/user-guide/license.html
 * @link		http://ellislab.com
 * @since		Version 3.0
 * @filesource
 */

// ------------------------------------------------------------------------

/**
 * ExpressionEngine Spam Module
 *
 * @package		ExpressionEngine
 * @subpackage	Modules
 * @category	Modules
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */

class Distribution {

	public $mean;
	public $variance;
	public $distribution = "normal";

	public function __construct($mean, $variance, $distribution = "normal")
	{
		$this->mean = $mean;
		$this->variance = $variance;
		$this->distribution = $distribution;
	}

	public function probability($x)
	{
		return $this->{$this->$distribution}($x);
	}

	/**
	 * This is the PDF for the standard normal distribution
	 * 
	 * @param float $x 
	 * @access public
	 * @return float
	 */
	public function normal($x)
	{
		return 1 / ($this->variance * sqrt(2 * M_PI)) * pow(M_E, -1 * pow($x - $this->mean, 2) / (2 * pow($this->variance, 2))) ;
	}

}

?>
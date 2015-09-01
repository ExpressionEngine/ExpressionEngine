<?php

namespace EllisLab\Addons\Spam\Library;

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
		$prob = $this->{$this->distribution}($x);
		return $prob;
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
		// In the limit when σ -> 0 the normal distribution is infinite at x = μ
		// and 0 every where else. A classic case for the dirac delta function.
		if ($this->variance == 0)
		{
			if ($x == $this->mean)
			{
				return INF;
			}
			else
			{
				return 0;
			}
		}

		return  1 / ($this->variance * sqrt(2 * M_PI)) * pow(M_E, -1 * pow($x - $this->mean, 2) / (2 * pow($this->variance, 2))) ;
	}

}

/* End of file Distribution.php */
/* Location: ./system/expressionengine/modules/spam/libraries/Distribution.php */

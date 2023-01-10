<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Addons\Spam\Library;

/**
 * Spam Distribution
 */
class Distribution
{
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
        if ($this->variance == 0) {
            if ($x == $this->mean) {
                return INF;
            } else {
                return 0;
            }
        }

        return  1 / ($this->variance * sqrt(2 * M_PI)) * pow(M_E, -1 * pow($x - $this->mean, 2) / (2 * pow($this->variance, 2))) ;
    }
}

// EOF

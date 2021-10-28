<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2021, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Addons\Spam\Library\Vectorizers;

use ExpressionEngine\Addons\spam\Library\Vectorizer;

/**
 * Spam Module Spaces Vectorizer
 */
class Spaces implements Vectorizer
{
    /**
     * Calculates the ratio of whitespace to non-whitespace
     *
     * @param string $source The source text
     * @access public
     * @return float The calculated ratio
     */
    public function vectorize($source)
    {
        ee()->load->helper('multibyte');

        $whitespace = preg_match_all('/\s/u', $source, $matches);

        $characters = ee_mb_strlen($source);

        if ($characters !== 0) {
            $ratio = $whitespace / $characters;
        } else {
            $ratio = 1;
        }

        return $ratio;
    }
}

// EOF

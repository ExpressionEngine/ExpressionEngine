<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Addons\Spam\Library\Vectorizers;

use ExpressionEngine\Addons\Spam\Library\Vectorizer;

/**
 * Spam Module Punctuation Vectorizer
 */
class Punctuation implements Vectorizer
{
    /**
     * Calculates the ratio of punctuation to non-punctuation
     *
     * @param string $source The source text
     * @access public
     * @return float The calculated ratio
     */
    public function vectorize($source)
    {
        ee()->load->helper('multibyte');

        $punctuation = preg_match_all('/[!-~]/u', $source, $matches);

        $characters = ee_mb_strlen($source);

        if ($characters !== 0) {
            $ratio = $punctuation / $characters;
        } else {
            $ratio = 1;
        }

        return $ratio;
    }
}

// EOF

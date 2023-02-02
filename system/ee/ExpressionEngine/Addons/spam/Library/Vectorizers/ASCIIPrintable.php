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
 * Spam Module ASCIIPrintable Vectorizer
 */
class ASCIIPrintable implements Vectorizer
{
    /**
     * Calculates the ratio of non-ASCII printable characters
     *
     * @param string $source The source text
     * @access public
     * @return float The calculated ratio
     */
    public function vectorize($source)
    {
        ee()->load->helper('multibyte');

        $non_ascii = preg_match_all('/[^\x20-\x7E]/u', $source, $matches);

        $length = ee_mb_strlen($source);

        if ($length !== 0) {
            $ratio = $non_ascii / $length;
        } else {
            $ratio = 1;
        }

        return $ratio;
    }
}

// EOF

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
 * Spam Module Entropy Vectorizer
 */
class Entropy implements Vectorizer
{
    /**
     * Estimates the entropy of a string by calculating the compression ratio.
     *
     * @param string $source The source text
     * @access public
     * @return float estimated entropy
     */
    public function vectorize($source)
    {
        ee()->load->helper('multibyte');

        $length = mb_strlen($source);

        if ($length > 0) {
            $compressed = gzcompress($source);

            $compressed_length = ee_mb_strlen($compressed) - 8; // 8 bytes of gzip overhead

            $ratio = $compressed_length / $length;
        } else {
            $ratio = 0;
        }

        return $ratio;
    }
}

// EOF

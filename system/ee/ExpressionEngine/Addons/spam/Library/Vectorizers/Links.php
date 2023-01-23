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
 * Spam Module Links Vectorizer
 */
class Links implements Vectorizer
{
    /**
     * Calculates the amount of links in the source
     *
     * @param string $source The source text
     * @access public
     * @return float The calculated ratio
     */
    public function vectorize($source)
    {
        $pattern = '#[-a-zA-Z0-9@:%_\+.~\#?&//=]{2,256}\.[a-z]{2,4}\b(\/[-a-zA-Z0-9@:%_\+.~\#?&//=]*)?#si';

        return preg_match_all($pattern, $source, $matches);
    }
}

// EOF

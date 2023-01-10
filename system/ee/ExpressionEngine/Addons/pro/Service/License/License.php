<?php

/**
 * ExpressionEngine Pro
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
*/

namespace ExpressionEngine\Addons\Pro\Service\License;

/**
 * License service
 */
class License
{
    public function __construct()
    {
        //fetch the license response
        //if it's too old, grab the new one
    }

    /**
     * Check whether proplet is registered
     * Permforms the match against license response
     *
     * @param string $source add-on short name
     * @param string $class FQCN of prolet class
     * @return boolean
     */
    public function isRegisteredProlet($source, $class)
    {
        return true;
    }
}

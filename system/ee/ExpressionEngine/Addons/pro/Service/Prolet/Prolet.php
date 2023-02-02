<?php

/**
 * ExpressionEngine Pro
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
*/

namespace ExpressionEngine\Addons\Pro\Service\Prolet;

/**
 * Prolet service
 */
class Prolet
{
    /**
     * Initialize the prolet so it could be used later
     *
     * @param string $prolet_class
     * @param array $data prolet data
     * @return void
     */
    public function initialize($prolet_class, $data = [])
    {
        //since this function is expected to be called from a template tag, caller is add-on
        list($this_class, $caller) = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
        $addon_name = strtolower($caller['class']);
        $addon = ee('pro:Addon')->get($addon_name);
        if (empty($addon)) {
            return false;
        }

        $prolets = $addon->getProletClasses();
        if (array_key_exists($prolet_class, $prolets)) {
            $prolet_class = $prolets[$prolet_class];
        }
        if (!in_array($prolet_class, $prolets)) {
            return false;
        }
        foreach ($data as $param => $value) {
            ee()->session->set_cache('pro::' . $addon_name . '::' . $prolet_class, $param, $value);
        }
        return true;
    }
}

// EOF

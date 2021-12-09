<?php
/**
 *
 * This file is part of Aura for PHP.
 *
 * @license http://opensource.org/licenses/bsd-license.php BSD
 *
 */

namespace ExpressionEngine\Cli\Context;

/**
 *
 * A read-only representation of $_ENV values; falls back to getenv() when the
 * value does not exist in $_ENV.
 *
 * @package Aura.Cli
 *
 */
class Env extends AbstractValues
{
    /**
     *
     * Returns a value.
     *
     * @param string $key The key, if any, to get the value of; if null, will
     * return all values.
     *
     * @param string $alt The alternative default value to return if the
     * requested key does not exist.
     *
     * @return mixed The requested value, or the alternative default
     * value.
     *
     */
    public function get($key = null, $alt = null)
    {
        $val = parent::get($key, $alt);
        if ($val !== $alt) {
            return $val;
        }

        $val = getenv($key);
        if ($val !== false) {
            return $val;
        }

        return $alt;
    }
}

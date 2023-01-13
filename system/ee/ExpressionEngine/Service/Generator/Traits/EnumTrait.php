<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Service\Generator\Traits;

use ReflectionClass;

trait EnumTrait
{
    /**
     * gets class constants with key and value
     * @return array
     */
    public static function constants(): array
    {
        $refl = new ReflectionClass(__CLASS__);

        return $refl->getConstants();
    }

    /**
     * gets class constant values
     * @return array
     */
    public static function constantKeys(): array
    {
        $refl = new ReflectionClass(__CLASS__);

        $output = array();

        foreach ($refl->getConstants() as $key => $value) {
            $output[] = $key;
        }

        return $output;
    }

    /**
     * gets class constant values
     * @return array
     */
    public static function constantValues(): array
    {
        $refl = new ReflectionClass(__CLASS__);

        $output = array();

        foreach ($refl->getConstants() as $key => $value) {
            $output[] = $value;
        }

        return $output;
    }

    /**
     * checks if value is a class constant
     * @param  [string]  $val [value to check]
     * @return boolean
     */
    public static function has($val): bool
    {
        $constants = self::constantValues();

        return in_array($val, $constants);
    }

    public static function get($val): array
    {
        $constants = self::constantValues();

        if (self::has($val)) {
            return $constants[$val];
        }

        return [];
    }

    public static function getKey($val)
    {
        $constants = self::constants();

        foreach ($constants as $key => $value) {
            if ($val == $value) {
                return $key;
            }
        }

        return false;
    }

    public static function getByKey($key)
    {
        if (!array_key_exists($key, self::constants())) {
            return false;
        }

        return constant('self::' . $key);
    }
}

<?php

/**
 * Helper utility for reflection operations that work across PHP versions
 * Handles the setAccessible() deprecation in PHP 8.5+
 */
class TestReflectionHelper
{
    /**
     * Make a reflection object accessible in a version-compatible way
     *
     * @param ReflectionProperty|ReflectionMethod $reflection
     * @return void
     */
    public static function makeAccessible($reflection): void
    {
        if ($reflection instanceof ReflectionProperty) {
            self::makePropertyAccessible($reflection);

            return;
        }

        if ($reflection instanceof ReflectionMethod) {
            self::makeMethodAccessible($reflection);
        }
    }

    /**
     * Make a reflection property accessible in a version-compatible way
     *
     * @param ReflectionProperty $property
     * @return void
     */
    public static function makePropertyAccessible(ReflectionProperty $property): void
    {
        // setAccessible() is deprecated in PHP 8.5+ and has no effect
        if (PHP_VERSION_ID < 80500) {
            $property->setAccessible(true);
        }
        // In PHP 8.5+, we don't need to call setAccessible()
    }

    /**
     * Make a reflection method accessible in a version-compatible way
     *
     * @param ReflectionMethod $method
     * @return void
     */
    public static function makeMethodAccessible(ReflectionMethod $method): void
    {
        // setAccessible() is deprecated in PHP 8.5+ and has no effect
        if (PHP_VERSION_ID < 80500) {
            $method->setAccessible(true);
        }
        // In PHP 8.5+, we don't need to call setAccessible()
    }
}

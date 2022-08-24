<?php

namespace ExpressionEngine\Library\String;

class Str
{
    /**
     * @param string $value
     * @return string
     */
    public static function studly($value)
    {
        return str_replace(' ', '',
            ucwords(str_replace(['-', '_'], ' ', $value))
        );
    }

    /**
     * Converts/A/String/Like/This into a String\Formatted\Like\This
     * @param $value
     * @return string
     */
    public static function path2ns($value)
    {
        return str_replace('/', '\\', $value);
    }

    /**
     * Converts a string formatted-with-dashes to a CamelCaseStringInstead
     * @param string $value
     * @return string
     */
    public static function dash2ns($value)
    {
        return self::path2ns(
            str_replace(' ', '/',
                ucwords(str_replace('_', ' ', $value))
            )
        );
    }
}

<?php

namespace ExpressionEngine\Library\String;

class Str
{
    /**
     * @param string $value
     * @return string
     */
    public static function studly(string $value): string
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
    public static function path2ns(string $value): string
    {
        return str_replace('/', '\\', $value);
    }

    /**
     * Converts a string formatted-with-dashes to a CamelCaseStringInstead
     * @param string $value
     * @return string
     */
    public static function dash2ns(string $value): string
    {
        return self::path2ns(
            str_replace(' ', '/',
                str_replace('cartthrob', 'CartThrob', // ;)
                    ucwords(str_replace('_', ' ', $value))
                )
            )
        );
    }
}

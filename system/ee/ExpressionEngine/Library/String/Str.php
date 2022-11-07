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
        return str_replace(
            ' ',
            '',
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
            str_replace(
                ' ',
                '/',
                ucwords(str_replace('_', ' ', $value))
            )
        );
    }

    /**
     * Converts a string formatted with spaces to a snake_case_string_instead
     * @param string $value
     * @return string
     */
    public static function snakecase($value)
    {
        return trim(str_replace(['-', ' ', '.'], '_', strtolower($value)));
    }

    /**
     * Checks to see if $textToSearch contains the string $word
     * @param string $textToSearch
     * @param string $word
     * @return bool
     */
    public static function string_contains($textToSearch, $word)
    {
        return (strpos($textToSearch, $word) !== false);
    }
}

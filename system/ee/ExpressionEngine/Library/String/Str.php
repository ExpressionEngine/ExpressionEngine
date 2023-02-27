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
        // handle the case of going from PascalCase to snake_case
        $value = strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', trim($value)));
        // ensure single space
        $value = preg_replace('/\s+/', '', $value);

        return str_replace(['-', ' ', '__'], '_', strtolower($value));
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

    /**
     * Filters $string so it only contains alpha characters
     * @param string $string
     * @return string
     */
    public static function alphaFilter($string)
    {
        return preg_replace("/[^A-Za-z]/", '', $string);
    }
}

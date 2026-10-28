<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2026, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Library\Parser;

/**
 * Core Abstract Lexer
 *
 * THIRD PARTY DEVS: Extend at your own risk, this is private API
 */
abstract class AbstractLexer
{
    /**
     * The string being worked on
     */
    protected $str;

    /**
     * Current position in $str. Moving the offset instead of cutting the
     * consumed part off $str avoids copying the rest of the template on every
     * move (quadratic for large templates). Regular expressions run on a short
     * window from $pos, because before PHP 8.3 PCRE with the u flag checks the
     * whole subject for valid UTF-8 on every call; they fall back to the rest
     * of the template if the window cannot decide.
     */
    protected $pos = 0;

    const WINDOW = 512;     // bytes handed to a regex
    const MARGIN = 64;      // a match must end this far before the window end

    /**
     * Final token array
     */
    protected $tokens = array();

    /**
     * All lexers must implement their own tokenizing method.
     *
     * @param String $str The string to tokenize
     * @return array of tokens
     */
    abstract public function tokenize($str);

    /**
     * Peek ahead n characters without moving
     *
     * @param int $n The number of characters to peek ahead
     * @return string The next $n characters
     */
    protected function peek($n = 1)
    {
        return substr($this->str, $this->pos, $n);
    }

    /**
     * Peek ahead on an anchored regex
     *
     * @param string $regex A regular expression
     * @param string $flags Optionally change the regex flags
     * @return array|string The result of the match or an empty string
     */
    protected function peekRegex($regex, $flags = 'us', $nomatch_final = false)
    {
        $matches = $this->matchAnchored('/' . $regex . '/A' . $flags, $nomatch_final);

        return isset($matches) ? $matches[0] : null;
    }

    /**
     * Anchored match at $pos on a window, same result as matching the rest
     * of the template.
     *
     * @param string $pattern Complete pattern, anchored (/A)
     * @param bool $nomatch_final The caller knows that a pattern which does not
     *   match within the window cannot match the rest either (short patterns,
     *   or decided by the first character)
     * @return array|null The matches or null
     */
    protected function matchAnchored($pattern, $nomatch_final = false)
    {
        $window = substr($this->str, $this->pos, self::WINDOW);
        $whole = strlen($window) < self::WINDOW; // the window is the whole rest

        if (! $whole) {
            // drop the last character, it may be cut in the middle (invalid UTF-8)
            $i = strlen($window) - 1;
            while ($i > 0 && (ord($window[$i]) & 0xC0) == 0x80) {
                $i--;
            }
            $window = substr($window, 0, $i);
        }

        if (preg_match($pattern, $window, $matches)) {
            if ($whole || strlen($matches[0]) <= strlen($window) - self::MARGIN) {
                return $matches;
            }
        } elseif ($whole || $nomatch_final) {
            return null;
        }

        // the window cannot decide: match the rest, as before the patch
        if (preg_match($pattern, substr($this->str, $this->pos), $matches)) {
            return $matches;
        }

        return null;
    }

    /**
     * Seek to the first character in char mask
     *
     * @param string $char_mask The characters we are seeking
     * @return string The characters lost in the move
     */
    protected function seekTo($char_mask)
    {
        $n = 0;

        // strcspn from the current position (mbstring.func_overload, the
        // reason for the old regex, no longer exists as of PHP 8)
        $n = strcspn($this->str, $char_mask, $this->pos);

        return $this->move($n);
    }

    /**
     * Move to the next character
     *
     * @return string The consumed character
     */
    protected function next()
    {
        return $this->move(1);
    }

    /**
     * Move ahead n characters in the string, returning the consumed bit
     *
     * @param int $n The number of characters to move ahead
     * @return string The characters lost in the move
     */
    protected function move($n)
    {
        $buffer = substr($this->str, $this->pos, $n);
        $this->pos += strlen($buffer);

        return $buffer;
    }

    /**
     * Check if end of string reached.
     *
     * @return Bool End reached
     */
    protected function eof()
    {
        return $this->pos >= strlen($this->str);
    }

    /**
     * Get the remaining string to be lexed
     *
     * @return Remaining string
     */
    protected function rest()
    {
        return (string) substr($this->str, $this->pos);
    }
}

// EOF

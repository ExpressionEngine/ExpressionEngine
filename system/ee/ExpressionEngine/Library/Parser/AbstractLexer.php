<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2021, Packet Tide, LLC (https://www.packettide.com)
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
        return substr($this->str, 0, $n);
    }

    /**
     * Peek ahead on an anchored regex
     *
     * @param string $regex A regular expression
     * @param string $flags Optionally change the regex flags
     * @return array|string The result of the match or an empty string
     */
    protected function peekRegex($regex, $flags = 'us')
    {
        if (preg_match('/' . $regex . '/A' . $flags, $this->str, $matches)) {
            return $matches[0];
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

        // if mbstring.func_overload is enabled strcspn here and substr in move()
        // will not have matching lengths, so only use strcspn when they match
        // and fall back to regex otherwise
        if ($n = preg_match('/^[^' . preg_quote($char_mask, '/') . ']*/', $this->str, $matches)) {
            $n = strlen($matches[0]);
        }

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
        $buffer = substr($this->str, 0, $n);
        $this->str = substr($this->str, $n);

        return $buffer;
    }

    /**
     * Check if end of string reached.
     *
     * @return Bool End reached
     */
    protected function eof()
    {
        return $this->str == '';
    }

    /**
     * Get the remaining string to be lexed
     *
     * @return Remaining string
     */
    protected function rest()
    {
        return $this->str;
    }
}

// EOF

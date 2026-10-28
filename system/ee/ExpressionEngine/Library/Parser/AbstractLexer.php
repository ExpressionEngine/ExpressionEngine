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
     * Current byte position in the unchanged input buffer.
     */
    protected $pos = 0;

    /**
     * Final token array
     */
    protected $tokens = array();

    /**
     * All lexers must implement their own tokenizing method.
     *
     * @param string $str The input to tokenize.
     * @return array The resulting tokens.
     */
    abstract public function tokenize($str);

    /**
     * Read up to $n bytes without advancing the cursor.
     *
     * @param int $n The number of bytes to read.
     * @return string The requested bytes, or an empty string at the end of input.
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
     * @return string|null The matched text or null
     */
    protected function peekRegex($regex, $flags = 'us')
    {
        $matches = $this->matchAnchored('/' . $regex . '/A' . $flags);

        return isset($matches) ? $matches[0] : null;
    }

    /**
     * Match an anchored pattern against the current regex subject.
     *
     * @param string $pattern The complete anchored regular expression.
     * @return array|null The matched captures or null.
     */
    protected function matchAnchored($pattern)
    {
        if (preg_match($pattern, $this->getRegexSubject(), $matches) === 1) {
            return $matches;
        }

        return null;
    }

    /**
     * Provide the remaining input with the current position at subject start.
     *
     * @return string
     */
    protected function getRegexSubject()
    {
        return $this->rest();
    }

    /**
     * Advance to the first byte in the character mask, leaving that byte unconsumed.
     *
     * @param string $char_mask The delimiter bytes to seek.
     * @return string The bytes consumed before the delimiter or end of input.
     */
    protected function seekTo($char_mask)
    {
        // strcspn from the current position (mbstring.func_overload, the
        // reason for the old regex, no longer exists as of PHP 8)
        $n = strcspn($this->str, $char_mask, $this->pos);

        return $this->move($n);
    }

    /**
     * Consume the next byte if one is available.
     *
     * @return string The consumed byte, or an empty string at the end of input.
     */
    protected function next()
    {
        return $this->move(1);
    }

    /**
     * Consume up to $n bytes and advance the cursor by the number consumed.
     *
     * @param int $n The number of bytes to consume.
     * @return string The consumed bytes.
     */
    protected function move($n)
    {
        $buffer = substr($this->str, $this->pos, $n);
        $this->pos += strlen($buffer);

        return $buffer;
    }

    /**
     * Check whether the cursor has reached the end of input.
     *
     * @return bool
     */
    protected function eof()
    {
        return $this->pos >= strlen($this->str);
    }

    /**
     * Return the input remaining at the cursor.
     *
     * @return string
     */
    protected function rest()
    {
        return (string) substr($this->str, $this->pos);
    }
}

// EOF

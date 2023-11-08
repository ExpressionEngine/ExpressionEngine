<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Addons\Spam\Library;

use Iterator;

/**
 * Spam Document class. Cleans and generates a frequency table of a document.
 *
 * @implements Iterator
 */
class Document implements Iterator
{
    public $tokenizer;
    public $text;
    public $frequency = array();
    public $words = array();
    public $size;
    public $max_frequency = 0;
    private $position = 0;

    /**
     * Clean the text, and then generate the frequency table.
     *
     * @access public
     * @param mixed   $text The text of the Document we are getting the frequencies for
     * @param string  $tokenizer  Tokenizer object
     * @param bool    $clean  Strip all non alpha-numeric characters
     * @return void
     */
    public function __construct($text, $tokenizer, $clean = true)
    {
        if ($clean === true) {
            $text = preg_replace("/[^a-zA-Z0-9\s]/", "", $text);
        }

        $text = trim($text);
        $this->tokenizer = $tokenizer;
        $this->text = $text;
        $this->frequency = $this->calculateFrequency($text);
        $this->words = array_keys($this->frequency);
        $this->size = count(explode(' ', $text));
    }

    /**
     * We override __invoke here to make the frequency easily callable.
     *
     * @access public
     * @param string $word The word you want the frequency of
     * @return float
     */
    public function __invoke($word)
    {
        return $this->getFrequency($word);
    }

    /**
     * Return the frequency of a word.
     *
     * @access public
     * @param string $word The word you want the frequency of
     * @return float
     */
    public function getfrequency($word)
    {
        if (empty($this->frequency[$word])) {
            return 0;
        } else {
            return $this->frequency[$word];
        }
    }

    /**
     * Counts all of the words in the text and returns a sorted array
     * of their counts.
     *
     * @access private
     * @param mixed $text
     * @return array
     */
    private function calculateFrequency($text)
    {
        $count = array();

        $words = $this->tokenizer->tokenize($text);

        $num = count($words);
        $max = 0;

        foreach ($words as $word) {
            $word = strtolower($word);

            if (isset($count[$word])) {
                $count[$word]++;
            } else {
                $count[$word] = 1;
            }

            $max = max($max, $count[$word]);
        }

        $this->max_frequency = $max;
        arsort($count);

        return $count;
    }

    #[\ReturnTypeWillChange]
    public function rewind()
    {
        $this->position = 0;
    }

    #[\ReturnTypeWillChange]
    public function current()
    {
        return $this->frequency[$this->words[$this->position]];
    }

    #[\ReturnTypeWillChange]
    public function key()
    {
        return $this->words[$this->position];
    }

    #[\ReturnTypeWillChange]
    public function next()
    {
        ++$this->position;
    }

    #[\ReturnTypeWillChange]
    public function valid()
    {
        return isset($this->words[$this->position]);
    }
}
// END CLASS

// EOF

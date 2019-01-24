<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2019, EllisLab Corp. (https://ellislab.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace EllisLab\Addons\Spam\Library;

/**
 * Spam Tokenizer
 */
class Tokenizer {

	/**
	 * __construct
	 *
	 * @param int $ngrams  Size of the n-grams to calculate
	 * @param string $pattern  Regex pattern used to split string, defaults to
	 * 						   splitting by character
	 * @access public
	 * @return void
	 */
	public function __construct($ngram = 1, $pattern = "\s")
	{
		$this->ngram = $ngram;
		$this->pattern = $pattern;
	}

    /**
     * Tokenize takes a string and splits it into ngrams. This will return
     * an array of string.
     *
     * @param mixed $string
     * @access public
     * @return array An array of strings split based on ngram
     */
	public function tokenize($string)
	{
		if ( ! empty($this->pattern))
		{
			$tokens = preg_split("/{$this->pattern}/i", $string);
		}
		else
		{
			$tokens = str_split($string);
		}

		$tokens = array_filter($tokens);
		return $this->ngrams($tokens, $this->ngram);
	}

	/**
	 * Calculates the n-grams for a string
	 *
	 * @param array $tokens
	 * @param int $n
	 * @access private
	 * @return array  The array of n-grams
	 */
	private function ngrams($tokens, $n = 1)
	{
		if ($n == 1)
		{
			return $tokens;
		}

		$length = count($tokens);
		$ngrams = array();
		$i = 0;

		while (count($tokens) > 0)
		{
			$token = "";

			for ($j = 0; $j < $n; $j++)
			{
				$token .= " " . $tokens[($n * $i) + $j];
			}

			$ngrams[] = $token;
			$i++;
		}

		return $ngrams;
	}

}

// EOF

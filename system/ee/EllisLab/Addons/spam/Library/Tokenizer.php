<?php

namespace EllisLab\Addons\Spam\Library;

/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2014, EllisLab, Inc.
 * @license		http://ellislab.com/expressionengine/user-guide/license.html
 * @link		http://ellislab.com
 * @since		Version 3.0
 * @filesource
 */

// ------------------------------------------------------------------------

/**
 * ExpressionEngine Spam Module
 *
 * @package		ExpressionEngine
 * @subpackage	Modules
 * @category	Modules
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
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

/* End of file Tokenizer.php */
/* Location: ./system/expressionengine/modules/spam/libraries/Tokenizer.php */

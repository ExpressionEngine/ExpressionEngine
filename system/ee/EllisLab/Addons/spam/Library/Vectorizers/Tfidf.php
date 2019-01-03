<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2019, EllisLab Corp. (https://ellislab.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace EllisLab\Addons\Spam\Library\Vectorizers;

use \EllisLab\Addons\Spam\Library\Document;
use \EllisLab\Addons\spam\Library\Vectorizer;

/**
 * Spam Module Tfidf Vectorizer
 */
class Tfidf implements Vectorizer {

	public $documents = array();
	public $vocabulary = array();
	public $idf_lookup = array();
	public $corpus = "";
	public $limit = 1000;

	/**
	 * Get our corpus ready. First we strip out all common words specified in our stop word list,
	 * then loop through each document and generate a frequency table.
	 *
	 * @access public
	 * @param array   	 $source
	 * @param Tokenizer  $tokenizer  Tokenizer object used to split string
	 * @param array   	 $stop_words
	 * @param array   	 $limit  Maximum number of features to select
	 * @param bool    	 $clean  Strip all non alpha-numeric characters
	 * @return void
	 */
	public function __construct($source, $tokenizer, $stop_words = array(), $limit = NULL, $clean = TRUE)
	{
		if (empty($limit))
		{
			$limit = ee()->config->item('spam_word_limit') ?: 1000;
		}

		$this->tokenizer = $tokenizer;
		$this->clean = $clean;
		$this->limit = $limit;
		$this->stop_words = $stop_words;

		foreach ($stop_words as $key => $word)
		{
			$stop_words[$key] = " " . trim($word) . " ";
		}

		foreach ($source as $text)
		{
			if( ! empty($text))
			{
				$text = str_ireplace($stop_words, ' ', $text, $count);
				$doc = ee('spam:Document', $text, $this->tokenizer, $this->clean);

				foreach($doc->words as $word)
				{
					if( ! empty($this->vocabulary[$word]))
					{
						$this->vocabulary[$word]++;
					}
					else
					{
						$this->vocabulary[$word] = 1;
					}
				}

				$this->documents[] = $doc;
				$this->corpus .= ' ' . $doc->text;
			}
		}

		$this->document_count = count($this->documents);
		$this->corpus = ee('spam:Document', $this->corpus, $this->tokenizer, $this->clean);

		arsort($this->vocabulary);
		$this->vocabulary = array_slice($this->vocabulary, 0, $this->limit);
		$this->generateLookups();
	}

	/**
	 * Computes a vector of feature values suitable for using with Naive Bayes
	 *
	 * @param string $source The string to vectorize
	 * @access public
	 * @return array An array of floats
	 */
	public function vectorize($source)
	{
		$source = str_ireplace($this->stop_words, ' ', $source);
		$source = ee('spam:Document', $source, $this->tokenizer, $this->clean);
		return $this->_tfidf($source);
	}

	/**
	 * Calculate term frequency normalized by the maximum frequency
	 *
	 * @param Document $doc
	 * @param string $term
	 * @access public
	 * @return float The term frequency
	 */
	public function term_frequency(Document $doc, $term)
	{
		return 0.5 + (0.5 * $doc->getFrequency($term)) / $doc->max_frequency;
	}

	/**
	 * Calculate the inverse document frequency
	 *
	 * @param mixed $term
	 * @access public
	 * @return float
	 */
	public function inverse_document_frequency($term)
	{
		// Normalize frequency if term does not appear anywhere in corpus
		$freq = empty($this->vocabulary[$term]) ? 1 : $this->vocabulary[$term];

		if( ! empty($this->idf_lookup[$freq]))
		{
			return $this->idf_lookup[$freq];
		}

		$idf = log($this->document_count / $freq);
		$this->idf_lookup[$freq] = $idf;

		return $idf;
	}

	/**
	 * _tfidf
	 *
	 * @param Document $source
	 * @access private
	 * @return array Calculated TFIDF vector
	 */
	private function _tfidf($source)
	{
		$vector = $this->tfidf_row;

		foreach ($source as $term => $freq)
		{
			if ( ! empty($this->vocabulary_index[$term]))
			{
				$tf = $this->term_frequency($source, $term);
				$idf = $this->inverse_document_frequency($term);
				$vector[$this->vocabulary_index[$term]] = $tf * $idf;
			}
		}

		return $vector;
	}

	/**
	 * Generate lookup tables
	 *
	 * @access public
	 * @return void
	 */
	public function generateLookups()
	{
		$tfidf_row = array();
		$vocabulary_index = array();

		$count = count($this->vocabulary);
		$i = 0;

		foreach ($this->vocabulary as $term => $freq)
		{
			$tfidf_row[$i] = .5 * $this->inverse_document_frequency($term);
			$vocabulary_index[$term] = $i;
			$i++;
		}

		$this->tfidf_row = $tfidf_row;
		$this->vocabulary_index = $vocabulary_index;
	}
}

// EOF

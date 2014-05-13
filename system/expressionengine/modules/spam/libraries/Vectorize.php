<?php
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

require_once PATH_MOD . 'spam/libraries/Document.php';

// Include our vectorizer rules
require_once PATH_MOD . 'spam/libraries/vectorizers/ASCII_Printable.php';
require_once PATH_MOD . 'spam/libraries/vectorizers/Entropy.php';
require_once PATH_MOD . 'spam/libraries/vectorizers/Links.php';
require_once PATH_MOD . 'spam/libraries/vectorizers/Punctuation.php';
require_once PATH_MOD . 'spam/libraries/vectorizers/Spaces.php';

class Collection {

	public $documents = array();
	public $vocabulary = array();
	public $vectorizers = array();
	public $corpus = "";
	public $idf_lookup = array();
	
	/**
	 * Get our corpus ready. First we strip out all common words specified in our stop word list,
	 * then loop through each document and generate a frequency table.
	 * 
	 * @access public
	 * @param array $source 
	 * @param array $stop_words
	 * @return void
	 */
	public function __construct($source, $stop_words = array())
	{
		// register our vectorizer rules
		$this->register('ASCII_Printable');
		$this->register('Entropy');
		$this->register('Links');
		$this->register('Punctuation');
		$this->register('Spaces');

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
				$doc = new Document($text);

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
		$this->corpus = new Document($this->corpus);

		// Create a lookup table of IDFs for our vocabulary
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

	/**
	 * Computes a vector of feature values suitable for using with Naive Bayes
	 * 
	 * @param string $source The string to vectorize
	 * @access public
	 * @return array An array of floats
	 */
	public function vectorize($source)
	{
		$source = str_ireplace($stop_words, ' ', $source);
		$source = new Document($source);
		$vector = $this->_tfidf($source);
		$heuristics = $this->_heuristics($source);
		return array_merge($vector, $heuristics);
	}

	/**
	 * Return the term frequency inverse document frequency for all documents in the collection
	 * 
	 * @access public
	 * @return array The calculated tfidf
	 */
	public function tfidf()
	{
		$tfidf = array();

		foreach ($this->documents as $source)
		{
			$vector = $this->_tfidf($source);
			$heuristics = $this->_heuristics($source);
			$tfidf[] = array_merge($vector, $heuristics);
		}

		return $tfidf;
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
		return 0.5 + (0.5 * $doc->frequency($term)) / $doc->max_frequency;
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
	 * Register a vectorizer rule
	 * 
	 * @param mixed $class 
	 * @access public
	 * @return void
	 */
	public function register($class)
	{
		$obj = new $class;

		if ( ! $obj instanceOf Vectorizer)
		{
			throw new InvalidArgumentException($class.' must implement the Vectorizer interface.');
		}

		$this->vectorizers[] = $obj;
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
			$tf = $this->term_frequency($source, $term);
			$idf = $this->inverse_document_frequency($term);
			$vector[$this->vocabulary_index[$term]] = $tf * $idf;
		}

		return $vector;
	}

	/**
	 * Calculates a feature vector for our heuristics
	 * 
	 * @param mixed $source 
	 * @access private
	 * @return array Feauture vector of our calculated heuristics
	 */
	private function _heuristics($source)
	{
		$heuristics = array();

		foreach($this->vectorizers as $vec)
		{
			$heuristics[] = $vec->vectorize($source->text);
		}

		return $heuristics;
	}

}

// ------------------------------------------------------------------------

/**
 * ExpressionEngine Document Vectorizer Interface
 *
 * @package		ExpressionEngine
 * @subpackage	Core
 * @category	Core
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */
interface Vectorizer {

	/**
	 * Return a scalar value computed from the source string
	 *
	 * @param string $source
	 * @return float
	 */
	public function vectorize($source);

}

/* End of file Vectorize.php */
/* Location: ./system/expressionengine/modules/spam/libraries/Vectorize.php */

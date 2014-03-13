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

include('vectorizers/ASCII_Printable.php');
include('vectorizers/Entropy.php');
include('vectorizers/Punctuation.php');
include('vectorizers/Spaces.php');

class Collection {

	public $documents = array();
	public $vocabulary = array();
	public $corpus = "";
	public $vectorizers = array('ASCII_Printable', 'Entropy', 'Punctuation', 'Spaces');
	
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

		$this->corpus = new Document($this->corpus);
	}

	public function vectorize($source)
	{
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

		return log(count($this->documents) / $freq);
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
		$vector = array();

		foreach ($this->corpus as $term => $freq)
		{
			$tf = $this->term_frequency($source, $term);
			$idf = $this->inverse_document_frequency($term);
			$vector[] = $tf * $idf;
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
			$vec = new $vec();
			$heuristics[] = $vec->vectorize($source->text);
		}

		return $heuristics;
	}

}


/**
 * Document class. Cleans and generates a frequency table of a document.
 * 
 * @implements Iterator
 */
class Document implements Iterator {

	public $frequency = array();
	public $words = array();
	public $max_frequency = 0;
	private $position = 0;
	
	/**
	 * Clean the text, and then generate the frequency table.
	 * 
	 * @access public
	 * @param mixed $text The text of the Document we are getting the frequencies for
	 * @return void
	 */
	public function __construct($text)
	{
		$text = preg_replace("/[^a-zA-Z0-9\s]/", "", $text);
		$text = trim($text);
		$this->text = $text;
		$this->frequency = $this->_frequency($text);
		$this->words = array_keys($this->frequency);
		$this->size = count(explode(' ',$text));
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
		return $this->frequency($word);
	}
	
	/**
	 * Return the frequency of a word.
	 * 
	 * @access public
	 * @param string $word The word you want the frequency of
	 * @return float
	 */
	public function frequency($word)
	{
		if (empty($this->frequency[$word]))
		{
			return 0;
		}
		else
		{
			return $this->frequency[$word];
		}
	}

	/**
	 * Count and rank the frequency of words
	 * 
	 * @access private
	 * @param mixed $text
	 * @return array
	 */
	private function _frequency($text)
	{
		$count = array();
		$words = preg_split('/\s+/', $text);
		$num = count($words);
		$max = 0;

		foreach ($words as $word)
		{
			$word = strtolower($word);

			if (isset($count[$word]))
			{
				$count[$word]++;
			}
			else
			{
				$count[$word] = 1;
			}

			$max = max($max, $count[$word]);
		}

		$this->max_frequency = $max;
		arsort($count);
		return $count; 
	}

	public function rewind()
	{
        $this->position = 0;
    }

	public function current()
	{
        return $this->frequency[$this->words[$this->position]];
    }

	public function key()
	{
        return $this->words[$this->position];
    }

	public function next()
	{
        ++$this->position;
    }

	public function valid()
	{
        return isset($this->words[$this->position]);
    }
	
}

?>
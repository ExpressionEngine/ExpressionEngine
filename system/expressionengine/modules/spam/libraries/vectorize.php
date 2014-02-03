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
	public $corpus = "";
	
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
		foreach($stop_words as $key => $word)
		{
			$stop_words[$key] = " " . trim($word) . " ";
		}
		foreach($source as $text)
		{
			if( ! empty($text))
			{
				$text = str_ireplace($stop_words,' ',$text);
				$doc = new Document($text);
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
		return $vector;
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
		foreach($this->documents as $source)
		{
			$tfidf[] = $this->_tfidf($source);
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
		return 0.5 + (0.5 * $doc($term)) / $doc->max_frequency;
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
		$freq = $this->corpus->frequency($term);
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
		foreach($this->corpus as $term => $freq)
		{
			$tf = $this->term_frequency($source, $term);
			$idf = $this->inverse_document_frequency($term);
			$vector[] = $tf * $idf;
		}
		return $vector;
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
		$text = str_replace(array("\n","\r","\t"),'',$text);
		$text = preg_replace("/[^a-zA-Z0-9\s]/", "", $text);
		$text = trim(preg_replace('/\s\s+/', ' ', $text));
		$this->text = $text;
		$this->frequency = $this->_frequency($text);
		$this->words = array_keys($this->frequency);
		$this->size = count(explode(' ',$text));
	}
	
	/**
	 * We overide __invoke here to make the frequency easily callable.
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
		if(empty($this->frequency[$word]))
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
		$words = explode(' ', $text);
		$num = count($words);
		$max = 0;
		foreach($words as $word)
		{
			if(isset($count[$word]))
			{
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
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
				$this->documents[] = new Document($text);
				$this->corpus .= ' ' . $doc->text;
			}
		}
		$this->corpus = new Distribution($this->corpus);
	}

	/**
	 * Return the term frequency inverse document frequency for a given source & term in the collection
	 * 
	 * @param string $source The input document 
	 * @access public
	 * @return float The calculated tfidf
	 */
	public function tfidf($source, $term)
	{
		$doc = new Document($source);
		return $this->term_frequency($doc, $term) * $this->inverse_document_frequency($term);
	}

	/**
	 * Calculate term frequency normalized by the maximum frequency
	 * 
	 * @param Document $doc 
	 * @param string $term 
	 * @access public
	 * @return floadt The term frequency
	 */
	public function term_frequency(Document $doc, $term)
	{
		return 0.5 + (0.5 * $doc->frequency($term)) / $doc[0];
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
		$count = 0;
		foreach($this->collection as $doc)
		{
			if( ! empty($doc->frequency[$term]))
			{
				$count++;
			}
		}
		return log(count($this->collection) / $count);
	}
	
}


/**
 * Document class. Cleans and generates a frequency table of a document.
 * 
 * @implements Iterator
 */
class Document implements Iterator {

	private $position = 0;
	public $frequency = array();
	
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
		$text = preg_replace("/[^a-zA-Z0-9\s\p{P}]/", "", $text);
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
		foreach($words as $word)
		{
			if(isset($count[$word]))
			{
				$count[$word]++;
			} else {
				$count[$word] = 1;
			}
		}
		foreach($count as $key => $val)
		{
			$count[$key] = $val / $num;
		}
		arsort($count);
		return array_slice($count, 0, $this->limit);
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
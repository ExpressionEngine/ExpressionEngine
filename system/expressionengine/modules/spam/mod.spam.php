<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
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

require_once('libraries/Classifier.php');

class Spam {

	// Naive Bayes parameters
	public $stop_words_path = 'training/stopwords.txt';
	public $stop_words = array();
	public $sensitivity = .5;
	public $spam_ratio = .8;
	public $vocabulary_cutoff = 5000;

	// Limits for heuristics
	public $ascii_printable = .2;
	public $account_age = 3600;
	public $entropy = .2;
	public $entropy_length = 300;

	/**
	 * Loops through a string and increments the document counts for each term
	 * 
	 * @param string $document 
	 * @access private
	 * @return void
	 */
	private function _set_vocabulary($document)
	{
	}

	/**
	 * Returns an array of all the parameters
	 * 
	 * @access private
	 * @return array
	 */
	private function _get_parameters()
	{
	}

	/**
	 * Returns an array of document counts for every word in the training set
	 * 
	 * @access private
	 * @return void
	 */
	private function _get_vocabulary()
	{
	}

	/**
	 * Set the maximim-likelihood estimates for a parameter
	 * 
	 * @access private
	 * @return void
	 */
	private function _set_parameter()
	{
	}

	/**
	 * Loops through all content marked as spam and re-trains the parameters
	 * 
	 * @access private
	 * @return void
	 */
	private function _train_parameters()
	{
	}

}

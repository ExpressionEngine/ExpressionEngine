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

include('vectorize.php');

class Classifier {

	public $classes = array();

	// Sensitivity of the classifier, are we at least X% sure this is spam?
	public $sensitivity = .9;

	// This is the assumed a priori spam to ham ratio
	public $ratio = .5;

	/**
	 * Train the classifier on the provided training corpus
	 * 
	 * @param array $training  A multidimensional array of the training data,
	 * 						   format is as follows:
	 * 						       class0 => feature_vector0,
	 * 						       			 feautre_vector1,
	 * 						       			 ...
	 * 						       class1 => feature_vector0,
	 * 						       			 ...
	 * @access public
	 * @return void
	 */
	public function __construct($training, $sensitivity = 0.9, $ratio = 0.5)
	{
		$this->training = $training;
		$this->sensitivity = $sensitivity;
		$this->ration = $ratio;
		foreach($training as $class => $vectors)
		{
			$this->classes[] = $class;
		}
	}

	/**
	 * Returns the probability that a given text belongs to the specified class.
	 * This uses a binomial naive bayes classifier.
	 * 
	 * @param string $source  The text to be classified.
	 * @param string $class   The class to test for.
	 * @access public
	 * @return void
	 */
	public function classify($source, $class)
	{
		$other = array_diff(array($class), $this->classes);
		$other = $other[0];
		$class = $this->array_sum($class);
		$other = $this->array_sum($other);
		$count = count($class);
		$probabilities = array();
		$negations = array();
		// We want to calculate Pr(Spam|F) ∀ F ∈ Features
		// We assume statistical independence for all features and multiply together
		// to calulcate the probability the source is spam
		foreach($source as $word => $frequency)
		{

			$class_dist = new Distribution($class[$i]);
			$other_dist = new Distribution($other[$i]);
			$spamicity = ($this->ratio * $class_dist->probability($source[$i]));
			$spamicity = $spamicity /
						 ($spamicity + (1 - $this->ratio) * $other_dist->probability($source[$i]));
			$probabilities[] = $spamicity;
			$negations[] =  1 - $spamicity;
		}
		$product = array_product($probabilities);
		$negations_product = array_product($negations);
		return $product / ($product + $negations_product) > $this->sensitivity;
	}

	/**
	 * Zip a series of rows together column wise
	 * 
	 * @param array $class 
	 * @access private
	 * @return array
	 */
	private function array_sum($class)
	{
		$count = count($this->training[$class]);
		$sum = $this->training[$class][0];
		for($i = 1; $i < $count; $i++)
		{
			$sum = array_map(NULL, $sum, $this->training[$class][$i]);
		}
		return $sum;
	}

	/**
	 * Calculate the probability distribution for a series of data
	 * 
	 * @param array $feature An array of floats
	 * @access private
	 * @return Distribution The initilized probability distribution
	 */
	private function distribution($feature)
	{
		$sample = new Expectation($feature);
		return new Distribution($sample->mean, $sample->variance);
	}

}

?>
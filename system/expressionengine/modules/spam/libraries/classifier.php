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
include('expectation.php');
include('distribution.php');

class Classifier {

	public $classes = array();

	// Sensitivity of the classifier, are we at least X% sure this is spam?
	public $sensitivity = .9;

	// This is the assumed a priori spam to ham ratio
	public $ratio = .5;

	/**
	 * Train the classifier on the provided training corpus
	 * 
	 * @access public
	 * @return void
	 */
	public function __construct($training, $classes, $sensitivity = 0.9, $ratio = 0.5)
	{
		$this->sensitivity = $sensitivity;
		$this->ratio = $ratio;
		$this->classes = array_unique($classes);
		$this->corpus = $training;
		$training = $training->tfidf();

		foreach ($training as $key => $vector)
		{
			$this->training[$classes[$key]][] = $vector;
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
		$source = $this->corpus->vectorize($source); 
		$other = array_diff($this->classes, array($class));
		$other = array_shift($other);
		$class = $this->array_zip($class);
		$other = $this->array_zip($other);
		$count = count($class);
		$probabilities = array();

		// We want to calculate Pr(Spam|F) ∀ F ∈ Features
		// We assume statistical independence for all features and multiply together
		// to calculcate the probability the source is spam
		foreach($source as $feature => $freq)
		{
			$class_dist = $this->distribution($class[$feature]);
			$other_dist = $this->distribution($other[$feature]);

			// Most calculate the product in the log domain to avoid underflow
			// so our product becomes a sum of logs
			$class_prob = log($class_dist->probability($freq));
			$other_prob = log($other_dist->probability($freq));
			$ratio = $class_prob - $other_prob;
			$probabilities[] = $ratio;
		}
		
		$log_sum = array_sum($probabilities);

		return log($this->sensitivity) + $log_sum > 0;

	}

	/**
	 * Zip a series of rows together column wise
	 * 
	 * @param array $class 
	 * @access private
	 * @return array
	 */
	private function array_zip($class)
	{
		$count = count($this->training[$class][0]);
		$zipped = array();

		foreach ($this->training[$class] as $row)
		{
			for ($i = 0; $i < $count; $i++)
			{
				$zipped[$i][] = $row[$i];
			}
		}

		return $zipped;
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
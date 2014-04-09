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

require_once('Vectorize.php');
require_once('Expectation.php');
require_once('Distribution.php');

class Classifier {

	public $classes = array();

	// Sensitivity of the classifier, are we at least X% sure this is spam?
	public $sensitivity = .5;

	// This is the assumed a priori spam to ham ratio
	public $ratio = .8;

	/**
	 * Train the classifier on the provided training corpus
	 * 
	 * @access public
	 * @return void
	 */
	public function __construct($training, $classes, $stop_words = array())
	{
		$this->classes = array_unique($classes);
		$training = new Collection($training, $stop_words);
		$this->corpus = $training;
		$this->tfidf = $training->tfidf();

		foreach ($this->tfidf as $key => $vector)
		{
			$this->training[$classes[$key]][] = $vector;
		}
	}

	/**
	 * Returns the probability that a given text belongs to the specified class.
	 * This uses a gaussian naive bayes classifier.
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
		$class = $this->array_zip($this->training[$class]);
		$other = $this->array_zip($this->training[$other]);
		$total = $this->array_zip($this->tfidf);
		$count = count($class);
		$probabilities = array();
		$log_sum = 0;

		// We want to calculate Pr(Spam|F) ∀ F ∈ Features
		// We assume statistical independence for all features and multiply together
		// to calculcate the probability the source is spam
		foreach($source as $feature => $freq)
		{
			$sample = new Expectation($total[$feature]);

			$class_dist = $this->distribution($class[$feature]);
			$other_dist = $this->distribution($other[$feature]);
			$class_prob = $class_dist->probability($freq);
			$other_prob = $other_dist->probability($freq);

			// If we don't have enough info to compute a prior simply default to the spam ratio
			$epsilon = 0.01;

			if($class_dist->variance < $epsilon || $other_dist->variance < $epsilon)
			{
				$prob = 1 - $this->ratio;
			}
			else
			{
				// Compute probability Using Paul Graham's formula
				$prob = $class_prob * $this->ratio;
				$prob = $prob / ($prob + $other_prob * (1 - $this->ratio));
			}

			// Must calculate the product in the log domain to avoid underflow
			// so our product becomes a sum of logs
			$log_sum = log($prob) - log(1 - $prob);
		}

		$probability = 1 / (1 + pow(M_E, $log_sum));

		return $probability > $this->sensitivity;

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
		$count = count($class[0]);
		$zipped = array();

		foreach ($class as $row)
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
	 * Uses a Maximum-Likelihood estimator for the parameters
	 * 
	 * @param array $feature An array of floats
	 * @access private
	 * @return Distribution The initilized probability distribution
	 */
	private function distribution($feature)
	{
		// The MLE for the Gaussian distribution is just the sample mean & variance
		$sample = new Expectation($feature);
		return new Distribution($sample->mean, $sample->variance);
	}

}

/* End of file Classifier.php */
/* Location: ./system/expressionengine/modules/spam/libraries/Classifier.php */

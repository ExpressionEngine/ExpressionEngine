<?php

namespace EllisLab\Addons\Spam\Library;

/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2016, EllisLab, Inc.
 * @license		https://expressionengine.com/license
 * @link		https://ellislab.com
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
 * @link		https://ellislab.com
 */

class Classifier {

	public $classes = array();

	// Sensitivity of the classifier, are we at least X% sure this is spam?
	public $sensitivity = .5;

	// This is the assumed a priori spam to ham ratio
	public $ratio = .8;

	/**
	 * Train the classifier on the provided training corpus
	 *
	 * @param array $training  An array of feature vectors using classes as keys
	 * @param Collection  $vocabulary An instantiatied Collection
	 * @access public
	 * @return void
	 */
	public function __construct($training, $vocabulary, $stop_words = array())
	{
		$this->classes = array_unique(array_keys($training));
		$this->corpus = $vocabulary;
		$this->training = $training;
		$this->sensitivity = (ee()->config->item('spam_sensitivity') / 100) ?: $this->sensitivity;
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
		$orig = $source;
		$source = $this->corpus->transform($source);
		$other = array_diff($this->classes, array($class));
		$other = array_shift($other);
		$class = $this->training[$class];
		$other = $this->training[$other];
		$probabilities = array();
		$log_sum = 0;

		// We want to calculate Pr(Spam|F) ∀ F ∈ Features
		// We assume statistical independence for all features and multiply together
		// to calculcate the probability the source is spam
		foreach($source as $feature => $freq)
		{
			if (empty($class[$feature]) || empty($other[$feature]))
			{
				// We might have more features then currently trained
				// so we'll ignore them until retrained
				continue;
			}

			$class_dist = $class[$feature];
			$other_dist = $other[$feature];
			$class_prob = $class_dist->probability($freq);
			$other_prob = $other_dist->probability($freq);

			// If we don't have enough info to compute a prior simply default to the spam ratio
			$epsilon = 0.01;

			if($class_prob < $epsilon || $other_prob < $epsilon)
			{
				$prob = 1 - $this->ratio;
			}
			else
			{
				$prob = $class_prob * $this->ratio;
				$prob = $prob / ($prob + $other_prob * (1 - $this->ratio));
			}

			// Must calculate the product in the log domain to avoid underflow
			// so our product becomes a sum of logs
			$log_sum += log($prob) - log(1 - $prob);
		}

		$probability = 1 / (1 + pow(M_E, $log_sum));

		return $probability > $this->sensitivity;

	}

}

// EOF

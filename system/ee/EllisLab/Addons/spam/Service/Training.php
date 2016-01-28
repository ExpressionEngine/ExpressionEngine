<?php

namespace EllisLab\Addons\Spam\Service;

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
 * @subpackage	Extensions
 * @category	Extensions
 * @author		EllisLab Dev Team
 * @link		https://ellislab.com
 */

class Training {

	public $stop_words_path = 'spam/training/stopwords.txt';

	/**
	 * __construct
	 *
	 * @param string $kernel The name of the kernel to use
	 * @access public
	 * @return void
	 */
	public function __construct($kernel)
	{
		$this->kernel = $this->getKernel($kernel);
	}

	// --------------------------------------------------------------------

	/**
	 * Close the shared memory segment if we're using it.
	 *
	 * @access public
	 * @return void
	 */
	public function __destruct()
	{
		if ( ! empty($this->shm_id))
		{
			shmop_close($this->shm_id);
		}
	}

	// --------------------------------------------------------------------

	/**
	 * Load the classifier object from memory if available, otherwise construct
	 * a new classifier from the database.
	 *
	 * @access public
	 * @return The prepared classifier
	 */
	public function loadClassifier($vectorizers)
	{
		$collection = ee('spam:Collection', $vectorizers);

		if (function_exists('shmop_open'))
		{
			// Generate System V IPC key to identify out shared memory segment
			$id = ftok(__FILE__, 't');

			// It's as if millions of Daniel Binghams suddenly cried out in terror
			$this->shm_id = @shmop_open($id, 'a', 0, 0);

			// first check if we already have a memory segment
			if ($this->shm_id === FALSE)
			{
				// No memory segment, serialize and write classifier from database
				$classifier = $this->classifier($collection);
				$data = serialize($classifier);
				$size = strlen($data);
				$this->shm_id = shmop_open($id, 'c', 0644, $size);
				shmop_write($this->shm_id, $data, 0);
				return $classifier;
			}
			else
			{
				// Read from the memory segment and unserialize
				$size = shmop_size($this->shm_id);
				$data = shmop_read($this->shm_id, 0, $size);
				return unserialize($data);
			}
		}
		else
		{
			return $this->classifier($collection);
		}
	}

	// --------------------------------------------------------------------

	/**
	 * Deletes the shared memory segment containing our classifier
	 *
	 * @access public
	 * @return void
	 */
	public function deleteClassifier()
	{
		if (function_exists('shmop_open'))
		{
			if ( ! empty($this->shm_id) && is_int($this->shm_id))
			{
				$shm_id = $this->shm_id;
				unset($this->shm_id);
			}
			else
			{
				$id = ftok(__FILE__, 't');
				$shm_id = @shmop_open($id, 'a', 0, 0);

				if ($shm_id === FALSE)
				{
					// No memory segment exists
					return;
				}
			}

			shmop_delete($shm_id);
			shmop_close($shm_id);
		}
	}

	// --------------------------------------------------------------------

	/**
	 * Returns a new classifier based on our training data.
	 *
	 * @param  Vectorizer $collection
	 * @access public
	 * @return boolean
	 */
	public function classifier($collection)
	{
		$stop_words = explode("\n", ee()->lang->load('spam/stopwords', NULL, TRUE, FALSE));

		// Grab the trained parameters
		$training = array(
			'spam' => $this->getParameters('spam'),
			'ham' => $this->getParameters('ham'),
		);

		return ee('spam:Classifier', $training, $collection, $stop_words);
	}

	// --------------------------------------------------------------------

	/**
	 * Returns an array of all the parameters for a class
	 *
	 * @param string The class name
	 * @access private
	 * @return array
	 */
	private function getParameters($class)
	{
		ee()->db->select('mean, variance');
		ee()->db->from('spam_parameters');
		ee()->db->where('class', $class);
		ee()->db->where('kernel_id', $this->kernel->kernel_id);
		$query = ee()->db->get();

		$result = array();

		foreach ($query->result() as $parameter)
		{
				$result[] = ee('spam:Distribution', $parameter->mean, $parameter->variance);
		}

		return $result;
	}

	/**
	 * Returns an array of document counts for every word in the training set
	 *
	 * @access public
	 * @return array
	 */
	public function getVocabulary()
	{
		$kernel = $this->getKernel($this->kernel);
		ee()->db->select('term, count');
		ee()->db->from('spam_vocabulary');
		ee()->db->where('kernel_id', $kernel->kernel_id);
		ee()->db->limit(ee()->config->item('spam_word_limit') ?: 5000);
		$query = ee()->db->get();

		$result = array();

		foreach ($query->result() as $word)
		{
			$result[$word->term] = $word->count;
		}

		return $result;
	}

	// --------------------------------------------------------------------

	/**
	 * Returns the total document count for the current kernel
	 *
	 * @access public
	 * @return array
	 */
	public function getDocumentCount($kernel = "")
	{
		$kernel = $this->getKernel($kernel) ?: $this->kernel;
		return $kernel->count;
	}

	/**
	 * Grab the appropriate kernel ID or insert a new one
	 *
	 * @param string $name The name of the kernel
	 * @access private
	 * @return int The kernel ID
	 */
	private function getKernel($name)
	{
		$kernel = ee('Model')->get('spam:SpamKernel')->first();

		if (empty($kernel))
		{
			$kernel = ee('Model')->make('spam:SpamKernel', array('name' => $name));
			$kernel->save();
		}

		return $kernel;
	}

}

// EOF

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

require_once PATH_MOD . 'spam/libraries/Classifier.php';
require_once PATH_MOD . 'spam/libraries/Spam_training.php';

class Spam_mcp {

	public $stop_words_path = "spam/training/stopwords.txt";
	public $stop_words = array();

	/**
	 * Constructor
	 *
	 * @access	public
	 */
	public function __construct()
	{
		ini_set('memory_limit', '16G');
		set_time_limit(0);
	}

	/**
	 * Controller for the index view
	 * 
	 * @access public
	 * @return void
	 */
	public function index()
	{
		$data = array();
		$data['moderation'] = $this->_get_spam_trap();
		ee()->load->library('table');
		return ee()->load->view('index', $data, TRUE);
	}

	/**
	 * Moderate content. Will insert record into training table and either delete
	 * or reinsert the data if it's spam or ham respectively.
	 * 
	 * @param integer $id    ID of the content to moderate
	 * @param boolean $spam  True if content is spa,
	 * @access public
	 * @return void
	 */
	public function moderate()
	{
		foreach ($_POST as $key => $class)
		{
			if (substr($key, 0, 5) == 'spam_')
			{
				$id = str_replace('spam_', '', $key);

				ee()->db->select('file, class, method, data, document');
				ee()->db->from('spam_trap');
				ee()->db->where('trap_id', $id);
				$query = ee()->db->get();

				if ($query->num_rows() > 0)
				{
					$spam = $query->row();

					if ($class == 'ham')
					{
						ee()->load->file($spam->file);
						$class = $spam->class;
						$class = new $class();

						$data = unserialize($spam->data);
						call_user_func_array(array($class, $spam->method), $data);
					}

					// Insert into the training table
					$data = array(
						'source' => $spam->document,
						'class' => (int)($class == 'spam')
					);
					ee()->db->insert('spam_training', $data);

					// Delete from the spam trap
					ee()->db->delete('spam_trap', array('trap_id' => $id));
				}
			}
		}
	}

	/**
	 * Controller for running the testing
	 * 
	 * @access public
	 * @return void
	 */
	public function test()
	{
		$start_time = microtime(true);
		$limit = 1000;

		ee()->db->select('source, class');
		ee()->db->from('spam_training');
		ee()->db->order_by('RAND()');
		ee()->db->limit($limit);
		$query = ee()->db->get();

		$data = array();
		$negatives = 0;
		$positives = 0;
		$total = $query->num_rows();

		foreach ($query->result() as $document)
		{
			$bayes = new Spam_training();
			$bayes = $bayes->load_classifier();
			$classification = (int) $bayes->classify($document->source, 'spam');

			if($classification > $document->class)
			{
				$positives++;
			}

			if($classification < $document->class)
			{
				$negatives++;
			}
		}
 
		$data['memory'] = memory_get_usage();
		$data['memory_per'] = $data['memory'] / $total;
		$data['accuracy'] = ($total - ($negatives + $positives)) / $total;
		$data['total'] = $total;
		$data['positives'] = $positives;
		$data['negatives'] = $negatives;
		$data['time'] = (microtime(true) - $start_time);
		$data['per'] = $data['time'] / $total;

		return ee()->load->view('test', $data, TRUE);
	}

	/**
	 * Controller for running the training
	 * 
	 * @access public
	 * @return void
	 */
	public function train()
	{
		$data = array();
		$this->_train_parameters();
		return ee()->load->view('train', $data, TRUE);
	}

	/**
	 * Returns an array of content flagged as spam
	 * 
	 * @param  integer $limit The number of entries to grab  
	 * @access private
	 * @return array   Array of content to moderate
	 */
	private function _get_spam_trap($limit = 1000)
	{
		ee()->db->select('trap_id, document');
		ee()->db->from('spam_trap');
		ee()->db->limit($limit);
		$query = ee()->db->get();

		$result = array();

		foreach ($query->result() as $spam)
		{
			$spam_form = "Spam: <input type='radio' name='spam_{$spam->trap_id}' value='spam'>";
			$ham_form = "Ham: <input type='radio' name='spam_{$spam->trap_id}' value='ham'>";
			$moderation_form = "$spam_form $ham_form";

			$result[] = array(
				$spam->trap_id,
				$spam->document,
				$moderation_form
			);
		}

		return $result;
	}

	/**
	 * Returns an array of sources and classes for training
	 * 
	 * @access private
	 * @return array
	 */
	private function _get_training_data($limit = 1000)
	{
		ee()->db->select('source, class');
		ee()->db->from('spam_training');
		ee()->db->order_by('RAND()');
		ee()->db->limit($limit);
		$query = ee()->db->get();

		$sources = array();
		$classes = array();

		foreach ($query->result() as $document)
		{
			$sources[] = $document->source;
			$classes[] = $document->class;
		}

		return array($sources, $classes);
	}

	/**
	 * Loops through a string and increments the document counts for each term
	 * 
	 * @param string $document 
	 * @access private
	 * @return void
	 */
	private function _set_vocabulary($document)
	{
		$document = new Document($document);
		
		foreach ($document->words as $word)
		{
			ee()->db->select('count');
			ee()->db->from('spam_vocabulary');
			ee()->db->where('term', $word);
			$query = ee()->db->get();

			if ($query->num_rows() > 0)
			{
				ee()->db->where('term', $word);
				ee()->db->set('count', 'count+1', FALSE);
				ee()->db->update('spam_vocabulary');
			}
			else
			{
				$data = array('term' => $word, 'count' => 1);
				ee()->db->insert('spam_vocabulary', $data);
			}
		}
	}

	/**
	 * Set the maximim-likelihood estimates for a parameter
	 * 
	 * @param string  $term
	 * @param string  $class
	 * @param float   $mean
	 * @param float   $variance
	 * @access private
	 * @return void
	 */
	private function _set_parameter($term, $class, $mean, $variance)
	{
		$class = ($class == 'spam') ? 1 : 0;

		ee()->db->select('mean');
		ee()->db->from('spam_parameters');
		ee()->db->where('term', $term);
		ee()->db->where('class', $class);
		$query = ee()->db->get();

		if ($query->num_rows() > 0)
		{
			ee()->db->where('term', $term);
			ee()->db->where('class', $class);
			ee()->db->update('spam_parameters', array('mean' => $mean, 'variance' => $variance));
		}
		else
		{
			$data = array(
				'term' => $term,
				'class' => $class,
				'mean' => $mean,
				'variance' => $variance
			);
			ee()->db->insert('spam_parameters', $data);
		}
	}

	/**
	 * Loops through all content marked as spam/ham and re-trains the parameters
	 * 
	 * @access private
	 * @return void
	 */
	private function _train_parameters()
	{
		$stop_words = explode("\n", file_get_contents(PATH_MOD . $this->stop_words_path));
		$training_data = $this->_get_training_data(5000);
		$classes = $training_data[1];
		$training_collection = new Collection($training_data[0], $stop_words);
		$training_classes = array();
		$training = array();

		// Set the new vocabulary
		$vocabulary = array();

		foreach ($training_collection->vocabulary as $term => $count)
		{
			$data = array(
				'term' => $term,
				'count' => $count,
			);

			$vocabulary[] = $data;
		}

		ee()->db->empty_table('spam_vocabulary'); 
		ee()->db->insert_batch('spam_vocabulary', $vocabulary); 

		// Loop through and calculate the parameters for each feature and class

		foreach ($training_collection->tfidf() as $key => $vector)
		{
			$training_classes[$classes[$key]][] = $vector;
		}

		foreach ($training_classes as $class => $sources)
		{
			$count = count($sources[0]);
			$zipped = array();

			foreach ($sources as $row)
			{
				for ($i = 0; $i < $count; $i++)
				{
					$zipped[$i][] = $row[$i];
				}
			}

			foreach ($zipped as $index => $feature)
			{
				// Zipped is now an array of values for a particular feature and 
				// class. Time to do some estimates.

				$sample = new Expectation($feature);

				$training[] = array(
					'class' => $class,
					'term' => $index,
					'mean' => $sample->mean,
					'variance' => $sample->variance
				);
			}
		}

		ee()->db->empty_table('spam_parameters'); 
		ee()->db->insert_batch('spam_parameters', $training); 

		// Delete any existing shared memory segments if we're using them
		// This will get re-cached the next time we call the classifier
		$spam_training = new Spam_training();
		$spam_training->delete_classifier();

		return TRUE;
	}

}

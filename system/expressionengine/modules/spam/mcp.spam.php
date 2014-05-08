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
		// Make a local reference to the ExpressionEngine super object
		$this->EE =& get_instance();
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
		return ee()->load->view('index', $data, TRUE);
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
	 * Returns an array of sources and classes for training
	 * 
	 * @access private
	 * @return array
	 */
	private function _get_training_data()
	{
		$this->EE->db->select('source, class');
		$this->EE->db->from('spam_training');
		$this->EE->db->order_by('RAND()');
		$this->EE->db->limit(50);
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
			$this->EE->db->select('count');
			$this->EE->db->from('spam_vocabulary');
			$this->EE->db->where('term', $word);
			$query = ee()->db->get();

			if ($query->num_rows() > 0)
			{
				$this->EE->db->where('term', $word);
				$this->EE->db->set('count', 'count+1', FALSE);
				$this->EE->db->update('spam_vocabulary');
			}
			else
			{
				$data = array('term' => $word, 'count' => 1);
				$this->EE->db->insert('spam_vocabulary', $data);
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

		$this->EE->db->select('mean');
		$this->EE->db->from('spam_parameters');
		$this->EE->db->where('term', $term);
		$this->EE->db->where('class', $class);
		$query = ee()->db->get();

		if ($query->num_rows() > 0)
		{
			$this->EE->db->where('term', $term);
			$this->EE->db->where('class', $class);
			$this->EE->db->update('spam_parameters', array('mean' => $mean, 'variance' => $variance));
		}
		else
		{
			$data = array(
				'term' => $term,
				'class' => $class,
				'mean' => $mean,
				'variance' => $variance
			);
			$this->EE->db->insert('spam_parameters', $data);
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
		ini_set('memory_limit', '4G');
		set_time_limit(0);
		$stop_words = explode("\n", file_get_contents(PATH_MOD . $this->stop_words_path));
		$training_data = $this->_get_training_data();
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

		$this->EE->db->empty_table('spam_vocabulary'); 
		$this->EE->db->insert_batch('spam_vocabulary', $vocabulary); 

		// Loop through and calculate the parameters for each feature and class

		foreach ($training_collection->tfidf() as $key => $vector)
		{
			$training_classes[$classes[$key]][] = $vector;
		}
		var_dump($training_classes);
		die();

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

		$this->EE->db->empty_table('spam_parameters'); 
		$this->EE->db->insert_batch('spam_parameters', $training); 

		return TRUE;
	}

}

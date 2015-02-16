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

require_once PATH_MOD . 'spam/libraries/Spam_core.php';

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

					if ($class == 'ham' && ! empty($spam->file))
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
			$bayes = new Spam_core();
			$classification = (int) $bayes->classifier->classify($document->source, 'spam');

			if($classification > $document->class)
			{
				$positives++;
			}

			if($classification < $document->class)
			{
				$negatives++;
			}

			if($classification != $document->class)
			{
				 //ee()->db->delete('spam_training', array('training_id' => $document->training_id)); 
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
		$start_time = microtime(true);
		$this->_train_parameters();
		$data['time'] = (microtime(true) - $start_time);
		return ee()->load->view('train', $data, TRUE);
	}

	/**
	 * Controller for running the member training
	 * 
	 * @access public
	 * @return void
	 */
	public function train_member()
	{
		$data = array();
		$this->_train_member_parameters();
		return ee()->load->view('train_member', $data, TRUE);
	}

	/**
	 * Grab the appropriate kernel ID or insert a new one
	 * 
	 * @param string $name The name of the kernel 
	 * @access private
	 * @return int The kernel ID
	 */
	private function _get_kernel($name)
	{
		ee()->db->select('kernel_id');
		ee()->db->from('spam_kernels');
		ee()->db->where('name', $name);
		$query = ee()->db->get();

		if ($query->num_rows() > 0)
		{
			$row = $query->row();
			$id = $row->kernel_id;
		}
		else
		{
			$data = array('name' => $name);
			ee()->db->insert('spam_kernels', $data);
			$id = ee()->db->insert_id();
		}

		return $id;
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
	 * Returns an array of member data for all our known spammers
	 * 
	 * @access private
	 * @return array
	 */
	private function _get_spammers($limit = 1000)
	{
		ee()->db->select('ip_addess', 'username', 'email', 'url');
		ee()->db->from('spam_trap');
		ee()->db->join('members', 'spam_trap.author = members.member_id');
		ee()->db->limit($limit);
		$query = ee()->db->get();

		$result = array();

		foreach ($query->result() as $spammer)
		{
			$result[] = $spammer;
		}

		return $result;
	}

	/**
	 * Returns an array of member data for known non-spammers 
	 * 
	 * @access private
	 * @return array
	 */
	private function _get_real_people($limit = 1000)
	{
		ee()->db->select('ip_addess', 'username', 'email', 'url');
		ee()->db->from('members');
		ee()->db->join('spam_trap', 'spam_trap.author = members.member_id');
		ee()->db->where(array('trap_id' => NULL));
		ee()->db->limit($limit);
		$query = ee()->db->get();

		$result = array();

		foreach ($query->result() as $member)
		{
			$result[] = $member;
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
	 * Sets the maximum likelihood estimates for a given training set and kernel
	 * 
	 * @param array $training Multi-dimensional array of training data:
	 * 						  $class => array(
	 * 						  	  array($feature0, $feauture1, ...),
	 * 						  	  ...
	 * 						  )
	 * @param string $kernel 
	 * @access private
	 * @return void
	 */
	private function _set_maximum_likelihood($training_collection, $kernel = 'default')
	{
		$kernel = $this->_get_kernel($kernel);
		$training = array();

		foreach ($training_collection as $class => $sources)
		{
			$count = count($sources[0]);
			$zipped = array();

			foreach ($sources as $key => $row)
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
					'kernel_id' => $kernel,
					'class' => $class,
					'term' => $index,
					'mean' => $sample->mean,
					'variance' => $sample->variance
				);
			}
		}

		ee()->db->empty_table('spam_parameters'); 
		ee()->db->insert_batch('spam_parameters', $training); 
	}


	/**
	 * Loops through all content marked as spam/ham and builds a member 
	 * classifier based on the authors' member data.
	 * 
	 * @access private
	 * @return void
	 */
	private function _train_member_parameters()
	{
		$spammers = $this->_get_spammers();
		$hammers = $this->_get_real_people();
		$members = array_merge($spammers, $hammers);

		$spam_classes = array_pad(array(), count($spammers), 1);
		$ham_classes = array_pad(array(), count($hammers), 0);
		$classes = array_merge($spam_classes, $ham_classes);
		$training = array();

		foreach ($members as $member)
		{
			$ip = str_replace('.', ' ', $member->ip);

			$training[] = implode(' ', array($member->username, $member->email, $member->url, $ip));
		}

		$tokenizer = new Tokenizer();
		$training_classes = array();

		$vocabulary = array();
		$kernel = $this->_get_kernel('member');
		$tfidf = new Tfidf($training, $tokenizer);

		$vectorizers = array();
		$vectorizers[] = $tfidf;
		$vectorizers[] = new ASCII_Printable();
		$vectorizers[] = new Punctuation();
		$training_collection = new Collection($vectorizers);

		foreach ($tfidf->vocabulary as $term => $count)
		{
			$data = array(
				'term' => $term,
				'count' => $count,
				'kernel_id' => $kernel
			);

			$vocabulary[] = $data;
		}

		ee()->db->empty_table("spam_vocabulary"); 
		ee()->db->insert_batch("spam_vocabulary", $vocabulary); 

		foreach ($training_collection->fit_transform($training) as $key => $vector)
		{
			$training_classes[$classes[$key]][$key] = $vector;
		}

		$this->_set_maximum_likelihood($training_classes, 'member');

		$spam_training = new Spam_training('default');
		$spam_training->delete_classifier();
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
		$training_data = $this->_get_training_data(10000);
		$classes = $training_data[1];

		$tokenizer = new Tokenizer();
		$tfidf = new Tfidf($training_data[0], $tokenizer, $stop_words);
		$vectorizers = array();
		$vectorizers[] = new ASCII_Printable();
		$vectorizers[] = new Entropy();
		$vectorizers[] = new Links();
		$vectorizers[] = new Punctuation();
		$vectorizers[] = new Spaces();
		$training_collection = new Collection($vectorizers);

		$training_classes = array();
		$training = array();

		$kernel = $this->_get_kernel('default');

		// Set the new vocabulary
		$vocabulary = array();

		foreach ($tfidf->vocabulary as $term => $count)
		{
			$data = array(
				'term' => $term,
				'count' => $count,
				'kernel_id' => $kernel
			);

			$vocabulary[] = $data;
		}

		ee()->db->empty_table('spam_vocabulary'); 
		ee()->db->insert_batch('spam_vocabulary', $vocabulary); 

		foreach ($training_collection->fit_transform($training_data[0]) as $key => $vector)
		{
			$training_classes[$classes[$key]][] = $vector;
		}

		$this->_set_maximum_likelihood($training_classes);
		$spam_training = new Spam_training('default');
		$spam_training->delete_classifier();
	}
}

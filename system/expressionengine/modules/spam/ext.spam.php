<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2014, EllisLab, Inc.
 * @license		http://ellislab.com/expressionengine/user-guide/license.html
 * @link		http://ellislab.com
 * @since		Version 2.5
 * @filesource
 */

// ------------------------------------------------------------------------

/**
 * ExpressionEngine Rich Text Editor Module
 *
 * @package		ExpressionEngine
 * @subpackage	Extensions
 * @category	Extensions
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */

class Spam_ext {

	public $name = 'Spam Filter';
	public $version = M_E;
	public $settings_exist = 'n';
	public $docs_url = '';

	// Naive Bayes parameters
	public $vocabulary_cutoff = 5000;
	public $sensitivity = .5;
	public $spam_ratio = .8;
	public $stop_words_path = 'training/stopwords.txt';

	// Limits for heuristics
	public $ascii_printable = .2;
	public $account_age = 3600;
	public $entropy = .2;
	public $entropy_length = 300;

	private $EE;
	private $module = 'spam';

	/**
	 * Constructor
	 */
	public function __construct()
	{
		$this->EE =& get_instance();
	}

	// --------------------------------------------------------------------

	/**
	 * Returns true if the string is classified as spam
	 * 
	 * @param string $source 
	 * @access public
	 * @return boolean
	 */
	public function classify($source)
	{
		$stop_words = explode("\n", file_get_contents($this->stop_words_path));
		$vocabulary = new Collection('', $this->stop_words);
		$vocabulary->vocabulary = $this->_get_vocabulary();

		$this->EE->db->select("COUNT(training_id) AS cnt");
		$this->EE->db->from("spam_training");
		$query = $this->EE->db->get(); 
		$row = $query->row();
		$vocabulary->document_count = $row->cnt;

		// Grab the trained parameters
		$training = array(
			'spam' => $this->_get_parameters('spam'),
			'ham' => $this->_get_parameters('ham'),
		);

		$classifier = new Classifier($training, $vocabulary);
		
		return $classifier->classify($soure, 'spam');
	}

	// --------------------------------------------------------------------

	/**
	 * Returns an array of all the parameters for a class
	 * 
	 * @param string The class name
	 * @access private
	 * @return array
	 */
	private function _get_parameters($class)
	{
		$class = ($class == 'spam') ? 1 : 0;

		$this->EE->db->select('mean, variance');
		$this->EE->db->from('spam_parameters');
		$this->EE->db->where('class', $class);
		$query = ee()->db->get();

		$result = array();

		foreach ($query->result() as $parameter)
		{
			$result[] = new Distribution($parameter->$mean, $parameter->variance);
		}
	
		return $result;
	}

	// --------------------------------------------------------------------

	/**
	 * Returns an array of document counts for every word in the training set
	 * 
	 * @access private
	 * @return array
	 */
	private function _get_vocabulary()
	{
		select('term, count');
		from('spam_vocabulary');
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
	 * Filter content for spam
	 * 
	 * @access public
	 * @return void
	 */
	public function filter_spam()
	{
	}

	// --------------------------------------------------------------------

	/**
	 * Activate Extension
	 * This extension is automatically installed with the Rich Text Editor module
	 */
	public function activate_extension()
	{
		return TRUE;
	}

	// --------------------------------------------------------------------

	/**
	 * Update Extension
	 * This extension is automatically updated with the Rich Text Editor module
	 */
	public function update_extension( $current = FALSE )
	{
		return TRUE;
	}

	// --------------------------------------------------------------------

	/**
	 * Disable Extension
	 * This extension is automatically disabled with the Rich Text Editor module
	 */
	public function disable_extension()
	{
		return TRUE;
	}

		// --------------------------------------------------------------------

	/**
	 * Uninstall Extension
	 * This extension is automatically uninstalled with the Rich Text Editor module
	 */
	public function uninstall_extension()
	{
		return TRUE;
	}

}

/* End of file ext.spam.php */
/* Location: ./system/expressionengine/modules/spam/ext.spam.php */

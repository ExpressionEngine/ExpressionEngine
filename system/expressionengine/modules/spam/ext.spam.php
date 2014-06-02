<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

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
 * ExpressionEngine Rich Text Editor Module
 *
 * @package		ExpressionEngine
 * @subpackage	Extensions
 * @category	Extensions
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */

require_once PATH_MOD . 'spam/libraries/Classifier.php';

class Spam_ext {

	public $name = 'Spam Filter';
	public $version = '1.0.0';
	public $settings_exist = 'n';
	public $docs_url = '';

	// Naive Bayes parameters
	public $vocabulary_cutoff = 1000;
	public $sensitivity = .5;
	public $spam_ratio = .8;
	public $stop_words_path = 'spam/training/stopwords.txt';

	// Limits for heuristics
	public $ascii_printable = .2;
	public $account_age = 3600;
	public $entropy = .2;
	public $entropy_length = 300;

	private $module = 'spam';

	/**
	 * Constructor
	 */
	public function __construct()
	{
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
		$stop_words = explode("\n", file_get_contents(PATH_MOD . $this->stop_words_path));
		$vocabulary = new Collection(array(), $stop_words);
		$vocabulary->vocabulary = $this->_get_vocabulary();

		ee()->db->select("COUNT(training_id) AS cnt");
		ee()->db->from("spam_training");
		$query = ee()->db->get(); 
		$row = $query->row();
		$vocabulary->document_count = $row->cnt;

		// Grab the trained parameters
		$training = array(
			'spam' => $this->_get_parameters('spam'),
			'ham' => $this->_get_parameters('ham'),
		);

		$classifier = new Classifier($training, $vocabulary, $stop_words);
		
		return $classifier->classify($source, 'spam');
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

		ee()->db->select('mean, variance');
		ee()->db->from('spam_parameters');
		ee()->db->where('class', $class);
		$query = ee()->db->get();

		$result = array();

		foreach ($query->result() as $parameter)
		{
			$result[] = new Distribution($parameter->mean, $parameter->variance);
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
		ee()->db->select('term, count');
		ee()->db->from('spam_vocabulary');
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
	 * Filter comments for spam
	 * 
	 * @access public
	 * @return void
	 */
	public function filter_comment($data)
	{
		if ($this->classify($data['comment']) === TRUE)
		{
			$this->moderate_content('comment', $data);
			ee()->extensions->end_script = TRUE;
		}

		return $data;
	}

	// --------------------------------------------------------------------

	/**
	 * Filter channel form submissions for spam
	 * 
	 * @access public
	 * @return void
	 */
	public function filter_channel_form($data)
	{
		// Channel form entries are tricky because we can have an arbitrary 
		// number of fields. Since we're using Naive Bayes, our assumption
		// of statistical independece means classifying all the content lumped 
		// together should give the same result as classifying each separately.
		$i = 1;
		$content = array();

		while ( ! empty($data->entry['field_id_' . $i]))
		{
			$content[] = $data->entry['field_id_' . $i];
			$i++;
		}

		$content = implode(' ', $content);

		if ($this->classify($content) === TRUE)
		{
			$this->moderate_content('comment', $data);
			ee()->extensions->end_script = TRUE;
		}

		return $data;
	}

	// --------------------------------------------------------------------

	/**
	 * Filter forum posts for spam
	 * 
	 * @access public
	 * @return void
	 */
	public function filter_forum_post($data)
	{
		ee()->extensions->end_script = TRUE;
	}

	// --------------------------------------------------------------------

	/**
	 * Store flagged spam to await moderation.
	 * 
	 * @param string $type     The content type (comment, post, etc..)
	 * @param string $content  Array of content data
	 * @access public
	 * @return void
	 */
	public function moderate_content($type, $content)
	{
		$data = array(
			'type' => $type,
			'content' => serialize($content)
		);
		ee()->db->insert('spam_trap', $data);
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

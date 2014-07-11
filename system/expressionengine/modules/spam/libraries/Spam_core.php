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
 * ExpressionEngine Spam Module
 *
 * @package		ExpressionEngine
 * @subpackage	Extensions
 * @category	Extensions
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */

require_once PATH_MOD . 'spam/libraries/Spam_training.php';

class Spam_core {

	/**
	 * Constructor
	 */
	public function __construct()
	{
		$training = new Spam_training();
		$this->classifier = $training->load_classifier();
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
		return $this->classifier->classify($source, 'spam');
	}

	// --------------------------------------------------------------------

	/**
	 * Store flagged spam to await moderation. We store a serialized array of any
	 * data we might need as well as a class and method name. If an entry that was
	 * caught by the spam filter is manually flagged as ham, the spam module will
	 * call the stored method with the unserialzed data as the argument. You must
	 * provide a method to handle re-inserting this data.
	 * 
	 * @param string $class    The class to call when re-inserting a false positive
	 * @param string $method   The method to call when re-inserting a false positive
	 * @param string $content  Array of content data
	 * @param string $doc      The document that was classified as spam
	 * @access public
	 * @return void
	 */
	public function moderate_content($file, $class, $method, $content, $doc)
	{
		$data = array(
			'file' => $file,
			'class' => $class,
			'method' => $method,
			'data' => serialize($content),
			'document' => $doc
		);
		ee()->db->insert('spam_trap', $data);
	}

}

/* End of file Spam_core.php */
/* Location: ./system/expressionengine/modules/spam/Spam_core.php */

<?php

namespace EllisLab\Addons\Spam\Service;

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

class Spam {

	/**
	 * Constructor
	 */
	public function __construct()
	{
		$this->classifier = $this->load_default_classifier();
		$this->member_classifier = $this->load_member_classifier();
	}

	// --------------------------------------------------------------------

	/**
	 * Returns true if the member is classified as a spammer
	 * 
	 * @param string $username 
	 * @param string $email 
	 * @param string $url 
	 * @param string $ip 
	 * @access public
	 * @return void
	 */
	public function member_classify($username, $email, $url, $ip)
	{
		// Split IP address with spaces so TFIDF will calculate each octet as a 
		// separate feature. We're definitely abusing TFIDF here but it should
		// calculate the frequencies correctly barring any member names that 
		// overlap with our octets.
		$ip = str_replace('.', ' ', $ip);

		$text = implode(' ', array($username, $email, $url, $ip));
		$source = ee('spam:Source', $text);

		return $this->member_classifier->classify($source, 'spam');
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
		$source = ee('spam:Source', $source);
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
	public function moderate_content($file, $author, $class, $method, $content, $doc)
	{
		$data = array(
			'file' => $file,
			'author' => $author,
			'date' => time(),
			'ip_address' => $_SERVER['REMOTE_ADDR'],
			'class' => $class,
			'method' => $method,
			'data' => serialize($content),
			'document' => $doc
		);
		$trap = ee('Model')->make('SpamTrap', $data);
		$trap->save();
	}

	/**
	 * load_default_classifier
	 * 
	 * @access public
	 * @return void
	 */
	public function load_default_classifier()
	{
		$training = ee('spam:Training', 'default');
		$stop_words = explode("\n", file_get_contents(PATH_MOD . $training->stop_words_path));
		$tokenizer = ee('spam:Tokenizer');

		// Prep the the TFIDF vectorizer with the vocabulary we have stored
		$tfidf = ee('spam:Tfidf', array(), $tokenizer, $stop_words);
		$tfidf->vocabulary = $training->get_vocabulary();
		$tfidf->document_count = $training->get_document_count();
		$tfidf->generate_lookups();

		$vectorizers = array();
		$vectorizers[] = ee('spam:Vectorizers/ASCII_Printable');
		$vectorizers[] = ee('spam:Vectorizers/Entropy');
		$vectorizers[] = ee('spam:Vectorizers/Links');
		$vectorizers[] = ee('spam:Vectorizers/Punctuation');
		$vectorizers[] = ee('spam:Vectorizers/Spaces');

		return $training->load_classifier($vectorizers);
	}

	/**
	 * load_member_classifier
	 * 
	 * @access public
	 * @return void
	 */
	public function load_member_classifier()
	{
		$training = ee('spam:Training', 'member');
		$stop_words = explode("\n", file_get_contents(PATH_MOD . $training->stop_words_path));
		$tokenizer = ee('spam:Tokenizer');

		$tfidf = ee('spam:Tfidf', array(), $tokenizer, $stop_words);
		$tfidf->vocabulary = $training->get_vocabulary();
		$tfidf->document_count = $training->get_document_count();
		$tfidf->generate_lookups();

		$vectorizers = array();
		$vectorizers[] = $tfidf;
		$vectorizers[] = ee('spam:Vectorizers/ASCIIPrintable');
		$vectorizers[] = ee('spam:Vectorizers/Punctuation');

		return $training->load_classifier($vectorizers);
	}

}

/* End of file Spam_core.php */
/* Location: ./system/expressionengine/modules/spam/Spam_core.php */

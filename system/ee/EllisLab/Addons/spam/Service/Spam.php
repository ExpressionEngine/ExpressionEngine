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
        // Check if the spam module is installed
		ee()->load->library('addons');
		$installed = ee()->addons->get_installed();

		if (empty($installed['spam']))
		{
			$this->installed = FALSE;
		}
		else
		{
			$this->installed = TRUE;
			$this->classifier = $this->loadDefaultClassifier();
		}
	}

	/**
	 * Returns true if the member is classified as a spammer
	 * 
	 * @param string $username 
	 * @param string $email 
	 * @param string $url 
	 * @param string $ip 
	 * @access public
	 * @return boolean
	 */
	public function memberIsSpammer($username, $email, $url, $ip)
	{
		// Split IP address with spaces so TFIDF will calculate each octet as a 
		// separate feature. We're definitely abusing TFIDF here but it should
		// calculate the frequencies correctly barring any member names that 
		// overlap with our octets.
		$ip = str_replace('.', ' ', $ip);

		$text = implode(' ', array($username, $email, $url, $ip));
		$source = ee('spam:Source', $text);

		return $this->memberClassifier->classify($source, 'spam');
	}

	/**
	 * Returns true if the string is classified as spam
	 * 
	 * @param string $source 
	 * @access public
	 * @return boolean
	 */
	public function isSpam($source)
	{
		if ($this->installed === FALSE)
		{
			// If the spam module isn't installed everything is ham!
			return FALSE;
		}

		$source = ee('spam:Source', $source);
		return $this->classifier->classify($source, 'spam');
	}

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
	public function moderate($file, $class, $approve_method, $remove_method, $content, $doc)
	{
		$data = array(
			'file' => $file,
			'author' => ee()->session->userdata('member_id'),
			'date' => time(),
			'ip_address' => $_SERVER['REMOTE_ADDR'],
			'class' => $class,
			'approve' => $approve_method,
			'remove' => $remove_method,
			'data' => serialize($content),
			'document' => $doc
		);
		$trap = ee('Model')->make('spam:SpamTrap', $data);
		$trap->save();
	}

	/**
	 * load_default_classifier
	 * 
	 * @access public
	 * @return void
	 */
	public function loadDefaultClassifier()
	{
		$training = ee('spam:Training', 'default');
		$stop_words = explode("\n", ee()->lang->load('spam/stopwords', NULL, TRUE, FALSE));
		$tokenizer = ee('spam:Tokenizer');

		// Prep the the TFIDF vectorizer with the vocabulary we have stored
		$tfidf = ee('spam:Vectorizers/Tfidf', array(), $tokenizer, $stop_words);
		$tfidf->vocabulary = $training->getVocabulary();
		$tfidf->document_count = $training->getDocumentCount();
		$tfidf->generateLookups();

		$vectorizers = array();
		$vectorizers[] = ee('spam:Vectorizers/ASCIIPrintable');
		$vectorizers[] = ee('spam:Vectorizers/Entropy');
		$vectorizers[] = ee('spam:Vectorizers/Links');
		$vectorizers[] = ee('spam:Vectorizers/Punctuation');
		$vectorizers[] = ee('spam:Vectorizers/Spaces');
		$vectorizers[] = $tfidf;

		return $training->loadClassifier($vectorizers);
	}

	/**
	 * load_member_classifier
	 * 
	 * @access public
	 * @return void
	 */
	public function loadMemberClassifier()
	{
		$training = ee('spam:Training', 'member');
		$stop_words = explode("\n", ee()->lang->load('spam/stopwords', NULL, TRUE, FALSE));
		$tokenizer = ee('spam:Tokenizer');

		$tfidf = ee('spam:Vectorizers/Tfidf', array(), $tokenizer, $stop_words);
		$tfidf->vocabulary = $training->getVocabulary()->getDictionary('term', 'count');
		$tfidf->document_count = $training->getDocumentCount();
		$tfidf->generateLookups();

		$vectorizers = array();
		$vectorizers[] = ee('spam:Vectorizers/ASCIIPrintable');
		$vectorizers[] = ee('spam:Vectorizers/Punctuation');
		$vectorizers[] = $tfidf;

		return $training->loadClassifier($vectorizers);
	}

}

/* End of file Spam_core.php */
/* Location: ./system/expressionengine/modules/spam/Spam_core.php */

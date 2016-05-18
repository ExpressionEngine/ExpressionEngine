<?php

namespace EllisLab\Addons\Spam\Service;

use EllisLab\ExpressionEngine\Protocol\Spam\Spam as SpamProtocol;

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

/**
 * ExpressionEngine Spam Module
 *
 * @package		ExpressionEngine
 * @subpackage	Extensions
 * @category	Extensions
 * @author		EllisLab Dev Team
 * @link		https://ellislab.com
 */

class Spam implements SpamProtocol {

	/**
	 * @var Classifier The currently active classifier
	 */
	protected $classifier;

	/**
	 * @var Bool If this module isn't installed, we won't do anything
	 */
	protected $installed = FALSE;

	/**
	 * Constructor
	 */
	public function __construct()
	{
        // Check if the spam module is installed
		ee()->load->library('addons');
		$installed = ee()->addons->get_installed();

		if ( ! empty($installed['spam']))
		{
			$this->installed = TRUE;
			$this->classifier = $this->loadDefaultClassifier();
		}
	}

	/**
	 * Returns true if the string is classified as spam
	 *
	 * @param string $source Text to classify
	 * @return bool Is Spam?
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
	 * @return Classifier
	 */
	protected function loadDefaultClassifier()
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
}

// EOF

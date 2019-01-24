<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2019, EllisLab Corp. (https://ellislab.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace EllisLab\Addons\Spam\Service;

use EllisLab\ExpressionEngine\Protocol\Spam\Spam as SpamProtocol;

/**
 * Spam Protocol
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
	 * @see EllisLab\ExpressionEngine\Protocol\Spam\Spam
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
	 * Moderate Spam
	 *
	 * @see EllisLab\ExpressionEngine\Protocol\Spam\Spam
	 */
	public function moderate($content_type, $entity, $document, $optional_data)
	{
		$data = array(
			'content_type'  => $content_type,
			'author_id'     => ee()->session->userdata('member_id'),
			'trap_date'     => ee()->localize->now,
			'ip_address'    => $_SERVER['REMOTE_ADDR'],
			'entity'        => $entity,
			'document'      => $document,
			'optional_data' => $optional_data,
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

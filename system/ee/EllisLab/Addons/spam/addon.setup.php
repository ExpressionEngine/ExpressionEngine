<?php

use EllisLab\Addons\Spam\Service\Spam;

return array(
	'author'      => 'EllisLab',
	'author_url'  => 'https://ellislab.com/',
	'name'        => 'ExpressionEngine Spam Module',
	'version'     => '1.0.0',
	'namespace'   => 'EllisLab\Addons\Spam',
	'settings_exist' => TRUE,
	'services' => array(
		'Spam' => 'Service\Spam',
		'Spam/Training' => function($ee, $kernel)
		{
			$kernel = empty($kernel) ? 'default' : $kernel;
			return new Training($kernel);
		},
		'Spam/Classifier' => 'Library\Classifier',
		'Spam/Distribution' => 'Library\Distribution',
		'Spam/Document' => 'Library\Document',
		'Spam/Expectation' => 'Library\Expectation',
		'Spam/Source' => 'Library\Source',
		'Spam/Tokenizer' => 'Library\Tokenizer',
		'Spam/Vectorize' => 'Library\Vectorize',
		'Spam/Vectorizers/ASCIIPrintable' => 'Library\Vectorizers\ASCIIPrintable',
		'Spam/Vectorizers/Entropy' => 'Library\Vectorizers\Entropy',
		'Spam/Vectorizers/Links' => 'Library\Vectorizers\Links',
		'Spam/Vectorizers/Punctuation' => 'Library\Vectorizers\Punctuation',
		'Spam/Vectorizers/Spaces' => 'Library\Vectorizers\Spaces',
		'Spam/Vectorizers/Tfidf' => 'Library\Vectorizers\Tfidf',
	),
	'models' => array(
		'SpamKernel' => 'Model\SpamKernel',
		'SpamParameter' => 'Model\SpamParameter',
		'SpamVocabulary' => 'Model\SpamVocabulary',
		'SpamTraining' => 'Model\SpamTraining',
		'SpamTrap' => 'Model\SpamTrap',
	)
);

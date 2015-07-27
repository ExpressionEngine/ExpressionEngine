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
		'Core' => 'Service\Spam',
		'Training' => function($ee, $kernel)
		{
			$kernel = empty($kernel) ? 'default' : $kernel;
			return new Training($kernel);
		},
		'Classifier' => 'Library\Classifier',
		'Distribution' => 'Library\Distribution',
		'Document' => 'Library\Document',
		'Expectation' => 'Library\Expectation',
		'Source' => 'Library\Source',
		'Tokenizer' => 'Library\Tokenizer',
		'Vectorize' => 'Library\Vectorize',
		'Vectorizers/ASCIIPrintable' => 'Library\Vectorizers\ASCIIPrintable',
		'Vectorizers/Entropy' => 'Library\Vectorizers\Entropy',
		'Vectorizers/Links' => 'Library\Vectorizers\Links',
		'Vectorizers/Punctuation' => 'Library\Vectorizers\Punctuation',
		'Vectorizers/Spaces' => 'Library\Vectorizers\Spaces',
		'Vectorizers/Tfidf' => 'Library\Vectorizers\Tfidf',
	),
	'models' => array(
		'SpamKernel' => 'Model\SpamKernel',
		'SpamParameter' => 'Model\SpamParameter',
		'SpamVocabulary' => 'Model\SpamVocabulary',
		'SpamTraining' => 'Model\SpamTraining',
		'SpamTrap' => 'Model\SpamTrap',
	)
);

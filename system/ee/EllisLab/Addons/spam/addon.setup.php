<?php

use EllisLab\ExpressionEngine\Addons\Spam;

return array(
	'author'      => 'EllisLab',
	'author_url'  => 'https://ellislab.com/',
	'name'        => 'ExpressionEngine Spam Module',
	'version'     => '1.0.0',
	'namespace'   => 'EllisLab\Addons\Spam',
	'settings_exist' => TRUE,
	'services' => array(
		'Spam' => function($ee)
		{
			return new Spam();
		},
		'Spam/Training' => function($ee)
		{
			return new Training();
		},
		'Spam/Classifier' => function($ee)
		{
			return new Classifier();
		},
		'Spam/Distribution' => function($ee)
		{
			return new Distribution();
		},
		'Spam/Document' => function($ee)
		{
			return new Document();
		},
		'Spam/Expectation' => function($ee)
		{
			return new Expectation();
		},
		'Spam/Source' => function($ee)
		{
			return new Source();
		},
		'Spam/Tokenizer' => function($ee)
		{
			return new Tokenizer();
		},
		'Spam/Vectorize' => function($ee)
		{
			return new Vectorize();
		},
		'Spam/Vectorizers/ASCIIPrintable' => function($ee)
		{
			return new ASCIIPrintable();
		},
		'Spam/Vectorizers/Entropy' => function($ee)
		{
			return new Entropy();
		},
		'Spam/Vectorizers/Links' => function($ee)
		{
			return new Links();
		},
		'Spam/Vectorizers/Punctuation' => function($ee)
		{
			return new Punctuation();
		},
		'Spam/Vectorizers/Spaces' => function($ee)
		{
			return new Spaces();
		},
		'Spam/Vectorizers/Tfidf' => function($ee)
		{
			return new Tfidf();
		},
	),
	'models' => array(
		'SpamKernel' => 'Model\SpamKernel',
		'SpamParameter' => 'Model\SpamParameter',
		'SpamVocabulary' => 'Model\SpamVocabulary',
		'SpamTraining' => 'Model\SpamTraining',
		'SpamTrap' => 'Model\SpamTrap',
	)
);

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
		}
	),
	'models' => array(
		'SpamKernel' => 'Model\SpamKernel',
		'SpamParameter' => 'Model\SpamParameter',
		'SpamVocabulary' => 'Model\SpamVocabulary',
		'SpamTraining' => 'Model\SpamTraining',
		'SpamTrap' => 'Model\SpamTrap',
	)
);

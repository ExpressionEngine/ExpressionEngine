<?php

return array(
	'author'         => 'EllisLab',
	'author_url'     => 'https://ellislab.com/',
	'name'           => 'Comment',
	'description'    => '',
	'version'        => '2.3.2',
	'namespace'      => 'EllisLab\Addons\Comment',
	'settings_exist' => TRUE,
	'built_in'       => TRUE,

	'spam.enabled' => TRUE,
	'spam.approve' => 'Service\Spam\Moderate',
	'spam.reject' => 'Service\Spam\Moderate',
);

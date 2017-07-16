<?php

return array(
	'author'         => 'EllisLab',
	'author_url'     => 'https://ellislab.com/',
	'name'           => 'Channel',
	'description'    => '',
	'version'        => '2.0.1',
	'namespace'      => 'EllisLab\Addons\Channel',
	'settings_exist' => TRUE,
	'built_in'       => TRUE,

	'spam.enabled' => TRUE,
	'spam.approve' => 'Service\Spam\Moderate',
	'spam.reject' => 'Service\Spam\Moderate',
);

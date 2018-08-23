<?php

return [
	'author'         => 'EllisLab',
	'author_url'     => 'https://ellislab.com/',
	'name'           => 'Grid',
	'description'    => '',
	'version'        => '1.0.0',
	'namespace'      => 'EllisLab\Addons\Grid',
	'settings_exist' => FALSE,
	'built_in'       => TRUE,
	'fieldtypes'     => [
		'grid' => [
			'name' => 'Grid',
			'compatibility' => 'grid'
		],
		'grid_images' => [
			'name' => 'Grid Images',
			'compatibility' => 'grid_images'
		]
	]
];

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
		'file_grid' => [
			'name' => 'File Grid',
			'compatibility' => 'file_grid'
		]
	]
];

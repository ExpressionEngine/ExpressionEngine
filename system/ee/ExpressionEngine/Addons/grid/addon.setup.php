<?php

return [
	'author'         => 'ExpressionEngine',
	'author_url'     => 'https://expressionengine.com/',
	'name'           => 'Grid',
	'description'    => '',
	'version'        => '1.0.0',
	'namespace'      => 'ExpressionEngine\Addons\Grid',
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

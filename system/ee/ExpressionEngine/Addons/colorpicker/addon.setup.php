<?php

return [
	'author'         => 'ExpressionEngine',
	'author_url'     => 'https://expressionengine.com/',
	'name'           => 'Color Picker',
	'description'    => 'A simple color picker fieldtype',
	'version'        => '1.0.0',
	'namespace'      => 'ExpressionEngine\Addons\ColorPicker',
	'settings_exist' => FALSE,
	'built_in'       => TRUE,
	'fieldtypes'     => array(
		'colorpicker' => array(
			'compatibility' => 'text'
		)
	)
];

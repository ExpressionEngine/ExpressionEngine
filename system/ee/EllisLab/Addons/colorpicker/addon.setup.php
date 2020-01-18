<?php

return [
	'author'         => 'EllisLab',
	'author_url'     => 'https://ellislab.com/',
	'name'           => 'Color Picker',
	'description'    => 'A simple color picker fieldtype',
	'version'        => '1.0.0',
	'namespace'      => 'EllisLab\Addons\ColorPicker',
	'settings_exist' => FALSE,
	'built_in'       => TRUE,
	'fieldtypes'     => array(
		'colorpicker' => array(
			'compatibility' => 'text'
		)
	)
];

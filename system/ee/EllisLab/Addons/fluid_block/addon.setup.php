<?php

return array(
	'author'         => 'EllisLab',
	'author_url'     => 'https://ellislab.com/',
	'name'           => 'Fluid Block',
	'description'    => 'Fluid Blocks',
	'version'        => '1.0.0',
	'namespace'      => 'EllisLab\Addons\FluidBlock',
	'settings_exist' => FALSE,
	'built_in'       => TRUE,

	'models' => array(
		'FluidBlock' => 'Model\FluidBlock',
	),

	'models.dependencies' => array(
		'FluidBlock' => array(
			'ee:ChannelEntry',
			'ee:ChannelField'
		),
	)

);
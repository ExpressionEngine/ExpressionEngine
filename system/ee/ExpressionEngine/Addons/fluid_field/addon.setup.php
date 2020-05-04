<?php

use EllisLab\Addons\FluidField\Service\Tag;

return array(
	'author'         => 'EllisLab',
	'author_url'     => 'https://ellislab.com/',
	'name'           => 'Fluid',
	'description'    => 'Fluid Fields',
	'version'        => '1.0.0',
	'namespace'      => 'EllisLab\Addons\FluidField',
	'settings_exist' => FALSE,
	'built_in'       => TRUE,

	'services' => array(
		'Tag' => function($ee, $tagdata)
		{
			return new Tag($tagdata, ee()->functions, ee()->api_channel_fields, $ee->make('ee:Variables/Parser'));
		}
	),

	'models' => array(
		'FluidField' => 'Model\FluidField',
	),

	'models.dependencies' => array(
		'FluidField' => array(
			'ee:ChannelEntry',
			'ee:ChannelField'
		),
	)

);

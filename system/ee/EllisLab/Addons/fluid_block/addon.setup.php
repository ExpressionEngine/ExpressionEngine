<?php

use EllisLab\Addons\FluidBlock\Service\Tag;

return array(
	'author'         => 'EllisLab',
	'author_url'     => 'https://ellislab.com/',
	'name'           => 'Fluid Field',
	'description'    => 'Fluid Fields',
	'version'        => '1.0.0',
	'namespace'      => 'EllisLab\Addons\FluidBlock',
	'settings_exist' => FALSE,
	'built_in'       => TRUE,

	'services' => array(
		'Tag' => function($ee, $tagdata)
		{
			return new Tag($tagdata, ee()->functions, ee()->api_channel_fields);
		}
	),

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

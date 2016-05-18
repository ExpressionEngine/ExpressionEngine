<?php

return array(
	'author'      => 'EllisLab',
	'author_url'  => 'https://ellislab.com/',
	'name'        => 'Simple Commerce',
	'description' => '',
	'version'     => '2.2.0',
	'namespace'   => 'EllisLab\Addons\SimpleCommerce',
	'settings_exist' => TRUE,

	'models' => array(
		'EmailTemplate' => 'Model\EmailTemplate',
		'Item'          => 'Model\Item',
		'Purchase'      => 'Model\Purchase'
	),

	'models.dependencies' => array(
		'Item' => array(
			'ee:ChannelEntry'
		),
		'Purchase' => array(
			'ee:Member'
		)
	)
);

// EOF

<?php

return array(
	'author'         => 'EllisLab',
	'author_url'     => 'https://ellislab.com/',
	'name'           => 'Rich Text Editor',
	'description'    => '',
	'version'        => '1.0.1',
	'namespace'      => 'EllisLab\Addons\Rte',
	'settings_exist' => TRUE,
	'docs_url'       => DOC_URL.'add-ons/rte/control_panel/index.html',
	'fieldtypes'     => array(
		'rte' => array(
			'compatibility' => 'text'
		)
	),

	'models' => array(
		'Tool' => 'Model\Tool',
		'Toolset' => 'Model\Toolset'
	),

	'models.dependencies' => array(
		'Toolset' => array(
			'ee:Member'
		),
	)

);

// EOF

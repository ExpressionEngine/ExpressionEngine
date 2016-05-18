<?php

return array(
	'author'         => 'EllisLab',
	'author_url'     => 'https://ellislab.com/',
	'name'           => 'Rich Text Editor',
	'description'    => '',
	'version'        => '1.0.1',
	'namespace'      => 'EllisLab\Addons\Rte',
	'settings_exist' => TRUE,
	'docs_url'       => 'https://ellislab.com/expressionengine/user-guide/modules/rte/index.html',
	'fieldtypes'     => array(
		'rte' => array(
			'compatibility' => 'text'
		)
	),

	'models' => array(
		'Tool' => 'Model\Tool',
		'Toolset' => 'Model\Toolset'
	)
);

// EOF

<?php

return array(
    'name'              => "Teepee",
    'description'       => "Wysiwyg editor powered by CKEditor 5",
    'version'           => "6.0.0",
    'namespace'         => 'ExpressionEngine\Addons\Teepee',
	'author'            => 'ExpressionEngine',
	'author_url'        => 'https://expressionengine.com/',
    'docs_url'          => 'https://eeharbor.com/teepee/documentation',
    'settings_exist'    => true,
    'services'          => array(),
    'models'            => array(
        'Toolset' => 'Model\Toolset'
    ),
    'fieldtypes'     => array(
        'teepee' => array(
            'compatibility' => 'text'
        )
    )
);

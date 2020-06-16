<?php

return array(
    'name'              => "Wygwam",
    'description'       => "Wysiwyg editor powered by CKEditor 5",
    'version'           => "6.0.0",
    'namespace'         => 'ExpressionEngine\Addons\Wygwam',
	'author'            => 'ExpressionEngine',
	'author_url'        => 'https://expressionengine.com/',
    'docs_url'          => 'https://eeharbor.com/wygwam/documentation',
    'settings_exist'    => true,
    'services'          => array(),
    'models'            => array(
        'Config' => 'Model\Config'
    ),
    'fieldtypes'     => array(
        'wygwam' => array(
            'compatibility' => 'text'
        )
    )
);

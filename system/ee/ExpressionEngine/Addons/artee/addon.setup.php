<?php

return array(
    'name'              => "Artee",
    'description'       => "Wysiwyg editor powered by CKEditor 5",
    'version'           => "1.0.0",
    'namespace'         => 'ExpressionEngine\Addons\Artee',
    'author'            => 'ExpressionEngine',
    'author_url'        => 'https://expressionengine.com/',
    'docs_url'          => 'https://eeharbor.com/artee/documentation',
    'settings_exist'    => true,
    'services'          => array(),
    'models'            => array(
        'Toolset' => 'Model\Toolset'
    ),
    'fieldtypes'     => array(
        'artee' => array(
            'compatibility' => 'text'
        )
    )
);

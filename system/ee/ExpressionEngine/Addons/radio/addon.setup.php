<?php

return array(
    'author' => 'ExpressionEngine',
    'author_url' => 'https://expressionengine.com/',
    'docs_url' => 'https://docs.expressionengine.com/latest/fieldtypes/radio-buttons.html',
    'name' => 'Radio Buttons',
    'description' => '',
    'version' => '1.0.0',
    'namespace' => 'ExpressionEngine\Addons\RadioButtons',
    'settings_exist' => false,
    'built_in' => true,
    'fieldtypes' => array(
        'radio' => array(
            'compatibility' => 'list',
            'use' => array(
                'MemberField'
            )
        )
    )
);

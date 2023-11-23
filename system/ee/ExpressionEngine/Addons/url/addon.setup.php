<?php

return array(
    'author' => 'ExpressionEngine',
    'author_url' => 'https://expressionengine.com/',
    'name' => 'URL',
    'description' => 'Simple URL Field',
    'version' => '1.0.0',
    'namespace' => 'ExpressionEngine\Addons\Url',
    'settings_exist' => false,
    'built_in' => true,
    'fieldtypes' => array(
        'url' => array(
            'compatibility' => 'text',
            'use' => array(
                'MemberField'
            )
        )
    )
);

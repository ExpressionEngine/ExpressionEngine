<?php

return array(
    'author' => 'ExpressionEngine',
    'author_url' => 'https://expressionengine.com/',
    'docs_url' => 'https://docs.expressionengine.com/latest/fieldtypes/date.html',
    'name' => 'Date',
    'description' => '',
    'version' => '1.0.0',
    'namespace' => 'ExpressionEngine\Addons\Date',
    'settings_exist' => false,
    'built_in' => true,
    'fieldtypes' => array(
        'date' => array(
            'compatibility' => 'date',
            'use' => array(
                'MemberField'
            )
        )
    )
);

<?php

return array(
    'author' => 'ExpressionEngine',
    'author_url' => 'https://expressionengine.com/',
    'docs_url' => 'https://docs.expressionengine.com/latest/fieldtypes/duration.html',
    'name' => 'Duration',
    'description' => 'Duration Field',
    'version' => '1.0.0',
    'namespace' => 'ExpressionEngine\Addons\Duration',
    'settings_exist' => false,
    'built_in' => true,
    'fieldtypes' => array(
        'duration' => array(
            'compatibility' => 'text',
            'use' => array(
                'MemberField'
            )
        )
    )
);
